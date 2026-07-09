<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Models\StudentSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentEbookController extends Controller
{
    public function ebooks()
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            $ebooks = collect();
            return view('student.ebooks', compact('user', 'student', 'ebooks'));
        }

        $targets = $this->getTargetGradeLevels($gradeLevel);

        $ebooks = Ebook::where('status', 'published')
            ->where(function($query) use ($targets) {
                foreach ($targets as $target) {
                    $query->orWhere('grade_level', $target)
                          ->orWhereRaw('LOWER(grade_level) = ?', [strtolower($target)]);
                }
            })
            ->orderBy('title', 'asc')
            ->get();

        return view('student.ebooks', compact('user', 'student', 'ebooks', 'gradeLevel'));
    }

    public function readEbook($id)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found.');
        }

        $ebook = Ebook::findOrFail($id);

        if ($ebook->status !== 'published') {
            abort(404, 'eBook is not available.');
        }

        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            abort(403, 'Grade level not found.');
        }

        $targets = $this->getTargetGradeLevels($gradeLevel);
        $authorized = false;
        foreach ($targets as $target) {
            if (strtolower(trim($ebook->grade_level)) === strtolower(trim($target))) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            abort(403, 'Unauthorized access to this eBook.');
        }

        DB::table('ebook_access_logs')->insert([
            'ebook_id'   => $ebook->id,
            'user_id'    => $user->id,
            'action'     => 'view',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $streamUrl = route('student.ebooks.stream', $ebook->id);

        return view('student.read_ebook', compact('user', 'student', 'ebook', 'streamUrl'));
    }

    public function streamEbook($id)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            abort(404, 'Student profile not found.');
        }

        $ebook = Ebook::findOrFail($id);

        if ($ebook->status !== 'published') {
            abort(404, 'eBook is not available.');
        }

        $studentSection = StudentSection::where('student_id', $student->id)
            ->with(['section'])
            ->first();

        $gradeLevel = $studentSection?->section?->grade_level ?? $student->grade_level;

        if (!$gradeLevel) {
            abort(403, 'Grade level not found.');
        }

        $targets = $this->getTargetGradeLevels($gradeLevel);
        $authorized = false;
        foreach ($targets as $target) {
            if (strtolower(trim($ebook->grade_level)) === strtolower(trim($target))) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            abort(403, 'Unauthorized access to this eBook.');
        }

        if (!Storage::disk('ebook_private')->exists($ebook->file_path)) {
            abort(404, 'eBook file not found.');
        }

        $absolutePath = Storage::disk('ebook_private')->path($ebook->file_path);
        $isDownload = request()->boolean('download');

        if ($isDownload && !$ebook->is_downloadable) {
            abort(403, 'This eBook is not downloadable.');
        }

        DB::table('ebook_access_logs')->insert([
            'ebook_id'   => $ebook->id,
            'user_id'    => $user->id,
            'action'     => 'stream',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $headers = [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Encoding' => 'identity',
        ];

        if ($isDownload) {
            $headers['Content-Disposition'] = 'attachment; filename="' . basename($ebook->file_path) . '"';
            return response()->download($absolutePath, basename($ebook->file_path), $headers);
        } else {
            $headers['Content-Disposition'] = 'inline; filename="' . basename($ebook->file_path) . '"';
            return response()->file($absolutePath, $headers);
        }
    }

    private function getTargetGradeLevels(string $gradeLevel): array
    {
        $gradeLevel = trim($gradeLevel);
        $targets = [$gradeLevel];

        if (strtolower($gradeLevel) === 'grade 12') {
            $targets[] = 'K12';
        } elseif (strtolower($gradeLevel) === 'k12') {
            $targets[] = 'Grade 12';
        }

        if (strtolower($gradeLevel) === 'grade 11') {
            $targets[] = 'K11';
        } elseif (strtolower($gradeLevel) === 'k11') {
            $targets[] = 'Grade 11';
        }

        if (in_array(strtolower($gradeLevel), ['kinder 1', 'kinder 2', 'kindergarten'])) {
            $targets = array_unique(array_merge($targets, ['Kinder 1', 'Kinder 2', 'Kindergarten']));
        }

        if (preg_match('/^G(\d{1,2})$/i', $gradeLevel, $matches)) {
            $num = $matches[1];
            $targets[] = 'Grade ' . $num;
            if ($num == 12) {
                $targets[] = 'K12';
            }
            if ($num == 11) {
                $targets[] = 'K11';
            }
        }

        return array_unique($targets);
    }
}
