<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $idTitle = 'AMIS-Student-ID-Cards';
        if (isset($students) && count($students) === 1) {
            $st = $students->first();
            $app = $st?->applicant;
            $ln = strtoupper(trim($app?->last_name ?? ''));
            $fn = strtoupper(trim($app?->first_name ?? ''));
            $sf = strtoupper(trim($app?->suffix ?? ''));
            $gr = strtoupper(trim(str_replace(' ', '', $st?->grade_level ?? '')));
            if ($ln || $fn) {
                $idTitle = implode('-', array_filter([$ln, $fn, $sf, $gr ?: 'GRADE']));
            }
        }
    @endphp
    <title>{{ $idTitle }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap');
        
        @page {
            size: portrait;
            margin: 0;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            margin: 0;
            padding: 12px 10px;
            background: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }
        .toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: #059669;
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            padding: 9px 14px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .toolbar button.btn-png {
            background: #0284c7;
        }
        .toolbar button.btn-png:hover {
            background: #0369a1;
        }
        @media print {
            .toolbar, .action-bar-container, .page-number-badge { display: none !important; }
            body { background: #fff; }
        }
        
        .id-print-page {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-after: always;
            page-break-inside: avoid;
            box-sizing: border-box;
            background: #ffffff;
        }
        .id-print-page:last-of-type {
            page-break-after: avoid !important;
        }
        
        .id-pair-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10mm;
            page-break-inside: avoid;
        }
        
        .id-card-wrapper {
            width: 70.2mm;
            height: 111.28mm;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            background: transparent;
        }
        
        .id-card-scaler {
            width: 340px;
            height: 538px;
            position: absolute;
            top: 0;
            left: 0;
            transform: scale(0.78);
            transform-origin: top left;
        }
        
        .id-card {
            width: 340px;
            height: 538px;
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: #064e3b;
        }
        
        .photo-clip {
            position: absolute;
            left: 71px;
            top: 144px;
            width: 198px;
            height: 192px;
            overflow: hidden;
            background: transparent;
            border-radius: 6px;
        }
        
        .photo-clip img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            z-index: 1;
        }
        
        .photo-placeholder {
            position: absolute;
            left: 71px;
            top: 144px;
            width: 198px;
            height: 192px;
            z-index: 5;
            border-radius: 6px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .student-id {
            position: absolute;
            left: 0;
            top: 325px;
            width: 340px;
            height: 15px;
            z-index: 20;
            background: transparent;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            letter-spacing: 0.05em;
            font-size: 12.5px;
            color: white;
            text-align: center;
            line-height: 15px;
        }
        
        .student-last-name {
            position: absolute;
            left: 15px;
            top: 352px;
            width: 310px;
            height: 32px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .student-last-name h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            line-height: 1;
            letter-spacing: -0.5px;
        }
        
        .student-first-name {
            position: absolute;
            left: 15px;
            top: 386px;
            width: 310px;
            height: 22px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .student-first-name h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin: 0;
            line-height: 1;
        }
        
        .student-grade {
            position: absolute;
            left: 15px;
            top: 412px;
            width: 310px;
            height: 30px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 16px;
        }
        
        .student-grade span {
            font-family: 'Outfit', sans-serif;
            font-size: 31px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
        }
        
        .student-lrn {
            position: absolute;
            font-family: 'Outfit', sans-serif;
            font-size: 15.5px;
            font-weight: 700;
            z-index: 20;
            left: 239px;
            top: 394px;
            width: 170px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            transform: rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
            letter-spacing: 0.05em;
            color: #1e293b;
        }
        
        .student-qr {
            position: absolute;
            left: 134.5px;
            top: 458px;
            width: 71px;
            height: 71px;
            z-index: 20;
            padding: 2.5px;
            border-radius: 2px;
            background: white;
        }
        
        .student-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        /* Back Card Styles */
        .emergency-info {
            position: absolute;
            left: 28px;
            top: 85px;
            width: 284px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .emerg-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .emerg-icon {
            flex-shrink: 0;
            width: 14px;
            height: 14px;
            color: #047857; /* Emerald green to match theme */
            margin-top: 1.5px;
        }
        .emerg-text {
            text-align: left;
        }
        .back-signature-qr {
            position: absolute;
            left: 142.5px;
            top: 422px;
            width: 55px;
            height: 55px;
            z-index: 25;
            padding: 1.5px;
            border-radius: 2px;
            background: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .back-signature-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .dropdown-item:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
    <!-- Page Skeleton Loading Overlay (Fades out when fully loaded) -->
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
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 28px;">
                    <div style="width: 250px; height: 380px; border-radius: 16px; background: #f1f5f9; border: 1px solid #e2e8f0;" class="skeleton-shimmer"></div>
                    <div style="width: 250px; height: 380px; border-radius: 16px; background: #f1f5f9; border: 1px solid #e2e8f0;" class="skeleton-shimmer"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .skeleton-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmerAnimation 1.5s infinite;
        }
        @keyframes shimmerAnimation {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @media print {
            #print-skeleton-overlay { display: none !important; }
        }
    </style>

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
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                            Student ID Card Sheet
                        </h2>
                        <p style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin: 2px 0 0 0;">
                            Student: <strong style="color: #0f172a;">{{ $singleStudent->full_name }}</strong> • AMIS ID: <strong style="color: #059669;">#{{ $singleStudent->student_number }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="dropdown" style="position: relative; display: inline-block;">
                        <button type="button" onclick="toggleDropdown()" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; background: #0284c7; color: #ffffff; border: none; cursor: pointer; transition: all 0.15s;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                            <span>Download High-Res PNGs</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div id="png-dropdown-menu" class="dropdown-content" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 5px; background-color: #ffffff; min-width: 250px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.15); border-radius: 12px; border: 1px solid #e2e8f0; padding: 6px; z-index: 9999; flex-direction: column; gap: 4px;">
                            <button type="button" onclick="triggerDownload('front-color-back-mono')" class="dropdown-item" style="text-align: left; background: none; color: #1e293b; padding: 10px 12px; border: none; font-size: 12px; font-weight: 700; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                <div style="display: flex; flex-direction: column;">
                                    <span>Front Color + Back Black/White</span>
                                    <span style="font-size: 9px; font-weight: 500; color: #059669; margin-top: 2px;">★ Recommended for Smart Printers</span>
                                </div>
                            </button>
                            <button type="button" onclick="triggerDownload('full-color')" class="dropdown-item" style="text-align: left; background: none; color: #1e293b; padding: 10px 12px; border: none; font-size: 12px; font-weight: 700; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #0284c7;"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.92 0 1.7-.75 1.7-1.67 0-.42-.16-.81-.44-1.11-.28-.3-.43-.7-.43-1.14 0-.92.75-1.67 1.67-1.67H16c3.31 0 6-2.69 6-6 0-4.96-4.49-9-10-9z"/></svg>
                                <span>Both Sides Full Color</span>
                            </button>
                            <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                            <button type="button" onclick="triggerDownload('front-only')" class="dropdown-item" style="text-align: left; background: none; color: #475569; padding: 8px 12px; border: none; font-size: 12px; font-weight: 600; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #64748b;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span>Front Side Only (Color)</span>
                            </button>
                            <button type="button" onclick="triggerDownload('back-only-mono')" class="dropdown-item" style="text-align: left; background: none; color: #475569; padding: 8px 12px; border: none; font-size: 12px; font-weight: 600; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #475569;"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h10v10H7z"/></svg>
                                <span>Back Side Only (Black/White)</span>
                            </button>
                        </div>
                    </div>

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
                <!-- ID FORM (ACTIVE) -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_id' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; text-decoration: none; border: 1px solid #059669; background: #ecfdf5; color: #047857; shadow: 0 1px 2px rgba(5,150,105,0.1); white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
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

                <!-- DOCUMENTS FORM -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_documents' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    <span>Documents Form</span>
                </a>
            </div>
        </div>
    @else
        <div class="toolbar" style="display: flex; gap: 8px; align-items: center;">
            <div class="dropdown" style="position: relative; display: inline-block;">
                <button type="button" onclick="toggleDropdown()" class="btn-png" style="display: flex; align-items: center; gap: 6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    <span>Download High-Res PNGs</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div id="png-dropdown-menu" class="dropdown-content" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 5px; background-color: #ffffff; min-width: 250px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.15); border-radius: 12px; border: 1px solid #e2e8f0; padding: 6px; z-index: 9999; flex-direction: column; gap: 4px;">
                    <button type="button" onclick="triggerDownload('front-color-back-mono')" class="dropdown-item" style="text-align: left; background: none; color: #1e293b; padding: 10px 12px; border: none; font-size: 12px; font-weight: 700; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                        <span style="font-size: 14px;">🖨️</span>
                        <div style="display: flex; flex-direction: column;">
                            <span>Front Color + Back Black/White</span>
                            <span style="font-size: 9px; font-weight: 500; color: #059669; margin-top: 2px;">★ Recommended for Smart Printers</span>
                        </div>
                    </button>
                    <button type="button" onclick="triggerDownload('full-color')" class="dropdown-item" style="text-align: left; background: none; color: #1e293b; padding: 10px 12px; border: none; font-size: 12px; font-weight: 700; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                        <span style="font-size: 14px;">🎨</span>
                        <span>Both Sides Full Color</span>
                    </button>
                    <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                    <button type="button" onclick="triggerDownload('front-only')" class="dropdown-item" style="text-align: left; background: none; color: #475569; padding: 8px 12px; border: none; font-size: 12px; font-weight: 600; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                        <span style="font-size: 14px;">🖼️</span>
                        <span>Front Side Only (Color)</span>
                    </button>
                    <button type="button" onclick="triggerDownload('back-only-mono')" class="dropdown-item" style="text-align: left; background: none; color: #475569; padding: 8px 12px; border: none; font-size: 12px; font-weight: 600; width: 100%; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.15s;">
                        <span style="font-size: 14px;">🖤</span>
                        <span>Back Side Only (Black/White)</span>
                    </button>
                </div>
            </div>
            <button type="button" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Print PDF / Printer
            </button>
        </div>
    @endif

    @php
        $getGradeColor = function($grade) {
            if (!$grade) return '#6d28d9';
            $g = strtoupper($grade);
            if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) return '#ea580c';
            if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) return '#0284c7';
            if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) return '#7c3aed';
            if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) return '#dc2626';
            if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) return '#4f46e5';
            return '#6d28d9';
        };
    @endphp

    @foreach ($students as $student)
        @php
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $firstName = trim($applicant->first_name ?? '');
            $middleName = trim($applicant->middle_name ?? '');
            $lastName = trim($applicant->last_name ?? '');
            $suffix = trim($applicant->suffix ?? '');
            
            $middleInitial = \App\Models\EnrollmentApplicant::formatMiddleInitial($middleName) ?? '';
            
            $fullNameParts = array_filter([$firstName, $middleInitial, $lastName, $suffix], function($val) {
                return $val !== '';
            });
            $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
            $displayName = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT RECORD';
            
            $photoPath = $applicant->photo_2x2_url ?? $student->photo_url ?? null;
            $photoUrl = \App\Support\EnrollmentStorage::url($photoPath);
            
            $emergencyAddress = trim($applicant->emergency_address ?? '');
            if (empty($emergencyAddress)) {
                $emergencyAddress = implode(', ', array_filter([$applicant->home_street_address, $applicant->home_city, $applicant->home_state_province]));
                if (empty($emergencyAddress)) {
                    $emergencyAddress = $applicant->home_address ?: '-';
                }
            }
            
            $emergencyName = $applicant->emergency_name ?: '-';
            if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
                $emergencyName = trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_last_name ?? '')) ?: (trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_last_name ?? '')) ?: 'Registrar Office');
            }
            
            $emergencyPhone = $applicant->emergency_phone ?: '-';
            if (empty($emergencyPhone)) {
                $emergencyPhone = $applicant->parent_mobile ?: ($applicant->mobile_number ?: '+63 900 000 0000');
            }

            // QR Code Verification URL
            $studentNumber = $student->student_number;
            $hash = base64_encode((int)$studentNumber + 987654);
            $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
            
            // Format ID for badge (e.g. 260123)
            $badgeStudentId = $studentNumber;

            $displayGrade = $student->grade_level;
        @endphp

        <!-- Print Page Container -->
        <div class="id-print-page">
            <div class="id-pair-container">
                <!-- Front Side -->
                <div class="id-card-wrapper">
                    <div class="id-card-scaler" id="print-front-box-{{ $student->id }}" data-filename="{{ implode('-', array_filter([$lastName, $firstName, $suffix, str_replace(' ', '', $displayGrade)])) }}">
                        <div class="id-card">
                             <!-- Background Template Image (Top Layer) -->
                             <img src="{{ asset('images/id/amis_frontid.png') }}?v={{ filemtime(public_path('images/id/amis_frontid.png')) }}" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 10; pointer-events: none;" alt="AMIS ID Template">
                             
                             <!-- Student Photo Container (Middle Layer) -->
                              @if($photoUrl)
                                  <div class="photo-clip">
                                      <img src="{{ $photoUrl }}" alt="Student Photo">
                                  </div>
                              @else
                                  <div class="photo-placeholder">Photo Missing</div>
                              @endif

                            <!-- Student ID Badge text -->
                            <div class="student-id" style="font-size: {{ $student->id_num_font_size ? $student->id_num_font_size . 'px' : '12.5px' }};">{{ $badgeStudentId }}</div>

                            <!-- Last Name -->
                            @php
                                $lastNameLen = strlen($lastName);
                                if ($lastNameLen <= 8) {
                                    $lastNameFontSize = '36px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } elseif ($lastNameLen <= 12) {
                                    $lastNameFontSize = '28px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } elseif ($lastNameLen <= 15) {
                                    $lastNameFontSize = '22px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } elseif ($lastNameLen <= 18) {
                                    $lastNameFontSize = '17px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } elseif ($lastNameLen <= 21) {
                                    $lastNameFontSize = '14px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } elseif ($lastNameLen <= 25) {
                                    $lastNameFontSize = '12.5px';
                                    $lastNameStyle = 'white-space: nowrap;';
                                } else {
                                    $lastNameFontSize = '13.5px';
                                    $lastNameStyle = 'white-space: normal; line-height: 1.05; word-break: normal; overflow-wrap: break-word; text-wrap: balance;';
                                }
                            @endphp
                            <div class="student-last-name">
                                <h3 style="font-size: {{ $student->id_last_name_font_size ? $student->id_last_name_font_size . 'px' : $lastNameFontSize }}; {{ $lastNameStyle }}">{{ $lastName }}</h3>
                            </div>

                            <!-- First Name -->
                            @php
                                $displayFirstName = trim(implode(' ', array_filter([$firstName, $middleInitial, $suffix])));
                                $firstNameLen = strlen($displayFirstName);
                                $firstNameFontSize = $firstNameLen > 25 ? '14px' : ($firstNameLen > 18 ? '16px' : '18px');
                            @endphp
                            <div class="student-first-name">
                                <h4 style="font-size: {{ $student->id_first_name_font_size ? $student->id_first_name_font_size . 'px' : $firstNameFontSize }};">{{ $displayFirstName }}</h4>
                            </div>

                            <!-- Grade Level -->
                            <div class="student-grade">
                                <span style="color: {{ $getGradeColor($displayGrade) }}; font-size: {{ $student->id_grade_font_size ? $student->id_grade_font_size . 'px' : '31px' }}">{{ $displayGrade }}</span>
                            </div>

                            <!-- LRN -->
                            @if($applicant->lrn && !in_array(strtoupper($applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                <div class="student-lrn">
                                    LRN: <span style="margin-left: 4px;">{{ $applicant->lrn }}</span>
                                </div>
                            @endif

                             <!-- QR Code -->
                            <div class="student-qr js-qr-code" data-qr="https://amis.edu.ph/v/{{ $hash }}"></div>
                        </div>
                    </div>
                </div>

                <!-- Back Side -->
                <div class="id-card-wrapper">
                    <div class="id-card-scaler" id="print-back-box-{{ $student->id }}" data-filename="{{ implode('-', array_filter([$lastName, $firstName, str_replace(' ', '', $displayGrade)])) }}">
                        <div class="id-card">
                            <!-- Background Template Image -->
                            <img src="{{ asset('images/id/amis_backid.png') }}?v=1" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;" alt="AMIS ID Template Back">

                            <!-- Emergency Details List -->
                            @php
                                $relationship = trim($applicant->emergency_relationship ?? '');
                                $parentNameLen = strlen($emergencyName);
                                $parentNameFontSize = $parentNameLen > 24 ? '14px' : ($parentNameLen > 18 ? '16px' : '19px');
                                
                                $addressLen = strlen($emergencyAddress);
                                $addressFontSize = $addressLen > 60 ? '12px' : ($addressLen > 40 ? '13px' : '14px');
                            @endphp
                            <div class="emergency-info">
                                <!-- Contact Name -->
                                <div class="emerg-row">
                                    <span class="emerg-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: {{ $parentNameFontSize }}; font-weight: 900; text-transform: uppercase; color: #0f172a; line-height: 1.2;">
                                        {{ $emergencyName }}
                                    </div>
                                </div>

                                <!-- Relationship -->
                                <div class="emerg-row">
                                    <span class="emerg-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                                    </span>
                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1.2;">
                                        {{ $relationship ?: 'PARENT / GUARDIAN' }}
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="emerg-row">
                                    <span class="emerg-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.589 2.356a1.75 1.75 0 0 1-.607 1.89l-1.077.808a12.983 12.983 0 0 0 5.753 5.753l.808-1.077a1.75 1.75 0 0 1 1.89-.607l2.356.589c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1.2;">
                                        {{ $emergencyPhone }}
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="emerg-row">
                                    <span class="emerg-icon" style="margin-top: 2.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 3.58-2.977c2.2-2.384 4.19-5.462 4.19-8.923 0-4.82-3.855-8.5-8.5-8.5-8.5 0-8.5 3.68-8.5 8.5c0 3.461 1.99 6.54 4.19 8.923a16.975 16.975 0 0 0 3.58 2.977Zm3.71-12.851a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: {{ $addressFontSize }}; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1.25;">
                                        {{ $emergencyAddress }}
                                    </div>
                                </div>
                            </div>


                            <!-- Secure Director Signature QR -->
                            <div class="back-signature-qr js-qr-code" data-qr="https://amis.edu.ph/signature"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        function adjustLastNameFontSizes() {
            // Font sizes are calculated server-side in PHP matching Student Records (show.blade.php)
        }

        function doPrint() {
            adjustLastNameFontSizes();
            window.print();
        }
        window.addEventListener('load', () => {
            runAdjustAfterFonts();
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1' || urlParams.get('auto_print') === '1' || urlParams.has('print_id')) {
                document.fonts.ready.then(() => setTimeout(() => window.print(), 500));
            }
        });

        function toggleDropdown() {
            const menu = document.getElementById('png-dropdown-menu');
            if (menu.style.display === 'none' || !menu.style.display) {
                menu.style.display = 'flex';
            } else {
                menu.style.display = 'none';
            }
        }
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                const menu = document.getElementById('png-dropdown-menu');
                if (menu) menu.style.display = 'none';
            }
        });

        async function triggerDownload(mode) {
            const btn = document.querySelector('.btn-png');
            let oldHTML = '';
            if (btn) {
                oldHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<svg class="animate-spin" style="animation: spin 1s linear infinite; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>Generating...`;
            }

            try {
                await downloadAllPngs(mode);
            } catch (err) {
                console.error(err);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = oldHTML;
                }
            }
        }

        async function downloadCardPng(elementId, filename, isMonochrome = false) {
            adjustLastNameFontSizes();
            if (typeof html2canvas === 'undefined') return;
            const cardEl = document.getElementById(elementId);
            if (!cardEl) return;
            try {
                const canvas = await html2canvas(cardEl, {
                    scale: 3, // 300 DPI high resolution
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: isMonochrome ? '#ffffff' : null,
                    logging: false
                });
                
                let dataUrl = canvas.toDataURL('image/png', 1.0);
                
                if (isMonochrome) {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = dataUrl;
                    await new Promise(res => {
                        img.onload = res;
                        img.onerror = res;
                    });
                    
                    const cvs = document.createElement('canvas');
                    cvs.width = img.width || 1020;
                    cvs.height = img.height || 1614;
                    const ctx = cvs.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    
                    const imgData = ctx.getImageData(0, 0, cvs.width, cvs.height);
                    const d = imgData.data;
                    for (let i = 0; i < d.length; i += 4) {
                        const gray = 0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2];
                        const val = gray > 215 ? 255 : (gray < 165 ? 0 : Math.round(gray));
                        d[i] = val;
                        d[i+1] = val;
                        d[i+2] = val;
                    }
                    ctx.putImageData(imgData, 0, 0);
                    dataUrl = cvs.toDataURL('image/png', 1.0);
                }
                
                const link = document.createElement('a');
                link.download = filename;
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                console.error(e);
            }
        }

        async function downloadAllPngs(mode = 'front-color-back-mono') {
            const frontEls = document.querySelectorAll('[id^="print-front-box-"]');
            const backEls = document.querySelectorAll('[id^="print-back-box-"]');
            
            // front-color-back-mono: Front Color, Back Black/White
            // full-color: Front Color, Back Color
            // front-only: Front Color only
            // back-only-mono: Back Black/White only

            if (mode === 'front-color-back-mono' || mode === 'full-color' || mode === 'front-only') {
                for (let el of frontEls) {
                    const name = el.getAttribute('data-filename') || 'STUDENT';
                    await downloadCardPng(el.id, `${name}-FRONT.png`, false);
                    await new Promise(r => setTimeout(r, 600));
                }
            }
            
            if (mode === 'front-color-back-mono' || mode === 'full-color' || mode === 'back-only-mono') {
                for (let el of backEls) {
                    const name = el.getAttribute('data-filename') || 'STUDENT';
                    const isMono = (mode === 'front-color-back-mono' || mode === 'back-only-mono');
                    await downloadCardPng(el.id, `${name}-BACK${isMono ? '-BLACK-ONLY' : ''}.png`, isMono);
                    await new Promise(r => setTimeout(r, 600));
                }
            }
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function renderAllQRCodes() {
            if (typeof QRCode === 'undefined') return;
            document.querySelectorAll('.js-qr-code').forEach(el => {
                if (el.dataset.qr && !el.dataset.qrRendered) {
                    el.dataset.qrRendered = "true";
                    new QRCode(el, {
                        text: el.dataset.qr,
                        width: el.classList.contains('back-signature-qr') ? 100 : 150,
                        height: el.classList.contains('back-signature-qr') ? 100 : 150,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.M
                    });
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderAllQRCodes);
        } else {
            renderAllQRCodes();
        }
    </script>
</body>
</html>
