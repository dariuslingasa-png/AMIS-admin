<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $docTitle = 'Student-Documents';
        if (isset($students) && count($students) === 1) {
            $st = $students->first();
            $app = $st?->applicant;
            $ln = strtoupper(trim($app?->last_name ?? ''));
            $fn = strtoupper(trim($app?->first_name ?? ''));
            $gr = strtoupper(trim(str_replace(' ', '', $st?->grade_level ?? '')));
            if ($ln || $fn) {
                $docTitle = implode('-', array_filter([$ln, $fn, $gr ?: 'GRADE', 'DOCUMENTS']));
            }
        }
    @endphp
    <title>{{ $docTitle }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        @page {
            size: portrait;
            margin: 10mm;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            margin: 0;
            padding: 12px 10px;
            background: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            color: #0f172a;
        }

        @media print {
            .action-bar-container, .page-number-badge, #print-skeleton-overlay { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .document-card-page { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; }
        }

        .document-card-page {
            max-width: 1000px;
            margin: 0 auto 40px auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 32px 36px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 4px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .doc-item-card {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            transition: all 0.2s ease;
        }

        .doc-item-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .doc-item-card.missing {
            background: #fffefb;
            border-color: #fde68a;
        }

        .doc-item-card.missing-mandatory {
            background: #fff5f5;
            border-color: #fecaca;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            background: #059669;
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: background 0.15s ease;
            width: 100%;
        }
        .btn-download:hover {
            background: #047857;
        }

        .btn-download-photo {
            background: #0284c7;
        }
        .btn-download-photo:hover {
            background: #0369a1;
        }

        .skeleton-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmerAnimation 1.5s infinite;
        }
        @keyframes shimmerAnimation {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Page Skeleton Loading Overlay -->
    <div id="print-skeleton-overlay" style="position: fixed; inset: 0; background: #f8fafc; z-index: 99999; overflow: hidden; padding: 20px; transition: opacity 0.25s ease;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <!-- Action Bar Skeleton -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;" class="animate-pulse">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    </div>
                    <div style="width: 180px; height: 16px; border-radius: 6px; background: #cbd5e1;"></div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div style="width: 80px; height: 32px; border-radius: 8px; background: #e2e8f0;"></div>
                    <div style="width: 100px; height: 32px; border-radius: 8px; background: #ecfdf5;"></div>
                </div>
            </div>

            <!-- Main Document Container Skeleton -->
            <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px; margin-top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04);" class="animate-pulse">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; margin-bottom: 12px;" class="skeleton-shimmer"></div>
                    <div style="width: 320px; height: 20px; border-radius: 6px; background: #e2e8f0; margin-bottom: 8px;" class="skeleton-shimmer"></div>
                    <div style="width: 220px; height: 14px; border-radius: 6px; background: #f1f5f9;" class="skeleton-shimmer"></div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 28px;">
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                </div>

                <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between;">
                        <div style="width: 120px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                        <div style="width: 80px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 90%;" class="skeleton-shimmer"></div>
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 75%;" class="skeleton-shimmer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            function hideSkeleton() {
                const el = document.getElementById('print-skeleton-overlay');
                if (el) {
                    el.style.opacity = '0';
                    setTimeout(() => {
                        if (el && el.parentNode) el.parentNode.removeChild(el);
                    }, 250);
                }
            }
            if (document.readyState === 'interactive' || document.readyState === 'complete') {
                setTimeout(hideSkeleton, 50);
            } else {
                document.addEventListener('DOMContentLoaded', () => setTimeout(hideSkeleton, 50));
            }
            window.addEventListener('load', hideSkeleton);
            window.addEventListener('pageshow', hideSkeleton);
            setTimeout(hideSkeleton, 500);
        })();
    </script>

    @if(isset($students) && count($students) === 1)
        @php $singleStudent = $students->first(); @endphp
        <!-- Top Action Bar & Student Form Switcher Navigation Bar -->
        <div class="action-bar-container" style="max-width: 1000px; margin: 0 auto 20px auto; background: #ffffff; padding: 14px 20px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03); font-family: 'Inter', system-ui, -apple-system, sans-serif; position: sticky; top: 12px; z-index: 1000; border: 1px solid #e2e8f0;">
            <!-- Row 1: Document & Student Profile Info + Actions -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; shrink-0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                            Student Registration Documents
                        </h2>
                        <p style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin: 2px 0 0 0;">
                            Student: <strong style="color: #0f172a;">{{ $singleStudent->full_name }}</strong> • AMIS ID: <strong style="color: #059669;">#{{ $singleStudent->student_number }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <!-- Download ZIP Archive -->
                    <a href="{{ route('admin.students.download-docs-zip', ['student_id' => $singleStudent->id]) }}" download style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; background: #0284c7; color: #ffffff; text-decoration: none; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        <span>Download ZIP Archive</span>
                    </a>

                    <!-- Close Button -->
                    <a href="{{ route('admin.students.show', $singleStudent) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#e2e8f0';this.style.color='#0f172a'" onmouseout="this.style.background='#f1f5f9';this.style.color='#475569'">
                        <span>Close</span>
                    </a>

                    <!-- Print Button -->
                    <button type="button" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; background: #059669; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); transition: all 0.15s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        <span>Print / Save as PDF</span>
                    </button>
                </div>
            </div>

            <!-- Row 2: Form Switcher Navigation Bar -->
            <div style="display: flex; align-items: center; gap: 6px; overflow-x: auto; padding-top: 10px;">
                <!-- ID FORM -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_id' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
                    <span>ID Form</span>
                </a>

                <!-- MICROSOFT ACCOUNT FORM -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_credentials' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                    <span>Microsoft Account Form</span>
                </a>

                <!-- ENROLLMENT FORM -->
                <a href="{{ route('admin.students.print-enrolment-form', $singleStudent) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    <span>Enrollment Form</span>
                </a>

                <!-- GRADE FORM -->
                <a href="{{ route('admin.students.show', $singleStudent) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span>Grade Form</span>
                </a>

                <!-- DOCUMENTS FORM (ACTIVE) -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_documents' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; text-decoration: none; border: 1px solid #059669; background: #ecfdf5; color: #047857; shadow: 0 1px 2px rgba(5,150,105,0.1); white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    <span>Documents Form</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Main Documents Sheet Container -->
    @foreach ($students as $student)
        @php
            $appl = $student->applicant;
            $photoUrl = \App\Support\EnrollmentStorage::url($appl?->photo_2x2_url);
            $birthCertUrl = \App\Support\EnrollmentStorage::url($appl?->birth_cert_url);
            $reportCardUrl = \App\Support\EnrollmentStorage::url($appl?->report_card_url);
            $marriageUrl = \App\Support\EnrollmentStorage::url($appl?->marriage_contract_url);
            $medicalUrl = \App\Support\EnrollmentStorage::url($appl?->medical_record_url);
            $affidavitUrl = \App\Support\EnrollmentStorage::url($appl?->affidavit_url);

            $documents = [
                [
                    'key' => '2x2_photo',
                    'label' => '2x2 Photo ID',
                    'url' => $photoUrl,
                    'is_photo' => true,
                    'mandatory' => true,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #0284c7;"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>',
                ],
                [
                    'key' => 'birth_cert',
                    'label' => 'Birth Certificate (PSA / NSO)',
                    'url' => $birthCertUrl,
                    'is_photo' => false,
                    'mandatory' => true,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
                ],
                [
                    'key' => 'report_card',
                    'label' => 'Report Card (Form 138 / SF9)',
                    'url' => $reportCardUrl,
                    'is_photo' => false,
                    'mandatory' => ($appl?->student_type !== 'Old'),
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #7c3aed;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                ],
                [
                    'key' => 'marriage_contract',
                    'label' => 'Marriage Contract (Parent Proof)',
                    'url' => $marriageUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #d97706;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>',
                ],
                [
                    'key' => 'medical_record',
                    'label' => 'Medical History Records',
                    'url' => $medicalUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #dc2626;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
                ],
                [
                    'key' => 'affidavit',
                    'label' => 'Temporary Proof (Affidavit / Form 137)',
                    'url' => $affidavitUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #475569;"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h18"/></svg>',
                ],
            ];

            $availableCount = count(array_filter($documents, fn($d) => !empty($d['url'])));
            $missingMandatoryCount = count(array_filter($documents, fn($d) => empty($d['url']) && $d['mandatory']));
        @endphp

        <div class="document-card-page">
            <!-- School & Form Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 18px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <img src="https://amis.edu.ph/images/logo.png" alt="AMIS Logo" style="width: 56px; height: 56px; object-fit: contain;" onerror="this.style.display='none'">
                    <div>
                        <h1 style="margin: 0; font-size: 1.25rem; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; text-transform: uppercase;">
                            AL MUNAWARRA ISLAMIC SCHOOL
                        </h1>
                        <p style="margin: 2px 0 0 0; font-size: 0.8rem; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.05em;">
                            Official Student Registration Documents & Attachments
                        </p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; display: block;">SY 2026-2027</span>
                    <span style="display: inline-block; background: #0f172a; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; margin-top: 4px;">
                        AMIS ID: #{{ $student->student_number }}
                    </span>
                </div>
            </div>

            <!-- Student Summary Info Box -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
                <div>
                    <span style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; display: block;">Student Name</span>
                    <strong style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $student->full_name }}</strong>
                </div>
                <div>
                    <span style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; display: block;">LRN (Learner Reference No.)</span>
                    <strong style="font-size: 0.9rem; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $appl?->lrn ?: 'NOT SET' }}</strong>
                </div>
                <div>
                    <span style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; display: block;">Grade Level</span>
                    <strong style="font-size: 0.9rem; font-weight: 800; color: #059669;">{{ $student->grade_level }}</strong>
                </div>
                <div>
                    <span style="font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase; display: block;">Gender / Type</span>
                    <strong style="font-size: 0.9rem; font-weight: 800; color: #0f172a;">{{ ucfirst($appl?->gender ?? 'Male') }} • {{ ucfirst($appl?->student_type ?? 'New') }}</strong>
                </div>
            </div>

            <!-- Status Banner -->
            @if($missingMandatoryCount === 0)
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; color: #065f46;">
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.82rem; font-weight: 800;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>ALL MANDATORY REGISTRATION DOCUMENTS VERIFIED & COMPLETE</span>
                    </div>
                    <span style="background: #059669; color: white; padding: 3px 10px; border-radius: 9999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">
                        {{ $availableCount }} / {{ count($documents) }} AVAILABLE
                    </span>
                </div>
            @else
                <div style="background: #fff1f2; border: 1px solid #fecaca; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; color: #9f1239;">
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.82rem; font-weight: 800;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        <span>REQUIREMENTS REMINDER: {{ $missingMandatoryCount }} Mandatory Document(s) Pending Clearance</span>
                    </div>
                    <span style="background: #e11d48; color: white; padding: 3px 10px; border-radius: 9999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">
                        {{ $missingMandatoryCount }} MISSING
                    </span>
                </div>
            @endif

            <!-- Documents Grid -->
            <div style="margin-bottom: 12px;">
                <h3 style="margin: 0 0 14px 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    <span>Attached Document Files (HD High Resolution)</span>
                </h3>

                <div class="doc-grid">
                    @foreach($documents as $doc)
                        @php
                            $hasFile = !empty($doc['url']);
                            $isPdf = $hasFile && strtolower(pathinfo($doc['url'], PATHINFO_EXTENSION)) === 'pdf';
                        @endphp

                        <div class="doc-item-card {{ !$hasFile ? ($doc['mandatory'] ? 'missing-mandatory' : 'missing') : '' }}">
                            <!-- Top: Header & Badge -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; shrink-0;">
                                        {!! $doc['svg'] !!}
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; font-size: 0.85rem; font-weight: 800; color: #0f172a; line-height: 1.3;">
                                            {{ $doc['label'] }}
                                        </h4>
                                        @if($doc['mandatory'])
                                            <span style="font-size: 0.65rem; font-weight: 700; color: #e11d48; text-transform: uppercase; margin-top: 2px; display: block;">Mandatory Requirement</span>
                                        @else
                                            <span style="font-size: 0.65rem; font-weight: 600; color: #64748b; text-transform: uppercase; margin-top: 2px; display: block;">Optional Attachment</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    @if($hasFile)
                                        <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; white-space: nowrap;">
                                            VERIFIED
                                        </span>
                                    @elseif($doc['mandatory'])
                                        <span style="background: #ffe4e6; color: #be123c; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; white-space: nowrap;">
                                            MISSING
                                        </span>
                                    @else
                                        <span style="background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; white-space: nowrap;">
                                            NOT ATTACHED
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Middle: Preview Box -->
                            <div style="height: 150px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                @if($hasFile && $doc['is_photo'])
                                    <a href="{{ $doc['url'] }}" target="_blank" title="Click to view full HD image" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                        <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px; image-rendering: -webkit-optimize-contrast;">
                                    </a>
                                @elseif($hasFile && !$isPdf)
                                    <a href="{{ $doc['url'] }}" target="_blank" title="Click to view full HD image" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                        <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px; image-rendering: -webkit-optimize-contrast;">
                                    </a>
                                @elseif($hasFile && $isPdf)
                                    <a href="{{ $doc['url'] }}" target="_blank" title="Click to open PDF" style="display: flex; flex-direction: column; align-items: center; gap: 6px; color: #dc2626; text-decoration: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #991b1b;">PDF Document Attached</span>
                                    </a>
                                @else
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; color: #94a3b8; text-align: center; padding: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        <span style="font-size: 0.7rem; font-weight: 700; color: #94a3b8;">No File Uploaded Yet</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Bottom: Action Buttons (View HD & Download) -->
                            <div>
                                @if($hasFile)
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <a href="{{ $doc['url'] }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; border-radius: 9px; font-size: 0.75rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; transition: background 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <span>View HD</span>
                                        </a>

                                        <a href="{{ $doc['url'] }}" download target="_blank" class="btn-download {{ $doc['is_photo'] ? 'btn-download-photo' : '' }}" style="padding: 8px 12px; font-size: 0.75rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                            <span>{{ $doc['is_photo'] ? '2x2 Photo' : 'Download' }}</span>
                                        </a>
                                    </div>
                                @else
                                    <button type="button" disabled style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 16px; border-radius: 10px; font-size: 0.78rem; font-weight: 700; background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; width: 100%; cursor: not-allowed;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/></svg>
                                        <span>Missing / File Unavailable</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

</body>
</html>
