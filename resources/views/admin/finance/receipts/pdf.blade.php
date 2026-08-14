<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official Payment Receipt - {{ $receiptData['receipt_number'] }}</title>
    <style>
        @page { margin: 18px 24px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 8.5px; line-height: 1.4; }
        
        .header-table { width: 100%; border-bottom: 2px solid #047857; padding-bottom: 10px; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .seal-cell { width: 65px; }
        .seal-img { width: 56px; height: 56px; object-fit: contain; }
        .brand-cell { padding-left: 6px; }
        .arabic-wordmark { display: block; width: 185px; height: auto; margin-bottom: 2px; }
        .school-name { color: #047857; font-size: 9.5px; font-weight: bold; letter-spacing: .5px; }
        .receipt-title { margin-top: 2px; color: #0f172a; font-size: 15px; font-weight: bold; letter-spacing: .3px; }
        .receipt-subtitle { color: #64748b; font-size: 7.5px; }
        
        .receipt-meta-cell { width: 220px; text-align: right; vertical-align: top; }
        .meta-card { display: inline-block; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; text-align: right; }
        .meta-label { color: #64748b; font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .meta-val-primary { color: #047857; font-size: 11px; font-weight: bold; }
        .meta-val-secondary { color: #0f172a; font-size: 8px; font-weight: bold; }
        .status-badge { display: inline-block; margin-top: 3px; padding: 2px 6px; border-radius: 3px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-partial { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

        .info-grid { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .info-grid td { width: 50%; vertical-align: top; }
        .info-card { border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; padding: 6px 9px; margin-right: 4px; }
        .info-card-right { border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; padding: 6px 9px; margin-left: 4px; }
        .card-header { color: #047857; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 5px; }
        .data-row { margin-bottom: 3px; }
        .data-label { color: #64748b; font-size: 7px; display: inline-block; width: 75px; }
        .data-value { color: #0f172a; font-size: 8px; font-weight: bold; }

        .section-heading { margin: 12px 0 5px; color: #0f172a; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        
        .breakdown-table { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; table-layout: fixed; }
        .breakdown-table th { padding: 5px 4px; background: #0f172a; color: #ffffff; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .breakdown-table td { padding: 5px 4px; border-top: 1px solid #e2e8f0; font-size: 7.5px; vertical-align: middle; }
        .breakdown-table tbody tr:nth-child(even) { background: #f8fafc; }
        .breakdown-table tfoot td { padding: 6px 4px; border-top: 2px solid #047857; background: #f0fdf4; font-weight: bold; font-size: 8px; }
        
        .col-student { width: 25%; }
        .col-grade { width: 11%; }
        .col-month { width: 14%; }
        .col-money { width: 10%; text-align: right; white-space: nowrap; }
        .col-status { width: 10%; text-align: center; }

        .tag-settled { color: #15803d; font-weight: bold; font-size: 6.5px; background: #dcfce7; padding: 1px 4px; border-radius: 2px; }
        .tag-partial { color: #b45309; font-weight: bold; font-size: 6.5px; background: #fef3c7; padding: 1px 4px; border-radius: 2px; }
        .tag-unpaid { color: #be123c; font-weight: bold; font-size: 6.5px; background: #ffe4e6; padding: 1px 4px; border-radius: 2px; }

        .summary-grid { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .summary-grid td { width: 50%; vertical-align: top; }
        .summary-box { border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff; padding: 7px 10px; margin-right: 4px; }
        .summary-box-right { border: 1px solid #86efac; border-radius: 4px; background: #f0fdf4; padding: 7px 10px; margin-left: 4px; }
        
        .sum-row { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .sum-row td { padding: 2px 0; }
        .sum-label { color: #475569; font-size: 7.5px; }
        .sum-val { text-align: right; color: #0f172a; font-size: 8px; font-weight: bold; }
        .sum-total-row { border-top: 1px solid #cbd5e1; margin-top: 3px; padding-top: 3px; }
        .sum-total-label { color: #047857; font-size: 8.5px; font-weight: bold; }
        .sum-total-val { text-align: right; color: #047857; font-size: 10px; font-weight: bold; }
        
        .rem-highlight-label { color: #0f172a; font-size: 8.5px; font-weight: bold; }
        .rem-highlight-val { text-align: right; color: #b45309; font-size: 10px; font-weight: bold; }

        .footer-table { width: 100%; margin-top: 14px; border-top: 1px dashed #cbd5e1; padding-top: 8px; border-collapse: collapse; }
        .footer-table td { vertical-align: top; }
        .notice-text { color: #64748b; font-size: 7px; line-height: 1.45; }
        .sign-cell { width: 170px; text-align: center; }
        .sign-line { border-bottom: 1px solid #0f172a; height: 28px; margin-bottom: 3px; }
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
                @endif
                <div class="school-name">AL MUNAWWARA ISLAMIC SCHOOL</div>
                <div class="receipt-title">FAMILY PAYMENT RECEIPT</div>
                <div class="receipt-subtitle">Finance Department · Student Accounts & Family Billing</div>
            </td>
            <td class="receipt-meta-cell">
                <div class="meta-card">
                    <div class="meta-label">Receipt No.</div>
                    <div class="meta-val-primary">{{ $receiptData['receipt_number'] }}</div>
                    <div class="meta-label" style="margin-top: 3px;">Billing Period</div>
                    <div class="meta-val-secondary">{{ $receiptData['billing_month'] ?? 'Current Billing' }}</div>
                    <div>
                        @if(($receiptData['remaining_balance'] ?? 0) <= 0.01)
                            <span class="status-badge badge-paid">● FULLY PAID</span>
                        @else
                            <span class="status-badge badge-partial">● PARTIALLY PAID</span>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Payer & Transaction Information Grid -->
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-card">
                    <div class="card-header">Parent / Guardian Information</div>
                    <div class="data-row"><span class="data-label">Parent / Guardian:</span><span class="data-value">{{ $receiptData['parent_name'] }}</span></div>
                    <div class="data-row"><span class="data-label">Account / Email:</span><span class="data-value">{{ $receiptData['family_email'] ?? ($receiptData['email'] ?? 'Registered Family Account') }}</span></div>
                    <div class="data-row"><span class="data-label">Enrolled Students:</span><span class="data-value">{{ count($receiptData['rows'] ?? []) }} Student(s) Included</span></div>
                </div>
            </td>
            <td>
                <div class="info-card-right">
                    <div class="card-header">Payment & Verification Details</div>
                    <div class="data-row"><span class="data-label">Date:</span><span class="data-value">{{ $receiptData['date'] }}</span></div>
                    <div class="data-row"><span class="data-label">Payment Method:</span><span class="data-value">{{ $receiptData['payment_method'] }}</span></div>
                    <div class="data-row"><span class="data-label">Reference No.:</span><span class="data-value">{{ $receiptData['reference_number'] }}</span></div>
                    <div class="data-row"><span class="data-label">Issued By:</span><span class="data-value">{{ $receiptData['cashier'] ?? 'AMIS Finance Cashier' }}</span></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Student Payment Breakdown Table -->
    <div class="section-heading">Student Payment Details ({{ $receiptData['billing_month'] ?? 'All' }})</div>
    <table class="breakdown-table">
        <thead>
            <tr>
                <th class="col-student">Student</th>
                <th class="col-grade">Grade Level</th>
                <th class="col-month">Billing Month</th>
                <th class="col-money">Amount Due</th>
                <th class="col-money">Prior Payments</th>
                <th class="col-money">Applied This Tx</th>
                <th class="col-money">Total Paid To Date</th>
                <th class="col-money">Balance</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAssessment = 0;
                $totalPrior = 0;
                $totalApplied = 0;
                $totalCumulative = 0;
                $totalBalance = 0;
            @endphp
            @forelse($receiptData['rows'] as $row)
                @php
                    $due = (float)($row['amount_due'] ?? $row['monthly_due'] ?? 0);
                    $applied = (float)($row['applied_this_transaction'] ?? $row['applied_amount'] ?? $row['amount_paid'] ?? 0);
                    $cumPaid = (float)($row['total_paid_to_date'] ?? ($row['amount_paid'] ?? ($applied + ($row['previous_paid'] ?? 0))));
                    $prior = max(0.0, round($cumPaid - $applied, 2));
                    $rem = max(0.0, round((float)($row['remaining'] ?? ($due - $cumPaid)), 2));
                    
                    $totalAssessment += $due;
                    $totalPrior += $prior;
                    $totalApplied += $applied;
                    $totalCumulative += $cumPaid;
                    $totalBalance += $rem;

                    $statusTag = match(true) {
                        $rem <= 0.01 => 'SETTLED',
                        $applied > 0.01 || $prior > 0.01 => 'PARTIAL',
                        default => 'UNPAID',
                    };
                @endphp
                <tr>
                    <td class="col-student"><strong>{{ $row['student_name'] }}</strong><br><span style="color:#64748b; font-size:6.5px;">{{ $row['student_id'] ?? '' }}</span></td>
                    <td class="col-grade">{{ $row['grade_level'] }}</td>
                    <td class="col-month">{{ $row['billing_month'] ?? ($receiptData['billing_month'] ?? '') }}</td>
                    <td class="col-money">₱{{ number_format($due, 2) }}</td>
                    <td class="col-money">₱{{ number_format($prior, 2) }}</td>
                    <td class="col-money" style="font-weight: bold; color: #047857;">₱{{ number_format($applied, 2) }}</td>
                    <td class="col-money">₱{{ number_format($cumPaid, 2) }}</td>
                    <td class="col-money" style="font-weight: bold;">₱{{ number_format($rem, 2) }}</td>
                    <td class="col-status">
                        @if($statusTag === 'SETTLED')
                            <span class="tag-settled">SETTLED</span>
                        @elseif($statusTag === 'PARTIAL')
                            <span class="tag-partial">PARTIAL</span>
                        @else
                            <span class="tag-unpaid">UNPAID</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding: 12px; color: #64748b;">No active student assessment rows for this period. Payment recorded as advance credit.</td></tr>
            @endforelse
        </tbody>
        @if(count($receiptData['rows'] ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="3"><strong>TOTAL</strong></td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['total_amount_due'] ?? $totalAssessment), 2) }}</td>
                    <td class="col-money">₱{{ number_format($totalPrior, 2) }}</td>
                    <td class="col-money" style="color: #047857;">₱{{ number_format((float)($receiptData['payment_applied_this_transaction'] ?? $receiptData['amount_applied'] ?? $totalApplied), 2) }}</td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totalCumulative), 2) }}</td>
                    <td class="col-money">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totalBalance), 2) }}</td>
                    <td class="col-status">
                        @if(($receiptData['remaining_balance'] ?? $totalBalance) <= 0.01)
                            <span class="tag-settled">SETTLED</span>
                        @else
                            <span class="tag-partial">PARTIAL</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Summary & Reconciliation Grid -->
    <div class="section-heading">Monthly Summary — {{ $receiptData['billing_month'] ?? 'Billing Month' }}</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-box">
                    <div class="card-header">Payment Distribution</div>
                    <table class="sum-row">
                        @if(isset($receiptData['previous_balance']) && (float)$receiptData['previous_balance'] > 0.001)
                            <tr><td class="sum-label">Previous Balance:</td><td class="sum-val">₱{{ number_format((float)$receiptData['previous_balance'], 2) }}</td></tr>
                        @endif
                        @if((float)($receiptData['credit_applied'] ?? 0) > 0.001)
                            <tr><td class="sum-label">Credit Applied:</td><td class="sum-val" style="color:#b45309;">−₱{{ number_format((float)$receiptData['credit_applied'], 2) }}</td></tr>
                            <tr><td class="sum-label">Balance After Credit:</td><td class="sum-val">₱{{ number_format((float)($receiptData['previous_remaining_balance'] ?? $receiptData['previous_balance']), 2) }}</td></tr>
                            <tr><td class="sum-label">Current Payment Received:</td><td class="sum-val">₱{{ number_format((float)$receiptData['amount_received'], 2) }}</td></tr>
                            <tr><td class="sum-label">Current Payment Applied:</td><td class="sum-val" style="color:#047857;">₱{{ number_format((float)$receiptData['amount_applied'], 2) }}</td></tr>
                        @else
                            <tr><td class="sum-label">Current Payment Received:</td><td class="sum-val">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $totalApplied), 2) }}</td></tr>
                            <tr><td class="sum-label">Current Payment Applied:</td><td class="sum-val" style="color:#047857;">₱{{ number_format((float)($receiptData['payment_applied_this_transaction'] ?? $receiptData['amount_applied'] ?? $totalApplied), 2) }}</td></tr>
                        @endif
                        @if((float)($receiptData['credit_created'] ?? 0) > 0.001)
                            <tr><td class="sum-label">Credit Balance:</td><td class="sum-val" style="color:#0284c7;">₱{{ number_format((float)$receiptData['credit_created'], 2) }}</td></tr>
                        @elseif((float)($receiptData['credit_balance'] ?? 0) > 0.001)
                            <tr><td class="sum-label">Credit Balance:</td><td class="sum-val" style="color:#0284c7;">₱{{ number_format((float)$receiptData['credit_balance'], 2) }}</td></tr>
                        @endif
                    </table>
                    <div class="sum-total-row">
                        <table style="width:100%;">
                            <tr><td class="sum-total-label">Total Paid to Date:</td><td class="sum-total-val">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totalCumulative), 2) }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
            <td>
                <div class="summary-box-right">
                    <div class="card-header" style="color:#047857;">Account Balance Status</div>
                    <table class="sum-row">
                        <tr>
                            <td class="sum-label">Remaining Balance for {{ $receiptData['billing_month'] ?? 'Month' }}:</td>
                            <td class="sum-val" style="{{ $totalBalance <= 0.01 ? 'color:#15803d;' : 'color:#b45309;' }}">
                                ₱{{ number_format($totalBalance, 2) }} {{ $totalBalance <= 0.01 ? '(SETTLED)' : '' }}
                            </td>
                        </tr>
                        @if(!empty($receiptData['future_balance']) && (float)$receiptData['future_balance'] > 0.01)
                            <tr><td class="sum-label">Future Scheduled Tuition:</td><td class="sum-val">₱{{ number_format((float)$receiptData['future_balance'], 2) }}</td></tr>
                        @endif
                    </table>
                    <div class="sum-total-row" style="border-top: 1px solid #86efac;">
                        <table style="width:100%;">
                            <tr><td class="rem-highlight-label">Remaining Balance:</td><td class="rem-highlight-val">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totalBalance), 2) }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer & Authentication -->
    <table class="footer-table">
        <tr>
            <td>
                <div class="notice-text">
                    <strong>Notice:</strong> This is a system-generated Family Payment Receipt. No manual signature is required.<br>
                    If you notice any incorrect payment details, student information, payment allocation, credit balance, or remaining balance on this receipt, please contact <strong>AMIS Support Staff</strong>.
                </div>
                <div style="margin-top: 4px; color: #94a3b8; font-size: 6.5px;">
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
