<?php

use App\Modules\System\Http\Controllers\AuthController;
use App\Modules\System\Http\Controllers\DashboardController;
use App\Http\Controllers\CRM\CompanyController;
use App\Http\Controllers\CRM\DivisionController;
use App\Http\Controllers\HRD\HRDController;
use App\Http\Controllers\HRD\HRReportController;
use App\Http\Controllers\HRD\EmployeeWizardController;
use App\Http\Controllers\HRD\EmployeeController;
use App\Http\Controllers\HRD\AttendanceController;
use App\Http\Controllers\HRD\ReportController;
use App\Http\Controllers\CRM\SettingsController;
use App\Http\Controllers\CRM\MasterDataController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Storage Serve Routes (Fallback for Windows without symlink)
|--------------------------------------------------------------------------
*/

// Serve files from storage/app/public/profile
Route::get('/storage/profile/{filename}', function ($filename) {
    $path = 'profile/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);

    return response($file, 200)->header('Content-Type', $type);
})->where('filename', '[\w\-\.]+');

// Serve files from storage/app/public/avatars
Route::get('/storage/avatars/{filename}', function ($filename) {
    $path = 'avatars/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);

    return response($file, 200)->header('Content-Type', $type);
})->where('filename', '[\w\-\.]+');

// Serve files from storage/app/public/assets
Route::get('/storage/assets/{path}', function ($path) {
    $fullPath = 'assets/' . $path;

    if (!Storage::disk('public')->exists($fullPath)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($fullPath);
    $type = Storage::disk('public')->mimeType($fullPath);

    return response($file, 200)->header('Content-Type', $type);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Guest Routes (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store')->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (All Users - Logout Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Laporan Routes - Top Level (New URL Structure)
|--------------------------------------------------------------------------
*/
// Redirect old URLs to new ones for backward compatibility
Route::middleware(['auth', 'tenant.auth'])->group(function () {
    Route::get('/administrasi/laporan/attendance', function () {
        return redirect()->route('laporan.absensi', 301);
    })->name('administrasi.laporan.attendance.redirect');

    Route::get('/administrasi/laporan/employees', function () {
        return redirect()->route('laporan.karyawan', 301);
    })->name('administrasi.laporan.employees.redirect');
});

// New laporan routes at root level
Route::middleware(['auth', 'tenant.auth', 'sidebar.permission:staff_reports'])->group(function () {
    Route::get('/laporan', function () {
        return redirect()->route('laporan.absensi', 301);
    })->name('laporan.index.redirect');

    Route::get('/laporan/absensi', [App\Http\Controllers\HRD\HRReportController::class, 'attendance'])->name('laporan.absensi');
    Route::get('/laporan/karyawan', [App\Http\Controllers\HRD\HRReportController::class, 'employees'])->name('laporan.karyawan');

    // Report Exports with type parameter (type is passed via URL segment)
    Route::get('/laporan/absensi/export/pdf/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportPdf'])
        ->where('type', '[a-z]+')
        ->name('laporan.absensi.export.pdf');
    Route::get('/laporan/absensi/export/excel/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportExcel'])
        ->where('type', '[a-z]+')
        ->name('laporan.absensi.export.excel');
    Route::get('/laporan/absensi/export/word/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportWord'])
        ->where('type', '[a-z]+')
        ->name('laporan.absensi.export.word');

    Route::get('/laporan/karyawan/export/pdf/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportPdf'])
        ->where('type', '[a-z]+')
        ->name('laporan.karyawan.export.pdf');
    Route::get('/laporan/karyawan/export/excel/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportExcel'])
        ->where('type', '[a-z]+')
        ->name('laporan.karyawan.export.excel');
    Route::get('/laporan/karyawan/export/word/{type}', [App\Http\Controllers\HRD\HRReportController::class, 'exportWord'])
        ->where('type', '[a-z]+')
        ->name('laporan.karyawan.export.word');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Tenant Mode)
