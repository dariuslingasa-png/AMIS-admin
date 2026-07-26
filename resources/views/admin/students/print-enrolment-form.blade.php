<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $app = $applicant;
        
        // Auto PDF Filename format for document title: LASTNAME FIRSTNAME GRADE LEVEL
        $lName = mb_strtoupper(trim($app->last_name ?? $student->last_name ?? ''));
        $fName = mb_strtoupper(trim($app->first_name ?? $student->first_name ?? ''));
        $gLevel = mb_strtoupper(trim($student->grade_level ?? $app->grade_level ?? ''));
        
        $autoFileName = implode(' ', array_filter([$lName, $fName, $gLevel ? 'GRADE ' . $gLevel : '']));
        if (empty(trim($autoFileName)) || $autoFileName === 'GRADE') {
            $autoFileName = mb_strtoupper(trim($student->full_name)) . ($gLevel ? ' GRADE ' . $gLevel : '');
        }
    @endphp
    <title>{{ $autoFileName }}</title>
    
    <!-- Premium Google Fonts: Merriweather for Headers & Inter for Fillable Data -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    
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
            max-width: 840px;
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
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
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

        /* NO REFUND OF ENROLLMENT FEE Box styling */
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

        /* Middle Header Row: Form Title, Checkboxes, 2x2 Photo Box */
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

        /* Student Info Header & LRN */
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

        /* OLD / NEW Checkboxes Vertically Stacked */
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

        /* 2x2 Photo Square Box */
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

        /* Section Header Divider */
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

        /* Fillable Text Lines & Input Fields with Auto-Truncate Safety */
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
            grid-template-columns: 2.6fr 2.6fr 2.4fr 0.6fr 1.8fr;
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

        /* Bottom Section: Applicant Lives With */
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

        /* PAGE 2 STYLES */
        .p2-question-row {
            margin-top: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            line-height: 1.45;
            color: #0f172a;
        }

        .p2-inline-line {
            display: inline-block;
            border-bottom: 1.5px solid #0f172a;
            width: 70px;
            height: 18px;
            vertical-align: bottom;
            text-align: center;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }

        .p2-explain-block {
            margin-top: 8px;
            margin-bottom: 14px;
        }

        .p2-explain-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            display: inline-block;
            margin-bottom: 4px;
        }

        .p2-full-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            width: 100%;
            font-family: 'Inter', sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 4px;
            margin-bottom: 6px;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            text-transform: uppercase;
        }

        .grid-physician-row {
            display: grid;
            grid-template-columns: 4fr 3fr;
            gap: 20px;
            margin-top: 16px;
            margin-bottom: 20px;
        }

        .p2-emergency-grid {
            display: grid;
            grid-template-columns: 4.5fr 3.5fr 3fr;
            gap: 15px;
            margin-top: 8px;
            margin-bottom: 20px;
        }

        .p2-policy-text {
            font-family: 'Merriweather', serif;
            font-size: 0.92rem;
            line-height: 1.5;
            margin-top: 12px;
            text-align: justify;
            color: #1e293b;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 30px;
            margin-top: 35px;
            margin-bottom: 8px;
        }

        .signature-disclaimer {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-style: italic;
            color: #64748b;
            margin-bottom: 25px;
        }

        .office-perforated-line {
            border: none;
            border-top: 1.5px dashed #64748b;
            margin: 18px 0 14px 0;
        }

        .grid-office-row {
            display: grid;
            grid-template-columns: 3.5fr 2.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .date-slash-inputs {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
        }

        .date-slash-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            width: 45px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
        }

        /* ATTACHMENTS CHECKLIST: Reduced font size by -0.5 specifically for checklist items */
        .attachments-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-style: italic;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .attachments-list {
            font-family: 'Inter', sans-serif;
            font-size: 0.72rem;
            line-height: 1.4;
            margin-left: 20px;
            color: #334155;
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

        /* Print Media Styles for Perfect PDF Save */
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

            .action-bar, .action-bar-container, .toolbar, .page-number-badge {
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
</head>
<body>

    @if(!($isPdf ?? false))
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
                <!-- Header Logo & Title Skeleton -->
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; margin-bottom: 12px;" class="skeleton-shimmer"></div>
                    <div style="width: 320px; height: 20px; border-radius: 6px; background: #e2e8f0; margin-bottom: 8px;" class="skeleton-shimmer"></div>
                    <div style="width: 220px; height: 14px; border-radius: 6px; background: #f1f5f9;" class="skeleton-shimmer"></div>
                </div>

                <!-- Info Grid Skeleton -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 28px;">
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- Table Rows Skeleton -->
                <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between;">
                        <div style="width: 120px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                        <div style="width: 80px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 90%;" class="skeleton-shimmer"></div>
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 75%;" class="skeleton-shimmer"></div>
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 85%;" class="skeleton-shimmer"></div>
                    </div>
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

    <!-- Top Action Bar & Student Form Switcher Navigation Bar -->
    <div class="action-bar-container" style="max-width: 1000px; margin: 0 auto 20px auto; background: #ffffff; padding: 14px 20px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03); font-family: 'Inter', system-ui, -apple-system, sans-serif; position: sticky; top: 12px; z-index: 1000; border: 1px solid #e2e8f0;">
        <!-- Row 1: Document & Student Profile Info + Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; shrink-0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </div>
                <div>
                    <h2 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                        Enrolment Application Form
                    </h2>
                    <p style="font-size: 0.75rem; font-weight: 600; color: #64748b; margin: 2px 0 0 0;">
                        Student: <strong style="color: #0f172a;">{{ $student->full_name }}</strong> • AMIS ID: <strong style="color: #059669;">#{{ $student->student_number }}</strong>
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button onclick="window.close()" style="font-family: inherit; font-size: 0.78rem; font-weight: 700; padding: 8px 14px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">Close</button>
                <button onclick="triggerPrintPDF()" style="font-family: inherit; font-size: 0.78rem; font-weight: 700; padding: 8px 18px; border-radius: 10px; border: none; background: #059669; color: white; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2); transition: all 0.15s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    <span>Print / Save as PDF</span>
                </button>
            </div>
        </div>

        <!-- Row 2: Sleek Segmented Tab Switcher Bar (SAME TAB NAVIGATION - target="_self") -->
        <div style="display: flex; align-items: center; justify-content: flex-start; gap: 6px; overflow-x: auto; padding-top: 10px;">
            <!-- ID FORM -->
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_id' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
                <span>ID Form</span>
            </a>

            <!-- MICROSOFT ACCOUNT FORM -->
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_credentials' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                <span>Microsoft Account Form</span>
            </a>

            <!-- ENROLLMENT FORM (ACTIVE) -->
            <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; text-decoration: none; border: 1px solid #059669; background: #ecfdf5; color: #047857; shadow: 0 1px 2px rgba(5,150,105,0.1); white-space: nowrap;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                <span>Enrollment Form</span>
            </a>


            <!-- GRADE FORM -->
            <a href="{{ route('admin.students.show', $student) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <span>Grade Form</span>
            </a>

            <!-- DOCUMENTS FORM -->
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_documents' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; transition: all 0.15s; white-space: nowrap;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='#f8fafc';this.style.color='#475569'">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                <span>Documents Form</span>
            </a>
        </div>
    </div>
    @endif @include('admin.students.partials.print.enrolment-form-body', [
        'student'    => $student,
        'applicant'  => $applicant,
        'siblings'   => $siblings,
        'pageNumber' => 1,
        'totalPages' => 1,
    ])

    <script>
        function triggerPrintPDF() {
            window.print();
        }

        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
