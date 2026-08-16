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
        abort_unless(
            auth()->user()?->canViewAdminGrade($student->grade_level) || auth()->user()?->hasAnyRole(['admin', 'super_admin']),
            403
        );

        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'cropped_image' => 'nullable|string',
        ]);

        $path = null;

        if ($request->filled('cropped_image')) {
            $base64String = $request->input('cropped_image');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $imageType = strtolower($matches[1]);
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $imageData = base64_decode($base64String);

                if ($imageData === false) {
                    return response()->json(['success' => false, 'message' => 'Invalid image data provided.'], 422);
                }

                $ext = in_array($imageType, ['jpg', 'jpeg', 'png', 'webp']) ? $imageType : 'jpg';
                $filename = 'optimized/photo_' . $student->id . '_' . time() . '_' . Str::random(8) . '.' . $ext;
                Storage::disk('public')->put($filename, $imageData);
                $path = $filename;
            }
        } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $path = $request->file('photo')->store('optimized', 'public');
        }

        if (! $path) {
            return response()->json([
                'success' => false,
                'message' => 'No valid photo or cropped image received.',
            ], 400);
        }

        if ($student->applicant) {
            $student->applicant->update([
                'photo_2x2_url' => $path,
            ]);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'photo_2x2_url')) {
            $student->update(['photo_2x2_url' => $path]);
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('students', 'photo_url')) {
            $student->update(['photo_url' => $path]);
        }

        $photoDoc = \App\Models\StudentDocument::where('student_id', $student->id)
            ->where('document_type', 'photo_2x2')
            ->first();
        if ($photoDoc) {
            $abs = Storage::disk('public')->path($path);
            $fileSize = file_exists($abs) ? filesize($abs) : 0;
            $photoDoc->update([
                'local_path' => $path,
                'file_size' => $fileSize,
                'checksum' => file_exists($abs) ? hash_file('sha256', $abs) : null,
                'generated_at' => now(),
            ]);
        }

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'update_student_2x2_photo',
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => 'Administrator updated 2x2 photo for student ID: ' . ($student->student_number ?? $student->id),
            'metadata' => [
                'student_id' => $student->id,
                'school_email' => $student->school_email,
                'photo_path' => $path,
            ],
        ]);

        $fullUrl = EnrollmentStorage::url($path);
        $cleanPath = ltrim(str_replace(['/storage/', 'storage/'], '', $path), '/');
        $localCandidate = storage_path('app/public/' . $cleanPath);
        $dataUri = $fullUrl;
        if (file_exists($localCandidate)) {
            $mime = @mime_content_type($localCandidate) ?: 'image/jpeg';
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localCandidate));
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully.',
            'photo_url' => $fullUrl,
            'data_uri' => $dataUri,
        ]);
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
