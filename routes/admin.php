<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentAnalyticsController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\RequirementController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDiscountSettingsController;
use App\Http\Controllers\AdminAcademicController;
use App\Http\Controllers\AdminMsSyncController;
use App\Http\Controllers\AdminMsTeamsController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminSoaController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'));

Route::name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

    Route::post('/logout', [AdminAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::get('/auth/microsoft', [AdminAuthController::class, 'microsoftRedirect'])
        ->middleware('throttle:5,1')
        ->name('microsoft.redirect');
    Route::get('/auth/microsoft/callback', [AdminAuthController::class, 'microsoftCallback'])
        ->middleware('throttle:10,1')
        ->name('microsoft.callback');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [EnrollmentController::class, 'index'])->name('index');
            Route::get('/analytics', [EnrollmentAnalyticsController::class, 'analytics'])->name('analytics');
            Route::get('/reports', [EnrollmentAnalyticsController::class, 'reports'])->name('reports');
            Route::get('/reports/export', [EnrollmentAnalyticsController::class, 'export'])->name('reports.export');
        });

        Route::prefix('applications')->name('applications.')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.applications.dashboard'))->name('index');
            Route::get('/dashboard', [ApplicantController::class, 'dashboard'])->name('dashboard');
            Route::get('/enrollment', [ApplicantController::class, 'enrollment'])->name('enrollment');
            Route::get('/review', [ApplicantController::class, 'review'])->name('review');
            Route::get('/requirements', [ApplicantController::class, 'requirements'])->name('requirements');
            Route::get('/approval-workflow', [ApplicantController::class, 'approval'])->name('approval');
        });

        Route::prefix('applicants')->name('applicants.')->group(function () {
            Route::get('/', [ApplicantController::class, 'index'])->name('index');
            Route::get('/{applicant}', [ApplicantController::class, 'show'])->name('show');
            Route::patch('/{applicant}/status', [ApprovalController::class, 'updateStatus'])->name('status');
            Route::patch('/{applicant}/document', [RequirementController::class, 'update'])->name('document');
            Route::patch('/{applicant}/discount', [ApplicantController::class, 'updateDiscount'])->name('discount');
            Route::post('/{applicant}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('/{applicant}/approve-family', [ApprovalController::class, 'approveFamily'])->name('approve-family');
        });

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/dashboard', [AdminStudentController::class, 'dashboard'])->name('students.dashboard');
        Route::get('/students/dashboard/sections/{section}/roster-print', [AdminStudentController::class, 'rosterPrint'])->name('students.roster-print');
        Route::get('/students/history', [AdminStudentController::class, 'history'])->name('students.history');
        Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/resend', [AdminStudentController::class, 'resendCredentials'])->name('students.resend');

        Route::get('/soa', [AdminSoaController::class, 'index'])->name('soa.index');
        Route::get('/soa/{account}', [AdminSoaController::class, 'show'])->name('soa.show');
        Route::patch('/soa-payments/{payment}/verify', [AdminSoaController::class, 'verifyPayment'])->name('soa.payments.verify');
        Route::patch('/soa-payments/{payment}/reject', [AdminSoaController::class, 'rejectPayment'])->name('soa.payments.reject');
        Route::post('/soa/{account}/payments', [AdminSoaController::class, 'addPayment'])->name('soa.payments.add');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('/finance', [AdminPaymentController::class, 'dashboard'])->name('finance.dashboard');
        Route::get('/finance/fees', [AdminPaymentController::class, 'fees'])->name('finance.fees');
        Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
        Route::patch('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
        Route::patch('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');

        Route::get('/finance/fees-manage', [\App\Http\Controllers\AdminFinanceController::class, 'feesIndex'])->name('finance.fees-manage');
        Route::post('/finance/fees-manage', [\App\Http\Controllers\AdminFinanceController::class, 'feesStore'])->name('finance.fees-manage.store');
        Route::delete('/finance/fees-manage/{fee}', [\App\Http\Controllers\AdminFinanceController::class, 'feesDestroy'])->name('finance.fees-manage.destroy');
        Route::post('/finance/soa/{account}/adjust', [\App\Http\Controllers\AdminFinanceController::class, 'adjustFee'])->name('finance.soa.adjust');
        Route::get('/finance/export-soa', [\App\Http\Controllers\AdminFinanceController::class, 'exportSoa'])->name('finance.export-soa');
        Route::get('/finance/soa/{account}/export-family', [\App\Http\Controllers\AdminFinanceController::class, 'exportFamilyPayments'])->name('finance.export-family');
        Route::get('/finance/receipt/{payment}', [\App\Http\Controllers\AdminFinanceController::class, 'printReceipt'])->name('finance.receipt');
        Route::get('/finance/aging-report', [\App\Http\Controllers\AdminFinanceController::class, 'agingReport'])->name('finance.aging-report');
        Route::post('/finance/soa/{account}/reminder', [\App\Http\Controllers\AdminFinanceController::class, 'sendReminder'])->name('finance.send-reminder');

        Route::get('/ms-sync', [AdminMsSyncController::class, 'index'])->name('ms-sync.index');
        Route::post('/ms-sync/cleanup-test', [AdminMsSyncController::class, 'cleanupTestAccounts'])->name('ms-sync.cleanup-test');
        Route::post('/ms-sync/cleanup-portal', [AdminMsSyncController::class, 'cleanupPortalTestData'])->name('ms-sync.cleanup-portal');
        Route::post('/ms-sync/fix-guests', [AdminMsSyncController::class, 'fixGuests'])->name('ms-sync.fix-guests');
        Route::post('/ms-sync/retry-failed', [AdminMsSyncController::class, 'retryFailed'])->name('ms-sync.retry-failed');
        Route::post('/ms-sync/import-all', [AdminMsSyncController::class, 'importAll'])->name('ms-sync.import-all');
        Route::post('/ms-sync/import', [AdminMsSyncController::class, 'importFromAzure'])->name('ms-sync.import');
        Route::post('/ms-sync/delete-azure', [AdminMsSyncController::class, 'deleteFromAzure'])->name('ms-sync.delete-azure');
        Route::post('/ms-sync/students/{student}', [AdminMsSyncController::class, 'syncStudent'])->name('ms-sync.student');

        Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
        Route::get('/admins/audit-logs', [AdminUserController::class, 'auditLogs'])->name('admins.audit-logs');
        Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');
        Route::get('/admins/{user}/edit', [AdminUserController::class, 'edit'])->name('admins.edit');
        Route::patch('/admins/{user}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::patch('/admins/{user}/role', [AdminUserController::class, 'updateRole'])->name('admins.role');
        Route::patch('/admins/{user}/access', [AdminUserController::class, 'updateAccess'])->name('admins.access');
        Route::delete('/admins/{user}', [AdminUserController::class, 'destroy'])->name('admins.destroy');

        Route::get('/settings/discounts', [AdminDiscountSettingsController::class, 'edit'])->name('settings.discounts');
        Route::patch('/settings/discounts', [AdminDiscountSettingsController::class, 'update'])->name('settings.discounts.update');

        Route::prefix('academic')->name('academic.')->group(function () {
            Route::get('/', [AdminAcademicController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard', [AdminAcademicController::class, 'dashboard'])->name('dashboard.index');
            Route::get('/subjects', [AdminAcademicController::class, 'subjects'])->name('subjects');
            Route::get('/curriculum', [AdminAcademicController::class, 'curriculum'])->name('curriculum');
            Route::get('/grade-levels', [AdminAcademicController::class, 'curriculum'])->name('grade-levels');
            Route::get('/teachers', [AdminAcademicController::class, 'teachers'])->name('teachers');
            Route::get('/schedules', [AdminAcademicController::class, 'schedules'])->name('schedules');
            Route::get('/school-years', [AdminAcademicController::class, 'schoolYears'])->name('school-years');
            Route::get('/calendar', [AdminAcademicController::class, 'calendar'])->name('calendar');
            Route::get('/operations', [AdminAcademicController::class, 'operations'])->name('operations');
        });

        Route::prefix('ms-teams')->name('ms-teams.')->group(function () {
            Route::get('/', [AdminMsTeamsController::class, 'index'])->name('index');
            Route::post('/', [AdminMsTeamsController::class, 'store'])->name('store');
            Route::post('/store-single', [AdminMsTeamsController::class, 'storeSingle'])->name('store-single');
            Route::post('/fix-admin-access', [AdminMsTeamsController::class, 'fixAdminAccess'])->name('fix-access');
            Route::post('/fix-team-ownership', [AdminMsTeamsController::class, 'fixTeamOwnership'])->name('fix-ownership');
            Route::post('/fix-guest-students', [AdminMsTeamsController::class, 'fixGuestStudents'])->name('fix-guests');
            Route::post('/students/{student}/enroll', [AdminMsTeamsController::class, 'enrollStudent'])->name('enroll');
            Route::patch('/subjects/{subject}', [AdminMsTeamsController::class, 'updateSubject'])->name('subjects.update');
            Route::post('/subjects/{subject}/update', [AdminMsTeamsController::class, 'updateSubject'])->name('subjects.update-post');
            Route::delete('/subjects/{subject}', [AdminMsTeamsController::class, 'destroySubject'])->name('subjects.destroy');
            Route::post('/subjects/{subject}/invite-teacher', [AdminMsTeamsController::class, 'inviteTeacher'])->name('subjects.invite-teacher');
            Route::get('/{section}', [AdminMsTeamsController::class, 'show'])->name('show');
            Route::patch('/{section}', [AdminMsTeamsController::class, 'update'])->name('update');
            Route::post('/{section}/update', [AdminMsTeamsController::class, 'update'])->name('update-post');
            Route::delete('/{section}', [AdminMsTeamsController::class, 'destroy'])->name('destroy');
            Route::post('/{section}/subjects', [AdminMsTeamsController::class, 'storeSubject'])->name('subjects.store');
            Route::post('/{section}/retry-team', [AdminMsTeamsController::class, 'retryTeam'])->name('retry-team');
        });
    });
});
