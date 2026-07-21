<?php

namespace App\Services\Microsoft;

use App\Models\LinkedIdentity;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MicrosoftAccountMatcher
{
    public function match(array $member): array
    {
        $entraId = trim((string) ($member['userId'] ?? ''));
        $email = self::normalizeEmail($member['email'] ?? null);
        $upn = self::normalizeEmail($member['userPrincipalName'] ?? null);

        if ($entraId !== '') {
            $students = Student::query()->where('ms_user_id', $entraId)->limit(2)->get();
            if ($result = $this->studentResult($students, 'entra_user_id')) {
                return $result;
            }
        }

        foreach (array_values(array_unique(array_filter([$email, $upn]))) as $candidateEmail) {
            $students = Student::query()
                ->where(function ($query) use ($candidateEmail) {
                    $query->whereRaw('LOWER(ms_email) = ?', [$candidateEmail])
                        ->orWhereRaw('LOWER(school_email) = ?', [$candidateEmail]);
                })
                ->limit(2)
                ->get();

            if ($result = $this->studentResult($students, $candidateEmail === $upn ? 'user_principal_name' : 'microsoft_email')) {
                return $result;
            }
        }

        foreach (array_values(array_unique(array_filter([$email, $upn]))) as $candidateEmail) {
            $linked = LinkedIdentity::query()
                ->with('user.students')
                ->whereRaw('LOWER(microsoft_email) = ?', [$candidateEmail])
                ->limit(2)
                ->get();

            if ($linked->count() > 1) {
                return $this->multipleResult();
            }

            if ($identity = $linked->first()) {
                $student = $identity->user?->students?->first();
                if ($student) {
                    return $this->matchedStudent($student->id, 'verified_microsoft_account');
                }
                if ($identity->user) {
                    return $this->matchedUser($identity->user, 'verified_microsoft_account');
                }
            }
        }

        foreach (array_values(array_unique(array_filter([$email, $upn]))) as $candidateEmail) {
            $users = User::query()
                ->whereIn('role', ['teacher', 'admin', 'finance', 'staff'])
                ->whereRaw('LOWER(email) = ?', [$candidateEmail])
                ->limit(2)
                ->get();

            if ($users->count() > 1) {
                return $this->multipleResult();
            }

            if ($user = $users->first()) {
                return $this->matchedUser($user, $candidateEmail === $upn ? 'user_principal_name' : 'microsoft_email');
            }
        }

        return [
            'local_student_id' => null,
            'local_faculty_id' => null,
            'account_type' => 'unknown',
            'match_method' => null,
            'match_status' => 'unmatched',
        ];
    }

    public static function normalizeEmail(mixed $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    private function studentResult(Collection $students, string $method): ?array
    {
        if ($students->count() > 1) {
            return $this->multipleResult();
        }

        if ($student = $students->first()) {
            return $this->matchedStudent($student->id, $method);
        }

        return null;
    }

    private function matchedStudent(int $id, string $method): array
    {
        return [
            'local_student_id' => $id,
            'local_faculty_id' => null,
            'account_type' => 'student',
            'match_method' => $method,
            'match_status' => 'matched_student',
        ];
    }

    private function matchedUser(User $user, string $method): array
    {
        $accountType = match ($user->role) {
            'teacher' => 'faculty',
            'admin' => 'admin',
            default => 'staff',
        };

        return [
            'local_student_id' => null,
            'local_faculty_id' => $user->id,
            'account_type' => $accountType,
            'match_method' => $method,
            'match_status' => $accountType === 'faculty' ? 'matched_faculty' : 'matched_staff',
        ];
    }

    private function multipleResult(): array
    {
        return [
            'local_student_id' => null,
            'local_faculty_id' => null,
            'account_type' => 'unknown',
            'match_method' => null,
            'match_status' => 'multiple_matches',
        ];
    }
}
