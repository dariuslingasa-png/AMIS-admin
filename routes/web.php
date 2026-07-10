<?php

use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\StudentScheduleController;
use App\Http\Controllers\StudentTeacherController;
use App\Http\Controllers\StudentEbookController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('student.login'));

// Under construction page
Route::get('/under-construction', fn() => view('under-construction'))->name('under-construction');

// Auth
Route::get('/login',  [StudentAuthController::class, 'showLogin'])->name('student.login');
Route::get('/login-redirect', fn() => redirect()->route('student.login'))->name('login');
Route::post('/login', [StudentAuthController::class, 'login'])->middleware('throttle:5,1')->name('student.login.store');
Route::post('/logout',[StudentAuthController::class, 'logout'])->name('student.logout');
Route::get('/auth/microsoft',          [StudentAuthController::class, 'redirectMicrosoft'])->name('student.microsoft.redirect');
Route::get('/auth/microsoft/callback', [StudentAuthController::class, 'callbackMicrosoft'])->name('student.microsoft.callback');

// Student protected routes
Route::middleware(['auth', 'student'])->group(function () {
    Route::post('/tester-override-section', function (\Illuminate\Http\Request $request) {
        if (auth()->check() && (auth()->user()->email === 'mon.lingasa@amis.edu.ph' || auth()->user()->username === '260000')) {
            $sectionId = $request->input('section_id');
            if (empty($sectionId)) {
                session()->forget('tester_override_section_id');
            } else {
                session(['tester_override_section_id' => (int) $sectionId]);
            }
        }
        return back();
    })->name('student.tester-override-section');

    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    Route::post('/dashboard/sync-teams', [StudentDashboardController::class, 'syncTeams'])->name('student.sync-teams');
    Route::get('/soa',       [StudentPaymentController::class, 'billing'])->name('student.billing');
    Route::post('/soa/pay',  [StudentPaymentController::class, 'submitPayment'])->name('student.billing.pay');
    Route::post('/soa/ocr-scan', [StudentPaymentController::class, 'ocrScan'])->name('student.billing.ocr');
    
    Route::get('/announcements', [StudentDashboardController::class, 'announcements'])->name('student.announcements');
    Route::get('/schedule',      [StudentScheduleController::class, 'schedule'])->name('student.schedule');
    Route::get('/teachers',      [StudentTeacherController::class, 'teachers'])->name('student.teachers');
    Route::get('/grades',        [StudentDashboardController::class, 'grades'])->name('student.grades');
    Route::get('/profile',       [StudentDashboardController::class, 'profile'])->name('student.profile');
    Route::get('/settings',      [StudentDashboardController::class, 'settings'])->name('student.settings');
    Route::get('/ebooks',        [StudentEbookController::class, 'ebooks'])->name('student.ebooks');
    Route::get('/ebooks/read/{id}', [StudentEbookController::class, 'readEbook'])->name('student.ebooks.read');
    Route::get('/ebooks/stream/{id}', [StudentEbookController::class, 'streamEbook'])->name('student.ebooks.stream');
});

// Chatbot api
Route::post('/api/chatbot', [ChatbotController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('api.chatbot');
