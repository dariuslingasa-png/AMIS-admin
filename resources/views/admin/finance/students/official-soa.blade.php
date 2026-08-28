<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Statement of Account - {{ $soaData['student_name'] }} (SY {{ $soaData['school_year'] }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.35;
        }

        .no-print-bar {
            width: 210mm;
            max-width: 100%;
            margin: 14px auto 0;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
        }
        .btn-print {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: background 0.15s;
        }
        .btn-print:hover {
            background: #334155;
        }
        .btn-back {
            color: #475569;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 0;
        }
        .btn-back:hover {
            color: #0f172a;
            text-decoration: underline;
        }

        /* SOA VIEWPORT (Interactive mobile viewer: pan & pinch-zoom) */
        .soa-viewport {
            width: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y pinch-zoom;
            padding: 12px 16px 36px;
            box-sizing: border-box;
        }

        /* FIXED A4 SOA PAGE CONTAINER (Never collapses or wraps on mobile) */
        .soa-page {
            width: 210mm;
            min-width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 16px 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            box-sizing: border-box;
        }

        @media screen and (max-width: 768px) {
            .soa-viewport {
                overflow-x: auto;
                overflow-y: auto;
                padding: 10px 8px 24px;
            }
            .soa-page {
                width: 210mm;
                min-width: 210mm;
                max-width: none;
                margin: 0 auto;
            }
        }

        /* HEADER */
        .school-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #065f46;
            padding-bottom: 6px;
            margin-bottom: 8px;
            width: 100%;
        }
        .school-header-cell {
            display: flex;
            align-items: center;
        }
        .header-english {
            width: 42%;
            font-size: 14.5px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.2px;
            text-align: left;
            justify-content: flex-start;
        }
        .header-logo {
            width: 16%;
            text-align: center;
            justify-content: center;
        }
        .header-logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .header-arabic {
            width: 42%;
            justify-content: flex-end;
            text-align: right;
            margin-left: auto;
        }
        .header-arabic img {
            max-height: 42px;
            max-width: 100%;
            height: auto;
            object-fit: contain;
            display: block;
            margin-left: auto;
            margin-right: 0;
        }
        .header-arabic span {
            font-family: 'Amiri', 'Traditional Arabic', serif;
            font-size: 25px;
            font-weight: 700;
            color: #065f46;
            line-height: 1.15;
            display: block;
            width: 100%;
            text-align: right;
            margin-left: auto;
            margin-right: 0;
            direction: rtl;
        }

        /* TITLE BANNER */
        .title-banner {
            background: #a9beba;
            color: #0f172a;
            text-align: center;
            font-size: 12.5px;
            font-weight: 900;
            padding: 4.5px 0;
            border: 1px solid #475569;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        /* UPPER SECTION GRID (3-Column Top-Aligned Layout) */
        .upper-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            table-layout: fixed;
        }
        .upper-left {
            display: table-cell;
            width: 28%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 10px;
            line-height: 1.35;
        }
        .upper-mid {
            display: table-cell;
            width: 38%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 10.5px;
        }
        .upper-right {
            display: table-cell;
            width: 34%;
            vertical-align: top;
        }

        .ayah-quote {
            margin-top: 8px;
            color: #1d4ed8;
            font-style: italic;
            font-size: 9.5px;
            line-height: 1.35;
        }
        .ayah-source {
            font-weight: bold;
            display: block;
            margin-top: 3px;
        }

        .student-header-block {
            margin-bottom: 5px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #cbd5e1;
            width: 100%;
            height: auto;
            min-height: auto;
            box-sizing: border-box;
            display: block;
        }
        .student-header-label {
            font-size: 9.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
            margin-bottom: 2px;
        }
        .student-header-name {
            font-size: 12.5px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.18;
            margin-top: 1px;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: normal;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            display: block;
        }

        .student-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-info-table td {
            padding: 2px 0;
            font-size: 10.5px;
        }
        .info-lbl {
            color: #64748b;
            width: 112px;
            font-weight: 500;
            white-space: nowrap;
        }
        .info-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* FEE SUMMARY TABLE */
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #475569;
        }
        .fee-table th {
            background: #ffffff;
            border: 1px solid #475569;
            padding: 3.5px 4px;
            font-weight: bold;
            text-align: center;
            font-size: 9.5px;
        }
        .fee-table td {
            border: 1px solid #475569;
            padding: 3px 5px;
        }
        .fee-table .text-right {
            text-align: right;
        }
        .fee-table .text-center {
            text-align: center;
        }
        .fee-table .font-bold {
            font-weight: bold;
        }

        /* MAIN SCHEDULE TABLE */
        .main-ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            border: 1px solid #475569;
            font-size: 10.5px;
        }
        .main-ledger-table th {
            background: #a9beba;
            color: #111827;
            border: 1px solid #475569;
            padding: 4.5px 6px;
            font-weight: bold;
            text-align: left;
            font-size: 10px;
        }
        .main-ledger-table th.th-center { text-align: center; }
        .main-ledger-table th.th-right { text-align: right; }
        .main-ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 6px;
            font-size: 10.5px;
        }
        .main-ledger-table td.cell-right { text-align: right; }
        .main-ledger-table td.cell-center { text-align: center; }
        .row-section-header {
            background: #f1f5f9;
            font-weight: bold;
            color: #1e293b;
        }
        .highlight-yellow {
            background-color: #fef08a !important;
            font-weight: bold;
            cursor: pointer;
        }
        .highlight-blue {
            background-color: #bae6fd !important;
            font-weight: 900;
        }

        /* SUMMARY & FOOTER */
        .summary-subtable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10.5px;
        }
        .summary-subtable td {
            padding: 3px 6px;
        }

        .discrepancy-note {
            margin-top: 10px;
            font-size: 10px;
            color: #111827;
        }
        .discrepancy-red {
            color: #dc2626;
            font-weight: bold;
            text-decoration: underline;
        }

        .shukran-bar {
            background: #fef08a;
            color: #111827;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 4px 0;
            margin: 10px 0 8px;
            border: 1px solid #eab308;
        }

        .legal-footer {
            display: table;
            width: 100%;
            font-size: 9px;
            color: #374151;
            margin-top: 4px;
        }
        .legal-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            line-height: 1.35;
        }
        .legal-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
            line-height: 1.35;
        }

        /* PRINT MEDIA QUERIES (A4 PORTRAIT EXACT SCALING) */
        @media print {
            html,
            body {
                width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .soa-viewport {
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .soa-page {
                width: 190mm !important;
                min-width: 190mm !important;
                max-width: 190mm !important;
                min-height: auto !important;
                margin: 0 auto !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
                transform: none !important;
                zoom: 1 !important;
            }

            table,
            .upper-grid,
            .main-ledger-table,
            .fee-table,
            .student-header-block,
            .discrepancy-note,
            .shukran-bar,
            .legal-footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .no-print-bar,
            .edit-modal-overlay {
                display: none !important;
            }
        }

        [x-cloak] {
            display: none !important;
        }

        .btn-edit {
            background: #4338ca;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: background 0.15s;
        }
        .btn-edit:hover {
            background: #3730a3;
        }
        .edit-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .edit-modal-box {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: 2px solid #065f46;
            border-color: #065f46;
        }
    </style>
</head>
<body x-data="{ showEditModal: false }">

    <div class="no-print-bar">
        <a href="{{ isset($soaData['family_id']) ? route('admin.finance.families.show', $soaData['family_id']) : 'javascript:history.back()' }}" class="btn-back">
            ← Back to Family Account
        </a>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" @click="showEditModal = true" class="btn-edit">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                ✏️ Edit SOA Values / Schedule
            </button>
            <button type="button" onclick="window.print()" class="btn-print">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save to PDF
            </button>
        </div>
    </div>

    @if (session('success'))
        <div style="width: 210mm; max-width: 100%; margin: 10px auto 0; padding: 10px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-weight: bold; font-size: 12px; box-sizing: border-box;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- EDIT SOA MODAL --}}
    <div x-show="showEditModal" x-cloak class="edit-modal-overlay" @click.self="showEditModal = false">
        <div class="edit-modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">Edit Statement of Account Values</h3>
                    <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">{{ $soaData['student_name'] }} ({{ $soaData['grade_level'] }})</p>
                </div>
                <button type="button" @click="showEditModal = false" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
            </div>

            <form action="{{ route('admin.finance.students.update-soa', ['studentIdentifier' => $soaData['student_number'] ?? $soaData['student_id']]) }}" method="POST">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Tuition Fee (₱)</label>
                        <input type="number" step="0.01" name="tuition_fee" value="{{ $soaData['tuition_fee'] }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Miscellaneous Fee (₱)</label>
                        <input type="number" step="0.01" name="misc_fee" value="{{ $soaData['misc_fee'] }}" required class="form-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Books &amp; Programs Fee (₱)</label>
                        <input type="number" step="0.01" name="books_fee" value="{{ $soaData['books_fee'] }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Sibling Discount (%)</label>
                        <input type="number" step="0.01" name="discount_percentage" value="{{ str_replace('%', '', $soaData['discount_privilege']) }}" class="form-input">
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Enrollment Downpayment Paid (₱)</label>
                    <input type="number" step="0.01" name="enrollment_paid" value="{{ $soaData['enrollment_paid'] }}" required class="form-input">
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Reason for SOA Adjustment <span style="color: #e11d48;">*</span></label>
                    <textarea name="reason" required rows="2" placeholder="e.g. Sibling discount correction / adjusted tuition schedule per approved concession." class="form-input" style="font-family: inherit; font-weight: normal;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" @click="showEditModal = false" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Cancel</button>
                    <button type="submit" style="background: #065f46; color: #ffffff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Save &amp; Recalculate SOA</button>
                </div>
            </form>
        </div>
    </div>

    <div class="soa-viewport">
        <div class="soa-page">
        {{-- TOP SCHOOL HEADER --}}
        <div class="school-header">
            <div class="school-header-cell header-english">
                AL MUNAWWARA ISLAMIC SCHOOL
            </div>
            <div class="school-header-cell header-logo">
                <img src="/images/AMIS_Logo.png" alt="AMIS Logo" onerror="this.src='/images/AMIS_Logo_receipt.jpg'">
            </div>
            <div class="school-header-cell header-arabic">
                <span dir="rtl">المدرسة المنورة الإسلامية</span>
            </div>
        </div>

        {{-- STATEMENT OF ACCOUNT BANNER --}}
        <div class="title-banner">
            STATEMENT OF ACCOUNT SY {{ $soaData['school_year'] }}
        </div>

        {{-- UPPER GRID --}}
        <div class="upper-grid">
            {{-- COLUMN 1: SCHOOL INFO & QURANIC QUOTE --}}
            <div class="upper-left">
                <div><strong>Address:</strong></div>
                <div>Bugac Ma-a Road, Davao City</div>
                <div style="margin-top: 6px;"><strong>Email Add:</strong></div>
                <div>almunawwaraislamicschool@gmail.com</div>

                <div class="ayah-quote">
                    <strong>Sahih International</strong><br>
                    "Whoever does righteousness, whether male or female, while he is a believer - We will surely cause him to live a good life, and We will surely give them their reward [in the Hereafter] according to the best of what they do."
                    <span class="ayah-source">Qur'an 16:97</span>
                </div>
            </div>

            {{-- COLUMN 2: STUDENT DETAILS --}}
            <div class="upper-mid">
                @php
                    $sName = strtoupper(trim($soaData['student_name'] ?? ''));
                    $sLen = mb_strlen($sName);
                    $nameSize = $sLen > 48 ? '10.5px' : ($sLen > 36 ? '11.5px' : ($sLen > 24 ? '12px' : '12.5px'));
                    $nameLineHeight = $sLen > 36 ? '1.15' : '1.18';
                @endphp
                
                <div class="student-header-block">
                    <div class="student-header-label">NAME OF STUDENT:</div>
                    <div class="student-header-name" style="font-size: {{ $nameSize }}; line-height: {{ $nameLineHeight }};">
                        {{ $sName }}
                    </div>
                </div>

                <table class="student-info-table">
                    <tr>
                        <td class="info-lbl">Address:</td>
                        <td class="info-val">{{ strtoupper($soaData['address']) }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Email:</td>
                        <td class="info-val" style="font-size:10px;">{{ $soaData['email'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">LRN:</td>
                        <td class="info-val">{{ $soaData['lrn'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Category:</td>
                        <td class="info-val">{{ $soaData['category'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Grade Level:</td>
                        <td class="info-val">{{ $soaData['grade_level'] }}</td>
                    </tr>
                    @php
                        $discVal = (float) str_replace('%', '', (string) ($soaData['discount_privilege'] ?? '0'));
                        $hasDiscount = $discVal > 0.01 && ((float) ($soaData['discount_amount'] ?? 0)) > 0.01;
                    @endphp
                    @if($hasDiscount)
                    <tr>
                        <td class="info-lbl">Discount Privilege:</td>
                        <td class="info-val">{{ $soaData['discount_privilege'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-lbl">Discount Status:</td>
                        <td class="info-val">{{ $soaData['discount_status'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- COLUMN 3: FEE BREAKDOWN TABLE --}}
            <div class="upper-right">
                <table class="fee-table">
                    <thead>
                        <tr>
                            <th rowspan="2">DESCRIPTION</th>
                            <th rowspan="2">AMOUNT</th>
                            <th colspan="2">DISCOUNT</th>
                            <th rowspan="2">NET</th>
                        </tr>
                        <tr>
                            <th>%</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tuition Fees</td>
                            <td class="text-right">{{ number_format($soaData['tuition_fee'], 2) }}</td>
                            <td class="text-center">{{ $hasDiscount ? $soaData['discount_privilege'] : '' }}</td>
                            <td class="text-center">{{ $hasDiscount ? number_format($soaData['discount_amount'], 2) : '' }}</td>
                            <td class="text-right">{{ number_format($soaData['tuition_fee'] - ($hasDiscount ? $soaData['discount_amount'] : 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Miscellaneous</td>
                            <td class="text-right">{{ number_format($soaData['misc_fee'], 2) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ number_format($soaData['misc_fee'], 2) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td>Total Fees</td>
                            <td class="text-right">{{ number_format($soaData['total_fees'], 2) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{ number_format($soaData['total_fees'], 2) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td>Final Fees</td>
                            <td></td>
                            <td></td>
                            <td class="text-center">{{ $hasDiscount ? '-' : '' }}</td>
                            <td class="text-right">{{ number_format($soaData['final_fees'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MAIN STATEMENT & PAYMENT LEDGER TABLE --}}
        <table class="main-ledger-table">
            <thead>
                <tr>
                    <th style="width: 26%;">Description</th>
                    <th style="width: 14%;">Month</th>
                    <th class="th-right" style="width: 12%;">Amount</th>
                    <th class="th-center" style="width: 12%;">Date</th>
                    <th class="th-right" style="width: 12%;">Amount Paid</th>
                    <th class="th-center" style="width: 10%;">Account</th>
                    <th class="th-right" style="width: 14%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $runningBalance = (float) $soaData['final_fees'];
                    $runningBalance -= (float) $soaData['enrollment_paid'];
                @endphp
                <tr>
                    <td>Paid Enrollment Fee</td>
                    <td></td>
                    <td class="cell-right"></td>
                    <td class="cell-center">{{ $soaData['enrollment_date'] }}</td>
                    <td class="cell-right highlight-yellow" title="Payment received and applied to this account">{{ number_format($soaData['enrollment_paid'], 2) }}</td>
                    <td class="cell-center">{{ $soaData['enrollment_account'] }}</td>
                    <td class="cell-right">{{ number_format($runningBalance, 2) }}</td>
                </tr>
                @php
                    $runningBalance += (float) $soaData['books_fee'];
                @endphp
                <tr>
                    <td>Books and programs</td>
                    <td></td>
                    <td class="cell-right">{{ number_format($soaData['books_fee'], 2) }}</td>
                    <td class="cell-center"></td>
                    <td class="cell-right"></td>
                    <td class="cell-center"></td>
                    <td class="cell-right">{{ number_format($runningBalance, 2) }}</td>
                </tr>
                @php
                    $runningBalance -= (float) $soaData['books_paid'];
                @endphp
                <tr>
                    <td>Paid Books</td>
                    <td></td>
                    <td class="cell-right"></td>
                    <td class="cell-center">{{ $soaData['books_date'] }}</td>
                    <td class="cell-right highlight-yellow" title="Payment received and applied to this account">{{ number_format($soaData['books_paid'], 2) }}</td>
                    <td class="cell-center">{{ $soaData['books_account'] }}</td>
                    <td class="cell-right">{{ number_format($runningBalance, 2) }}</td>
                </tr>

                {{-- REQUIRED PAYMENT MONTHLY SECTION --}}
                <tr class="row-section-header">
                    <td colspan="7">Required Payment Monthly</td>
                </tr>

                @php
                    $sched = collect($soaData['monthly_schedule']);
                    $seenTxKeys = [];
                @endphp

                <tr class="row-section-header" style="background:#ffffff; font-size:10px;">
                    <td colspan="7">Year: 2026</td>
                </tr>

                @foreach (['July', 'August', 'September', 'October', 'November', 'December'] as $monthName)
                    @php
                        $mRow = $sched->first(fn($m) => str_contains(strtoupper($m->month ?? ''), strtoupper($monthName)));
                        $mFee = (float) ($mRow->fee ?? ($mRow->original ?? $soaData['monthly_rate']));
                        $mPaid = (float) ($mRow->paid ?? ($mRow->verified ?? 0));
                        $isPaidMonth = $mPaid > 0.01;
                        if ($isPaidMonth) {
                            $runningBalance -= $mPaid;
                        }

                        $txDateDisplay = '';
                        $txAccountDisplay = '';
                        if ($isPaidMonth) {
                            $txDate = $mRow->payment_date ?? '15-Aug-26';
                            $txAccount = $mRow->or_number ?? $mRow->account_no ?? '10539';
                            $txKey = ($mRow->payment_id ?? null) ? 'tx_'.$mRow->payment_id : 'tx_10539';

                            if (!in_array($txKey, $seenTxKeys, true)) {
                                $txDateDisplay = $txDate;
                                $seenTxKeys[] = $txKey;
                            } else {
                                $txDateDisplay = ''; // Blank on subsequent auto-allocated months
                            }
                            $txAccountDisplay = $txAccount;
                        }
                    @endphp
                    <tr>
                        <td></td>
                        <td>{{ $monthName }}</td>
                        <td class="cell-right {{ ($monthName === 'July' && ! $isPaidMonth) ? 'highlight-yellow' : '' }}" @if($monthName === 'July' && ! $isPaidMonth) title="Required payment for this month" @endif>{{ number_format($mFee, 2) }}</td>
                        <td class="cell-center">{{ $txDateDisplay }}</td>
                        <td class="cell-right {{ $isPaidMonth ? 'highlight-yellow' : '' }}" @if($isPaidMonth) title="Payment received and applied to this account" @endif>
                            {{ $isPaidMonth ? number_format($mPaid, 2) : '-' }}
                        </td>
                        <td class="cell-center">{{ $isPaidMonth ? $txAccountDisplay : '' }}</td>
                        <td class="cell-right">{{ $isPaidMonth ? number_format($runningBalance, 2) : '' }}</td>
                    </tr>
                @endforeach

                <tr class="row-section-header" style="background:#ffffff; font-size:10px;">
                    <td colspan="7">Year: 2027</td>
                </tr>

                @foreach (['January', 'February', 'March'] as $monthName)
                    @php
                        $mRow = $sched->first(fn($m) => str_contains(strtoupper($m->month ?? ''), strtoupper($monthName)));
                        $mFee = (float) ($mRow->fee ?? ($mRow->original ?? $soaData['monthly_rate']));
                        $mPaid = (float) ($mRow->paid ?? ($mRow->verified ?? 0));
                        $isPaidMonth = $mPaid > 0.01;
                        if ($isPaidMonth) {
                            $runningBalance -= $mPaid;
                        }

                        $txDateDisplay = '';
                        $txAccountDisplay = '';
                        if ($isPaidMonth) {
                            $txDate = $mRow->payment_date ?? '15-Jan-27';
                            $txAccount = $mRow->or_number ?? $mRow->account_no ?? '10539';
                            $txKey = ($mRow->payment_id ?? null) ? 'tx_'.$mRow->payment_id : 'tx_2027_block';

                            if (!in_array($txKey, $seenTxKeys, true)) {
                                $txDateDisplay = $txDate;
                                $seenTxKeys[] = $txKey;
                            } else {
                                $txDateDisplay = '';
                            }
                            $txAccountDisplay = $txAccount;
                        }
                    @endphp
                    <tr>
                        <td></td>
                        <td>{{ $monthName }}</td>
                        <td class="cell-right">{{ number_format($mFee, 2) }}</td>
                        <td class="cell-center">{{ $txDateDisplay }}</td>
                        <td class="cell-right {{ $isPaidMonth ? 'highlight-yellow' : '' }}" @if($isPaidMonth) title="Payment received and applied to this account" @endif>
                            {{ $isPaidMonth ? number_format($mPaid, 2) : '-' }}
                        </td>
                        <td class="cell-center">{{ $isPaidMonth ? $txAccountDisplay : '' }}</td>
                        <td class="cell-right">{{ $isPaidMonth ? number_format($runningBalance, 2) : '' }}</td>
                    </tr>
                @endforeach

                {{-- TO BE PAID / PAID LABEL ROW --}}
                <tr style="border-top: 2px solid #475569;">
                    <td colspan="4" style="border:none;"></td>
                    <td class="cell-center" style="font-weight:bold; font-size:10px; border: 1px solid #475569;">TO BE PAID</td>
                    <td class="cell-center highlight-yellow" style="font-weight:bold; font-size:10px; border: 1px solid #475569;">PAID</td>
                    <td style="border:none;"></td>
                </tr>
                <tr>
                    <td colspan="6" style="font-weight:bold; border-right:none;">Total Amount to pay</td>
                    <td class="cell-right highlight-blue" style="font-size:11px;">{{ number_format($runningBalance, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-weight:bold; border-right:none;">Due Monthly Payment (9 Months)</td>
                    <td class="cell-right highlight-yellow" style="border-left:none;" title="Monthly amount due for this student">{{ number_format($soaData['monthly_rate'], 2) }}</td>
                    <td colspan="4" style="border-left:none;"></td>
                </tr>
            </tbody>
        </table>

        {{-- DISCREPANCY NOTE --}}
        <div class="discrepancy-note">
            Note: Any discrepancies please inform the office. &nbsp;
            <span class="discrepancy-red">ANY DISCREPANCY PLEASE INFORM, WE WILL CORRECT</span>
        </div>

        {{-- SHUKRAN BAR --}}
        <div class="shukran-bar">
            Shukran. JazakAllahu khayran
        </div>

        {{-- LEGAL FOOTER --}}
        <div class="legal-footer">
            <div class="legal-left">
                Mayor's Permit No. B-86418-8<br>
                SEC Registration No. CN200826457
            </div>
            <div class="legal-right">
                DepED Recognition No. R-XI-019, s. 2016<br>
                DepED Recognition No. R-XI-005, s. 2016
            </div>
        </div>
    </div>
</div>

</body>
</html>
