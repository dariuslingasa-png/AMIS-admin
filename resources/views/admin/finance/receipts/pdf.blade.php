<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official Payment Receipt - {{ $receiptData['receipt_number'] }}</title>
    <style>
        @page { margin: 24px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 8.5px; line-height: 1.45; }
        
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .seal-cell { width: 62px; }
        .seal-img { width: 52px; height: 52px; object-fit: contain; }
        .brand-cell { padding-left: 6px; }
        .arabic-wordmark { display: block; width: 175px; height: auto; margin-bottom: 2px; }
        .arabic-text { font-family: DejaVu Sans, serif; font-size: 13px; color: #064e3b; margin-bottom: 2px; }
        .school-name { color: #047857; font-size: 10px; font-weight: bold; letter-spacing: .5px; white-space: nowrap; }
        .school-address { color: #64748b; font-size: 7.5px; margin-top: 1px; }
        .receipt-title { margin-top: 3px; color: #0f172a; font-size: 13px; font-weight: bold; letter-spacing: .4px; }
        
        .receipt-meta-cell { width: 220px; text-align: right; vertical-align: top; padding-top: 2px; }
        .meta-line { margin-bottom: 2px; font-size: 8px; }
        .meta-label { color: #64748b; }
        .meta-val-primary { color: #0f172a; font-weight: bold; }
        
        .header-divider { border-bottom: 1.5px solid #047857; margin-top: 8px; margin-bottom: 12px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { vertical-align: top; }
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table td { padding: 2px 0; font-size: 8px; }
        .kv-label { color: #64748b; width: 110px; }
        .kv-value { color: #0f172a; font-weight: bold; }

        .section-divider { border-bottom: 1px solid #e2e8f0; margin: 8px 0 10px; }
        .section-heading { margin: 10px 0 5px; color: #0f172a; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; }
        
        .breakdown-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .breakdown-table th { padding: 5px 4px; background: #f8fafc; color: #0f172a; font-size: 7.5px; font-weight: bold; text-transform: uppercase; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; letter-spacing: .2px; }
        .breakdown-table td { padding: 5px 4px; border-bottom: 1px solid #f1f5f9; font-size: 8px; vertical-align: middle; }
        .breakdown-table tbody tr:nth-child(even) { background: #fafafa; }
        .breakdown-table tfoot td { padding: 6px 4px; border-top: 1.5px solid #047857; border-bottom: 1px solid #cbd5e1; background: #ffffff; font-weight: bold; font-size: 8px; }
        
        .col-student { width: 27%; text-align: left; }
        .col-grade { width: 11%; text-align: left; }
        .col-money { width: 13%; text-align: right; }
        .col-applied { width: 13%; text-align: right; }
        .col-status { width: 10%; text-align: center; }
        .breakdown-table td.col-money, .breakdown-table td.col-applied, .breakdown-table tfoot td { white-space: nowrap; }

        .student-id { color: #64748b; font-size: 7px; display: block; margin-top: 1px; }

        .summary-wrap { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .summary-wrap td { vertical-align: top; }
        .summary-table { width: 330px; border-collapse: collapse; }
        .summary-table td { padding: 2.5px 0; font-size: 8px; }
        .sum-lbl { color: #475569; width: 180px; }
        .sum-val { text-align: right; color: #0f172a; font-weight: bold; }
        .sum-status-row { border-top: 1px solid #e2e8f0; }

        .footer-table { width: 100%; margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 10px; border-collapse: collapse; }
        .footer-table td { vertical-align: top; }
        .notice-text { color: #64748b; font-size: 7px; line-height: 1.45; }
        .meta-footer { margin-top: 4px; color: #94a3b8; font-size: 6.5px; }
        .sign-cell { width: 180px; text-align: center; }
        .sign-line { border-bottom: 1px solid #0f172a; height: 26px; margin-bottom: 3px; }
        .sign-name { font-size: 7.5px; font-weight: bold; color: #0f172a; }
        .sign-title { font-size: 6.5px; color: #64748b; }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td class="seal-cell">
                @if(!empty($receiptData['logo_data']))
                    <img src="{{ $receiptData['logo_data'] }}" alt="AMIS Seal" class="seal-img">
                @endif
            </td>
            <td class="brand-cell">
                @if(!empty($receiptData['arabic_data']))
                    <img class="arabic-wordmark" src="{{ $receiptData['arabic_data'] }}" alt="المدرسة المنورة الإسلامية">
                @else
                    <div class="arabic-text">المدرسة المنورة الإسلامية</div>
                @endif
                <div class="school-name">AL MUNAWWARA ISLAMIC SCHOOL</div>
                <div class="school-address">Don Julian Rodriguez Avenue, Ma-a, Davao City</div>
                <div class="receipt-title">FAMILY PAYMENT RECEIPT</div>
            </td>
            <td class="receipt-meta-cell">
                <div class="meta-line"><span class="meta-label">Receipt No.:</span> <span class="meta-val-primary">{{ $receiptData['receipt_number'] }}</span></div>
                <div class="meta-line"><span class="meta-label">Billing Period:</span> <span class="meta-val-primary">{{ $receiptData['billing_month'] ?? 'Current Billing' }}</span></div>
                <div class="meta-line"><span class="meta-label">Status:</span> <span class="meta-val-primary">{{ (($receiptData['remaining_balance'] ?? 0) <= 0.01) ? 'Fully Paid' : 'Partially Paid' }}</span></div>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <!-- Payer & Payment Details -->
    <table class="info-table">
        <tr>
            <td style="width: 50%; padding-right: 15px;">
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Parent / Guardian:</td>
                        <td class="kv-value">{{ $receiptData['parent_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Account / Email:</td>
                        <td class="kv-value">{{ $receiptData['family_email'] ?? ($receiptData['email'] ?? 'Registered Family Account') }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Enrolled Students:</td>
                        <td class="kv-value">{{ count($receiptData['rows'] ?? []) }} {{ count($receiptData['rows'] ?? []) === 1 ? 'Student' : 'Students' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 15px;">
                <table class="kv-table">
                    <tr>
                        <td class="kv-label">Date:</td>
                        <td class="kv-value">{{ $receiptData['date'] }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Payment Method:</td>
                        <td class="kv-value">{{ $receiptData['payment_method'] }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Reference No.:</td>
                        <td class="kv-value">{{ $receiptData['reference_number'] ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="kv-label">Issued By:</td>
                        <td class="kv-value">{{ $receiptData['cashier'] ?? 'AMIS Finance Cashier' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="section-divider"></div>

    <!-- Student Payment Breakdown Table (7 Columns) -->
    <div class="section-heading">Student Payment Details ({{ $receiptData['billing_month'] ?? 'All' }})</div>
    <table class="breakdown-table">
        <thead>
            <tr>
                <th class="col-student">Student</th>
                <th class="col-grade">Grade</th>
                <th class="col-money">Amount Due</th>
                <th class="col-applied">Applied</th>
                <th class="col-money">Total Paid</th>
                <th class="col-money">Balance</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAssessment = 0;
                $totalApplied = 0;
                $totalCumulative = 0;
                $totalBalance = 0;
            @endphp
            @forelse($receiptData['rows'] as $row)
                @php
                    $due = (float)($row['amount_due'] ?? $row['monthly_due'] ?? 0);
                    $applied = (float)($row['applied_this_transaction'] ?? $row['applied_amount'] ?? $row['amount_paid'] ?? 0);
                    $cumPaid = (float)($row['total_paid_to_date'] ?? ($row['amount_paid'] ?? ($applied + ($row['previous_paid'] ?? 0))));
                    $rem = max(0.0, round((float)($row['remaining'] ?? ($due - $cumPaid)), 2));
                    
                    $totalAssessment += $due;
                    $totalApplied += $applied;
                    $totalCumulative += $cumPaid;
                    $totalBalance += $rem;

                    $statusText = match(true) {
                        $rem <= 0.01 => 'PAID',
                        $applied > 0.01 || $cumPaid > 0.01 => 'PARTIAL',
                        default => 'UNPAID',
                    };
                @endphp
                <tr>
                    <td class="col-student">
                        <strong>{{ $row['student_name'] }}</strong>
                        @if(!empty($row['student_id']))
                            <span class="student-id">{{ $row['student_id'] }}</span>
                        @endif
                    </td>
                    <td class="col-grade">{{ $row['grade_level'] }}</td>
                    <td class="col-money">₱{{ number_format($due, 2) }}</td>
                    <td class="col-applied" style="font-weight: bold; color: #047857;">₱{{ number_format($applied, 2) }}</td>
                    <td class="col-money">₱{{ number_format($cumPaid, 2) }}</td>
                    <td class="col-money" style="font-weight: bold;">₱{{ number_format($rem, 2) }}</td>
                    <td class="col-status">
                        <strong>{{ $statusText }}</strong>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding: 12px; color: #64748b;">No active student assessment rows for this period. Payment recorded as advance credit.</td></tr>
            @endforelse
        </tbody>
        @if(count($receiptData['rows'] ?? []) > 0)
            @php
                $monthlyAppliedTotal = $totalApplied > 0.001 ? $totalApplied : (float)($receiptData['payment_applied_this_transaction'] ?? $receiptData['amount_applied'] ?? 0);
            @endphp
            <tfoot>
                <tr>
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['total_amount_due'] ?? $totalAssessment), 2) }}</td>
                    <td class="col-applied" style="color: #047857;">₱{{ number_format($monthlyAppliedTotal, 2) }}</td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totalCumulative), 2) }}</td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totalBalance), 2) }}</td>
                    <td class="col-status"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Monthly Summary Section (Single Clean Section) -->
    <div class="section-heading">Monthly Summary — {{ $receiptData['billing_month'] ?? 'Billing Month' }}</div>
    <table class="summary-wrap">
        <tr>
            <td style="width: 60%;">
                <table class="summary-table">
                    @if(isset($receiptData['previous_balance']) && (float)$receiptData['previous_balance'] > 0.001)
                        <tr>
                            <td class="sum-lbl">Previous Balance:</td>
                            <td class="sum-val">₱{{ number_format((float)$receiptData['previous_balance'], 2) }}</td>
                        </tr>
                    @endif
                    @if((float)($receiptData['credit_applied'] ?? 0) > 0.001)
                        <tr>
                            <td class="sum-lbl">Credit Applied:</td>
                            <td class="sum-val" style="color: #b45309;">−₱{{ number_format((float)$receiptData['credit_applied'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-lbl">Balance After Credit:</td>
                            <td class="sum-val">₱{{ number_format((float)($receiptData['previous_remaining_balance'] ?? $receiptData['previous_balance']), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-lbl">Current Payment Received:</td>
                            <td class="sum-val">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-lbl">Current Payment Applied:</td>
                            <td class="sum-val" style="color: #047857;">₱{{ number_format($monthlyAppliedTotal, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="sum-lbl">Current Payment Received:</td>
                            <td class="sum-val">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-lbl">Applied to {{ $receiptData['billing_month'] ?? 'Billing Month' }}:</td>
                            <td class="sum-val" style="color: #047857;">₱{{ number_format($monthlyAppliedTotal, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="sum-lbl">Total Paid to Date:</td>
                        <td class="sum-val">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totalCumulative), 2) }}</td>
                    </tr>
                    <tr>
                        <td class="sum-lbl">Remaining {{ $receiptData['billing_month'] ?? 'Month' }} Balance:</td>
                        <td class="sum-val" style="font-weight: bold;">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totalBalance), 2) }}</td>
                    </tr>
                    @php
                        $amountReceived = (float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal);
                        $excessMonth = max(0, round($amountReceived - $monthlyAppliedTotal, 2));
                        $creditVal = (float)($receiptData['credit_created'] ?? $receiptData['credit_balance'] ?? 0);
                    @endphp
                    @if($creditVal > 0.001)
                        <tr>
                            <td class="sum-lbl">Credit Balance:</td>
                            <td class="sum-val" style="color: #047857; font-weight: bold;">₱{{ number_format($creditVal, 2) }}</td>
                        </tr>
                    @elseif($excessMonth > 0.001)
                        <tr>
                            <td class="sum-lbl">Carried to Next Month:</td>
                            <td class="sum-val" style="color: #047857; font-weight: bold;">₱{{ number_format($excessMonth, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="sum-status-row">
                        <td class="sum-lbl" style="font-weight: bold; padding-top: 5px;">Payment Status:</td>
                        <td class="sum-val" style="font-weight: bold; padding-top: 5px; color: {{ ($receiptData['remaining_balance'] ?? $totalBalance) <= 0.01 ? '#047857' : '#b45309' }};">
                            {{ ($receiptData['remaining_balance'] ?? $totalBalance) <= 0.01 ? 'FULLY PAID' : 'PARTIALLY PAID' }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%;"></td>
        </tr>
    </table>

    <!-- Footer & Authentication -->
    <table class="footer-table">
        <tr>
            <td style="width: 65%;">
                <div class="notice-text">
                    This is a system-generated Family Payment Receipt. No manual signature is required.<br>
                    If you notice any incorrect payment details, student information, payment allocation, credit balance, or remaining balance on this receipt, please contact <strong>AMIS Finance Support</strong>.
                </div>
                <div class="meta-footer">
                    Date Generated: {{ $receiptData['generated_at'] ?? now()->format('F d, Y · h:i A') }} · Receipt ID: {{ $receiptData['receipt_number'] }}
                </div>
            </td>
            <td class="sign-cell">
                <div class="sign-line"></div>
                <div class="sign-name">AMIS FINANCE CASHIER</div>
                <div class="sign-title">Authorized Finance Representative</div>
            </td>
        </tr>
    </table>

</body>
</html>
