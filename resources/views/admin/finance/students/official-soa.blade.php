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
            width: 100%;
            max-width: 1240px;
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

        /* WORKSPACE LAYOUT (SIDEBAR + SOA SHEET) */
        .soa-workspace {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
            max-width: 1240px;
            margin: 14px auto 36px;
            padding: 0 16px;
            box-sizing: border-box;
        }

        /* LINKED CHILDREN SIDEBAR */
        .linked-children-sidebar {
            width: 280px;
            flex-shrink: 0;
            position: sticky;
            top: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .sidebar-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .sidebar-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .sidebar-title {
            font-size: 12.5px;
            font-weight: 900;
            color: #0f172a;
            margin: 0 0 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge-count {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 9999px;
        }
        .badge-multi {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-solo {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .sibling-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sibling-card {
            display: block;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.15s ease-in-out;
        }
        .sibling-card:hover {
            border-color: #4338ca;
            background: #f8fafc;
            transform: translateX(2px);
        }
        .sibling-card.active-student {
            background: #ecfdf5;
            border-color: #059669;
            box-shadow: 0 0 0 1.5px #059669;
        }
        .sib-name {
            font-weight: 900;
            font-size: 11.5px;
            color: #0f172a;
            margin-bottom: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sibling-card.active-student .sib-name {
            color: #065f46;
        }
        .sib-meta {
            font-size: 10px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sib-balance {
            font-weight: 800;
            color: #0f172a;
        }
        .sib-active-badge {
            background: #065f46;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            padding: 1.5px 5px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* SOA VIEWPORT */
        .soa-viewport {
            flex-grow: 1;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y pinch-zoom;
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

        @media screen and (max-width: 1024px) {
            .soa-workspace {
                flex-direction: column;
                align-items: center;
            }
            .linked-children-sidebar {
                width: 100%;
                max-width: 210mm;
                position: static;
            }
        }

        @media screen and (max-width: 768px) {
            .soa-viewport {
                width: 100%;
                overflow-x: auto;
                overflow-y: auto;
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

        /* CLICKABLE INTERACTIVE ROWS & CELLS */
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.12s ease-in-out;
        }
        .clickable-row:hover {
            background-color: #ecfdf5 !important;
        }
        .clickable-row:hover td {
            color: #065f46;
        }
        .clickable-table {
            cursor: pointer;
            transition: box-shadow 0.15s ease-in-out;
        }
        .clickable-table:hover {
            box-shadow: 0 0 0 2px #4338ca;
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

        /* MODAL POPUPS */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(2px);
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
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
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

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print-bar,
            .linked-children-sidebar,
            .modal-overlay {
                display: none !important;
            }
            .soa-workspace {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }
            .clickable-table:hover,
            .clickable-row:hover {
                box-shadow: none !important;
                background-color: transparent !important;
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
            <button type="button" @click="openFeeModal()" class="btn-edit" title="Edit assessment and fees">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                ✏️ Edit Fees &amp; Discounts
            </button>
            <button type="button" onclick="window.print()" class="btn-print">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save to PDF
            </button>
        </div>
    </div>

    @if (session('success'))
        <div style="width: 100%; max-width: 1240px; margin: 10px auto 0; padding: 10px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-weight: bold; font-size: 12px; box-sizing: border-box;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- MAIN WORKSPACE (SIDEBAR + SOA SHEET) --}}
    <div class="soa-workspace">
        
        {{-- LEFT SIDEBAR: LINKED CHILDREN & SIBLING SWITCHER --}}
        <aside class="linked-children-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <div class="sidebar-title">
                        <span>👨‍👩‍👧‍👦</span>
                        <span>{{ $soaData['family_name'] ?? 'Family Account' }}</span>
                    </div>
                    <div>
                        @if(($soaData['siblings_count'] ?? 1) > 1)
                            <span class="badge-count badge-multi">{{ $soaData['siblings_count'] }} Linked Children</span>
                            <span style="font-size: 10px; color: #059669; font-weight: bold; margin-left: 4px;">
                                ({{ $soaData['discount_privilege'] }} Sibling Discount)
                            </span>
                        @else
                            <span class="badge-count badge-solo">Solo Student (1 Child)</span>
                        @endif
                    </div>
                </div>

                @if(($soaData['siblings_count'] ?? 1) > 1)
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">
                        Switch Student SOA:
                    </div>
                    <div class="sibling-list">
                        @foreach(($soaData['siblings'] ?? []) as $sib)
                            <a href="{{ $sib['url'] }}" class="sibling-card {{ $sib['is_current'] ? 'active-student' : '' }}" title="View SOA for {{ $sib['name'] }}">
                                <div class="sib-name">
                                    <span>{{ $sib['name'] }}</span>
                                    @if($sib['is_current'])
                                        <span class="sib-active-badge">Viewing</span>
                                    @else
                                        <span style="font-size: 12px; color: #94a3b8;">→</span>
                                    @endif
                                </div>
                                <div class="sib-meta">
                                    <span>{{ $sib['grade_level'] }}</span>
                                    <span class="sib-balance">Bal: ₱{{ number_format($sib['remaining_balance'], 2) }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px; text-align: center; color: #64748b; font-size: 11px;">
                        <div style="font-size: 18px; margin-bottom: 2px;">👤</div>
                        <strong>Solo Student in Family</strong>
                        <p style="margin: 4px 0 0; font-size: 10px; color: #94a3b8;">Standard tuition assessment applies (No sibling discount).</p>
                    </div>
                @endif

                @if(isset($soaData['family_id']))
                    <div style="margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                        <a href="{{ route('admin.finance.families.show', $soaData['family_id']) }}" style="display: flex; align-items: center; justify-content: center; gap: 5px; background: #f1f5f9; color: #334155; text-decoration: none; padding: 7px 12px; border-radius: 8px; font-weight: bold; font-size: 11px; transition: background 0.15s;">
                            <span>📊</span> Full Family Financial Ledger
                        </a>
                    </div>
                @endif
            </div>
        </aside>

        {{-- RIGHT COLUMN: OFFICIAL STATEMENT OF ACCOUNT PAGE --}}
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

                    {{-- COLUMN 3: FEE BREAKDOWN TABLE (CLICK TO OPEN FEE MODAL) --}}
                    <div class="upper-right">
                        <table class="fee-table clickable-table" @click="openFeeModal()" title="Click to edit Tuition, Misc, and Sibling Discounts">
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
                        <tr class="clickable-row" @click="openFeeModal()" title="Click to edit Enrollment Downpayment">
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
                        <tr class="clickable-row" @click="openFeeModal()" title="Click to edit Books & Programs">
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
                        <tr class="clickable-row" @click="openFeeModal()" title="Click to edit Paid Books">
                            <td>Paid Books</td>
                            <td></td>
                            <td class="cell-right"></td>
                            <td class="cell-center">{{ $soaData['books_date'] ?: '-' }}</td>
                            <td class="cell-right {{ (float) $soaData['books_paid'] > 0.01 ? 'highlight-yellow' : '' }}">{{ (float) $soaData['books_paid'] > 0.01 ? number_format($soaData['books_paid'], 2) : '-' }}</td>
                            <td class="cell-center">{{ $soaData['books_account'] ?: '-' }}</td>
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
                                    $txDateDisplay = '';
                                }
                                $txAccountDisplay = $txAccount;
                            }

                            $monthPayload = [
                                'month' => $monthName . ' 2026',
                                'fee' => $mFee,
                                'paid' => $mPaid,
                                'status' => $isPaidMonth ? ($mPaid >= $mFee ? 'paid' : 'partial') : 'unpaid',
                                'payment_date' => $txDateDisplay ?: now()->format('d-M-y'),
                                'or_number' => $txAccountDisplay ?: '',
                            ];
                        @endphp
                        <tr class="clickable-row" @click="openMonthModal({{ Js::from($monthPayload) }})" title="Click to edit {{ $monthName }} 2026 payment">
                            <td></td>
                            <td>{{ $monthName }}</td>
                            <td class="cell-right {{ ($monthName === 'July' && ! $isPaidMonth) ? 'highlight-yellow' : '' }}">{{ number_format($mFee, 2) }}</td>
                            <td class="cell-center">{{ $txDateDisplay ?: '-' }}</td>
                            <td class="cell-right {{ $isPaidMonth ? 'highlight-yellow' : '' }}">
                                {{ $isPaidMonth ? number_format($mPaid, 2) : '-' }}
                            </td>
                            <td class="cell-center">{{ $isPaidMonth ? $txAccountDisplay : '-' }}</td>
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

                            $monthPayload = [
                                'month' => $monthName . ' 2027',
                                'fee' => $mFee,
                                'paid' => $mPaid,
                                'status' => $isPaidMonth ? ($mPaid >= $mFee ? 'paid' : 'partial') : 'unpaid',
                                'payment_date' => $txDateDisplay ?: now()->format('d-M-y'),
                                'or_number' => $txAccountDisplay ?: '',
                            ];
                        @endphp
                        <tr class="clickable-row" @click="openMonthModal({{ Js::from($monthPayload) }})" title="Click to edit {{ $monthName }} 2027 payment">
                            <td></td>
                            <td>{{ $monthName }}</td>
                            <td class="cell-right">{{ number_format($mFee, 2) }}</td>
                            <td class="cell-center">{{ $txDateDisplay ?: '-' }}</td>
                            <td class="cell-right {{ $isPaidMonth ? 'highlight-yellow' : '' }}">
                                {{ $isPaidMonth ? number_format($mPaid, 2) : '-' }}
                            </td>
                            <td class="cell-center">{{ $isPaidMonth ? $txAccountDisplay : '-' }}</td>
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
</div>

    {{-- MODAL 1: EDIT SPECIFIC MONTH PAYMENT RECORD --}}
    <div x-show="showMonthModal" x-cloak class="modal-overlay" @click.self="showMonthModal = false">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <span style="display: inline-block; background: #ecfdf5; color: #065f46; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; margin-bottom: 3px;">Monthly Installment Editor</span>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;" x-text="activeMonth.month + ' Billing Record'"></h3>
                    <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">{{ $soaData['student_name'] }} ({{ $soaData['grade_level'] }})</p>
                </div>
                <button type="button" @click="showMonthModal = false" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
            </div>

            <form action="{{ route('admin.finance.students.update-month-billing', ['studentIdentifier' => $soaData['student_number'] ?? $soaData['student_id']]) }}" method="POST">
                @csrf
                <input type="hidden" name="billing_month" :value="activeMonth.month">

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Assessed Monthly Due:</span>
                        <strong style="color: #0f172a;" x-text="'₱' + formatMoney(activeMonth.fee)"></strong>
                    </div>
                </div>

                {{-- QUICK STATUS TOGGLE BUTTONS --}}
                <div style="margin-bottom: 12px;">
                    <label class="form-label">Payment Status</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                        <button type="button" @click="setMonthStatus('paid')" :style="activeMonth.status === 'paid' ? 'background:#065f46; color:#ffffff;' : 'background:#f1f5f9; color:#334155;'" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 11px; cursor: pointer;">
                            ✓ Paid Full (₱<span x-text="formatMoney(activeMonth.fee)"></span>)
                        </button>
                        <button type="button" @click="setMonthStatus('partial')" :style="activeMonth.status === 'partial' ? 'background:#d97706; color:#ffffff;' : 'background:#f1f5f9; color:#334155;'" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 11px; cursor: pointer;">
                            Partial Amount
                        </button>
                        <button type="button" @click="setMonthStatus('unpaid')" :style="activeMonth.status === 'unpaid' ? 'background:#e11d48; color:#ffffff;' : 'background:#f1f5f9; color:#334155;'" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 11px; cursor: pointer;">
                            Unpaid (₱0.00)
                        </button>
                    </div>
                    <input type="hidden" name="status" :value="activeMonth.status">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Amount Paid (₱)</label>
                        <input type="number" step="0.01" name="amount_paid" x-model.number="activeMonth.paid" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Payment Date</label>
                        <input type="text" name="payment_date" x-model="activeMonth.payment_date" placeholder="e.g. 15-Aug-26" class="form-input">
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="form-label">Official Receipt / Account No.</label>
                    <input type="text" name="or_number" x-model="activeMonth.or_number" placeholder="e.g. 10539 or OR-2026-008" class="form-input">
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Reason for Adjustment <span style="color: #e11d48;">*</span></label>
                    <textarea name="reason" required rows="2" placeholder="e.g. Encoded historical official receipt / cleared payment per parent receipt." class="form-input" style="font-family: inherit; font-weight: normal;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" @click="showMonthModal = false" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Cancel</button>
                    <button type="submit" style="background: #065f46; color: #ffffff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Save Month Payment</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: EDIT GENERAL FEES & DISCOUNTS --}}
    <div x-show="showFeeModal" x-cloak class="modal-overlay" @click.self="showFeeModal = false">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div>
                    <span style="display: inline-block; background: #e0e7ff; color: #3730a3; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; margin-bottom: 3px;">Assessment &amp; Fee Studio</span>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">Edit Statement Assessment</h3>
                    <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">{{ $soaData['student_name'] }} ({{ $soaData['grade_level'] }})</p>
                </div>
                <button type="button" @click="showFeeModal = false" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;">✕</button>
            </div>

            <form action="{{ route('admin.finance.students.update-soa', ['studentIdentifier' => $soaData['student_number'] ?? $soaData['student_id']]) }}" method="POST">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Tuition Fee (₱)</label>
                        <input type="number" step="0.01" name="tuition_fee" x-model.number="feeData.tuition" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Miscellaneous Fee (₱)</label>
                        <input type="number" step="0.01" name="misc_fee" x-model.number="feeData.misc" required class="form-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Books &amp; Programs Fee (₱)</label>
                        <input type="number" step="0.01" name="books_fee" x-model.number="feeData.books" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Sibling Discount (%)</label>
                        <input type="number" step="0.01" name="discount_percentage" x-model.number="feeData.discountPercent" class="form-input" placeholder="0">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label">Enrollment Downpayment Paid (₱)</label>
                    <input type="number" step="0.01" name="enrollment_paid" x-model.number="feeData.enrollmentPaid" required class="form-input">
                </div>

                {{-- LIVE RECALCULATION SUMMARY PREVIEW --}}
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 11.5px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Total Fees:</span>
                        <strong style="color: #0f172a;" x-text="'₱' + formatMoney(calcTotalFees)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Sibling Discount (<span x-text="feeData.discountPercent + '%'"></span>):</span>
                        <strong style="color: #e11d48;" x-text="'- ₱' + formatMoney(calcDiscountAmount)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: #64748b;">Final Assessed Balance:</span>
                        <strong style="color: #0f172a;" x-text="'₱' + formatMoney(calcFinalFees)"></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                        <span style="color: #065f46; font-weight: bold;">Monthly Rate (9 mos):</span>
                        <strong style="color: #065f46;" x-text="'₱' + formatMoney(calcMonthlyRate)"></strong>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Reason for Adjustment <span style="color: #e11d48;">*</span></label>
                    <textarea name="reason" required rows="2" placeholder="e.g. Sibling discount correction / adjusted tuition schedule per approved concession." class="form-input" style="font-family: inherit; font-weight: normal;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" @click="showFeeModal = false" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Cancel</button>
                    <button type="submit" style="background: #065f46; color: #ffffff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 12px;">Save &amp; Recalculate SOA</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function soaStudio(initialData) {
            return {
                showMonthModal: false,
                showFeeModal: false,
                activeMonth: {
                    month: '',
                    fee: 0,
                    paid: 0,
                    status: 'unpaid',
                    payment_date: '',
                    or_number: ''
                },
                feeData: {
                    tuition: Number(initialData.tuition_fee || 0),
                    misc: Number(initialData.misc_fee || 0),
                    books: Number(initialData.books_fee || 0),
                    discountPercent: Number((initialData.discount_privilege || '0').replace('%', '')) || 0,
                    enrollmentPaid: Number(initialData.enrollment_paid || 0),
                },

                openMonthModal(monthData) {
                    this.activeMonth = Object.assign({}, monthData);
                    this.showMonthModal = true;
                },

                setMonthStatus(status) {
                    this.activeMonth.status = status;
                    if (status === 'paid') {
                        this.activeMonth.paid = this.activeMonth.fee;
                    } else if (status === 'unpaid') {
                        this.activeMonth.paid = 0;
                    }
                },

                openFeeModal() {
                    this.showFeeModal = true;
                },

                get calcTotalFees() {
                    return Number(this.feeData.tuition || 0) + Number(this.feeData.misc || 0);
                },
                get calcDiscountAmount() {
                    return Math.round(Number(this.feeData.tuition || 0) * (Number(this.feeData.discountPercent || 0) / 100) * 100) / 100;
                },
                get calcFinalFees() {
                    return Math.max(0, this.calcTotalFees - this.calcDiscountAmount);
                },
                get calcMonthlyRate() {
                    let netAfterDown = Math.max(0, this.calcFinalFees - Number(this.feeData.enrollmentPaid || 0));
                    return Math.round((netAfterDown / 9) * 100) / 100;
                },
                formatMoney(val) {
                    let n = Number(val || 0);
                    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</body>
</html>
