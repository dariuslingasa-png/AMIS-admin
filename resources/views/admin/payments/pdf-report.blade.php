<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>AMIS Family Payments Report</title>
    <style>
        @page { margin: 12mm; }
        body { margin: 0; background: #fff; color: #0f172a; font-family: Helvetica, Arial, sans-serif; font-size: 10px; line-height: 1.3; }
        .family-card { page-break-inside: avoid !important; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 20px; background: #ffffff; }
        
        .family-header-table { width: 100%; border-collapse: collapse; border-bottom: 1.5px solid #94a3b8; margin-bottom: 10px; }
        .family-header-table td { border: none !important; padding: 4px 0 !important; background: transparent !important; }
        .family-name { font-size: 12px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .family-no { font-size: 9px; font-weight: bold; color: #64748b; text-align: right; text-transform: uppercase; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .details-table td { border: none !important; padding: 4px 0 !important; background: transparent !important; vertical-align: top; }
        .detail-label { font-weight: bold; text-transform: uppercase; color: #64748b; font-size: 7.5px; }
        .detail-val { font-weight: bold; color: #1e293b; font-size: 9px; margin-top: 1px; }

        .section-header { font-size: 8.5px; font-weight: bold; text-transform: uppercase; color: #059669; margin: 10px 0 5px 0; }
        
        table.data-table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
        table.data-table th { background: #cbd5e1; color: #1e293b; font-weight: bold; font-size: 8px; padding: 5px 6px; text-transform: uppercase; text-align: left; border: 1px solid #cbd5e1; }
        table.data-table td { border: 1px solid #cbd5e1; padding: 5px 6px; font-size: 8px; color: #334155; background: #ffffff; vertical-align: top; }
        
        .receipt-preview-block { text-align: center; margin-top: 12px; padding: 8px; border: 1px dashed #94a3b8; border-radius: 6px; page-break-inside: avoid !important; }
        .receipt-preview-title { font-size: 8px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 6px; }
        .receipt-img-large { max-width: 440px; max-height: 480px; object-fit: contain; }
    </style>
</head>
<body>
    <!-- Print Header -->
    <table style="width: 100%; border-collapse: collapse; border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 20px;">
        <tr>
            <td style="width: 45%; border: none; padding: 0; vertical-align: middle;">
                <h1 style="font-weight: bold; font-size: 13px; margin: 0; text-transform: uppercase; color: #0f172a;">
                    AL MUNAWWARA ISLAMIC SCHOOL
                </h1>
                <div style="margin-top: 2px; color: #64748b; font-size: 7.5px; font-weight: bold; text-transform: uppercase;">
                    Official School Portal
                </div>
            </td>
            <td style="width: 10%; border: none; padding: 0; text-align: center; vertical-align: middle;">
                @if(file_exists(public_path('images/AMIS_Logo.png')))
                    <img src="{{ public_path('images/AMIS_Logo.png') }}" style="height: 48px; width: auto; display: inline-block;">
                @endif
            </td>
            <td style="width: 45%; border: none; padding: 0; text-align: right; vertical-align: middle;">
                <h1 style="font-family: Georgia, serif; font-weight: bold; font-size: 16px; margin: 0; color: #059669;">
                    المدرسة المنورة الإسلامية
                </h1>
            </td>
        </tr>
    </table>

    <div style="margin-bottom: 20px; text-align: center;">
        <h2 style="text-transform: uppercase; font-size: 12px; margin: 0; font-weight: bold;">
            FAMILY PAYMENT & ENROLLMENT PROOF LEDGER
        </h2>
        <div style="color: #64748b; font-size: 8px; margin-top: 3px; font-weight: bold;">
            Report Generated: {{ now()->format('M d, Y h:i A') }} | Part {{ $part }} of {{ $totalParts }}
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
        <div class="family-card">
            <!-- Title Block -->
            <table class="family-header-table">
                <tr>
                    <td class="family-name">{{ $family['family_label'] }}</td>
                    <td class="family-no">Family ID #{{ str_pad((string)$family['family_no'], 4, '0', STR_PAD_LEFT) }}</td>
                </tr>
            </table>

            <!-- Family Contacts Details -->
            <table class="details-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="detail-label">Parent / Guardian Name</div>
                        <div class="detail-val">{{ $parentName }}</div>
                    </td>
                    <td style="width: 50%;">
                        <div class="detail-label">Contact Details</div>
                        <div class="detail-val">
                            {{ $parentEmail }}
                            @if($parentMobile)
                                | {{ $parentMobile }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Students in the Family -->
            <div class="section-header">
                Enrolled Family Students ({{ $family['children']->count() }})
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Student #</th>
                        <th style="width: 45%;">Student Name</th>
                        <th style="width: 20%;">Grade Level</th>
                        <th style="width: 20%;">LRN</th>
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
                Family Proof of Payment Files ({{ $family['payments']->count() }})
            </div>
            <table class="data-table">
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
                    $isPdf = $payment->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
                    $imgPath = $payment->rendered_image_path ?? null;
                @endphp
                @if($imgPath && file_exists($imgPath))
                    <div class="receipt-preview-block">
                        <div class="receipt-preview-title">
                            Receipt Proof File &mdash; PHP {{ number_format($payment->amount, 2) }} ({{ strtoupper($payment->method ?: 'PAYMENT') }} / Ref: {{ $payment->reference_no ?: 'N/A' }})
                        </div>
                        <img src="{{ $imgPath }}" class="receipt-img-large">
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach
</body>
</html>
