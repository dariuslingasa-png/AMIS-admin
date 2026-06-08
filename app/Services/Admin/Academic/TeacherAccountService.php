<?php

namespace App\Services\Admin\Academic;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeacherAccountService
{
    public function generatePassword(): string
    {
        return 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);
    }

    public function syncCreatedTeacher(array $teacher, string $password): void
    {
        User::updateOrCreate(['email' => $teacher['email']], [
            'name' => $teacher['name'],
            'username' => Str::slug($teacher['name']),
            'password' => Hash::make($password),
            'role' => 'teacher',
            'account_status' => $teacher['status'] === 'Active' ? 'verified' : 'suspended',
            'email_verified_at' => now(),
        ]);

        $this->syncMicrosoft($teacher, $password);
    }

    public function syncUpdatedTeacher(array $teacher, array $existing, ?string $password, ?string $oldEmail): void
    {
        $user = $oldEmail ? User::where('email', $oldEmail)->first() : null;
        $user ??= User::where('email', $teacher['email'])->first();

        $updates = [
            'name' => $teacher['name'],
            'email' => $teacher['email'],
            'account_status' => $teacher['status'] === 'Active' ? 'verified' : 'suspended',
        ];

        if ($user) {
            if (blank($existing['temporary_password'] ?? null) && $teacher['password_changed'] === 'No' && $password) {
                $updates['password'] = Hash::make($password);
            }
            $user->update($updates);
        } else {
            User::create($updates + [
                'username' => Str::slug($teacher['name']),
                'password' => Hash::make($password ?: $this->generatePassword()),
                'role' => 'teacher',
                'email_verified_at' => now(),
            ]);
        }

        $this->syncMicrosoft($teacher, $password, $oldEmail);
    }

    public function resetCredentials(array $teacher): array
    {
        $password = $this->generatePassword();
        $email = $teacher['email'];

        User::where('email', $email)->first()?->update(['password' => Hash::make($password)]);

        try {
            $graph = new MicrosoftGraphService();
            if ($graph->userExists($email)) {
                $graph->resetPassword($email, $password);
                AdminAuditLog::record('password_reset_resend', true, "Resent and reset Microsoft account password for teacher {$email}", [
                    'email' => $email,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error("Teacher password reset/resend failed for {$email}: " . $exception->getMessage());
        }

        return ['email' => $email, 'password' => $password];
    }

    private function syncMicrosoft(array $teacher, ?string $password, ?string $oldEmail = null): void
    {
        if (! ($teacher['microsoft_sync'] ?? true)) {
            return;
        }

        $email = $teacher['email'];
        $license = $teacher['license'] ?? 'faculty_a1';
        $facultySkuIds = array_filter([
            config('services.microsoft.faculty_sku_id'),
            config('services.microsoft.faculty_a3_sku_id'),
            config('services.microsoft.faculty_a5_sku_id'),
        ]);
        $selectedSkuId = $license === 'faculty_a1' ? config('services.microsoft.faculty_sku_id') : null;

        try {
            $graph = new MicrosoftGraphService();
            $this->disableOldMicrosoftAccount($graph, $oldEmail, $email, $facultySkuIds);
            $msUserId = $this->ensureMicrosoftUser($graph, $teacher, $password);

            if ($teacher['status'] === 'Active') {
                $graph->setAccountEnabled($msUserId, true);
                $selectedSkuId
                    ? $graph->assignLicense($msUserId, [$selectedSkuId], array_diff($facultySkuIds, [$selectedSkuId]))
                    : $graph->assignLicense($msUserId, [], $facultySkuIds);
            } else {
                $graph->setAccountEnabled($msUserId, false);
                $graph->assignLicense($msUserId, [], $facultySkuIds);
            }
        } catch (\Throwable $exception) {
            Log::error("Teacher Microsoft sync failed for {$email}: " . $exception->getMessage());
        }
    }

    private function ensureMicrosoftUser(MicrosoftGraphService $graph, array $teacher, ?string $password): string
    {
        if ($graph->userExists($teacher['email'])) {
            return $graph->resolveUserId($teacher['email']);
        }

        $user = $graph->createUser($teacher['name'], explode('@', $teacher['email'])[0], $teacher['email'], $password ?: $this->generatePassword());
        AdminAuditLog::record('microsoft_account_created', true, "Automatically created Microsoft account for teacher {$teacher['email']}", [
            'email' => $teacher['email'],
            'user_id' => $user['id'],
        ]);

        return $user['id'];
    }

    private function disableOldMicrosoftAccount(MicrosoftGraphService $graph, ?string $oldEmail, string $newEmail, array $facultySkuIds): void
    {
        if (! $oldEmail || strtolower($oldEmail) === strtolower($newEmail) || ! $graph->userExists($oldEmail)) {
            return;
        }

        $oldUserId = $graph->resolveUserId($oldEmail);
        $graph->setAccountEnabled($oldUserId, false);
        $graph->assignLicense($oldUserId, [], $facultySkuIds);
    }
}
