<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 9.5px; line-height: 1.45; }
        .header { width: 100%; padding-bottom: 14px; border-bottom: 2px solid #047857; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .seal { width: 82px; }
        .seal img { display: block; width: 72px; height: 72px; object-fit: contain; }
        .brand { padding-left: 4px; }
        .arabic-img { display: block; width: 210px; height: auto; margin-bottom: 1px; }
        .school { color: #047857; font-size: 10px; font-weight: bold; letter-spacing: .55px; }
        .title { margin-top: 4px; color: #0f172a; font-size: 22px; font-weight: bold; letter-spacing: .35px; }
        .receipt-number { width: 220px; text-align: right; }
        .receipt-number .label { display: block; color: #64748b; font-size: 8px; text-transform: uppercase; }
        .receipt-number strong { display: block; margin-top: 2px; color: #065f46; font-size: 13px; }
        .receipt-number .month-tag { display: block; margin-top: 3px; color: #047857; font-size: 10px; font-weight: bold; }
        .details { width: 100%; margin-top: 15px; border: 1px solid #dbe5e1; border-collapse: collapse; }
        .details td { width: 50%; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .details tr:last-child td { border-bottom: 0; }
        .details td:nth-child(2) { border-left: 1px solid #e2e8f0; }
        .label { color: #64748b; font-size: 7.5px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }
        .value { display: block; margin-top: 2px; color: #0f172a; font-size: 10px; font-weight: bold; }
        h2 { margin: 17px 0 7px; color: #0f172a; font-size: 12px; }
        .student-table { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; table-layout: fixed; }
        .student-table th { padding: 7px 5px; background: #047857; color: #ffffff; font-size: 7px; letter-spacing: .2px; text-align: left; text-transform: uppercase; }
        .student-table td { padding: 8px 5px; border-top: 1px solid #e2e8f0; vertical-align: middle; }
        .student-table tbody tr:nth-child(even) { background: #f8fafc; }
        .student-table tfoot td { padding: 8px 5px; border-top: 2px solid #047857; background: #ecfdf5; font-weight: bold; }
        .student-table .student { width: 25%; }
        .student-table .grade { width: 13%; }
        .student-table .month { width: 18%; }
        .student-table .money { width: 14.66%; text-align: right; white-space: nowrap; }
        .student-name { font-weight: bold; }
        .summary { width: 100%; margin-top: 7px; border: 1px solid #a7f3d0; background: #ecfdf5; border-collapse: collapse; }
        .summary td { padding: 7px 10px; border-bottom: 1px solid #d1fae5; }
        .summary tr:last-child td { border-bottom: 0; }
        .summary .number { text-align: right; color: #065f46; font-size: 11px; font-weight: bold; }
        .summary .remaining .number { color: #b45309; font-size: 14px; }
        .summary .credit .number { color: #0369a1; font-size: 14px; }
        .allocation-list { width: 100%; border: 1px solid #dbe5e1; border-collapse: collapse; }
        .allocation-list td { padding: 7px 10px; border-top: 1px solid #e2e8f0; }
        .allocation-list tr:first-child td { border-top: 0; }
        .allocation-list .status { width: 50%; text-align: right; font-weight: bold; }
        .fully-paid { color: #047857; }
        .partially-paid { color: #b45309; }
        .unpaid { color: #be123c; }
        .payment-status { margin-top: 13px; padding: 10px 12px; border-left: 4px solid #047857; background: #ecfdf5; font-size: 11px; font-weight: bold; color: #065f46; }
        .generated { margin-top: 8px; color: #64748b; font-size: 8.5px; }
        .notice { margin-top: 14px; padding: 10px 12px; border: 1px solid #dbe5e1; border-radius: 6px; background: #f8fafc; color: #475569; font-size: 8.5px; line-height: 1.55; }
        .notice strong { color: #0f172a; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="seal">
                @if($receiptData['logo_data'])
                    <img src="{{ $receiptData['logo_data'] }}" alt="AMIS Logo">
                @endif
            </td>
            <td class="brand">
                @if($receiptData['arabic_data'])
                    <img class="arabic-img" src="{{ $receiptData['arabic_data'] }}" alt="المدرسة المنورة الإسلامية">
                @endif
                <div class="school">AL MUNAWWARA ISLAMIC SCHOOL</div>
                <div class="title">FAMILY PAYMENT RECEIPT</div>
            </td>
            <td class="receipt-number">
                <span class="label">Receipt No.</span>
                <strong>{{ $receiptData['receipt_number'] }}</strong>
                @if(!empty($receiptData['billing_month']))
                    <span class="label" style="margin-top: 4px;">Billing Month</span>
                    <span class="month-tag">{{ $receiptData['billing_month'] }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="details">
        <tr>
            <td><span class="label">Date</span><span class="value">{{ $receiptData['date'] }}</span></td>
            <td><span class="label">Parent / Guardian</span><span class="value">{{ $receiptData['parent_name'] }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Payment Method</span><span class="value">{{ $receiptData['payment_method'] }}</span></td>
            <td><span class="label">Reference No.</span><span class="value">{{ $receiptData['reference_number'] }}</span></td>
        </tr>
    </table>

    <h2>Student Payment Details ({{ $receiptData['billing_month'] ?? 'All' }})</h2>
    <table class="student-table">
        <thead>
            <tr>
                <th class="student">Student</th>
                <th class="grade">Grade Level</th>
                <th class="month">Billing Month</th>
                <th class="money">Amount Due</th>
                <th class="money">Total Paid to Date</th>
                <th class="money">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receiptData['rows'] as $row)
                <tr>
                    <td class="student"><span class="student-name">{{ $row['student_name'] }}</span></td>
                    <td class="grade">{{ $row['grade_level'] }}</td>
                    <td class="month">{{ $row['billing_month'] }}</td>
                    <td class="money">₱{{ number_format($row['amount_due'], 2) }}</td>
                    <td class="money">₱{{ number_format($row['amount_paid'], 2) }}</td>
                    <td class="money">₱{{ number_format($row['remaining'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No open billing row was available. The approved amount was recorded as family advance credit.</td></tr>
            @endforelse
        </tbody>
        @if(collect($receiptData['rows'])->isNotEmpty())
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td><strong>TOTAL</strong></td>
                    <td class="money">₱{{ number_format($receiptData['total_amount_due'], 2) }}</td>
                    <td class="money">₱{{ number_format($receiptData['total_paid_to_date'], 2) }}</td>
                    <td class="money">₱{{ number_format($receiptData['remaining_balance'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>Monthly Summary — {{ $receiptData['billing_month'] ?? 'Billing Month' }}</h2>
    <table class="summary">
        @if(!empty($receiptData['previous_month_label']))
            <tr><td>Previous Balance — {{ $receiptData['previous_month_label'] }}</td><td class="number">₱{{ number_format($receiptData['previous_balance'], 2) }}</td></tr>
        @else
            <tr><td>Previous Balance</td><td class="number">₱{{ number_format($receiptData['previous_balance'] ?? 0, 2) }}</td></tr>
        @endif
        <tr><td>Monthly Charge</td><td class="number">₱{{ number_format($receiptData['total_amount_due'], 2) }}</td></tr>
        <tr><td>Payment Received (Transaction)</td><td class="number">₱{{ number_format($receiptData['amount_received'], 2) }}</td></tr>
        <tr><td>Payment Applied ({{ $receiptData['billing_month'] ?? 'Month' }})</td><td class="number">₱{{ number_format($receiptData['amount_applied'], 2) }}</td></tr>
        <tr class="remaining"><td>Remaining {{ $receiptData['billing_month'] ?? 'Month' }} Balance</td><td class="number">₱{{ number_format($receiptData['remaining_balance'], 2) }}</td></tr>
        @if(($receiptData['credit_created'] ?? 0) > 0.01)
            <tr class="credit"><td>Family Advance Credit Created</td><td class="number">₱{{ number_format($receiptData['credit_created'], 2) }}</td></tr>
        @endif
    </table>

    <h2>Payment Allocation</h2>
    <table class="allocation-list">
        @forelse($receiptData['rows'] as $row)
            @php
                $statusClass = match($row['status']) {
                    'FULLY PAID' => 'fully-paid',
                    'PARTIALLY PAID' => 'partially-paid',
                    default => 'unpaid',
                };
                $statusText = match($row['status']) {
                    'FULLY PAID' => 'Fully Paid',
                    'PARTIALLY PAID' => 'Partially Paid, ₱'.number_format($row['remaining'], 2).' remaining',
                    default => 'Unpaid, ₱'.number_format($row['remaining'], 2).' remaining',
                };
            @endphp
            <tr>
                <td>{{ $row['student_name'] }} — {{ $row['billing_month'] }}</td>
                <td class="status {{ $statusClass }}">{{ $statusText }}</td>
            </tr>
        @empty
            <tr><td>Approved as family advance credit.</td><td class="status fully-paid">Recorded</td></tr>
        @endforelse
    </table>

    <div class="payment-status">Status: {{ $receiptData['payment_status'] }}</div>
    <div class="generated">Date Generated: <strong>{{ $receiptData['generated_at'] }}</strong></div>

    <div class="notice">
        <strong>Notice:</strong><br>
        This is a system-generated Family Payment Receipt. No manual signature is required.<br><br>
        If you notice any incorrect payment details, student information, payment allocation, credit balance, or remaining balance on this receipt, please contact&nbsp;<strong>AMIS Support Staff</strong> for verification and correction.
    </div>
</body>
</html>
