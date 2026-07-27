<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENROLMENT APPLICATION FORMS - {{ mb_strtoupper($gradeTitle ?? 'BATCH') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,700;1,700&family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Merriweather', Georgia, serif;
            background-color: #f1f5f9;
            color: #0f172a;
            line-height: 1.3;
            padding: 20px 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Bar (Screen Only) */
        .action-bar {
            max-width: 860px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            font-family: 'Inter', sans-serif;
            position: sticky;
            top: 10px;
            z-index: 100;
            border: 1px solid #e2e8f0;
        }

        .action-bar h2 {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0 16px;
            height: 38px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            white-space: nowrap;
            line-height: 1;
        }

        .btn-primary {
            background-color: #059669;
            color: white;
        }
        .btn-primary:hover {
            background-color: #047857;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .btn-zip {
            background-color: #f0fdf4;
            color: #166534;
            border: 1.5px solid #bbf7d0;
            text-decoration: none;
        }
        .btn-zip:hover {
            background-color: #dcfce7;
            border-color: #86efac;
            transform: translateY(-1px);
        }

        .btn-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        /* Full-Screen Loading Overlay */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            transition: opacity 0.35s ease, visibility 0.35s ease;
        }
        .loading-overlay.hidden-overlay {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .loading-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px 36px;
            width: 360px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
        }
        .spinner-ring {
            width: 40px;
            height: 40px;
            margin: 0 auto 16px auto;
            border: 3.5px solid #e2e8f0;
            border-top-color: #059669;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .loading-progress-bg {
            width: 100%;
            height: 8px;
            background: #f1f5f9;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
        }
        .loading-progress-fill {
            height: 100%;
            background: #059669;
            width: 5%;
            transition: width 0.2s ease-in-out;
            border-radius: 9999px;
        }
        .loading-subtext {
            font-family: 'Inter', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
        }

        /* Paper Document Layout (A4 Scale) */
        .paper-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 14mm 16mm 14mm 16mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            border-radius: 2px;
        }

        .paper-page-break {
            page-break-after: always;
            break-after: page;
        }

        /* PAGE 1: Header Layout */
        .top-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 10px;
        }

        .header-left-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .header-logo-amis {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .header-school-text {
            text-align: center;
            flex-grow: 1;
            padding: 0 5px;
        }

        .school-arabic-name {
            font-family: 'Amiri', 'Traditional Arabic', 'Scheherazade New', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: #047857;
            text-align: center;
            direction: rtl;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .school-name {
            font-family: 'Merriweather', serif;
            font-size: 1.18rem;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.2;
        }

        .school-address {
            font-family: 'Merriweather', serif;
            font-size: 0.88rem;
            margin-top: 2px;
            color: #334155;
            white-space: nowrap;
            text-align: center;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .header-logo-deped {
            width: 66px;
            height: 66px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .refund-notice-box {
            border: 2px solid #dc2626;
            padding: 6px 12px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 0.88rem;
            line-height: 1.25;
            color: #dc2626;
            text-transform: uppercase;
            white-space: nowrap;
            border-radius: 5px;
            margin: 0;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-self: center;
        }

        .form-middle-grid {
            display: grid;
            grid-template-columns: 1fr auto 125px;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .form-title-area {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .form-title {
            font-family: 'Merriweather', serif;
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            color: #0f172a;
        }

        .sy-title {
            font-family: 'Merriweather', serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-top: 3px;
            margin-bottom: 14px;
            color: #1e293b;
        }

        .student-info-bar {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-top: 4px;
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .lrn-container {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 6px;
            white-space: nowrap;
        }

        .lrn-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            width: 200px;
            outline: none;
            padding: 0 4px;
            text-transform: uppercase;
        }

        .checkbox-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 25px;
            align-items: flex-start;
            font-family: 'Inter', sans-serif;
            padding-right: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 900;
            background: #fff;
            line-height: 1;
            flex-shrink: 0;
            border-radius: 3px;
        }

        .photo-box {
            width: 125px;
            height: 125px;
            border: 2px solid #0f172a;
            background: #fafafa;
            justify-self: end;
            align-self: flex-start;
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 3px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .section-header-row {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-top: 18px;
            margin-bottom: 10px;
            white-space: nowrap;
        }

        .field-container {
            margin-bottom: 12px;
            width: 100%;
        }

        .input-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 4px;
            width: 100%;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            text-transform: uppercase;
        }

        .label-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
            margin-top: 3px;
            display: block;
        }

        .grid-5-col {
            display: grid;
            grid-template-columns: 2.9fr 2.9fr 2.6fr 0.6fr 1.0fr;
            gap: 12px;
        }

        .grid-4-col-birth {
            display: grid;
            grid-template-columns: 1.2fr 2.5fr 3.5fr 2fr;
            gap: 15px;
        }

        .grid-2-col-school {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 15px;
        }

        .grid-parent-row {
            display: grid;
            grid-template-columns: 3.8fr 2.1fr 2.9fr;
            gap: 15px;
            align-items: flex-end;
        }

        .grid-children-row {
            display: grid;
            grid-template-columns: 4.5fr 1.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 8px;
        }

        .lives-with-row {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 25px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .radio-option {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .radio-line {
            display: inline-block;
            width: 40px;
            border-bottom: 1.5px solid #0f172a;
            text-align: center;
            font-weight: 800;
            height: 18px;
            line-height: 18px;
        }

        .page-number-badge {
            position: absolute;
            top: 5mm;
            right: 8mm;
            font-family: 'Inter', sans-serif;
            font-size: 0.78rem;
            font-weight: 800;
            color: #1e293b;
            background-color: #f1f5f9;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            z-index: 10;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            body {
                background: none;
                padding: 0;
            }

            .action-bar, .loading-overlay {
                display: none !important;
            }

            .paper-container {
                box-shadow: none;
                padding: 10mm 12mm 10mm 12mm;
                width: 100%;
                margin: 0;
            }

            .input-line, .p2-full-line, .lrn-input {
                border-bottom: 1.5px solid #000 !important;
            }

            img {
                max-width: 100% !important;
                display: block !important;
                visibility: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
    <!-- html2canvas, jsPDF, and JSZip CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
</head>
<body>

    <!-- Full-Screen Loading Overlay with robust inline styles -->
    <div id="loadingOverlay" class="loading-overlay" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 999999;">
        <div class="loading-card">
            <div class="spinner-ring"></div>
            <div class="loading-title">Loading Enrollment Forms</div>
            <div class="loading-progress-bg">
                <div id="loadingProgressBar" class="loading-progress-fill"></div>
            </div>
            <div id="loadingProgressCount" class="loading-subtext">Rendering student forms (0/{{ count($students) }})...</div>
        </div>
    </div>

    <!-- Top Action Bar for Screen Viewing -->
    <div class="action-bar">
        <h2>Enrolment Application Forms ({{ $gradeTitle ?? 'All Grades' }}) — {{ count($students) }} Students</h2>
        <div class="btn-group">
            <button class="btn btn-secondary" onclick="window.close()">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                Close
            </button>
            <button class="btn btn-primary" onclick="generateBatchPdfDownload()">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
                Download PDF
            </button>
            <button class="btn btn-zip" onclick="generatePdfZip()" style="background-color: #eff6ff; color: #1e40af; border-color: #bfdbfe;">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                ZIP (PDFs)
            </button>
            <button class="btn btn-zip" id="btn-download-png-zip" onclick="generatePngZip()" style="background-color: #f5f3ff; color: #5b21b6; border-color: #ddd6fe;">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                ZIP (JPGs)
            </button>
            <button class="btn btn-secondary" onclick="window.print()">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    @forelse($students as $student)
        @php
            $appl = $student->applicant;
            $lastNameClean = mb_strtoupper(str_replace([' ', '/'], '_', trim($appl->last_name ?? 'STUDENT')));
            $firstNameClean = mb_strtoupper(str_replace([' ', '/'], '_', trim($appl->first_name ?? 'PROFILE')));
            $gradeFolder = trim($student->grade_level ?: 'Grade_1');
            if (preg_match('/^Grade\s*(\d+)$/i', $gradeFolder, $m)) {
                $gShort = 'G' . $m[1];
            } elseif (preg_match('/^Kinder\s*(\d+)$/i', $gradeFolder, $m)) {
                $gShort = 'K' . $m[1];
            } else {
                $gShort = str_replace(' ', '_', $gradeFolder);
            }
            $learningMode = strtolower($appl->learning_mode ?? '');
            $isF2f = str_contains($learningMode, 'face') || str_contains($learningMode, 'f2f');
            $modeLabel = $isF2f ? 'F2F' : 'ODL';
            if (!$isF2f) {
                $shiftFolder = '1ST_SHIFT';
                if (str_contains($learningMode, '2nd') || str_contains($learningMode, 'second') || str_contains($learningMode, 'shift 2')) {
                    $shiftFolder = '2ND_SHIFT';
                }
                $modeLabel = "ODL/{$shiftFolder}";
            }
        @endphp
        <div class="student-print-wrapper" data-student-id="{{ $student->id }}" data-student-name="{{ $lastNameClean }}_{{ $firstNameClean }}" data-grade="{{ $gShort }}" data-mode="{{ $modeLabel }}">
            @include('admin.students.partials.print.enrolment-form-body', [
                'student'    => $student,
                'applicant'  => $student->applicant,
                'siblings'   => $siblingsMap[$student->id] ?? [],
                'pageNumber' => $loop->iteration,
                'totalPages' => count($students),
            ])
        </div>
    @empty
        <div class="paper-container text-center" style="padding: 50px; font-family: 'Inter', sans-serif;">
            <h3>No student records found for printing.</h3>
        </div>
    @endforelse

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_started' }, '*');
            }
            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const images = document.querySelectorAll('img');
            const totalImages = images.length || 1;
            let loadedImages = 0;

            function updateProgress(percent, countMsg) {
                if (fill) fill.style.width = percent + '%';
                if (text) text.innerText = countMsg;
            }

            if (images.length === 0) {
                updateProgress(100, 'Ready');
                setTimeout(() => overlay && overlay.classList.add('hidden-overlay'), 200);
                return;
            }

            images.forEach(img => {
                if (img.complete) {
                    onImgLoad();
                } else {
                    img.addEventListener('load', onImgLoad);
                    img.addEventListener('error', onImgLoad);
                }
            });

            function onImgLoad() {
                loadedImages++;
                const pct = Math.min(100, Math.round((loadedImages / totalImages) * 100));
                updateProgress(pct, `Loading student forms (${loadedImages}/${totalImages})...`);

                if (loadedImages >= totalImages) {
                    setTimeout(() => {
                        updateProgress(100, 'Loading complete!');
                        setTimeout(() => {
                            if (overlay) overlay.classList.add('hidden-overlay');
                        }, 200);
                    }, 100);
                }
            }

            setTimeout(() => {
                if (overlay && !overlay.classList.contains('hidden-overlay')) {
                    updateProgress(100, 'Ready');
                    overlay.classList.add('hidden-overlay');
                }
            }, 3500);
        });

        async function generatePngZip() {
            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const title = overlay ? overlay.querySelector('.loading-title') : null;
            
            if (overlay) {
                overlay.classList.remove('hidden-overlay');
            }
            if (title) {
                title.innerText = 'Generating PNG Images...';
            }
            if (fill) fill.style.width = '0%';
            
            const zip = new JSZip();
            const wrappers = document.querySelectorAll('.student-print-wrapper');
            const totalStudents = wrappers.length;
            
            if (totalStudents === 0) {
                alert('No student records found to export.');
                if (overlay) overlay.classList.add('hidden-overlay');
                return;
            }
            
            let processedPages = 0;
            const totalPages = totalStudents * 2;
            
            for (let i = 0; i < totalStudents; i++) {
                const wrapper = wrappers[i];
                const studentName = wrapper.getAttribute('data-student-name') || 'STUDENT_' + i;
                const grade = wrapper.getAttribute('data-grade') || 'Grade_1';
                const mode = wrapper.getAttribute('data-mode') || 'F2F';
                const pages = wrapper.querySelectorAll('.paper-container');
                
                const basePath = `${grade}/${mode}`;
                
                for (let pageIdx = 0; pageIdx < pages.length; pageIdx++) {
                    const pageEl = pages[pageIdx];
                    const pageNum = pageIdx + 1;
                    
                    const pct = Math.round((processedPages / totalPages) * 100);
                    if (fill) fill.style.width = pct + '%';
                    if (text) {
                        text.innerText = `Rendering Student ${i + 1} of ${totalStudents} (Page ${pageNum}/2)...`;
                    }
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'zip_log',
                            current: i + 1,
                            total: totalStudents,
                            percent: pct,
                            message: `[${i + 1}/${totalStudents}] Processing: ${studentName.replace(/_/g, ' ')} (Page ${pageNum}/2)`
                        }, '*');
                    }
                    
                    try {
                        const canvas = await html2canvas(pageEl, {
                            scale: 1.15,
                            useCORS: true,
                            logging: false,
                            allowTaint: true,
                            backgroundColor: '#ffffff',
                            imageTimeout: 0,
                            removeContainer: true
                        });
                        
                        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
                        zip.file(`${basePath}/${studentName}_Page_${pageNum}.jpg`, blob);
                        
                    } catch (err) {
                        console.error(`Failed to render ${studentName} page ${pageNum}:`, err);
                    }
                    
                    processedPages++;
                }
            }
            
            if (fill) fill.style.width = '100%';
            if (text) text.innerText = 'Creating ZIP archive... Please wait.';
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({
                    type: 'zip_log',
                    percent: 98,
                    message: '📦 Compiling JPG files into ZIP archive...'
                }, '*');
            }
            
            try {
                const content = await zip.generateAsync({ type: 'blob', compression: 'STORE' });
                const url = URL.createObjectURL(content);
                const link = document.createElement('a');
                const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                const gradeClean = "{{ str_replace(' ', '_', $gradeTitle ?? 'Batch') }}";
                link.href = url;
                link.download = `Enrollment_Forms_SY_2026-2027_${gradeClean}_${dateStr}_JPG.zip`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(() => URL.revokeObjectURL(url), 100);
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'zip_done',
                        message: '⚡ ZIP Archive generated & download started!'
                    }, '*');
                }
            } catch (zipErr) {
                console.error('Error generating ZIP:', zipErr);
                alert('Failed to generate ZIP file.');
            }
            
            if (title) {
                title.innerText = 'Loading Enrollment Forms';
            }
            if (overlay) {
                overlay.classList.add('hidden-overlay');
            }
        }

        async function generateBatchPdfDownload() {
            const { jsPDF } = window.jspdf;
            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const title = overlay ? overlay.querySelector('.loading-title') : null;
            
            if (overlay) overlay.classList.remove('hidden-overlay');
            if (title) title.innerText = 'Generating Pixel-Perfect PDF...';
            if (fill) fill.style.width = '0%';
            
            const pdf = new jsPDF('p', 'mm', 'a4');
            const wrappers = document.querySelectorAll('.student-print-wrapper');
            const totalStudents = wrappers.length;
            
            if (totalStudents === 0) {
                alert('No student records found to export.');
                if (overlay) overlay.classList.add('hidden-overlay');
                return;
            }
            
            let pdfPageCount = 0;
            const totalPages = totalStudents * 2;
            
            for (let i = 0; i < totalStudents; i++) {
                const wrapper = wrappers[i];
                const studentName = wrapper.getAttribute('data-student-name') || 'STUDENT_' + i;
                const pages = wrapper.querySelectorAll('.paper-container');
                
                for (let pageIdx = 0; pageIdx < pages.length; pageIdx++) {
                    const pageEl = pages[pageIdx];
                    const pageNum = pageIdx + 1;
                    
                    const pct = Math.round((pdfPageCount / totalPages) * 100);
                    if (fill) fill.style.width = pct + '%';
                    if (text) text.innerText = `Rendering Student ${i + 1} of ${totalStudents} (Page ${pageNum}/2)...`;
                    
                    try {
                        const canvas = await html2canvas(pageEl, {
                            scale: 2,
                            useCORS: true,
                            logging: false,
                            allowTaint: true,
                            backgroundColor: '#ffffff',
                            imageTimeout: 0,
                            removeContainer: true
                        });
                        
                        const imgData = canvas.toDataURL('image/jpeg', 0.92);
                        
                        if (pdfPageCount > 0) {
                            pdf.addPage('a4', 'p');
                        }
                        
                        pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);
                        
                    } catch (err) {
                        console.error(`Failed to render ${studentName} page ${pageNum}:`, err);
                    }
                    
                    pdfPageCount++;
                }
            }
            
            if (fill) fill.style.width = '100%';
            if (text) text.innerText = 'Saving PDF File... Please wait.';
            
            setTimeout(() => {
                const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                const gradeClean = "{{ str_replace(' ', '_', $gradeTitle ?? 'Batch') }}";
                pdf.save(`Enrollment_Forms_SY_2026-2027_${gradeClean}_${dateStr}.pdf`);
                if (title) title.innerText = 'Loading Enrollment Forms';
                if (overlay) overlay.classList.add('hidden-overlay');
            }, 400);
        }

        function fitAllFormFontSizes() {
            document.querySelectorAll('.input-line, .lrn-input, .p2-full-line, .auto-fit-field, .address-auto-fit').forEach(el => {
                const text = (el.value || el.innerText || '').trim();
                if (!text) return;
                
                el.style.whiteSpace = 'nowrap';
                const style = window.getComputedStyle(el);
                let size = parseFloat(style.fontSize) || 14;
                const minSize = 8.5;

                const containerWidth = el.clientWidth || el.getBoundingClientRect().width;
                if (containerWidth <= 0) return;

                const dummyCanvas = document.createElement('canvas');
                const ctx = dummyCanvas.getContext('2d');
                const fontWeight = style.fontWeight || '700';
                const fontFamily = style.fontFamily || 'Inter, sans-serif';

                while (size > minSize) {
                    ctx.font = `${fontWeight} ${size}px ${fontFamily}`;
                    const textWidth = ctx.measureText(text).width;
                    if (textWidth <= containerWidth - 4) {
                        break;
                    }
                    size -= 0.5;
                }
                el.style.fontSize = size + 'px';
            });
        }
        document.addEventListener('DOMContentLoaded', fitAllFormFontSizes);
        window.addEventListener('load', fitAllFormFontSizes);
        setTimeout(fitAllFormFontSizes, 100);

        async function generatePdfZip() {
            const { jsPDF } = window.jspdf;
            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const title = overlay ? overlay.querySelector('.loading-title') : null;
            
            if (overlay) overlay.classList.remove('hidden-overlay');
            if (title) title.innerText = 'Generating Individual PDFs ZIP...';
            if (fill) fill.style.width = '0%';
            
            const zip = new JSZip();
            const wrappers = document.querySelectorAll('.student-print-wrapper');
            const totalStudents = wrappers.length;
            
            if (totalStudents === 0) {
                alert('No student records found to export.');
                if (overlay) overlay.classList.add('hidden-overlay');
                return;
            }
            
            let processedPages = 0;
            const totalPages = totalStudents * 2;
            
            for (let i = 0; i < totalStudents; i++) {
                const wrapper = wrappers[i];
                const studentName = wrapper.getAttribute('data-student-name') || 'STUDENT_' + i;
                const grade = wrapper.getAttribute('data-grade') || 'Grade_1';
                const mode = wrapper.getAttribute('data-mode') || 'F2F';
                const pages = wrapper.querySelectorAll('.paper-container');
                const basePath = `${grade}/${mode}`;
                
                const studentPdf = new jsPDF('p', 'mm', 'a4');
                
                for (let pageIdx = 0; pageIdx < pages.length; pageIdx++) {
                    const pageEl = pages[pageIdx];
                    const pageNum = pageIdx + 1;
                    
                    const pct = Math.round((processedPages / totalPages) * 100);
                    if (fill) fill.style.width = pct + '%';
                    if (text) text.innerText = `Rendering Student ${i + 1} of ${totalStudents} (Page ${pageNum}/2)...`;
                    
                    try {
                        const canvas = await html2canvas(pageEl, {
                            scale: 2,
                            useCORS: true,
                            logging: false,
                            allowTaint: true,
                            backgroundColor: '#ffffff',
                            imageTimeout: 0,
                            removeContainer: true
                        });
                        
                        const imgData = canvas.toDataURL('image/jpeg', 0.92);
                        if (pageIdx > 0) studentPdf.addPage('a4', 'p');
                        studentPdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);
                        
                    } catch (err) {
                        console.error(`Failed to render ${studentName} page ${pageNum}:`, err);
                    }
                    
                    processedPages++;
                }
                
                const pdfBlob = studentPdf.output('blob');
                zip.file(`${basePath}/Enrollment Application Form - ${studentName.replace(/_/g, ' ')}.pdf`, pdfBlob);
            }
            
            if (fill) fill.style.width = '100%';
            if (text) text.innerText = 'Creating ZIP archive... Please wait.';
            
            try {
                const content = await zip.generateAsync({ type: 'blob', compression: 'STORE' });
                const url = URL.createObjectURL(content);
                const link = document.createElement('a');
                const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                const gradeClean = "{{ str_replace(' ', '_', $gradeTitle ?? 'Batch') }}";
                link.href = url;
                link.download = `Enrollment_Forms_SY_2026-2027_${gradeClean}_${dateStr}_PDF.zip`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(() => URL.revokeObjectURL(url), 100);
            } catch (zipErr) {
                console.error('Error generating ZIP:', zipErr);
                alert('Failed to generate ZIP file.');
            }
            
            if (title) title.innerText = 'Loading Enrollment Forms';
            if (overlay) overlay.classList.add('hidden-overlay');
        }

        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 500);
            });
        }

        if (new URLSearchParams(window.location.search).get('auto_pdf') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                const checkReadyInterval = setInterval(async () => {
                    const overlay = document.getElementById('loadingOverlay');
                    if (!overlay || overlay.classList.contains('hidden-overlay')) {
                        clearInterval(checkReadyInterval);
                        await generateBatchPdfDownload();
                    }
                }, 200);
            });
        }

        if (new URLSearchParams(window.location.search).get('auto_zip_pdf') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                const checkReadyInterval = setInterval(async () => {
                    const overlay = document.getElementById('loadingOverlay');
                    if (!overlay || overlay.classList.contains('hidden-overlay')) {
                        clearInterval(checkReadyInterval);
                        await generatePdfZip();
                        setTimeout(() => window.close(), 1500);
                    }
                }, 200);
            });
        }

        if (new URLSearchParams(window.location.search).get('auto_zip_docx') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                const checkReadyInterval = setInterval(async () => {
                    const overlay = document.getElementById('loadingOverlay');
                    if (!overlay || overlay.classList.contains('hidden-overlay')) {
                        clearInterval(checkReadyInterval);
                        await generateDocxZip();
                        setTimeout(() => window.close(), 1500);
                    }
                }, 200);
            });
        }

        if (new URLSearchParams(window.location.search).get('auto_zip_png') === '1' || new URLSearchParams(window.location.search).get('auto_zip_jpg') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                const checkReadyInterval = setInterval(async () => {
                    const overlay = document.getElementById('loadingOverlay');
                    if (!overlay || overlay.classList.contains('hidden-overlay')) {
                        clearInterval(checkReadyInterval);
                        await generatePngZip();
                        setTimeout(() => window.close(), 1500);
                    }
                }, 200);
            });
        }
    </script>
</body>
</html>
