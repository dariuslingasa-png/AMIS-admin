<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use App\Support\EnrollmentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentPhotoController extends Controller
{
    public function updatePhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $path = $request->file('photo')->store('optimized', 'public');

            if ($student->applicant) {
                $student->applicant->update([
                    'photo_2x2_url' => $path,
                ]);

                AdminAuditLog::create([
                    'user_id' => auth()->id(),
                    'event' => 'update_student_photo',
                    'ip_address' => request()->ip(),
                    'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                    'successful' => true,
                    'message' => 'Super Administrator updated profile photo for student UPN: '.$student->school_email,
                    'metadata' => [
                        'student_id' => $student->id,
                        'school_email' => $student->school_email,
                        'photo_path' => $path,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo updated successfully.',
                    'photo_url' => EnrollmentStorage::url($path),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload photo.',
        ], 400);
    }

    public function deletePhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        if ($student->applicant) {
            $student->applicant->update([
                'photo_2x2_url' => null,
            ]);

            AdminAuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'delete_student_photo',
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'successful' => true,
                'message' => 'Super Administrator deleted/reset profile photo for student UPN: '.$student->school_email,
                'metadata' => [
                    'student_id' => $student->id,
                    'school_email' => $student->school_email,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo reset to default successfully.',
                'photo_url' => null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset photo.',
        ], 400);
    }

    public function syncMicrosoftPhoto(Request $request, Student $student)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $upn = $student->school_email;
        if (empty($upn)) {
            return response()->json([
                'success' => false,
                'message' => 'Student does not have a Microsoft 365 school email UPN.',
            ], 400);
        }

        try {
            $graph = new MicrosoftGraphService;
            $photoData = $graph->getUserPhoto($upn);

            if (! $photoData || empty($photoData['bytes'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No profile photo found in Microsoft 365 / Azure AD for this account.',
                ], 404);
            }

            $bytes = $photoData['bytes'];
            $extension = str_contains($photoData['content_type'], 'png') ? 'png' : 'jpg';
            $filename = 'optimized/'.Str::random(40).'.'.$extension;

            Storage::disk('public')->put($filename, $bytes);

            if ($student->applicant) {
                $student->applicant->update([
                    'photo_2x2_url' => $filename,
                ]);

                AdminAuditLog::create([
                    'user_id' => auth()->id(),
                    'event' => 'sync_microsoft_photo',
                    'ip_address' => request()->ip(),
                    'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                    'successful' => true,
                    'message' => 'Super Administrator pulled profile photo from Microsoft M365 for student UPN: '.$student->school_email,
                    'metadata' => [
                        'student_id' => $student->id,
                        'school_email' => $student->school_email,
                        'photo_path' => $filename,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo recovered from Microsoft 365 successfully.',
                    'photo_url' => EnrollmentStorage::url($filename),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('syncMicrosoftPhoto failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve photo from Microsoft: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to sync photo.',
        ], 400);
    }
}
