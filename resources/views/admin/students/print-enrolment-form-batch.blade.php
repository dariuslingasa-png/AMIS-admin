<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ENROLMENT APPLICATION FORMS - {{ mb_strtoupper($gradeTitle ?? 'BATCH') }}</title>
    
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

            .action-bar {
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

    <!-- Top Action Bar for Screen Viewing -->
    <div class="action-bar">
        <h2>📄 Enrolment Application Forms ({{ $gradeTitle ?? 'All Grades' }}) - {{ count($students) }} Students</h2>
        <div class="btn-group">
            <button class="btn btn-secondary" onclick="window.close()">Close Window</button>
            <a href="{{ route('admin.students.download-documents-zip', request()->query()) }}" class="btn btn-zip">
                📦 Download ZIP Archive
            </a>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print All / Save as PDF</button>
        </div>
    </div>

    @forelse($students as $student)
        @include('admin.students.partials.print.enrolment-form-body', [
            'student'    => $student,
            'applicant'  => $student->applicant,
            'siblings'   => $siblingsMap[$student->id] ?? [],
            'pageNumber' => $loop->iteration,
            'totalPages' => count($students),
        ])
    @empty
        <div class="paper-container text-center" style="padding: 50px; font-family: 'Inter', sans-serif;">
            <h3>No student records found for printing.</h3>
        </div>
    @endforelse

    <script>
        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