|--------------------------------------------------------------------------
| All routes here require the user to be authenticated AND a tenant member.
| Developers are blocked from accessing tenant routes (403 Forbidden).
*/
Route::middleware(['auth', 'tenant.auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Beranda - accessible to ALL logged-in users
    | Data is filtered by scope permissions
    |--------------------------------------------------------------------------
    */
    // Redirect old /dashboard URL to /beranda for backward compatibility
    Route::get('dashboard', function () {
        return redirect()->route('beranda');
    })->name('dashboard');

    Route::get('beranda', [DashboardController::class, 'index'])->name('beranda');

    // Permission Test (for debugging)
    Route::get('permission-test', [\App\Http\Controllers\CRM\PermissionTestController::class, 'index'])->name('permission-test');

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Module - accessible to all logged-in users
    |--------------------------------------------------------------------------
    */
    // Pengaturan index - redirect to Pengaturan Umum
    Route::get('/pengaturan', function () {
        return redirect()->route('pengaturan.umum.index', [], 301);
    })->name('pengaturan.index');

    /*
    |--------------------------------------------------------------------------
    | CRM Permissions - accessible to all logged-in users
    |--------------------------------------------------------------------------
    */
    // Redirect old URL to new URL
    Route::get('/crm/permissions', function () {
        return redirect()->route('pengaturan.hak_akses.index', [], 301);
    })->name('crm.permissions.old');

    Route::prefix('pengaturan/hak-akses')->name('pengaturan.hak_akses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CRM\CrmPermissionController::class, 'index'])->name('index');
        Route::get('/{userId}', [\App\Http\Controllers\CRM\CrmPermissionController::class, 'getUserPermissions'])->name('show');
        Route::put('/{userId}', [\App\Http\Controllers\CRM\CrmPermissionController::class, 'updateUserPermissions'])->name('update');
        Route::post('/{userId}/reset', [\App\Http\Controllers\CRM\CrmPermissionController::class, 'resetUserPermissions'])->name('reset');
    });

    /*
    |--------------------------------------------------------------------------
    | Companies (Multi-Tenant Management)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');

        // Member management
        Route::get('/{company}/members/create', [CompanyController::class, 'createMember'])->name('members.create');
        Route::post('/{company}/members', [CompanyController::class, 'storeMember'])->name('members.store');
        Route::get('/{company}/members/{user}/edit', [CompanyController::class, 'editMember'])->name('members.edit');
        Route::put('/{company}/members/{user}', [CompanyController::class, 'updateMember'])->name('members.update');
        Route::delete('/{company}/members/{user}', [CompanyController::class, 'destroyMember'])->name('members.destroy');

        // Division management
        Route::get('/{company}/divisions', [DivisionController::class, 'index'])->name('divisions.index');
        Route::get('/{company}/divisions/create', [DivisionController::class, 'create'])->name('divisions.create');
        Route::post('/{company}/divisions', [DivisionController::class, 'store'])->name('divisions.store');
        Route::get('/{company}/divisions/{division}/edit', [DivisionController::class, 'edit'])->name('divisions.edit');
        Route::put('/{company}/divisions/{division}', [DivisionController::class, 'update'])->name('divisions.update');
        Route::delete('/{company}/divisions/{division}', [DivisionController::class, 'destroy'])->name('divisions.destroy');

        // Company Structure API (Departments, Positions, enhanced Divisions)
        Route::prefix('structure-api/{company}')->name('structure-api.')->group(function () {
            // Departments
            Route::post('/departments', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'storeDepartment'])->name('departments.store');
            Route::put('/departments/{department}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'updateDepartment'])->name('departments.update');
            Route::delete('/departments/{department}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'destroyDepartment'])->name('departments.destroy');
            Route::get('/departments', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getDepartments'])->name('departments.index');

            // Positions
            Route::post('/positions', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'storePosition'])->name('positions.store');
            Route::put('/positions/{position}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'updatePosition'])->name('positions.update');
            Route::delete('/positions/{position}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'destroyPosition'])->name('positions.destroy');
            Route::get('/positions', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getPositions'])->name('positions.index');

            // Enhanced Divisions
            Route::post('/divisions', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'storeDivision'])->name('divisions.store');
            Route::put('/divisions/{division}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'updateDivision'])->name('divisions.update');
            Route::delete('/divisions/{division}', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'destroyDivision'])->name('divisions.destroy');
            Route::get('/divisions', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getDivisions'])->name('divisions.index');

            // Members & Employees
            Route::get('/members', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getMembers'])->name('members.index');
            Route::get('/employees', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getEmployees'])->name('employees.index');
            Route::get('/departments/{department}/divisions', [\App\Http\Controllers\CRM\CompanyStructureController::class, 'getDivisionsByDepartment'])->name('divisions.by-department');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Staff Module - permission check per section
    |--------------------------------------------------------------------------
    */
    // Redirect old /hrd and /karyawan URLs to /administrasi for backward compatibility
    Route::get('hrd', function () {
        return redirect()->route('administrasi.dashboard');
    })->name('hrd.redirect');

    Route::prefix('administrasi')->name('administrasi.')->group(function () {
        // RequireCompanyContext ensures user has company_id before accessing company-specific modules
        Route::middleware(['company.context', 'sidebar.permission:staff_dashboard'])->group(function () {
            // Administrasi Dashboard
            Route::get('/', [HRDController::class, 'index'])->name('dashboard');
            Route::get('/index', [HRDController::class, 'index'])->name('index');
        });

        // Employees - requires employees permission
        Route::middleware(['company.context', 'sidebar.permission:employees'])->group(function () {
            // Employees - 7-Step Wizard (REQUIRED)
            Route::get('/data-karyawan', [EmployeeController::class, 'index'])->name('data_karyawan.index');
            Route::get('/data-karyawan/create', [EmployeeWizardController::class, 'create'])->name('data_karyawan.wizard.create');
            Route::post('/data-karyawan/wizard', [EmployeeWizardController::class, 'store'])->name('data_karyawan.wizard.store');
            Route::post('/data-karyawan', [EmployeeController::class, 'store'])->name('data_karyawan.store');
            Route::get('/data-karyawan/{employee}', [EmployeeController::class, 'show'])->name('data_karyawan.show');
            Route::get('/data-karyawan/{employee}/edit', [EmployeeWizardController::class, 'edit'])->name('data_karyawan.wizard.edit');
            Route::put('/data-karyawan/{employee}/wizard', [EmployeeWizardController::class, 'update'])->name('data_karyawan.wizard.update');
            Route::get('/data-karyawan/{employee}/edit-old', [EmployeeController::class, 'edit'])->name('data_karyawan.edit');
            Route::put('/data-karyawan/{employee}', [EmployeeController::class, 'update'])->name('data_karyawan.update');
            Route::delete('/data-karyawan/{employee}', [EmployeeController::class, 'destroy'])->name('data_karyawan.destroy');
            Route::post('/data-karyawan/{employee}/document', [EmployeeController::class, 'uploadDocument'])->name('data_karyawan.document');
            Route::get('/data-karyawan/export', [EmployeeController::class, 'export'])->name('data_karyawan.export');

            // Soft Delete - Employee Management
            Route::get('/data-karyawan/trashed', [EmployeeController::class, 'trashed'])->name('data_karyawan.trashed');
            Route::get('/data-karyawan/{employee}/delete-data', [EmployeeController::class, 'getDeleteData'])->name('data_karyawan.delete-data');
            Route::post('/data-karyawan/{employee}/restore', [EmployeeController::class, 'restore'])->name('data_karyawan.restore');
            Route::post('/data-karyawan/{employee}/resign', [EmployeeController::class, 'resign'])->name('data_karyawan.resign');
            Route::delete('/data-karyawan/{employee}/force-delete', [EmployeeController::class, 'forceDelete'])->name('data_karyawan.force-delete');

            // Quick create via AJAX for wizard
            Route::post('/data-karyawan/wizard/quick-shift', [EmployeeWizardController::class, 'quickCreateShift'])->name('data_karyawan.wizard.quick-shift');
            Route::post('/data-karyawan/wizard/quick-leave-type', [EmployeeWizardController::class, 'quickCreateLeaveType'])->name('data_karyawan.wizard.quick-leave-type');

            // Get data for dropdown refresh
            Route::get('/data-karyawan/wizard/get-placements', [EmployeeWizardController::class, 'getPlacements'])->name('data_karyawan.wizard.get-placements');
            Route::get('/data-karyawan/wizard/get-shifts', [EmployeeWizardController::class, 'getShifts'])->name('data_karyawan.wizard.get-shifts');
            Route::get('/data-karyawan/wizard/get-leave-types', [EmployeeWizardController::class, 'getLeaveTypes'])->name('data_karyawan.wizard.get-leave-types');
        });

        // Placements (Staff Placement) - requires employees permission
        Route::middleware(['company.context', 'sidebar.permission:employees'])->group(function () {
            Route::get('/placements', [App\Http\Controllers\HRD\PlacementController::class, 'index'])->name('placements.index');
            Route::get('/placements/create', [App\Http\Controllers\HRD\PlacementController::class, 'create'])->name('placements.create');
            Route::post('/placements', [App\Http\Controllers\HRD\PlacementController::class, 'store'])->name('placements.store');
            Route::get('/placements/{placement}', [App\Http\Controllers\HRD\PlacementController::class, 'show'])->name('placements.show');
            Route::get('/placements/{placement}/edit', [App\Http\Controllers\HRD\PlacementController::class, 'edit'])->name('placements.edit');
            Route::put('/placements/{placement}', [App\Http\Controllers\HRD\PlacementController::class, 'update'])->name('placements.update');
            Route::delete('/placements/{placement}', [App\Http\Controllers\HRD\PlacementController::class, 'destroy'])->name('placements.destroy');
            Route::get('/placements/get', [App\Http\Controllers\HRD\PlacementController::class, 'getPlacements'])->name('placements.get');
            Route::post('/placements/assign', [App\Http\Controllers\HRD\PlacementController::class, 'assignEmployee'])->name('placements.assign');
        });

        // Attendances - requires attendances permission
        Route::middleware(['company.context', 'sidebar.permission:attendances'])->group(function () {
            Route::get('/absen', [AttendanceController::class, 'index'])->name('absen.index');
            Route::get('/absen/face', [AttendanceController::class, 'faceAttendance'])->name('absen.face');
            Route::post('/absen/face/submit', [AttendanceController::class, 'submitFaceAttendance'])->name('absen.face.submit');
            Route::post('/absen/link/request', [AttendanceController::class, 'createLinkRequest'])->name('absen.face.submit-request');
            Route::get('/absen/link/employees', [AttendanceController::class, 'getLinkableEmployees'])->name('absen.face.linkable-employees');
            Route::get('/absen/pending-requests', [AttendanceController::class, 'getPendingRequest'])->name('absen.face.pending-requests');
            Route::post('/absen/link', [AttendanceController::class, 'linkEmployee'])->name('absen.face.link');
            Route::delete('/absen/link/cancel', [AttendanceController::class, 'cancelLinkRequest'])->name('absen.face.cancel-request');
            Route::get('/absen/report', [AttendanceController::class, 'report'])->name('absen.report');
            Route::get('/absen/export', [AttendanceController::class, 'export'])->name('absen.export');
            Route::get('/absen/calendar-data', [AttendanceController::class, 'calendarData'])->name('absen.calendar-data');
            // API endpoints for attendance popup cards (admin/director only)
            Route::get('/absen/summary', [AttendanceController::class, 'getSummary'])->name('absen.summary');
            Route::get('/absen/present-list', [AttendanceController::class, 'getPresentList'])->name('absen.present-list');
            Route::get('/absen/not-present-list', [AttendanceController::class, 'getNotPresentList'])->name('absen.not-present-list');
            Route::get('/absen/calendar-stats', [AttendanceController::class, 'getCalendarStats'])->name('absen.calendar-stats');
            // Calendar period-based endpoints
            Route::get('/absen/calendar-present-list', [AttendanceController::class, 'getCalendarPresentList'])->name('absen.calendar-present-list');
            Route::get('/absen/calendar-not-present-list', [AttendanceController::class, 'getCalendarNotPresentList'])->name('absen.calendar-not-present-list');
        });

        // Reports - requires staff_reports permission
        Route::middleware(['company.context', 'sidebar.permission:staff_reports'])->group(function () {
            Route::get('/laporan', [HRReportController::class, 'index'])->name('laporan.index');
            Route::get('/laporan/attendance', [HRReportController::class, 'attendance'])->name('laporan.attendance');
            Route::get('/laporan/employees', [HRReportController::class, 'employees'])->name('laporan.employees');
            Route::get('/laporan/leaves', [HRReportController::class, 'leaves'])->name('laporan.leaves');
            Route::get('/laporan/salary', [HRReportController::class, 'salary'])->name('laporan.salary');
            Route::get('/laporan/training', [ReportController::class, 'trainingReport'])->name('laporan.training');
            Route::get('/laporan/recruitment', [ReportController::class, 'recruitmentReport'])->name('laporan.recruitment');

            // Report Exports
            Route::get('/laporan/export/pdf/{type}', [HRReportController::class, 'exportPdf'])->name('laporan.export.pdf');
            Route::get('/laporan/export/excel/{type}', [HRReportController::class, 'exportExcel'])->name('laporan.export.excel');
            Route::get('/laporan/export/word/{type}', [HRReportController::class, 'exportWord'])->name('laporan.export.word');
        });

        // Leaves - requires attendances permission
        Route::middleware(['company.context', 'sidebar.permission:attendances'])->group(function () {
            Route::get('/leaves', [\App\Http\Controllers\HRD\LeaveController::class, 'index'])->name('leaves.index');
        });

        // Payroll - requires staff_reports permission
        Route::middleware(['company.context', 'sidebar.permission:staff_reports'])->group(function () {
            Route::get('/payroll', [\App\Http\Controllers\HRD\PayrollController::class, 'index'])->name('payroll.index');
        });

        // Audit - requires staff_reports permission
        Route::middleware(['company.context', 'sidebar.permission:staff_reports'])->group(function () {
            Route::get('/audit', [\App\Http\Controllers\HRD\AuditController::class, 'index'])->name('audit.index');
        });

        // Recruitment - requires employees permission
        Route::middleware(['company.context', 'sidebar.permission:employees'])->group(function () {
            Route::get('/recruitment', [\App\Http\Controllers\HRD\RecruitmentController::class, 'index'])->name('recruitment.index');
        });

        // Trainings - requires employees permission
        Route::middleware(['company.context', 'sidebar.permission:employees'])->group(function () {
            Route::get('/trainings', [\App\Http\Controllers\HRD\TrainingController::class, 'index'])->name('trainings.index');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Settings - requires developer/owner/director role
    |--------------------------------------------------------------------------
    */
    Route::middleware(['developer'])->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.crm.update');

        /*
        |--------------------------------------------------------------------------
        | Proposal Templates
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings/proposal-templates')->name('settings.proposal-templates.')->group(function () {
            Route::get('/', [App\Http\Controllers\CRM\ProposalTemplateController::class, 'index'])->name('index');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Profile
    |--------------------------------------------------------------------------
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CRM\ProfileController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\CRM\ProfileController::class, 'update'])->name('update');
        Route::post('/password', [\App\Http\Controllers\CRM\ProfileController::class, 'updatePassword'])->name('password');
        Route::get('/activity', [\App\Http\Controllers\CRM\ProfileController::class, 'activity'])->name('activity');

        // Profile Photo Routes
        Route::post('/photo', [\App\Http\Controllers\CRM\ProfileController::class, 'uploadPhoto'])->name('photo.upload');
        Route::delete('/photo', [\App\Http\Controllers\CRM\ProfileController::class, 'deletePhoto'])->name('photo.delete');
        Route::get('/photo', [\App\Http\Controllers\CRM\ProfileController::class, 'getPhoto'])->name('photo.get');
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\CRM\NotificationController::class, 'index'])->name('index');
        Route::get('/all', [App\Http\Controllers\CRM\NotificationController::class, 'all'])->name('all');

        // AJAX/API Endpoints - return JSON
        Route::get('/dropdown', [App\Http\Controllers\CRM\NotificationController::class, 'dropdown'])->name('dropdown');
        Route::get('/unread-count', [App\Http\Controllers\CRM\NotificationController::class, 'unreadCount'])->name('unread-count');

        // Actions
        Route::put('/{id}/read', [App\Http\Controllers\CRM\NotificationController::class, 'markAsRead'])->name('read');
        Route::put('/read-all', [App\Http\Controllers\CRM\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [App\Http\Controllers\CRM\NotificationController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Data Backup Routes
    |--------------------------------------------------------------------------
    */
    // Redirect old URL to new URL
    Route::get('/backup', function () {
        return redirect()->route('pengaturan.backup.index', [], 301);
    })->name('backup.old');

    Route::prefix('pengaturan/backup')
        ->name('pengaturan.backup.')
        ->middleware(['auth', 'backup.access'])
        ->group(function () {
            // Dashboard
            Route::get('/', [\App\Http\Controllers\CRM\BackupController::class, 'index'])->name('index');

            // History
            Route::get('/history', [\App\Http\Controllers\CRM\BackupController::class, 'history'])->name('history');

            // Settings
            Route::match(['get', 'put'], '/settings', [\App\Http\Controllers\CRM\BackupController::class, 'index'])->name('settings');

            // Backup Actions
            Route::post('/database', [\App\Http\Controllers\CRM\BackupController::class, 'backupDatabase'])->name('database');
            Route::post('/file', [\App\Http\Controllers\CRM\BackupController::class, 'backupFiles'])->name('file');
            Route::post('/full', [\App\Http\Controllers\CRM\BackupController::class, 'backupFull'])->name('full');

            // Backup Management
            Route::get('/{backup}/download', [\App\Http\Controllers\CRM\BackupController::class, 'download'])->name('download');
            Route::get('/{backup}/restore', [\App\Http\Controllers\CRM\BackupController::class, 'restoreShow'])->name('restore.show');
            Route::post('/{backup}/restore', [\App\Http\Controllers\CRM\BackupController::class, 'restore'])->name('restore');
            Route::delete('/{backup}', [\App\Http\Controllers\CRM\BackupController::class, 'destroy'])->name('destroy');

            // Statistics
            Route::get('/statistics', [\App\Http\Controllers\CRM\BackupController::class, 'statistics'])->name('statistics');

            // Settings Update (separate route)
            Route::post('/settings', [\App\Http\Controllers\CRM\BackupController::class, 'updateSettings'])->name('settings.update');
        });

    /*
    |--------------------------------------------------------------------------
    | Master Data Routes (HRD)
    |--------------------------------------------------------------------------
    */
    Route::prefix('pengaturan/umum')
        ->name('pengaturan.umum.')
        ->middleware(['auth'])
        ->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
        });

    /*
    |--------------------------------------------------------------------------
    | Utility Routes
    |--------------------------------------------------------------------------
    */
    // Select2 / Autocomplete
    Route::get('api/users/search', [\App\Http\Controllers\CRM\UserController::class, 'search'])->name('api.users.search');

});

/*
|--------------------------------------------------------------------------
| Developer Center Routes - Loaded from separate file
|--------------------------------------------------------------------------
*/
require __DIR__.'/developer.php';
