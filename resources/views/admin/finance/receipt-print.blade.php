@php
    $studentName = $applicant?->full_name ?? 'Student';
    $studentNumber = $account?->student?->student_number ?? '-';
    $gradeLevel = $account?->grade_level ?? '-';
    $schoolYear = $account?->school_year ?? config('services.school.year');
    $orNumber = $payment->or_number ?? '-';
    $method = strtoupper($payment->method ?? '-');
    $referenceNo = $payment->reference_no ?? '-';
    $amount = (float) $payment->amount;
    $paidAt = $payment->paid_at?->format('F d, Y') ?? now()->format('F d, Y');
    $remarks = $payment->remarks ?? 'Tuition Fee';
    $checkedBy = $payment->checked_by ?? config('services.school.finance_checked_by', 'Finance Office');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Official Receipt — {{ $orNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #f3f4f6; padding: 40px 20px; color: #1f2937; }
        .receipt-container { max-width: 600px; margin: 0 auto; background: white; border: 2px solid #d1d5db; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .receipt-header { background: linear-gradient(135deg, #059669, #047857); padding: 24px 32px; text-align: center; color: white; }
        .receipt-header h1 { font-size: 18px; font-weight: 900; letter-spacing: 0.05em; text-transform: uppercase; }
        .receipt-header p { font-size: 12px; margin-top: 4px; opacity: 0.85; }
        .receipt-body { padding: 32px; }
        .receipt-title { text-align: center; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: #059669; margin-bottom: 24px; border-bottom: 2px solid #d1fae5; padding-bottom: 12px; }
        .receipt-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .receipt-row .label { color: #6b7280; font-weight: 600; }
        .receipt-row .value { font-weight: 700; color: #111827; text-align: right; }
        .receipt-amount { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin: 24px 0; text-align: center; }
        .receipt-amount .label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #059669; }
        .receipt-amount .value { font-size: 28px; font-weight: 900; color: #059669; margin-top: 4px; }
        .receipt-footer { padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #9ca3af; }
        .print-btn { display: block; margin: 20px auto; padding: 12px 32px; background: #059669; color: white; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; }
        .print-btn:hover { background: #047857; }
        @media print {
            body { background: white; padding: 0; }
            .receipt-container { border: none; box-shadow: none; border-radius: 0; }
            .print-btn, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print Receipt</button>

    <div class="receipt-container">
        <div class="receipt-header">
            <h1>Al Munawwara Islamic School</h1>
            <p>Official Receipt — SY {{ $schoolYear }}</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-title">Official Receipt</div>

            <div class="receipt-row">
                <span class="label">OR Number</span>
                <span class="value">{{ $orNumber }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Date</span>
                <span class="value">{{ $paidAt }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Student Name</span>
                <span class="value">{{ $studentName }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Student Number</span>
                <span class="value">{{ $studentNumber }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Grade Level</span>
                <span class="value">{{ $gradeLevel }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Payment Method</span>
                <span class="value">{{ $method }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Reference No.</span>
                <span class="value">{{ $referenceNo }}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Purpose</span>
                <span class="value">{{ $remarks }}</span>
            </div>

            <div class="receipt-amount">
                <div class="label">Amount Paid</div>
                <div class="value">PHP {{ number_format($amount, 2) }}</div>
            </div>

            <div class="receipt-row" style="border-bottom: none; margin-top: 16px;">
                <span class="label">Checked By</span>
                <span class="value">{{ $checkedBy }}</span>
            </div>
        </div>

        <div class="receipt-footer">
            <p>Al Munawwara Islamic School &bull; {{ config('services.school.address', 'Bugac Ma-a Road, Davao City') }}</p>
            <p style="margin-top: 4px;">&copy; {{ date('Y') }} All rights reserved.</p>
        </div>
    </div>

    <p class="no-print" style="text-align: center; margin-top: 16px; font-size: 12px; color: #9ca3af;">
        <a href="{{ url()->previous() }}" style="color: #059669; font-weight: 700;">← Back to SOA</a>
    </p>
</body>
</html>
