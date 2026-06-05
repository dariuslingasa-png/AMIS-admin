<?php

use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentAccountLinkController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentPaymentController;
use Illuminate\Support\Facades\Route;

// Redirect home to dashboard
Route::get('/', function () {
    return redirect()->route('student.dashboard');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showLogin'])->name('student.login');
    Route::post('/login', [StudentAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('student.login.store');
    Route::get('/login/google/redirect', [StudentAuthController::class, 'redirectGoogle'])->name('student.login.google.redirect');
    Route::get('/login/google/callback', [StudentAuthController::class, 'callbackGoogle'])->name('student.login.google.callback');
});

// Authenticated Student routes
Route::middleware(['auth', 'student'])->group(function () {
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('student.logout');
    Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/announcements', [StudentPortalController::class, 'announcements'])->name('student.announcements');
    Route::get('/subjects', [StudentPortalController::class, 'subjects'])->name('student.subjects');
    Route::get('/grades', [StudentPortalController::class, 'grades'])->name('student.grades');
    Route::get('/profile', [StudentPortalController::class, 'profile'])->name('student.profile');
    Route::get('/settings', [StudentPortalController::class, 'settings'])->name('student.settings');
    Route::get('/settings/google/redirect', [StudentAccountLinkController::class, 'redirectGoogle'])->name('student.settings.google.redirect');
    Route::get('/settings/google/callback', [StudentAccountLinkController::class, 'callbackGoogle'])->name('student.settings.google.callback');
    Route::delete('/settings/google', [StudentAccountLinkController::class, 'unlinkGoogle'])->name('student.settings.google.unlink');
    Route::get('/schedule', [StudentPortalController::class, 'schedule'])->name('student.schedule');
    Route::get('/billing', [StudentPaymentController::class, 'billing'])->name('student.billing');
    Route::get('/payment-history', [StudentPaymentController::class, 'history'])->name('student.payments.history');
    Route::post('/billing/pay', [StudentPaymentController::class, 'submitPayment'])->name('student.billing.pay');
});
