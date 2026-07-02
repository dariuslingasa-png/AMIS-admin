<?php

// Public enrollment routes stay outside this admin app.
// Admin portal routes are isolated in routes/admin.php.

use App\Http\Controllers\FacebookBotController;
use App\Http\Controllers\PublicVerificationController;

Route::get('/messenger/webhook', [FacebookBotController::class, 'verify']);
Route::post('/messenger/webhook', [FacebookBotController::class, 'handle']);
Route::get('/messenger/setup', [FacebookBotController::class, 'setupMessengerProfile']);

// Public Student QR Code verification portal
Route::get('/verify/student/{student_number}', [PublicVerificationController::class, 'verifyStudent'])->name('public.student.verify');
Route::get('/v/{student_number}', [PublicVerificationController::class, 'verifyStudent'])->name('public.student.verify.short');

