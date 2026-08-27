<?php

declare(strict_types=1);

use App\Jobs\SendFinalPaymentAdvisoryJob;
use App\Models\MonthlyPaymentReminder;
use App\Services\Finance\MonthlyPaymentReminderService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$billingMonth = '2026-08';
$queueName = 'final-payment-advisory';
$families = app(MonthlyPaymentReminderService::class)->getFamiliesCollection($billingMonth);
$counts = [
    'unique_recipients' => $families->count(),
    'newly_queued' => 0,
    'already_sent' => 0,
    'already_queued' => 0,
    'invalid' => 0,
];

foreach ($families as $family) {
    $email = strtolower(trim((string) $family->email));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $counts['invalid']++;
        continue;
    }

    $reminder = MonthlyPaymentReminder::query()->firstOrCreate(
        [
            'billing_month' => $billingMonth,
            'parent_email' => $email,
            'reminder_type' => SendFinalPaymentAdvisoryJob::REMINDER_TYPE,
        ],
        [
            'family_id' => $family->family_id,
            'parent_name' => $family->parent_name,
            'student_names' => $family->student_names,
            'student_count' => $family->student_count,
            'total_balance' => $family->total_balance ?? 0,
            'status' => MonthlyPaymentReminder::STATUS_PENDING,
            'attempts' => 0,
        ],
    );

    if (! $reminder->wasRecentlyCreated) {
        if ($reminder->status === MonthlyPaymentReminder::STATUS_SENT) {
            $counts['already_sent']++;
        } else {
            $counts['already_queued']++;
        }
        continue;
    }

    SendFinalPaymentAdvisoryJob::dispatch($reminder->id)->onQueue($queueName);
    $counts['newly_queued']++;
}

echo json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
