<x-student-layout title="Grades">

@php
    $fullName = trim(implode(' ', array_filter([
        $student?->applicant?->first_name,
        $student?->applicant?->middle_name,
        $student?->applicant?->last_name,
    ]))) ?: ($user->name ?? 'Student');

    $initials = collect(explode(' ', $fullName))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
    $gradeLevel = $section?->grade_level ?? ($student?->grade_level ?: 'Grade 1');
    
    // Clean section name
    $rawSection = $section?->name ?? ($student?->section ?? 'Section');
    $cleanSection = preg_replace('/^(?:G\d+|Grade\s*\d+|K(?:inder)?\s*\d*)\s*[-_:]\s*/i', '', $rawSection);
    $cleanSection = trim(str_replace(['_', '-'], ' ', $cleanSection));
    if (strcasecmp($cleanSection, 'AL MUNAWWARA') === 0 || strcasecmp($cleanSection, 'AL-MUNAWWARA') === 0 || str_contains(strtolower($cleanSection), 'munawwara')) {
        $sectionName = 'Al Munawwara';
    } else {
        $sectionName = ucwords(strtolower($cleanSection)) ?: 'General';
    }

    $currentSchoolYear = $schoolYear ?? ($student?->school_year ?? '2026-2027');
    $lrn = $student?->student_number ?? '260000';
    $learningMode = $section?->learning_mode ?? ($student?->applicant?->learning_mode ?? 'Flexible Online Learning');

    // Build real grade records from DB
    $gradeRecords = $subjects->map(function ($subj) use ($grades) {
        $subjGrades = $grades->where('section_subject_id', $subj->id);

        $g1 = $subjGrades->first(fn($g) => in_array($g->grading_period, ['1st Quarter', '1st Term', 'Q1']));
        $g2 = $subjGrades->first(fn($g) => in_array($g->grading_period, ['2nd Quarter', '2nd Term', 'Q2']));
        $g3 = $subjGrades->first(fn($g) => in_array($g->grading_period, ['3rd Quarter', '3rd Term', 'Q3']));
        $g4 = $subjGrades->first(fn($g) => in_array($g->grading_period, ['4th Quarter', '4th Term', 'Q4']));

        $q1 = $g1?->quarter_grade;
        $q2 = $g2?->quarter_grade;
        $q3 = $g3?->quarter_grade;
        $q4 = $g4?->quarter_grade;

        $availableScores = array_filter([$q1, $q2, $q3, $q4], fn($v) => !is_null($v));
        $isComplete = count($availableScores) === 4;
        $final = !empty($availableScores) ? round(array_sum($availableScores) / count($availableScores), 1) : null;
        $remarks = !empty($availableScores) ? ($final >= 75 ? 'Passed' : 'Failed') : 'Ongoing';

        return [
            'id' => $subj->id,
            'subject_name' => $subj->subject_name,
            'teacher_name' => $subj->teacher_name ?: 'Assigned Faculty',
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'final' => $final,
            'remarks' => $remarks,
            'status' => $isComplete ? 'Completed' : (!empty($availableScores) ? 'In Progress' : 'Ongoing'),
        ];
    });

    // Compute Term Averages across all subjects
    $term1Avg = $gradeRecords->whereNotNull('q1')->isNotEmpty() ? round($gradeRecords->whereNotNull('q1')->avg('q1'), 1) : null;
    $term2Avg = $gradeRecords->whereNotNull('q2')->isNotEmpty() ? round($gradeRecords->whereNotNull('q2')->avg('q2'), 1) : null;
    $term3Avg = $gradeRecords->whereNotNull('q3')->isNotEmpty() ? round($gradeRecords->whereNotNull('q3')->avg('q3'), 1) : null;
    $term4Avg = $gradeRecords->whereNotNull('q4')->isNotEmpty() ? round($gradeRecords->whereNotNull('q4')->avg('q4'), 1) : null;

    $gradedFinals = $gradeRecords->whereNotNull('final');
    $generalAverage = $gradedFinals->isNotEmpty() ? round($gradedFinals->avg('final'), 1) : null;

    $descriptor = function (?float $avg): string {
        if (is_null($avg)) return 'Evaluation In Progress';
        if ($avg >= 90) return 'Outstanding';
        if ($avg >= 85) return 'Very Satisfactory';
        if ($avg >= 80) return 'Satisfactory';
        if ($avg >= 75) return 'Fairly Satisfactory';
        return 'Did Not Meet Expectations';
    };
@endphp

