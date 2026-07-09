<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Family Payments Report</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #fff; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 10mm 5mm; }
        .header { display: table; width: 100%; border-bottom: 2px solid #059669; padding-bottom: 14px; margin-bottom: 18px; }
        h1 { margin: 0; font-size: 15px; font-weight: 900; letter-spacing: .02em; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; z-index: 50; }
        .toolbar button { border: 0; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; }
        
        .family-card { page-break-inside: avoid !important; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; background: #f8fafc; }
        .family-title-row { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 12px; }
        .family-name { font-size: 13px; font-weight: 900; color: #0f172a; text-transform: uppercase; }
        .family-no { font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; }
        
        .grid-details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; font-size: 9.5px; }
        .detail-label { font-weight: 800; text-transform: uppercase; color: #64748b; font-size: 8px; tracking-wider: 0.05em; }
        .detail-val { font-weight: 600; color: #1e293b; margin-top: 1px; }

        .section-header { font-size: 9px; font-weight: 900; text-transform: uppercase; color: #059669; margin: 12px 0 6px; letter-spacing: 0.03em; display: flex; align-items: center; gap: 4px; }
        
        table { border-collapse: collapse !important; width: 100% !important; border: none !important; margin-bottom: 10px !important; }
        table th { background: #e2e8f0 !important; color: #334155 !important; font-weight: bold !important; font-size: 8.5px !important; padding: 6px 8px !important; text-transform: uppercase !important; text-align: left; }
        table td { border-bottom: 1px solid #e2e8f0 !important; padding: 6px 8px !important; font-size: 8.5px !important; color: #334155 !important; background: #fff !important; vertical-align: top; }
        
        .receipt-preview-block { text-align: center; margin-top: 16px; padding: 12px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px; page-break-inside: avoid !important; }
        .receipt-preview-title { font-size: 8.5px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.03em; }
        .receipt-img-large { max-width: 100%; width: 480px; max-height: 520px; border: 1px solid #94a3b8; border-radius: 8px; object-fit: contain; background: #fff; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pdf-canvas { max-width: 100%; width: 480px; max-height: 520px; border: 1px solid #94a3b8; border-radius: 8px; background: #fff; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .badge { display: inline-block; border-radius: 999px; font-size: 8px; font-weight: 900; padding: 2px 6px; text-transform: uppercase; }
        .badge-verified { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-rejected { background: #ffe4e6; color: #991b1b; border: 1px solid #fecdd3; }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .pagination-container a, .pagination-container span {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            text-decoration: none;
            color: #334155;
            font-weight: bold;
            font-size: 11px;
            background: #fff;
            transition: all 0.2s ease;
        }
        .pagination-container a:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .pagination-container .active {
            background: #059669;
            color: #fff;
            border-color: #059669;
        }
        .pagination-container .disabled {
            background: #f8fafc;
            color: #94a3b8;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }

        @media print {
            .toolbar { display: none; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; }
            .family-card { background: #fff; border-color: #cbd5e1; }
            .pagination-container { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="page">
        <!-- Print Header -->
        <header class="header">
            <div style="display: table; width: 100%; border-collapse: collapse;">
                <div style="display: table-row;">
                    <!-- Left: English Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: left;">
                        <h1 style="font-family: Arial, sans-serif; font-weight: 900; font-size: 14px; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">
                            AL MUNAWWARA ISLAMIC SCHOOL
                        </h1>
                        <div style="margin-top: 2px; color: #64748b; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            Official School Portal
                        </div>
                    </div>
                    <!-- Center: Logo -->
                    <div style="display: table-cell; vertical-align: middle; width: 20%; text-align: center;">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 54px; width: auto; display: inline-block;">
                    </div>
                    <!-- Right: Arabic Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: right; direction: rtl;">
                        <h1 style="font-family: 'Times New Roman', serif; font-weight: 900; font-size: 18px; margin: 0; color: #059669; letter-spacing: 0.03em;">
                            المدرسة المنورة الإسلامية
                        </h1>
                    </div>
                </div>
            </div>
            <!-- Address Centered Below Logo -->
            <div style="text-align: center; font-size: 9px; color: #475569; font-weight: 700; margin-top: 8px; font-family: Arial, sans-serif; text-transform: uppercase; letter-spacing: 0.03em;">
                Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines, 8000
            </div>
        </header>

        <div style="margin-bottom: 24px; text-align: center;">
            <h1 style="text-transform: uppercase; font-size: 14px; margin: 0; letter-spacing: 0.05em;">
                FAMILY PAYMENT & ENROLLMENT PROOF LEDGER
            </h1>
            <div style="color: #64748b; font-size: 9px; margin-top: 4px; font-weight: bold;">
                Report Generated: {{ now()->format('M d, Y h:i A') }}
                @if($pageInfo['is_paginated'])
                    | Page {{ $pageInfo['current'] }} of {{ $pageInfo['last'] }}
                @endif
                | Total Active Families: {{ $pageInfo['total'] }}
            </div>
        </div>

        <!-- Families Ledger Loop -->
        @foreach($families as $family)
            @php
                $rep = $family['payment']->applicant;
                $mother = trim(($rep->mother_first_name ?? '').' '.($rep->mother_last_name ?? ''));
                $father = trim(($rep->father_first_name ?? '').' '.($rep->father_last_name ?? ''));
                $parentName = $mother && $father ? $father . ' & ' . $mother : ($mother ?: ($father ?: 'N/A'));
                
                $parentEmail = $rep->parent_email ?: ($rep->email ?: ($rep->user?->email ?: 'No Email'));
                $parentMobile = trim(($rep->parent_country_code ? $rep->parent_country_code.' ' : '').($rep->parent_mobile ?? ''));
            @endphp
            <section class="family-card">
                <!-- Title Block -->
                <div class="family-title-row">
                    <span class="family-name">{{ $family['family_label'] }}</span>
                    <span class="family-no">Family ID #{{ str_pad((string)$family['family_no'], 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <!-- Family Contacts Details -->
                <div class="grid-details">
                    <div>
                        <div class="detail-label">Parent / Guardian Name</div>
                        <div class="detail-val">{{ $parentName }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Contact Details</div>
                        <div class="detail-val">
                            {{ $parentEmail }}
                            @if($parentMobile)
                                | {{ $parentMobile }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Students in the Family -->
                <div class="section-header">
                    <svg style="width: 11px; height: 11px; display: inline-block; fill: currentColor;" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5.89 12.55L12 15.88l6.11-3.33v2.33L12 18.21l-6.11-3.33v-2.33z"/></svg>
                    Enrolled Family Students ({{ $family['children']->count() }})
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%;">Student #</th>
                            <th style="width: 40%;">Student Name</th>
                            <th style="width: 25%;">Grade Level</th>
                            <th style="width: 25%;">LRN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($family['children'] as $child)
                            <tr>
                                <td style="font-weight: bold;">{{ $child->student?->student_number ?? '-' }}</td>
                                <td style="font-weight: bold; color: #0f172a;">{{ $child->full_name }}</td>
                                <td>{{ $child->grade_level ?: 'Pending' }}</td>
                                <td>{{ $child->lrn ?: 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Payments Submitted by Family -->
                <div class="section-header">
                    <svg style="width: 11px; height: 11px; display: inline-block; fill: currentColor;" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                    Family Proof of Payment Files ({{ $family['payments']->count() }})
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Date Paid</th>
                            <th style="width: 20%;">Amount</th>
                            <th style="width: 20%;">Method</th>
                            <th style="width: 20%;">Reference No</th>
                            <th style="width: 20%;">OR / Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($family['payments'] as $payment)
                            <tr>
                                <td>{{ $payment->paid_at?->format('Y-m-d') ?: ($payment->created_at?->format('Y-m-d') ?: '-') }}</td>
                                <td style="font-weight: bold; color: #0f172a;">PHP {{ number_format($payment->amount, 2) }}</td>
                                <td style="text-transform: uppercase;">{{ $payment->method ?: '-' }}</td>
                                <td>{{ $payment->reference_no ?: '-' }}</td>
                                <td>
                                    @if($payment->or_number)
                                        <div style="font-weight: bold; color: #059669;">OR: {{ $payment->or_number }}</div>
                                    @endif
                                    @if($payment->invoice?->invoice_no)
                                        <div style="font-size: 8px; color: #64748b;">INV: {{ $payment->invoice->invoice_no }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Large Receipt Images Displayed Centered Below Table -->
                @foreach($family['payments'] as $payment)
                    @php
                        $receiptUrl = $payment->receipt_url ? \App\Support\EnrollmentStorage::url($payment->receipt_url) : null;
                        $isPdf = $payment->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
                    @endphp
                    @if($receiptUrl)
                        <div class="receipt-preview-block">
                            <div class="receipt-preview-title">
                                Receipt Proof File &mdash; PHP {{ number_format($payment->amount, 2) }} ({{ strtoupper($payment->method ?: 'PAYMENT') }} / Ref: {{ $payment->reference_no ?: 'N/A' }})
                            </div>
                            @if($isPdf)
                                <div class="pdf-canvas-container" data-pdf-url="{{ $receiptUrl }}" style="margin: 10px 0;">
                                    <a href="{{ $receiptUrl }}" target="_blank">
                                        <canvas class="pdf-canvas"></canvas>
                                    </a>
                                </div>
                            @else
                                <a href="{{ $receiptUrl }}" target="_blank">
                                    <img src="{{ $receiptUrl }}" alt="Receipt Proof" class="receipt-img-large">
                                </a>
                            @endif
                        </div>
                    @endif
                @endforeach
            </section>
        @endforeach

        @if($pageInfo['is_paginated'])
            <!-- Pagination Controls -->
            <div class="pagination-container">
                @if ($families->onFirstPage())
                    <span class="disabled">&laquo; Prev</span>
                @else
                    <a href="{{ $families->previousPageUrl() }}">&laquo; Prev</a>
                @endif

                @foreach ($families->getUrlRange(max(1, $families->currentPage() - 3), min($families->lastPage(), $families->currentPage() + 3)) as $pageNumber => $url)
                    @if ($pageNumber == $families->currentPage())
                        <span class="active">{{ $pageNumber }}</span>
                    @else
                        <a href="{{ $url }}">{{ $pageNumber }}</a>
                    @endif
                @endforeach

                @if ($families->hasMorePages())
                    <a href="{{ $families->nextPageUrl() }}">Next &raquo;</a>
                @else
                    <span class="disabled">Next &raquo;</span>
                @endif
            </div>
        @endif
    </main>

    <script>
        // Set worker URL
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        let hasPrinted = false;
        function doPrint() {
            if (hasPrinted) return;
            hasPrinted = true;
            window.print();
        }

        // Render all PDFs onto their canvases
        const pdfContainers = document.querySelectorAll('.pdf-canvas-container');
        const renderPromises = Array.from(pdfContainers).map(container => {
            const url = container.dataset.pdfUrl;
            return pdfjsLib.getDocument(url).promise.then(pdf => {
                return pdf.getPage(1).then(page => {
                    const canvas = container.querySelector('.pdf-canvas');
                    const context = canvas.getContext('2d');
                    
                    // Render at high scale for clean vector print outputs
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    
                    return page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
                });
            }).catch(err => {
                console.error("Error rendering PDF to canvas:", err);
            });
        });

        // Trigger print only after all PDFs are loaded and fully drawn
        Promise.all(renderPromises).then(() => {
            setTimeout(doPrint, 1500);
        });

        window.addEventListener('focus', () => {
            setTimeout(doPrint, 300);
        });
    </script>
</body>
</html>
