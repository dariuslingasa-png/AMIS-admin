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
        .btn-save {
            background: #065f46;
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
        .btn-save:hover {
            background: #047857;
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

        /* SOA VIEWPORT */
        .soa-viewport {
            width: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y pinch-zoom;
            padding: 12px 16px 36px;
            box-sizing: border-box;
        }

        /* FIXED A4 SOA PAGE CONTAINER */
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
            position: relative;
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
            padding: 4px;
            border-top: 1px solid #475569;
            border-bottom: 1px solid #475569;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
        }

        /* UPPER THREE-COLUMN LAYOUT */
        .upper-grid {
            display: grid;
            grid-template-columns: 2.1fr 2.9fr 2.6fr;
            gap: 8px;
            margin-bottom: 8px;
            align-items: start;
        }

        /* COLUMN 1: LEFT CONTACT & AYAH */
        .upper-left {
            font-size: 10px;
            line-height: 1.25;
            color: #0f172a;
        }
        .upper-left strong {
            font-size: 10.5px;
            color: #0f172a;
        }
        .ayah-quote {
            margin-top: 8px;
            font-style: italic;
            font-size: 9.5px;
            color: #1e3a8a;
            line-height: 1.25;
            border-left: 2px solid #3b82f6;
            padding-left: 5px;
        }
        .ayah-source {
            font-weight: bold;
            font-style: normal;
            font-size: 9px;
            color: #1e3a8a;
            display: block;
            margin-top: 3px;
        }

        /* COLUMN 2: MIDDLE STUDENT DETAILS */
        .upper-mid {
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            padding: 0 8px;
        }
        .student-header-block {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .student-header-label {
            font-size: 9.5px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }
        .student-header-name {
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.1px;
            margin-top: 1px;
            word-break: break-word;
        }
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .student-info-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .student-info-table .info-lbl {
            width: 85px;
            color: #64748b;
            font-weight: bold;
        }
        .student-info-table .info-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* COLUMN 3: RIGHT FEE MATRIX */
        .upper-right {
            width: 100%;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            border: 1px solid #475569;
        }
        .fee-table th {
            background: #ffffff;
            border: 1px solid #475569;
            padding: 2.5px 3px;
            font-weight: 900;
            text-align: center;
            font-size: 9px;
            color: #0f172a;
        }
        .fee-table td {
            border: 1px solid #475569;
            padding: 2.5px 4px;
            color: #0f172a;
        }

        /* MAIN TABLE */
        .main-ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #475569;
            margin-bottom: 6px;
        }
        .main-ledger-table th {
            background: #a9beba;
            color: #0f172a;
            border: 1px solid #475569;
            padding: 3.5px 4px;
            font-weight: 900;
            font-size: 9.5px;
            text-align: left;
        }
        .main-ledger-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            color: #0f172a;
        }
        .main-ledger-table .row-section-header {
            background: #e2e8f0;
            font-weight: 900;
            font-size: 10px;
            border: 1px solid #475569;
        }

        /* INLINE SPREADSHEET INPUTS */
        .cell-input {
            width: 100%;
            border: 1px solid transparent;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
            text-align: inherit;
            padding: 1px 3px;
            margin: 0;
            box-sizing: border-box;
            border-radius: 4px;
            transition: all 0.12s ease-in-out;
        }
        .cell-input:hover {
            background-color: #f0fdf4;
            border-color: #86efac;
            cursor: pointer;
        }
        .cell-input:focus {
            background-color: #ffffff;
            border-color: #059669;
            outline: 2px solid #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            cursor: text;
        }

        .th-center, .cell-center { text-align: center; }
        .th-right, .cell-right { text-align: right; }
        .highlight-yellow { background-color: #fef08a !important; font-weight: bold; }
        .highlight-blue { background-color: #bae6fd !important; font-weight: bold; }

        /* FOOTERS */
        .discrepancy-note {
            font-size: 9px;
            font-style: italic;
            color: #475569;
            margin-top: 4px;
            line-height: 1.25;
        }
        .discrepancy-red {
            color: #dc2626;
            font-weight: 900;
            font-style: normal;
        }
        .shukran-bar {
            background: #fef08a;
            border: 1px solid #ca8a04;
            color: #0f172a;
            text-align: center;
            font-size: 11px;
            font-weight: 900;
            padding: 4px 0;
            margin: 6px 0 8px;
            letter-spacing: 0.3px;
        }
        .legal-footer {
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            color: #475569;
            line-height: 1.2;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
        }

        [x-cloak] {
            display: none !important;
        }

        /* MODAL OVERLAY */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 520px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print-bar,
            .modal-overlay,
            .edit-badge {
                display: none !important;
            }
            .soa-viewport {
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            .soa-page {
                width: 190mm !important;
                min-width: 190mm !important;
                max-width: 190mm !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
            }
            .cell-input {
                border: none !important;
                background: transparent !important;
                outline: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                -moz-appearance: textfield;
            }
            .cell-input::-webkit-outer-spin-button,
            .cell-input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
        }
    </style>
</head>
<body x-data="soaStudio({{ Js::from($soaData) }})">

    {{-- TOP ACTION BAR --}}
    <div class="no-print-bar">
        <a href="{{ isset($soaData['family_id']) ? route('admin.finance.families.show', $soaData['family_id']) : 'javascript:history.back()' }}" class="btn-back">
            ← Back to Family Account
        </a>
        <div style="display: flex; gap: 8px; align-items: center;">
            <div class="edit-badge" style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; color: #065f46;">
                <span style="height: 6px; width: 6px; border-radius: 9999px; background: #10b981;"></span>
                Click any cell to edit &amp; recalculate
            </div>
            <button type="button" @click="openSaveModal()" class="btn-save">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                💾 Save Changes
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

    {{-- SAVE CONFIRMATION MODAL --}}
    <div x-show="showSave" x-cloak class="modal-overlay" @click.self="showSave = false">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">Save Statement of Account Changes</h3>
                    <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">{{ $soaData['student_name'] }} ({{ $soaData['grade_level'] }})</p>
                </div>
                <button type="button" @click="showSave = false" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
            </div>

            <form action="{{ route('admin.finance.students.update-soa', ['studentIdentifier' => $soaData['student_number'] ?? $soaData['student_id']]) }}" method="POST">
                @csrf
                <input type="hidden" name="tuition_fee" :value="tuitionFee">
                <input type="hidden" name="misc_fee" :value="miscFee">
                <input type="hidden" name="books_fee" :value="booksFee">
                <input type="hidden" name="discount_percentage" :value="discountPercent">
                <input type="hidden" name="discount_amount" :value="discountAmount">
                <input type="hidden" name="enrollment_paid" :value="enrollmentPaid">

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Tuition Fee:</span>
                        <strong style="color: #0f172a;" x-text="'₱' + formatMoney(tuitionFee)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Sibling Discount (<span x-text="discountPercent + '%'"></span>):</span>
                        <strong style="color: #e11d48;" x-text="'- ₱' + formatMoney(discountAmount)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Final Assessed Balance:</span>
                        <strong style="color: #0f172a;" x-text="'₱' + formatMoney(finalFees)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                        <span style="color: #0f172a; font-weight: bold;">New Monthly Rate (9 mos):</span>
                        <strong style="color: #065f46;" x-text="'₱' + formatMoney(autoMonthlyRate)"></strong>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 11px; font-weight: bold; color: #334155; margin-bottom: 4px; text-transform: uppercase;">
                        Reason for SOA Adjustment <span style="color: #e11d48;">*</span>
                    </label>
                    <textarea name="reason" required rows="2" placeholder="e.g. Adjusted tuition rate / sibling discount per approved request." style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 12px; box-sizing: border-box;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" @click="showSave = false" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Cancel</button>
                    <button type="submit" style="background: #065f46; color: #ffffff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Save &amp; Recalculate</button>
                </div>
            </form>
        </div>
    </div>

    {{-- PRINT / A4 PAGE --}}
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
                        <template x-if="discountPercent > 0">
                            <tr>
                                <td class="info-lbl">Discount:</td>
                                <td class="info-val" x-text="discountPercent + '% Sibling Discount'"></td>
                            </tr>
                        </template>
                    </table>
                </div>

                {{-- COLUMN 3: FEE BREAKDOWN TABLE (INLINE EDITABLE) --}}
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
                                <td class="cell-right" style="padding:0;">
                                    <input type="number" step="0.01" x-model.number="tuitionFee" @input="recalculateMonthlyFees()" class="cell-input cell-right" title="Click to edit Tuition Fee">
                                </td>
                                <td class="cell-center" style="padding:0; width:35px;">
                                    <input type="number" step="0.01" x-model.number="discountPercent" @input="recalculateMonthlyFees()" class="cell-input cell-center" title="Click to edit Sibling Discount %" placeholder="0">
                                </td>
                                <td class="cell-center" style="font-weight:bold;" x-text="discountAmount > 0 ? formatMoney(discountAmount) : ''"></td>
                                <td class="cell-right" style="font-weight:bold;" x-text="formatMoney(netTuition)"></td>
                            </tr>
                            <tr>
                                <td>Miscellaneous</td>
                                <td class="cell-right" style="padding:0;">
                                    <input type="number" step="0.01" x-model.number="miscFee" class="cell-input cell-right" title="Click to edit Misc Fee">
                                </td>
                                <td></td>
                                <td></td>
                                <td class="cell-right" x-text="formatMoney(miscFee)"></td>
                            </tr>
                            <tr class="font-bold" style="background:#f8fafc;">
                                <td>Total Fees</td>
                                <td class="cell-right" x-text="formatMoney(totalFees)"></td>
                                <td></td>
                                <td></td>
                                <td class="cell-right" x-text="formatMoney(totalFees)"></td>
                            </tr>
                            <tr class="font-bold">
                                <td>Final Fees</td>
                                <td></td>
                                <td></td>
                                <td class="cell-center" x-text="discountAmount > 0 ? '-' : ''"></td>
                                <td class="cell-right" style="font-size:10px; font-weight:900;" x-text="formatMoney(finalFees)"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MAIN STATEMENT & PAYMENT LEDGER TABLE (INLINE EDITABLE) --}}
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
                    {{-- ROW 1: ENROLLMENT DOWNPAYMENT --}}
                    <tr>
                        <td>Paid Enrollment Fee</td>
                        <td></td>
                        <td class="cell-right"></td>
                        <td class="cell-center" style="padding:0;">
                            <input type="text" x-model="enrollmentDate" class="cell-input cell-center" title="Click to edit payment date">
                        </td>
                        <td class="cell-right highlight-yellow" style="padding:0;" title="Click to edit enrollment payment amount">
                            <input type="number" step="0.01" x-model.number="enrollmentPaid" @input="recalculateMonthlyFees()" class="cell-input cell-right highlight-yellow">
                        </td>
                        <td class="cell-center" style="padding:0;">
                            <input type="text" x-model="enrollmentAccount" class="cell-input cell-center" title="Click to edit Account / OR number">
                        </td>
                        <td class="cell-right" x-text="formatMoney(finalFees - enrollmentPaid)"></td>
                    </tr>

                    {{-- ROW 2: BOOKS AND PROGRAMS --}}
                    <tr>
                        <td>Books and programs</td>
                        <td></td>
                        <td class="cell-right" style="padding:0;">
                            <input type="number" step="0.01" x-model.number="booksFee" class="cell-input cell-right" title="Click to edit Books & Programs Fee">
                        </td>
                        <td class="cell-center"></td>
                        <td class="cell-right"></td>
                        <td class="cell-center"></td>
                        <td class="cell-right" x-text="formatMoney(finalFees - enrollmentPaid + Number(booksFee || 0))"></td>
                    </tr>

                    {{-- ROW 3: PAID BOOKS --}}
                    <tr>
                        <td>Paid Books</td>
                        <td></td>
                        <td class="cell-right"></td>
                        <td class="cell-center" style="padding:0;">
                            <input type="text" x-model="booksDate" class="cell-input cell-center" title="Click to edit payment date" placeholder="-">
                        </td>
                        <td class="cell-right highlight-yellow" style="padding:0;" title="Click to edit books paid amount">
                            <input type="number" step="0.01" x-model.number="booksPaid" class="cell-input cell-right highlight-yellow">
                        </td>
                        <td class="cell-center" style="padding:0;">
                            <input type="text" x-model="booksAccount" class="cell-input cell-center" title="Click to edit Account / OR number" placeholder="-">
                        </td>
                        <td class="cell-right" x-text="formatMoney(finalFees - enrollmentPaid + Number(booksFee || 0) - Number(booksPaid || 0))"></td>
                    </tr>

                    {{-- REQUIRED PAYMENT MONTHLY SECTION --}}
                    <tr class="row-section-header">
                        <td colspan="7">Required Payment Monthly</td>
                    </tr>

                    {{-- 2026 MONTHS (JULY - DECEMBER) --}}
                    <tr class="row-section-header" style="background:#ffffff; font-size:10px;">
                        <td colspan="7">Year: 2026</td>
                    </tr>
                    <template x-for="(m, idx) in months.slice(0, 6)" :key="idx">
                        <tr>
                            <td></td>
                            <td x-text="m.month"></td>
                            <td class="cell-right" style="padding:0;">
                                <input type="number" step="0.01" x-model.number="m.fee" class="cell-input cell-right" title="Click to edit monthly fee">
                            </td>
                            <td class="cell-center" style="padding:0;">
                                <input type="text" x-model="m.payment_date" class="cell-input cell-center" placeholder="-">
                            </td>
                            <td class="cell-right" :class="Number(m.paid || 0) > 0 ? 'highlight-yellow' : ''" style="padding:0;">
                                <input type="number" step="0.01" x-model.number="m.paid" class="cell-input cell-right" :class="Number(m.paid || 0) > 0 ? 'highlight-yellow' : ''" placeholder="-">
                            </td>
                            <td class="cell-center" style="padding:0;">
                                <input type="text" x-model="m.or_number" class="cell-input cell-center" placeholder="-">
                            </td>
                            <td class="cell-right" x-text="calculateRowBalance(idx)"></td>
                        </tr>
                    </template>

                    {{-- 2027 MONTHS (JANUARY - MARCH) --}}
                    <tr class="row-section-header" style="background:#ffffff; font-size:10px;">
                        <td colspan="7">Year: 2027</td>
                    </tr>
                    <template x-for="(m, idx) in months.slice(6, 9)" :key="idx + 6">
                        <tr>
                            <td></td>
                            <td x-text="m.month"></td>
                            <td class="cell-right" style="padding:0;">
                                <input type="number" step="0.01" x-model.number="m.fee" class="cell-input cell-right" title="Click to edit monthly fee">
                            </td>
                            <td class="cell-center" style="padding:0;">
                                <input type="text" x-model="m.payment_date" class="cell-input cell-center" placeholder="-">
                            </td>
                            <td class="cell-right" :class="Number(m.paid || 0) > 0 ? 'highlight-yellow' : ''" style="padding:0;">
                                <input type="number" step="0.01" x-model.number="m.paid" class="cell-input cell-right" :class="Number(m.paid || 0) > 0 ? 'highlight-yellow' : ''" placeholder="-">
                            </td>
                            <td class="cell-center" style="padding:0;">
                                <input type="text" x-model="m.or_number" class="cell-input cell-center" placeholder="-">
                            </td>
                            <td class="cell-right" x-text="calculateRowBalance(idx + 6)"></td>
                        </tr>
                    </template>

                    {{-- TO BE PAID / PAID LABEL ROW --}}
                    <tr style="border-top: 2px solid #475569;">
                        <td colspan="4" style="border:none;"></td>
                        <td class="cell-center" style="font-weight:bold; font-size:10px; border: 1px solid #475569;">TO BE PAID</td>
                        <td class="cell-center highlight-yellow" style="font-weight:bold; font-size:10px; border: 1px solid #475569;">PAID</td>
                        <td style="border:none;"></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="font-weight:bold; border-right:none;">Total Amount to pay</td>
                        <td class="cell-right highlight-blue" style="font-size:11px;" x-text="formatMoney(calculateFinalTotalToPay())"></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight:bold; border-right:none;">Due Monthly Payment (9 Months)</td>
                        <td class="cell-right highlight-yellow" style="border-left:none;" title="Monthly amount due for this student" x-text="formatMoney(autoMonthlyRate)"></td>
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

    <script>
        function soaStudio(initialData) {
            const allMonthNames = ['July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];
            
            // Normalize 9 months
            let existingSchedule = initialData.monthly_schedule || [];
            let normalizedMonths = allMonthNames.map((name, i) => {
                let match = existingSchedule.find(m => (m.month || '').toUpperCase().includes(name.toUpperCase())) || {};
                let defRate = Number(initialData.monthly_rate || 0);
                return {
                    id: match.id || null,
                    month: name,
                    fee: match.fee !== undefined ? Number(match.fee) : defRate,
                    paid: match.paid !== undefined ? Number(match.paid) : 0,
                    payment_date: match.payment_date || '',
                    or_number: match.or_number || '',
                };
            });

            return {
                showSave: false,
                tuitionFee: Number(initialData.tuition_fee || 0),
                miscFee: Number(initialData.misc_fee || 0),
                booksFee: Number(initialData.books_fee || 0),
                discountPercent: Number((initialData.discount_privilege || '0').replace('%', '')) || 0,
                enrollmentPaid: Number(initialData.enrollment_paid || 0),
                enrollmentDate: initialData.enrollment_date || '5-May-26',
                enrollmentAccount: initialData.enrollment_account || '10539',
                booksPaid: Number(initialData.books_paid || 0),
                booksDate: initialData.books_date || '',
                booksAccount: initialData.books_account || '',
                months: normalizedMonths,

                get totalFees() {
                    return Number(this.tuitionFee || 0) + Number(this.miscFee || 0);
                },
                get discountAmount() {
                    return Math.round(Number(this.tuitionFee || 0) * (Number(this.discountPercent || 0) / 100) * 100) / 100;
                },
                get finalFees() {
                    return Math.max(0, this.totalFees - this.discountAmount);
                },
                get netTuition() {
                    return Math.max(0, Number(this.tuitionFee || 0) - this.discountAmount);
                },
                get autoMonthlyRate() {
                    let netAfterDown = Math.max(0, this.finalFees - Number(this.enrollmentPaid || 0));
                    return Math.round((netAfterDown / 9) * 100) / 100;
                },

                recalculateMonthlyFees() {
                    const rate = this.autoMonthlyRate;
                    this.months.forEach(m => {
                        m.fee = rate;
                    });
                },

                calculateRowBalance(monthIdx) {
                    let currentBal = this.finalFees - Number(this.enrollmentPaid || 0) + Number(this.booksFee || 0) - Number(this.booksPaid || 0);
                    for (let i = 0; i <= monthIdx; i++) {
                        let p = Number(this.months[i].paid || 0);
                        if (p > 0) {
                            currentBal -= p;
                        }
                    }
                    let currentMonthPaid = Number(this.months[monthIdx].paid || 0);
                    return currentMonthPaid > 0 ? this.formatMoney(Math.max(0, currentBal)) : '';
                },

                calculateFinalTotalToPay() {
                    let currentBal = this.finalFees - Number(this.enrollmentPaid || 0) + Number(this.booksFee || 0) - Number(this.booksPaid || 0);
                    for (let i = 0; i < this.months.length; i++) {
                        let p = Number(this.months[i].paid || 0);
                        if (p > 0) {
                            currentBal -= p;
                        }
                    }
                    return Math.max(0, currentBal);
                },

                formatMoney(val) {
                    let n = Number(val || 0);
                    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                openSaveModal() {
                    this.showSave = true;
                }
            };
        }
    </script>
</body>
</html>
