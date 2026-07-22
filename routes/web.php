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

// Standalone Selfie Verification Module (Localhost)
use App\Http\Controllers\SelfieVerificationController;
Route::get('/selfie-verification', [SelfieVerificationController::class, 'index'])->name('selfie.index');
Route::post('/selfie-verification/start', [SelfieVerificationController::class, 'start'])->name('selfie.start');
Route::get('/selfie-verification/session/{session_id}', [SelfieVerificationController::class, 'sessionView'])->name('selfie.session');
Route::get('/selfie-verification/status/{session_id}', [SelfieVerificationController::class, 'checkStatus'])->name('selfie.status');
Route::post('/selfie-verification/upload/{session_id}', [SelfieVerificationController::class, 'upload'])->name('selfie.upload');

