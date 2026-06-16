<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentAnalyticsController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\RequirementController;
use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\SecurityWorkspaceController;
use App\Http\Controllers\Admin\SystemManagementController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminClassScheduleController;
use App\Http\Controllers\AdminDiscountSettingsController;
use App\Http\Controllers\AdminEbookController;
use App\Http\Controllers\AdminAcademicController;
use App\Http\Controllers\AdminAcademicSubjectController;
use App\Http\Controllers\AdminAcademicTeacherController;
use App\Http\Controllers\AdminMsSyncController;
use App\Http\Controllers\AdminMsTeamsController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminSoaController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminStudentDashboardController;
use App\Http\Controllers\AdminStudentProcessController;
use App\Http\Controllers\AdminStudentAccountController;
use App\Http\Controllers\AdminStudentFamilyController;
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

    Route::post('/api/identity/login', [\App\Http\Controllers\IdentityRoutingController::class, 'login'])
        ->name('identity.login');
    Route::post('/api/identity/link', [\App\Http\Controllers\IdentityRoutingController::class, 'link'])
        ->middleware('auth')
        ->name('identity.link');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('ebook')->name('ebook.')->group(function () {
            Route::get('/', [AdminEbookController::class, 'index'])->name('index');
            Route::get('/create', [AdminEbookController::class, 'create'])->name('create');
            Route::post('/', [AdminEbookController::class, 'store'])->name('store');
            Route::get('/{ebook}/edit', [AdminEbookController::class, 'edit'])->name('edit');
            Route::put('/{ebook}', [AdminEbookController::class, 'update'])->name('update');
            Route::delete('/{ebook}', [AdminEbookController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [EnrollmentController::class, 'index'])->name('index');
            Route::get('/analytics', [EnrollmentAnalyticsController::class, 'analytics'])->name('analytics');
            Route::get('/reports', [EnrollmentAnalyticsController::class, 'reports'])->name('reports');
            Route::get('/reports/export', [EnrollmentAnalyticsController::class, 'export'])->name('reports.export');
            Route::get('/masters-list', [EnrollmentAnalyticsController::class, 'mastersList'])->name('masters-list');
            Route::get('/masters-list/export', [EnrollmentAnalyticsController::class, 'mastersListExport'])->name('masters-list.export');
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
            Route::post('/email-registry', [ApplicantController::class, 'emailRegistry'])->name('email-registry');
            Route::get('/{applicant}', [ApplicantController::class, 'show'])->name('show');
            Route::get('/{applicant}/review', [ApplicantController::class, 'reviewApplicant'])->name('review');
            Route::patch('/{applicant}/status', [ApprovalController::class, 'updateStatus'])->name('status');
            Route::patch('/{applicant}/document', [RequirementController::class, 'update'])->name('document');
            Route::patch('/{applicant}/discount', [ApplicantController::class, 'updateDiscount'])->name('discount');
            Route::post('/{applicant}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('/{applicant}/approve-family', [ApprovalController::class, 'approveFamily'])->name('approve-family');
            Route::post('/{applicant}/send-welcome', [ApprovalController::class, 'resendOnboardingInbox'])->name('send-welcome');
            Route::post('/{applicant}/resend-onboarding-inbox', [ApprovalController::class, 'resendOnboardingInbox'])->name('resend-onboarding-inbox');
            Route::post('/{applicant}/verify-section', [ApprovalController::class, 'verifySection'])->name('verify-section');
        });

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/dashboard', [AdminStudentDashboardController::class, 'dashboard'])->name('students.dashboard');
        Route::get('/students/dashboard/sections/{section}/roster-print', [AdminStudentDashboardController::class, 'rosterPrint'])->name('students.roster-print');
        Route::get('/students/history', [AdminStudentProcessController::class, 'history'])->name('students.history');
        Route::get('/students/accounts', [AdminStudentProcessController::class, 'accounts'])->name('students.accounts');
        Route::get('/students/documents', [AdminStudentProcessController::class, 'documents'])->name('students.documents');
        Route::get('/students/verification', [AdminStudentProcessController::class, 'verification'])->name('students.verification');
        Route::get('/students/promotions', [AdminStudentProcessController::class, 'promotions'])->name('students.promotions');
        Route::get('/students/occupancy', [AdminStudentDashboardController::class, 'occupancy'])->name('students.occupancy');
        Route::get('/students/occupancy/grade/{grade}/roster-print', [AdminStudentDashboardController::class, 'gradeRosterPrint'])->name('students.grade-roster-print');
        Route::get('/students/families', [AdminStudentFamilyController::class, 'families'])->name('students.families');
        Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/resend', [AdminStudentAccountController::class, 'resendCredentials'])->name('students.resend');
        Route::post('/students/{student}/status', [AdminStudentAccountController::class, 'updateStatus'])->name('students.update-status');
        Route::post('/students/{student}/update-email', [AdminStudentAccountController::class, 'updateEmail'])->name('students.update-email');
        Route::delete('/students/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');

        Route::get('/soa', [AdminSoaController::class, 'index'])->name('soa.index');
        Route::get('/soa/{account}', [AdminSoaController::class, 'show'])->name('soa.show');
        Route::patch('/soa-payments/{payment}/verify', [AdminSoaController::class, 'verifyPayment'])->name('soa.payments.verify');
        Route::patch('/soa-payments/{payment}/reject', [AdminSoaController::class, 'rejectPayment'])->name('soa.payments.reject');
        Route::post('/soa/{account}/payments', [AdminSoaController::class, 'addPayment'])->name('soa.payments.add');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('/finance', [AdminPaymentController::class, 'dashboard'])->name('finance.dashboard');
        Route::get('/finance/fees', [AdminPaymentController::class, 'fees'])->name('finance.fees');
        Route::get('/finance/masters-list', [\App\Http\Controllers\AdminFinanceMasterController::class, 'index'])->name('finance.masters-list');
        Route::patch('/finance/masters-list/{entry}', [\App\Http\Controllers\AdminFinanceMasterController::class, 'update'])->name('finance.masters-list.update');
        Route::get('/payments/receipt-file', [AdminPaymentController::class, 'viewReceiptFile'])->name('payments.receipt-file');
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
        Route::get('/ms-sync/data', [AdminMsSyncController::class, 'data'])->name('ms-sync.data');
        Route::post('/ms-sync/cleanup-test', [AdminMsSyncController::class, 'cleanupTestAccounts'])->name('ms-sync.cleanup-test');
        Route::post('/ms-sync/cleanup-portal', [AdminMsSyncController::class, 'cleanupPortalTestData'])->name('ms-sync.cleanup-portal');
        Route::post('/ms-sync/fix-guests', [AdminMsSyncController::class, 'fixGuests'])->name('ms-sync.fix-guests');
        Route::post('/ms-sync/retry-failed', [AdminMsSyncController::class, 'retryFailed'])->name('ms-sync.retry-failed');
        Route::post('/ms-sync/import-all', [AdminMsSyncController::class, 'importAll'])->name('ms-sync.import-all');
        Route::post('/ms-sync/import', [AdminMsSyncController::class, 'importFromAzure'])->name('ms-sync.import');
        Route::post('/ms-sync/delete-azure', [AdminMsSyncController::class, 'deleteFromAzure'])->name('ms-sync.delete-azure');
        Route::post('/ms-sync/students/{student}', [AdminMsSyncController::class, 'syncStudent'])->name('ms-sync.student');
        Route::post('/ms-sync/sync-all-licenses', [AdminMsSyncController::class, 'syncAllLicenses'])->name('ms-sync.sync-all-licenses');

        Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
        Route::get('/admins/audit-logs', [AdminUserController::class, 'auditLogs'])->name('admins.audit-logs');
        Route::get('/admins/login-activity', [AdminUserController::class, 'loginActivity'])->name('admins.login-activity');
        Route::get('/admins/backups', [\App\Http\Controllers\AdminBackupController::class, 'index'])->name('admins.backups');
        Route::post('/admins/backups', [\App\Http\Controllers\AdminBackupController::class, 'create'])->name('admins.backups.create');
        Route::post('/admins/backups/full', [\App\Http\Controllers\AdminBackupController::class, 'runFullBackup'])->name('admins.backups.full');
        Route::get('/admins/backups/{filename}/download', [\App\Http\Controllers\AdminBackupController::class, 'download'])->name('admins.backups.download');
        Route::delete('/admins/backups/{filename}', [\App\Http\Controllers\AdminBackupController::class, 'destroy'])->name('admins.backups.destroy');
        Route::post('/admins/backups/{filename}/google-drive', [\App\Http\Controllers\AdminBackupController::class, 'uploadToDrive'])->name('admins.backups.google-drive');
        Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');
        Route::get('/admins/{user}/edit', [AdminUserController::class, 'edit'])->name('admins.edit');
        Route::patch('/admins/{user}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::patch('/admins/{user}/role', [AdminUserController::class, 'updateRole'])->name('admins.role');
        Route::patch('/admins/{user}/access', [AdminUserController::class, 'updateAccess'])->name('admins.access');
        Route::patch('/admins/{user}/accept', [AdminUserController::class, 'accept'])->name('admins.accept');
        Route::delete('/admins/{user}', [AdminUserController::class, 'destroy'])->name('admins.destroy');

        // Administration Workspace
        Route::get('/administration/users', [AdministrationController::class, 'usersIndex'])->name('administration.users.index');
        Route::get('/administration/users/create', [AdministrationController::class, 'usersCreate'])->name('administration.users.create');
        Route::post('/administration/users', [AdministrationController::class, 'usersStore'])->name('administration.users.store');
        Route::patch('/administration/users/{user}/status', [AdministrationController::class, 'usersStatus'])->name('administration.users.status');
        Route::get('/administration/users/{user}/security', [AdministrationController::class, 'usersSecurity'])->name('administration.users.security');
        Route::patch('/administration/users/{user}/security', [AdministrationController::class, 'usersSecurityUpdate'])->name('administration.users.security.update');

        // Access Control Workspace
        Route::get('/access-control/roles', [AccessControlController::class, 'rolesIndex'])->name('access-control.roles.index');
        Route::post('/access-control/roles', [AccessControlController::class, 'rolesStore'])->name('access-control.roles.store');
        Route::patch('/access-control/roles/{role}', [AccessControlController::class, 'rolesUpdate'])->name('access-control.roles.update');
        Route::delete('/access-control/roles/{role}', [AccessControlController::class, 'rolesDestroy'])->name('access-control.roles.destroy');
        Route::get('/access-control/permissions', [AccessControlController::class, 'permissionsIndex'])->name('access-control.permissions.index');
        Route::post('/access-control/permissions', [AccessControlController::class, 'permissionsUpdate'])->name('access-control.permissions.update');
        Route::get('/access-control/assignment', [AccessControlController::class, 'assignmentIndex'])->name('access-control.assignment.index');
        Route::patch('/access-control/assignment/{user}', [AccessControlController::class, 'assignmentUpdate'])->name('access-control.assignment.update');
        Route::get('/access-control/policies', [AccessControlController::class, 'policiesIndex'])->name('access-control.policies.index');

        // Security Workspace
        Route::get('/security-workspace/metrics', [SecurityWorkspaceController::class, 'securityMetrics'])->name('security-workspace.metrics');
        Route::get('/security-workspace/login-activity', [SecurityWorkspaceController::class, 'loginActivity'])->name('security-workspace.login-activity');
        Route::get('/security-workspace/sessions', [SecurityWorkspaceController::class, 'activeSessions'])->name('security-workspace.sessions.index');
        Route::post('/security-workspace/sessions/revoke', [SecurityWorkspaceController::class, 'revokeSession'])->name('security-workspace.sessions.revoke');
        Route::get('/security-workspace/events', [SecurityWorkspaceController::class, 'securityEvents'])->name('security-workspace.events.index');
        Route::get('/security-workspace/audit-logs', [SecurityWorkspaceController::class, 'auditLogs'])->name('security-workspace.audit-logs');
        Route::get('/security-workspace/alerts', [SecurityWorkspaceController::class, 'securityAlerts'])->name('security-workspace.alerts.index');

        // System Management Workspace
        Route::get('/system-management/backups', [SystemManagementController::class, 'backupsIndex'])->name('system-management.backups.index');
        Route::post('/system-management/backups/create', [SystemManagementController::class, 'backupsCreate'])->name('system-management.backups.create');
        Route::post('/system-management/backups/trigger-full', [SystemManagementController::class, 'backupsTriggerFull'])->name('system-management.backups.trigger-full');
        Route::get('/system-management/backups/{filename}/download', [SystemManagementController::class, 'backupsDownload'])->name('system-management.backups.download');
        Route::post('/system-management/backups/{filename}/google-drive', [SystemManagementController::class, 'backupsUploadToDrive'])->name('system-management.backups.google-drive');
        Route::delete('/system-management/backups/{filename}', [SystemManagementController::class, 'backupsDestroy'])->name('system-management.backups.destroy');
        Route::post('/system-management/backups/restore', [SystemManagementController::class, 'backupsRestore'])->name('system-management.backups.restore');
        Route::post('/system-management/backups/schedule', [SystemManagementController::class, 'backupsSaveSchedule'])->name('system-management.backups.schedule');
        Route::get('/system-management/health', [SystemManagementController::class, 'systemHealth'])->name('system-management.health.index');
        Route::get('/system-management/integrations', [SystemManagementController::class, 'integrationsIndex'])->name('system-management.integrations.index');

        Route::get('/settings/discounts', [AdminDiscountSettingsController::class, 'edit'])->name('settings.discounts');
        Route::patch('/settings/discounts', [AdminDiscountSettingsController::class, 'update'])->name('settings.discounts.update');

        Route::get('/settings/enrollment', [\App\Http\Controllers\Admin\AdminEnrollmentSettingsController::class, 'edit'])->name('settings.enrollment');
        Route::patch('/settings/enrollment', [\App\Http\Controllers\Admin\AdminEnrollmentSettingsController::class, 'update'])->name('settings.enrollment.update');

        Route::prefix('academic')->name('academic.')->group(function () {
            Route::get('/', [AdminAcademicController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard', [AdminAcademicController::class, 'dashboard'])->name('dashboard.index');
            Route::get('/subjects', [AdminAcademicController::class, 'subjects'])->name('subjects');
            Route::post('/subjects', [AdminAcademicSubjectController::class, 'store'])->name('subjects.store');
            Route::patch('/subjects/{subject}', [AdminAcademicSubjectController::class, 'update'])->name('subjects.update');
            Route::patch('/subjects/{subject}/archive', [AdminAcademicSubjectController::class, 'archive'])->name('subjects.archive');
            Route::patch('/subjects/{subject}/restore', [AdminAcademicSubjectController::class, 'restore'])->name('subjects.restore');
            Route::get('/curriculum', [AdminAcademicController::class, 'curriculum'])->name('curriculum');
            Route::get('/grade-levels', [AdminAcademicController::class, 'curriculum'])->name('grade-levels');
            Route::get('/class-advisory', [AdminAcademicController::class, 'classAdvisory'])->name('class-advisory');
            Route::post('/class-advisory', [AdminAcademicController::class, 'assignClassAdvisory'])->name('class-advisory.store');
            Route::get('/teachers', [AdminAcademicTeacherController::class, 'index'])->name('teachers');
            Route::post('/teachers', [AdminAcademicTeacherController::class, 'store'])->name('teachers.store');
            Route::patch('/teachers', [AdminAcademicTeacherController::class, 'update'])->name('teachers.update');
            Route::post('/teachers/resend', [AdminAcademicTeacherController::class, 'resendCredentials'])->name('teachers.resend');
            Route::patch('/teachers/{id}/subjects', [AdminAcademicTeacherController::class, 'updateSubjects'])->name('teachers.subjects.update');
            Route::get('/teachers/{id}', [AdminAcademicTeacherController::class, 'show'])->name('teachers.view');
            Route::post('/teachers/{id}/toggle-password', [AdminAcademicTeacherController::class, 'togglePasswordChanged'])->name('teachers.toggle-password');
            Route::delete('/teachers/{id}', [AdminAcademicTeacherController::class, 'destroy'])->name('teachers.destroy');
            Route::get('/schedules', [AdminClassScheduleController::class, 'index'])->name('schedules');
            Route::post('/schedules', [AdminClassScheduleController::class, 'store'])->name('schedules.store');
            Route::patch('/schedules/{schedule}', [AdminClassScheduleController::class, 'update'])->name('schedules.update');
            Route::delete('/schedules/{schedule}', [AdminClassScheduleController::class, 'destroy'])->name('schedules.destroy');
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
            Route::post('/{section}/sync-advisor', [AdminMsTeamsController::class, 'syncAdvisor'])->name('sync-advisor');
        });
    });
});
