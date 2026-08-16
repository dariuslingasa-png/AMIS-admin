<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENROLMENT APPLICATION FORMS - {{ mb_strtoupper($gradeTitle ?? 'BATCH') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Noto+Naskh+Arabic:wght@700&family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    
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
            width: 84px;
            height: 84px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .header-school-text {
            text-align: center;
            flex-grow: 1;
            padding: 0 5px;
        }

        .school-arabic-name {
            font-family: 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif;
            font-size: 1.80rem;
            font-weight: 700;
            color: #047857;
            text-align: center;
            direction: rtl;
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .header-arabic-wordmark {
            height: 40px;
            max-width: 380px;
            object-fit: contain;
            display: inline-block;
            margin-bottom: 2px;
        }

        .school-name {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 1.26rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.15;
        }

        .school-address {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 0.86rem;
            font-weight: 600;
            margin-top: 2px;
            color: #334155;
            white-space: nowrap;
            text-align: center;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .header-logo-deped {
            width: 84px;
            height: 84px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .refund-notice-box {
            border: 2px solid #dc2626;
            padding: 5px 8px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 0.82rem;
            line-height: 1.15;
            color: #dc2626;
            text-transform: uppercase;
            white-space: nowrap;
            border-radius: 4px;
            margin: 0;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-self: center;
        }

        .form-middle-grid {
            display: grid;
            grid-template-columns: 1fr auto 112px;
            align-items: flex-start;
            gap: 16px;
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
            width: 112px;
            height: 112px;
            border: 1px solid #94a3b8;
            background: #f8fafc;
            justify-self: end;
            align-self: flex-start;
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 3px;
            box-sizing: border-box;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
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
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 2px 1px 2px;
            line-height: 1.25;
            min-height: 20px;
            height: auto;
            width: 100%;
            background: transparent;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
            display: block;
            box-sizing: border-box;
            text-transform: uppercase;
        }

        .p2-full-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.90rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 2px 1px 2px;
            line-height: 1.25;
            min-height: 20px;
            height: auto;
            width: 100%;
            background: transparent;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
            display: block;
            box-sizing: border-box;
            text-transform: uppercase;
        }

        .lrn-input {
            display: inline-block;
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
            padding: 0 4px;
            line-height: 1.2;
            min-width: 85px;
            min-height: 16px;
            height: auto;
            text-align: center;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
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
            align-items: end;
        }

        .grid-4-col-birth {
            display: grid;
            grid-template-columns: 1.2fr 2.5fr 3.5fr 2fr;
            gap: 15px;
            align-items: end;
        }

        .grid-2-col-school {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 15px;
            align-items: end;
        }

        .grid-parent-row {
            display: grid;
            grid-template-columns: 3.8fr 2.1fr 2.9fr;
            gap: 15px;
            align-items: end;
        }

        .grid-children-row {
            display: grid;
            grid-template-columns: 4.5fr 1.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 8px;
            align-items: end;
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

        .p2-question-row {
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 4px;
            color: #1e293b;
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
        }

        .p2-inline-line {
            display: inline-block;
            width: 28px;
            border-bottom: 1.5px solid #0f172a;
            text-align: center;
            font-weight: 800;
            height: 18px;
            line-height: 18px;
        }

        .p2-explain-block {
            margin-top: 4px;
            margin-bottom: 10px;
        }

        .p2-explain-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            display: block;
        }

        .p2-full-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 2px 1px 2px;
            line-height: 1.25;
            min-height: 20px;
            height: auto;
            margin-bottom: 6px;
            background: transparent;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
            display: block;
            box-sizing: border-box;
            text-transform: uppercase;
        }

        .grid-physician-row {
            display: grid;
            grid-template-columns: 4fr 3fr;
            gap: 20px;
            margin-top: 6px;
            margin-bottom: 6px;
            align-items: end;
        }

        .p2-emergency-grid {
            display: grid;
            grid-template-columns: 4.5fr 3.5fr 3fr;
            gap: 15px;
            margin-top: 6px;
            margin-bottom: 8px;
            align-items: end;
        }

        .p2-policy-text {
            font-family: 'Merriweather', serif;
            font-size: 0.88rem;
            line-height: 1.4;
            margin-top: 8px;
            text-align: justify;
            color: #1e293b;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 30px;
            margin-top: 18px;
            margin-bottom: 6px;
            align-items: end;
        }

        .signature-disclaimer {
            font-family: 'Inter', sans-serif;
            font-size: 0.80rem;
            font-style: italic;
            color: #64748b;
            margin-bottom: 12px;
        }

        .office-perforated-line {
            border: none;
            border-top: 1.5px dashed #64748b;
            margin: 16px 0 12px 0;
        }

        .office-use-box {
            border: 1px solid #94a3b8;
            padding: 10px 14px;
            margin-top: 4px;
            background: #ffffff;
            border-radius: 4px;
        }

        .office-use-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .grid-office-row {
            display: grid;
            grid-template-columns: 3.5fr 2.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.90rem;
            font-weight: 700;
            color: #1e293b;
            align-items: end;
        }

        .office-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.90rem;
            font-weight: 700;
            color: #1e293b;
        }

        .date-slash-inputs {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
        }

        .date-slash-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            width: 36px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 1px 2px;
        }

        .attachments-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-style: italic;
            font-weight: 800;
            color: #0f172a;
            margin-top: 12px;
            margin-bottom: 6px;
        }

        .attachments-list {
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            line-height: 1.55;
            margin-left: 22px;
            color: #334155;
        }

        .attachments-list li {
            margin-bottom: 3px;
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
    <div id="loadingOverlay" class="loading-overlay" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 999999; transition: opacity 0.25s ease;">
        <div class="loading-card">
            <div class="spinner-ring"></div>
            <div id="loadingTitle" class="loading-title">Preparing Enrollment Forms</div>
            <div class="loading-progress-bg">
                <div id="loadingProgressBar" class="loading-progress-fill"></div>
            </div>
            <div id="loadingProgressCount" class="loading-subtext">Loading student forms (0/{{ count($students) }})...</div>
        </div>
    </div>

    <!-- Top Action Bar for Screen Viewing -->
    <div class="action-bar" style="max-width: 960px; margin: 0 auto 16px auto; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 12px 20px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06); font-family: 'Inter', sans-serif; position: sticky; top: 10px; z-index: 100; border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 34px; height: 34px; border-radius: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; shrink-0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            </div>
            <div>
                <h2 style="font-size: 0.92rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                    Batch Enrollment Forms ({{ $gradeTitle ?? 'All Grades' }})
                </h2>
                <p style="font-size: 0.74rem; font-weight: 600; color: #64748b; margin: 2px 0 0 0;">
                    Total: <strong style="color: #059669;">{{ count($students) }} Students</strong> • 2 Pages per form
                </p>
            </div>
        </div>
        <div class="btn-group" style="display: flex; gap: 8px; align-items: center;">
            <button class="btn btn-secondary" onclick="window.close()" style="padding: 7px 12px; font-size: 0.78rem;">
                Close
            </button>
            <button class="btn btn-zip" onclick="generatePdfZip()" style="background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 7px 14px; font-size: 0.78rem;">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                ZIP (PDFs)
            </button>
            <button class="btn btn-zip" id="btn-download-png-zip" onclick="generatePngZip()" style="background-color: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; padding: 7px 14px; font-size: 0.78rem;">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                ZIP (JPGs)
            </button>
            <button class="btn btn-primary" onclick="window.print()" style="padding: 7px 16px; font-size: 0.78rem; background-color: #059669;">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Forms
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
        document.addEventListener('DOMContentLoaded', async () => {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_started' }, '*');
            }

            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const title = document.getElementById('loadingTitle');
            const totalStudents = {{ count($students) }};

            function updateProgress(percent, countMsg) {
                if (fill) fill.style.width = Math.min(100, Math.max(0, percent)) + '%';
                if (text) text.innerText = countMsg;
            }

            if (totalStudents === 0) {
                updateProgress(100, 'No student records found.');
                setTimeout(() => {
                    if (overlay) {
                        overlay.style.display = 'none';
                        overlay.classList.add('hidden-overlay');
                    }
                }, 300);
                return;
            }

            // 1. Safe Image Preloading: Never block on broken / missing images
            const images = Array.from(document.querySelectorAll('.student-print-wrapper img'));
            const imagePromises = images.map(img => {
                if (img.complete && img.naturalHeight !== 0) {
                    return Promise.resolve();
                }
                return new Promise((resolve) => {
                    const timer = setTimeout(() => resolve(), 1200); // 1.2s max safety fallback per image
                    img.addEventListener('load', () => { clearTimeout(timer); resolve(); }, { once: true });
                    img.addEventListener('error', () => { clearTimeout(timer); resolve(); }, { once: true });
                });
            });

            // 2. Accurate progress ticker tracking students (1/25 ... 25/25)
            let loadedCount = 0;
            const stepTime = Math.max(25, Math.min(75, Math.floor(1200 / totalStudents)));
            const interval = setInterval(() => {
                if (loadedCount < totalStudents) {
                    loadedCount++;
                    const pct = Math.round((loadedCount / totalStudents) * 85);
                    updateProgress(pct, `Loading student forms (${loadedCount}/${totalStudents})...`);
                }
            }, stepTime);

            // Wait for all images or their fast timeouts
            try {
                await Promise.allSettled(imagePromises);
            } catch (err) {
                console.warn('Image preloader warning:', err);
            } finally {
                clearInterval(interval);
            }

            // 3. Auto-fit all dynamic form text sizes
            updateProgress(95, `Loading student forms (${totalStudents}/${totalStudents})...`);
            try {
                fitAllFormFontSizes();
            } catch (err) {
                console.warn('Font fitting error:', err);
            }

            // 4. Transition to "Preparing print preview..."
            updateProgress(100, 'Preparing print preview...');
            if (title) title.innerText = 'Preparing Print Preview';

            await new Promise(r => setTimeout(r, 350));

            // 5. Hide loading overlay BEFORE calling window.print()
            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.classList.add('hidden-overlay');
                }, 200);
            }

            // 6. Handle auto actions or trigger browser print preview
            const urlParams = new URLSearchParams(window.location.search);
            const autoAction = urlParams.get('auto') || urlParams.get('download') || urlParams.get('action');

            if (autoAction === 'pdf' || autoAction === 'batch_pdf') {
                setTimeout(() => generateBatchPdfDownload(), 150);
            } else if (autoAction === 'zip' || autoAction === 'zip_pdf') {
                setTimeout(() => generatePdfZip(), 150);
            } else if (autoAction === 'jpg' || autoAction === 'zip_jpg') {
                setTimeout(() => generatePngZip(), 150);
            } else if (autoAction === 'no_print' || autoAction === 'view') {
                // Stay in view mode
            } else {
                // Default: Automatically trigger print preview!
                setTimeout(() => {
                    window.print();
                }, 300);
            }
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

        async function fitAllFormFontSizes() {
            // Text naturally wraps to multiple lines with auto-height and full visibility
        }
        document.addEventListener('DOMContentLoaded', fitAllFormFontSizes);
        window.addEventListener('load', fitAllFormFontSizes);

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

            // Notify parent iframe of total count
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_start', total: totalStudents, format: 'pdf' }, '*');
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
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'zip_log',
                            current: i + 1,
                            total: totalStudents,
                            percent: pct,
                            message: `[${i + 1}/${totalStudents}] Rendering PDF: ${studentName.replace(/_/g, ' ')} (Page ${pageNum}/2)`
                        }, '*');
                    }
                    
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
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_log', percent: 98, message: '📦 Compiling PDF files into ZIP archive...' }, '*');
            }
            
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
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'zip_done', message: '⚡ PDF ZIP Archive generated & download started!' }, '*');
                }
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

        async function generateDocxZip() {
            const overlay = document.getElementById('loadingOverlay');
            const fill = document.getElementById('loadingProgressBar');
            const text = document.getElementById('loadingProgressCount');
            const title = overlay ? overlay.querySelector('.loading-title') : null;

            if (overlay) overlay.classList.remove('hidden-overlay');
            if (title) title.innerText = 'Generating DOCX ZIP Archive...';
            if (fill) fill.style.width = '0%';

            const JSZipLib = (typeof JSZip !== 'undefined') ? JSZip : null;
            if (!JSZipLib) {
                alert('JSZip library not loaded. Cannot generate DOCX ZIP.');
                if (overlay) overlay.classList.add('hidden-overlay');
                return;
            }

            const outerZip = new JSZipLib();
            const wrappers = document.querySelectorAll('.student-print-wrapper');
            const totalStudents = wrappers.length;

            if (totalStudents === 0) {
                alert('No student records found to export.');
                if (overlay) overlay.classList.add('hidden-overlay');
                return;
            }

            // Notify parent iframe of total count
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_start', total: totalStudents, format: 'docx' }, '*');
            }

            let processedStudents = 0;

            for (let i = 0; i < totalStudents; i++) {
                const wrapper = wrappers[i];
                const studentName = wrapper.getAttribute('data-student-name') || 'STUDENT_' + i;
                const grade = wrapper.getAttribute('data-grade') || 'Grade_1';
                const pctStart = Math.round((i / totalStudents) * 95);
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'zip_log',
                        current: i + 1,
                        total: totalStudents,
                        percent: pctStart,
                        message: `[${i + 1}/${totalStudents}] Generating DOCX: ${studentName.replace(/_/g, ' ')}`
                    }, '*');
                }
                const mode = wrapper.getAttribute('data-mode') || 'F2F';
                const pages = wrapper.querySelectorAll('.paper-container');
                const basePath = `${grade}/${mode}`;

                const pct = Math.round((i / totalStudents) * 95);
                if (fill) fill.style.width = pct + '%';
                if (text) text.innerText = `Generating DOCX ${i + 1} of ${totalStudents}: ${studentName.replace(/_/g, ' ')}`;

                try {
                    // Build a real DOCX (OpenXML ZIP) for this student
                    const docZip = new JSZipLib();
                    const xmlHeader = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?' + '>';

                    docZip.file('[Content_Types].xml', `${xmlHeader}
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="png" ContentType="image/png"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>`);

                    docZip.file('_rels/.rels', `${xmlHeader}
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>`);

                    let docRels = `${xmlHeader}\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">`;
                    let docBody = '';

                    for (let pageIdx = 0; pageIdx < pages.length; pageIdx++) {
                        const pageEl = pages[pageIdx];
                        const canvas = await html2canvas(pageEl, {
                            scale: 2.5,
                            useCORS: true,
                            logging: false,
                            allowTaint: true,
                            backgroundColor: '#ffffff',
                            imageTimeout: 0,
                            removeContainer: true
                        });
                        const imgDataUrl = canvas.toDataURL('image/png');
                        const base64Data = imgDataUrl.replace(/^data:image\/png;base64,/, '');
                        const imgId = `rId${pageIdx + 2}`;
                        const imgFileName = `image${pageIdx + 1}.png`;

                        docZip.file(`word/media/${imgFileName}`, base64Data, { base64: true });
                        docRels += `\n    <Relationship Id="${imgId}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/${imgFileName}"/>`;

                        docBody += `
<w:p>
    <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
    </w:pPr>
    <w:r>
        <w:drawing>
            <wp:inline distT="0" distB="0" distL="0" distR="0">
                <wp:extent cx="7560000" cy="10440000"/>
                <wp:docPr id="${pageIdx + 1}" name="Page ${pageIdx + 1}"/>
                <a:graphic>
                    <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                        <pic:pic>
                            <pic:nvPicPr>
                                <pic:cNvPr id="0" name="Picture"/>
                                <pic:cNvPicPr/>
                            </pic:nvPicPr>
                            <pic:blipFill>
                                <a:blip r:embed="${imgId}"/>
                                <a:stretch><a:fillRect/></a:stretch>
                            </pic:blipFill>
                            <pic:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>
                                    <a:ext cx="7560000" cy="10440000"/>
                                </a:xfrm>
                                <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                            </pic:spPr>
                        </pic:pic>
                    </a:graphicData>
                </a:graphic>
            </wp:inline>
        </w:drawing>
    </w:r>
</w:p>`;
                        if (pageIdx < pages.length - 1) {
                            docBody += '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
                        }
                    }

                    docRels += '\n</Relationships>';
                    docZip.file('word/_rels/document.xml.rels', docRels);

                    const docXml = `${xmlHeader}
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
    <w:body>
        ${docBody}
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838"/>
            <w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>`;
                    docZip.file('word/document.xml', docXml);

                    const docBlob = await docZip.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
                    outerZip.file(`${basePath}/Enrollment Application Form - ${studentName.replace(/_/g, ' ')}.docx`, docBlob);

                } catch (err) {
                    console.error(`Failed to generate DOCX for ${studentName}:`, err);
                }

                processedStudents++;
            }

            if (fill) fill.style.width = '98%';
            if (text) text.innerText = 'Compiling DOCX files into ZIP archive...';
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'zip_log', percent: 98, message: '📦 Compiling DOCX files into ZIP archive...' }, '*');
            }

            try {
                const content = await outerZip.generateAsync({ type: 'blob', compression: 'STORE' });
                const url = URL.createObjectURL(content);
                const link = document.createElement('a');
                const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                const gradeClean = "{{ str_replace(' ', '_', $gradeTitle ?? 'Batch') }}";
                link.href = url;
                link.download = `Enrollment_Forms_SY_2026-2027_${gradeClean}_${dateStr}_DOCX.zip`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(() => URL.revokeObjectURL(url), 100);
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'zip_done', message: '⚡ DOCX ZIP Archive generated & download started!' }, '*');
                }
            } catch (zipErr) {
                console.error('Error generating DOCX ZIP:', zipErr);
                alert('Failed to generate DOCX ZIP file.');
            }

            if (fill) fill.style.width = '100%';
            if (title) title.innerText = 'Loading Enrollment Forms';
            if (overlay) overlay.classList.add('hidden-overlay');
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