<style>
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .no-print, .student-sidebar, header, nav, footer, .student-header-bar {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        .report-card-print-box {
            border: 2px solid #000000 !important;
            box-shadow: none !important;
            padding: 1.5rem !important;
            border-radius: 0 !important;
            width: 100% !important;
        }
        table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        th, td {
            border: 1px solid #000000 !important;
            padding: 6px 10px !important;
            color: #000000 !important;
        }
    }
</style>

<div class="space-y-6" x-data="{ activeTerm: 'all' }">

    {{-- ── 1. Page Header & Action Bar ─────────────────────────────── --}}
    <div class="no-print flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Official DepEd Grading System
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight" style="margin: 0;">
                Report Card & Academic Grades
            </h1>
            <p class="text-sm font-medium text-gray-500 mt-1">
                Official scholastic records and verified quarterly evaluations for SY {{ $currentSchoolYear }}.
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- School Year Form / Selector --}}
            <form method="GET" action="{{ route('student.grades') }}" class="flex items-center gap-2">
                <select name="school_year" onchange="this.form.submit()" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-700 shadow-xs focus:border-emerald-500 focus:outline-hidden">
                    <option value="2026-2027" {{ $currentSchoolYear === '2026-2027' ? 'selected' : '' }}>SY 2026–2027</option>
                    <option value="2025-2026" {{ $currentSchoolYear === '2025-2026' ? 'selected' : '' }}>SY 2025–2026</option>
                </select>
            </form>

            <button type="button" onclick="window.print()" class="student-primary-btn flex items-center gap-1.5 py-2 px-4 text-xs font-black shadow-xs cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Card</span>
            </button>
        </div>
    </div>

    {{-- ── 2. Performance Summary Metric Cards ─────────────────────── --}}
    <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: General Average --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700">General Average</span>
                <div class="text-3xl font-black text-gray-900 mt-2">
                    @if($generalAverage)
                        {{ number_format($generalAverage, 1) }}%
                    @else
                        <span class="text-xl font-bold text-gray-400">Ongoing</span>
                    @endif
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500 font-semibold">Descriptor:</span>
                <span class="font-extrabold text-emerald-800">{{ $descriptor($generalAverage) }}</span>
            </div>
        </div>

        {{-- Card 2: 1st Quarter Average --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-teal-700">1st Quarter Rating</span>
                <div class="text-3xl font-black text-gray-900 mt-2">
                    @if($term1Avg)
                        {{ number_format($term1Avg, 1) }}
                    @else
                        <span class="text-xl font-bold text-gray-400">—</span>
                    @endif
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500 font-semibold">Evaluated Subjects:</span>
                <span class="font-bold text-gray-700">{{ $gradeRecords->whereNotNull('q1')->count() }} / {{ $gradeRecords->count() }}</span>
            </div>
        </div>

        {{-- Card 3: Academic Status --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-700">Academic Standing</span>
                <div class="text-2xl font-black text-gray-900 mt-2">
                    @if($generalAverage)
                        <span class="{{ $generalAverage >= 75 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $generalAverage >= 75 ? 'Passed' : 'Failed' }}
                        </span>
                    @else
                        <span class="text-gray-500">Regular</span>
                    @endif
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500 font-semibold">Passing Standard:</span>
                <span class="font-bold text-gray-700">75% & Above</span>
            </div>
        </div>

        {{-- Card 4: Grading Scale Reference --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-700">DepEd Evaluation Scale</span>
                <div class="text-xs font-semibold text-gray-600 mt-2 space-y-0.5">
                    <div><strong class="text-gray-900">90–100:</strong> Outstanding</div>
                    <div><strong class="text-gray-900">85–89:</strong> Very Satisfactory</div>
                    <div><strong class="text-gray-900">75–84:</strong> Satisfactory</div>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-100 text-[10px] text-gray-400 font-semibold">
                DepEd Order No. 8, s. 2015
            </div>
        </div>
    </div>

    {{-- ── 3. Main Report Card Document ─────────────────────────────── --}}
    <div class="fade-up report-card-print-box" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 24px; padding: 2.25rem; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 2rem; width: 100%;">

        {{-- Printable Official Header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid #0f172a;">
            <div style="display: flex; align-items: center; gap: 1.15rem;">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="width: 64px; height: 64px; object-fit: contain;">
                <div>
                    <div class="arabic-school-title" style="font-family: 'Scheherazade New', 'Amiri', 'Traditional Arabic', serif !important; font-size: 1.6rem; font-weight: 700; color: #047857; line-height: 1.1; margin-bottom: 0.2rem; direction: rtl; text-align: left;" dir="rtl" lang="ar">
                        المدرسة المنورة الإسلامية
                    </div>
                    <h2 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2;">
                        AL MUNAWWARA ISLAMIC SCHOOL
                    </h2>
                    <p style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin: 0.2rem 0 0 0;">
                        Official Student Progress Report Card (Form 138 / SF9)
                    </p>
                </div>
            </div>
            <div class="hidden sm:block text-right">
                <div class="inline-block rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-right">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-800">DepEd Accredited</p>
                    <p class="text-xs font-black text-gray-900">K to 12 Basic Education</p>
                </div>
            </div>
        </div>

        {{-- Student Information Banner --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1.25rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 1.25rem;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Learner Name</span>
                <span style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">{{ $fullName }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Learner Reference No. (LRN)</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $lrn }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Grade & Section</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $gradeLevel }} — {{ $sectionName }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Curriculum Year</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #059669;">SY {{ $currentSchoolYear }}</span>
            </div>
        </div>

        {{-- Scholastic Grades Table --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </div>
                    <h3 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin: 0;">Periodic Academic Achievement</h3>
                </div>
                <div class="text-xs font-semibold text-gray-500">
                    * Confirmed and published marks only
                </div>
            </div>

            <div style="overflow-x: auto; border: 1.5px solid #e2e8f0; border-radius: 16px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.85rem 1.25rem; min-width: 200px;">Learning Area / Subject</th>
                            <th style="padding: 0.85rem 1rem; min-width: 160px;">Assigned Teacher</th>
                            <th style="padding: 0.85rem 0.65rem; text-align: center; width: 75px;">Q1</th>
                            <th style="padding: 0.85rem 0.65rem; text-align: center; width: 75px;">Q2</th>
                            <th style="padding: 0.85rem 0.65rem; text-align: center; width: 75px;">Q3</th>
                            <th style="padding: 0.85rem 0.65rem; text-align: center; width: 75px;">Q4</th>
                            <th style="padding: 0.85rem 0.85rem; text-align: center; width: 95px;">Final</th>
                            <th style="padding: 0.85rem 1.15rem; text-align: center; width: 105px;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gradeRecords as $row)
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 0.95rem 1.25rem; font-weight: 800; color: #0f172a;">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0;"></span>
                                        <span>{{ $row['subject_name'] }}</span>
                                    </div>
                                </td>
                                <td style="padding: 0.95rem 1rem; font-weight: 600; color: #475569; font-size: 0.825rem;">
                                    {{ $row['teacher_name'] }}
                                </td>
                                <td style="padding: 0.95rem 0.65rem; text-align: center; font-weight: 900; color: {{ $row['q1'] ? '#047857' : '#94a3b8' }}; font-size: {{ $row['q1'] ? '0.95rem' : '0.85rem' }};">
                                    {{ $row['q1'] ?? '—' }}
                                </td>
                                <td style="padding: 0.95rem 0.65rem; text-align: center; font-weight: 900; color: {{ $row['q2'] ? '#047857' : '#94a3b8' }}; font-size: {{ $row['q2'] ? '0.95rem' : '0.85rem' }};">
                                    {{ $row['q2'] ?? '—' }}
                                </td>
                                <td style="padding: 0.95rem 0.65rem; text-align: center; font-weight: 900; color: {{ $row['q3'] ? '#047857' : '#94a3b8' }}; font-size: {{ $row['q3'] ? '0.95rem' : '0.85rem' }};">
                                    {{ $row['q3'] ?? '—' }}
                                </td>
                                <td style="padding: 0.95rem 0.65rem; text-align: center; font-weight: 900; color: {{ $row['q4'] ? '#047857' : '#94a3b8' }}; font-size: {{ $row['q4'] ? '0.95rem' : '0.85rem' }};">
                                    {{ $row['q4'] ?? '—' }}
                                </td>
                                <td style="padding: 0.95rem 0.85rem; text-align: center; font-weight: 900; color: #0f172a;">
                                    @if($row['final'])
                                        <span style="color: #047857; font-weight: 950; font-size: 0.95rem;">
                                            {{ number_format($row['final'], 1) }}
                                        </span>
                                    @else
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">Ongoing</span>
                                    @endif
                                </td>
                                <td style="padding: 0.95rem 1.15rem; text-align: center;">
                                    @if($row['final'])
                                        <span style="font-size: 0.72rem; font-weight: 800; color: {{ $row['final'] >= 75 ? '#047857' : '#dc2626' }}; background: {{ $row['final'] >= 75 ? '#ecfdf5' : '#fef2f2' }}; border: 1px solid {{ $row['final'] >= 75 ? '#a7f3d0' : '#fecaca' }}; padding: 0.2rem 0.6rem; border-radius: 999px; text-transform: uppercase;">
                                            {{ $row['remarks'] }}
                                        </span>
                                    @else
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                            Ongoing
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem 1.5rem; color: #64748b; font-weight: 600;">
                                    No subjects currently registered for grading evaluation.
                                </td>
                            </tr>
                        @endforelse

                        {{-- General Average Summary Row --}}
                        <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 900;">
                            <td colspan="2" style="padding: 1.1rem 1.25rem; color: #0f172a; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                General Average
                            </td>
                            <td style="padding: 1.1rem 0.65rem; text-align: center; color: #047857; font-size: 1.05rem; font-weight: 950;">
                                {{ $term1Avg ? number_format($term1Avg, 1) : '—' }}
                            </td>
                            <td style="padding: 1.1rem 0.65rem; text-align: center; color: {{ $term2Avg ? '#047857' : '#94a3b8' }}; font-size: {{ $term2Avg ? '1.05rem' : '0.85rem' }}; font-weight: {{ $term2Avg ? '950' : '600' }};">
                                {{ $term2Avg ? number_format($term2Avg, 1) : '—' }}
                            </td>
                            <td style="padding: 1.1rem 0.65rem; text-align: center; color: {{ $term3Avg ? '#047857' : '#94a3b8' }}; font-size: {{ $term3Avg ? '1.05rem' : '0.85rem' }}; font-weight: {{ $term3Avg ? '950' : '600' }};">
                                {{ $term3Avg ? number_format($term3Avg, 1) : '—' }}
                            </td>
                            <td style="padding: 1.1rem 0.65rem; text-align: center; color: {{ $term4Avg ? '#047857' : '#94a3b8' }}; font-size: {{ $term4Avg ? '1.05rem' : '0.85rem' }}; font-weight: {{ $term4Avg ? '950' : '600' }};">
                                {{ $term4Avg ? number_format($term4Avg, 1) : '—' }}
                            </td>
                            <td style="padding: 1.1rem 0.85rem; text-align: center;">
                                @if($generalAverage)
                                    <span style="color: #047857; font-weight: 950; font-size: 1.15rem;">
                                        {{ number_format($generalAverage, 1) }}
                                    </span>
                                @else
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                        Ongoing
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 1.1rem 1.15rem; text-align: center;">
                                @if($generalAverage)
                                    <span style="font-size: 0.75rem; font-weight: 900; color: #ffffff; background: {{ $generalAverage >= 75 ? '#059669' : '#dc2626' }}; padding: 0.25rem 0.75rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $generalAverage >= 75 ? 'PASSED' : 'FAILED' }}
                                    </span>
                                @else
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                        Ongoing
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grading System Legend & Signatures --}}
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; border-top: 1.5px solid #e2e8f0; padding-top: 1.5rem;" class="flex-col sm:flex-row">
            <div>
                <h4 style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin: 0 0 0.5rem 0;">
                    Descriptors & Grading Scale
                </h4>
                <div style="font-size: 0.75rem; color: #64748b; line-height: 1.6;">
                    <div><strong style="color: #0f172a;">Outstanding:</strong> 90 – 100 (Passed)</div>
                    <div><strong style="color: #0f172a;">Very Satisfactory:</strong> 85 – 89 (Passed)</div>
                    <div><strong style="color: #0f172a;">Satisfactory:</strong> 80 – 84 (Passed)</div>
                    <div><strong style="color: #0f172a;">Fairly Satisfactory:</strong> 75 – 79 (Passed)</div>
                    <div><strong style="color: #0f172a;">Did Not Meet Expectations:</strong> Below 75 (Failed)</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; text-align: center;">
                <div style="width: 200px; border-bottom: 1.5px solid #0f172a; padding-bottom: 0.25rem;">
                    <span style="font-size: 0.85rem; font-weight: 800; color: #0f172a; display: block;">Academic Office</span>
                </div>
                <span style="font-size: 0.7rem; font-weight: 600; color: #64748b; margin-top: 0.25rem; display: block; width: 200px;">
                    Principal / Registrar Signature
                </span>
            </div>
        </div>

    </div>

</div>

</x-student-layout>