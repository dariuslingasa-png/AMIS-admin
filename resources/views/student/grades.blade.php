<x-student-layout title="Academic Grades & Report Card">

@php
    $fullName = trim(implode(' ', array_filter([
        $student?->applicant?->first_name,
        $student?->applicant?->middle_name,
        $student?->applicant?->last_name,
    ]))) ?: ($user->name ?? 'Student');

    $initials = collect(explode(' ', $fullName))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
    $gradeLevel = $student?->grade_level ?: 'Grade 1';
    $sectionName = $section?->name ?? ($student?->section ?? 'G1-AL-MUNAWWARA');
    $schoolYear = $student?->school_year ?? '2026–2027';
    $lrn = $student?->student_number ?? '260000';
    $learningMode = $student?->applicant?->learning_mode ?? 'Flexible Online Learning';

    // Core DepEd Subject Grades Mapping
    // Generate realistic or published grade data for current school year
    $gradeRecords = $subjects->map(function($subj, $idx) {
        $name = $subj->subject_name;
        // Mock Q1 grades for demo/preview based on subject index
        $q1Grades = [92, 90, 94, 91, 95, 93, 89, 92, 91, 93, 90];
        $q1 = $q1Grades[$idx % count($q1Grades)];
        
        return [
            'id' => $subj->id,
            'subject_name' => $name,
            'teacher_name' => $subj->teacher_name ?: 'Assigned Faculty',
            'q1' => $q1,
            'q2' => null,
            'q3' => null,
            'q4' => null,
            'final' => null,
            'remarks' => 'Passed',
            'status' => 'Ongoing'
        ];
    });

    // Compute Q1 General Weighted Average
    $q1Average = $gradeRecords->isNotEmpty() ? round($gradeRecords->avg('q1'), 1) : 92.5;
    $gwaDescriptor = match(true) {
        $q1Average >= 90 => 'Outstanding',
        $q1Average >= 85 => 'Very Satisfactory',
        $q1Average >= 80 => 'Satisfactory',
        $q1Average >= 75 => 'Fairly Satisfactory',
        default => 'Did Not Meet Expectations'
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
        .grades-container {
            max-width: 100% !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
        .report-card-print-box {
            border: 2px solid #000000 !important;
            box-shadow: none !important;
            padding: 1.5rem !important;
            border-radius: 0 !important;
        }
        table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        th, td {
            border: 1px solid #cbd5e1 !important;
            padding: 6px 10px !important;
            color: #000000 !important;
        }
    }
    .print-only {
        display: none;
    }
</style>

<div class="space-y-6" x-data="{ activeQuarter: 'all' }">

    {{-- ── 1. Page Header & Action Bar ─────────────────────────────── --}}
    <div class="no-print" style="display: flex; flex-direction: column; gap: 1.25rem;">
        
        {{-- Top Title Row --}}
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.78rem; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                        Academic Performance & Evaluation
                    </span>
                </div>
                <h1 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.75rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2; letter-spacing: -0.025em;">
                    Report Card & Grades
                </h1>
                <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0; font-weight: 500;">
                    Official scholastic performance, quarterly marks, and behavioral assessment for SY {{ $schoolYear }}.
                </p>
            </div>

            {{-- Action Buttons --}}
            <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                <button type="button" onclick="window.print()" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.6rem 1.15rem;
                    border-radius: 12px;
                    background: #ffffff;
                    color: #0f172a;
                    font-size: 0.825rem;
                    font-weight: 700;
                    border: 1.5px solid #e2e8f0;
                    cursor: pointer;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                    transition: all 0.15s ease;
                " onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e2e8f0';">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    <span>Print Report Card</span>
                </button>

                <a href="{{ route('student.schedule') }}" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.6rem 1.15rem;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    color: #ffffff;
                    font-size: 0.825rem;
                    font-weight: 700;
                    text-decoration: none;
                    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
                    transition: all 0.15s ease;
                " onmouseover="this.style.boxShadow='0 6px 18px rgba(5, 150, 105, 0.32)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(5, 150, 105, 0.2)'; this.style.transform='none'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>View Class Schedule</span>
                </a>
            </div>
        </div>

        {{-- ── 2. Academic Summary Metrics Grid ────────────────────────── --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            
            {{-- Card 1: GWA --}}
            <div class="fade-up" style="background: linear-gradient(135deg, #064e3b 0%, #047857 70%, #0d9488 100%); border-radius: 20px; padding: 1.35rem; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 10px 24px rgba(5, 150, 105, 0.15);">
                <div style="position: absolute; right: -15px; bottom: -15px; width: 90px; height: 90px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); pointer-events: none;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.08em;">General Weighted Average</span>
                    <span style="font-size: 0.68rem; font-weight: 800; background: rgba(255,255,255,0.22); color: #ffffff; padding: 0.15rem 0.55rem; border-radius: 999px;">Q1 Active</span>
                </div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 2.15rem; font-weight: 950; line-height: 1; letter-spacing: -0.03em;">{{ number_format($q1Average, 1) }}</span>
                    <span style="font-size: 0.85rem; font-weight: 700; color: #d1fae5;">/ 100</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; color: #a7f3d0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>{{ $gwaDescriptor }}</span>
                </div>
            </div>

            {{-- Card 2: Student Status & Section --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">Academic Standing</span>
                    <div style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin-bottom: 0.2rem;">Good Standing</div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #059669; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                        Eligible for Next Level
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Class Section:</span>
                    <strong style="color: #0f172a; font-weight: 800;">{{ $sectionName }}</strong>
                </div>
            </div>

            {{-- Card 3: Enrolled Subjects --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">Registered Learning Areas</span>
                    <div style="font-size: 1.55rem; font-weight: 950; color: #0f172a; margin-bottom: 0.2rem;">
                        {{ $gradeRecords->count() }} <small style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Subjects</small>
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #64748b;">
                        DepEd K-12 MATATAG Curriculum
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Grading System:</span>
                    <strong style="color: #059669; font-weight: 800;">Quarterly / DepEd</strong>
                </div>
            </div>

            {{-- Card 4: Academic Year Info --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">School Year & Term</span>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin-bottom: 0.2rem;">
                        SY {{ $schoolYear }}
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #d97706; background: #fef3c7; padding: 0.15rem 0.55rem; border-radius: 6px; display: inline-block;">
                        Quarter 1 Ongoing
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Student LRN:</span>
                    <strong style="color: #0f172a; font-family: monospace;">{{ $lrn }}</strong>
                </div>
            </div>

        </div>

    </div>

    {{-- ── 3. Main Report Card Panel (Single Continuous Document) ─── --}}
    <div class="fade-up report-card-print-box" style="
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 2.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 24px -4px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        gap: 2rem;
        width: 100%;
    ">

        {{-- Printable Official Header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid #0f172a;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="width: 58px; height: 58px; object-fit: contain;">
                <div>
                    <h2 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2;">
                        AL-MUNAWWARA ISLAMIC SCHOOL
                    </h2>
                    <p style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin: 0.15rem 0 0 0;">
                        Official Progress Report Card (Form 138-E) · School Year {{ $schoolYear }}
                    </p>
                </div>
            </div>
            
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.25rem 0.65rem; border-radius: 8px;">
                    DepEd Recognized
                </span>
            </div>
        </div>

        {{-- Student Information Banner --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Learner Name</span>
                <span style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">{{ $fullName }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Student ID / LRN</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $lrn }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Grade & Section</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $gradeLevel }} — {{ $sectionName }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Curriculum</span>
                <span style="font-size: 0.925rem; font-weight: 700; color: #059669;">K-12 Enhanced / MATATAG</span>
            </div>
        </div>

        {{-- ── 4. Official Scholastic Grades Table ─────────────────────── --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 900; color: #0f172a; margin: 0;">Report on Learning Progress & Achievement</h3>
                </div>

                <div class="no-print" style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; color: #64748b;">
                    <span>Grading Period:</span>
                    <span style="color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.55rem; border-radius: 6px;">Quarter 1</span>
                </div>
            </div>

            <div style="overflow-x: auto; border: 1.5px solid #e2e8f0; border-radius: 16px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.85rem 1.25rem; min-width: 200px;">Learning Areas</th>
                            <th style="padding: 0.85rem 1rem; min-width: 160px;">Subject Teacher</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: center; width: 70px;">Q1</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: center; width: 70px;">Q2</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: center; width: 70px;">Q3</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: center; width: 70px;">Q4</th>
                            <th style="padding: 0.85rem 1rem; text-align: center; width: 100px;">Final Rating</th>
                            <th style="padding: 0.85rem 1.25rem; text-align: center; width: 110px;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody style="divide-y: 1px solid #f1f5f9;">
                        @forelse($gradeRecords as $row)
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 0.95rem 1.25rem; font-weight: 800; color: #0f172a;">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                                        <span>{{ $row['subject_name'] }}</span>
                                    </div>
                                </td>
                                <td style="padding: 0.95rem 1rem; font-weight: 600; color: #475569; font-size: 0.825rem;">
                                    {{ $row['teacher_name'] }}
                                </td>
                                <td style="padding: 0.95rem 0.75rem; text-align: center; font-weight: 900; color: #047857; font-size: 0.95rem;">
                                    {{ $row['q1'] }}
                                </td>
                                <td style="padding: 0.95rem 0.75rem; text-align: center; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">
                                    —
                                </td>
                                <td style="padding: 0.95rem 0.75rem; text-align: center; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">
                                    —
                                </td>
                                <td style="padding: 0.95rem 0.75rem; text-align: center; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">
                                    —
                                </td>
                                <td style="padding: 0.95rem 1rem; text-align: center; font-weight: 800; color: #0f172a;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">Ongoing</span>
                                </td>
                                <td style="padding: 0.95rem 1.25rem; text-align: center;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.2rem 0.6rem; border-radius: 999px; text-transform: uppercase;">
                                        Passed
                                    </span>
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
                                General Average (Quarter 1)
                            </td>
                            <td style="padding: 1.1rem 0.75rem; text-align: center; color: #047857; font-size: 1.15rem; font-weight: 950;">
                                {{ number_format($q1Average, 1) }}
                            </td>
                            <td colspan="3" style="padding: 1.1rem 0.75rem; text-align: center; color: #94a3b8; font-weight: 600;">
                                —
                            </td>
                            <td style="padding: 1.1rem 1rem; text-align: center; color: #047857; font-weight: 900;">
                                {{ number_format($q1Average, 1) }}
                            </td>
                            <td style="padding: 1.1rem 1.25rem; text-align: center;">
                                <span style="font-size: 0.75rem; font-weight: 900; color: #ffffff; background: #059669; padding: 0.25rem 0.75rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.04em;">
                                    PASSED
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── 5. Observed Core Values (DepEd Standard Form) ──────────── --}}
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="width: 30px; height: 30px; border-radius: 8px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; color: #16a34a;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 900; color: #0f172a; margin: 0;">Observed Learner Core Values</h3>
            </div>

            <div style="overflow-x: auto; border: 1.5px solid #e2e8f0; border-radius: 16px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.85rem 1.25rem; width: 22%;">Core Values</th>
                            <th style="padding: 0.85rem 1.25rem; width: 50%;">Behavior Statements</th>
                            <th style="padding: 0.85rem 0.5rem; text-align: center; width: 7%;">Q1</th>
                            <th style="padding: 0.85rem 0.5rem; text-align: center; width: 7%;">Q2</th>
                            <th style="padding: 0.85rem 0.5rem; text-align: center; width: 7%;">Q3</th>
                            <th style="padding: 0.85rem 0.5rem; text-align: center; width: 7%;">Q4</th>
                        </tr>
                    </thead>
                    <tbody style="divide-y: 1px solid #f1f5f9;">
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.85rem 1.25rem; font-weight: 800; color: #0f172a;">1. Maka-Diyos</td>
                            <td style="padding: 0.85rem 1.25rem; color: #475569;">Expresses one's spiritual beliefs while respecting the spiritual beliefs of others; practices Islamic virtues.</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; font-weight: 800; color: #047857;">AO</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.85rem 1.25rem; font-weight: 800; color: #0f172a;">2. Makatao</td>
                            <td style="padding: 0.85rem 1.25rem; color: #475569;">Shows kindness, compassion, humility, and willingness to help classmates and teachers.</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; font-weight: 800; color: #047857;">AO</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.85rem 1.25rem; font-weight: 800; color: #0f172a;">3. Makakalikasan</td>
                            <td style="padding: 0.85rem 1.25rem; color: #475569;">Cares for the environment and utilizes resources wisely, judiciously, and economically.</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; font-weight: 800; color: #047857;">AO</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.85rem 1.25rem; font-weight: 800; color: #0f172a;">4. Makabansa</td>
                            <td style="padding: 0.85rem 1.25rem; color: #475569;">Demonstrates pride in being a Filipino; exercises the rights and responsibilities of a Filipino citizen.</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; font-weight: 800; color: #047857;">AO</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                            <td style="padding: 0.85rem 0.5rem; text-align: center; color: #94a3b8;">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Values Marking Code --}}
            <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; font-size: 0.75rem; color: #64748b; font-weight: 600;">
                <span><strong>AO</strong>: Always Observed (95–100%)</span>
                <span><strong>SO</strong>: Sometimes Observed (85–94%)</span>
                <span><strong>RO</strong>: Rarely Observed (75–84%)</span>
                <span><strong>NO</strong>: Not Observed (Below 75%)</span>
            </div>
        </div>

        {{-- ── 6. Grading Scale & Descriptors Legend ──────────────────── --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem;">
            <div style="font-size: 0.825rem; font-weight: 800; color: #0f172a; margin-bottom: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em;">
                DepEd Grading Scale & Academic Descriptors
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; font-size: 0.78rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0.65rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-weight: 700; color: #047857;">90 – 100</span>
                    <strong style="color: #0f172a;">Outstanding (O)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0.65rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-weight: 700; color: #0284c7;">85 – 89</span>
                    <strong style="color: #0f172a;">Very Satisfactory (VS)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0.65rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-weight: 700; color: #d97706;">80 – 84</span>
                    <strong style="color: #0f172a;">Satisfactory (S)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0.65rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-weight: 700; color: #64748b;">75 – 79</span>
                    <strong style="color: #0f172a;">Fairly Satisfactory (FS)</strong>
                </div>
            </div>
        </div>

        {{-- ── 7. Official Signatures (Form 138 Authentic Format) ───────── --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-top: 1rem; padding-top: 1.5rem; border-top: 1px dashed #cbd5e1;">
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1.5px solid #0f172a; width: 80%; margin: 0 auto; padding-top: 0.35rem;">
                    <strong style="font-size: 0.875rem; color: #0f172a; display: block;">TEACHER SAHDIA LANDAS</strong>
                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;">Class Adviser</span>
                </div>
            </div>

            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1.5px solid #0f172a; width: 80%; margin: 0 auto; padding-top: 0.35rem;">
                    <strong style="font-size: 0.875rem; color: #0f172a; display: block;">ADMINISTRATOR</strong>
                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;">School Principal / Registrar</span>
                </div>
            </div>
        </div>

    </div>

</div>

</x-student-layout>