<?php

/*
|--------------------------------------------------------------------------
| Developer Center Routes - DISABLED
|--------------------------------------------------------------------------
|
| Developer Center controllers are not yet implemented.
| This file is disabled to prevent errors.
|
| To enable, create the following controllers in app/Http/Controllers/Developer/:
| - DashboardController.php
| - SuperadminCompanyController.php
| - ProfileController.php
| - NotificationController.php
| - SettingsController.php
| - AuditLogController.php
| - ApiKeyController.php
|
| Then uncomment the routes below.
*/

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Developer\DashboardController;
// use App\Http\Controllers\Developer\SuperadminCompanyController;
// use App\Http\Controllers\Developer\ProfileController;
// use App\Http\Controllers\Developer\NotificationController;
// use App\Http\Controllers\Developer\SettingsController;
// use App\Http\Controllers\Developer\AuditLogController;
// use App\Http\Controllers\Developer\ApiKeyController;

/*
|--------------------------------------------------------------------------
| Superadmin Panel Routes
|--------------------------------------------------------------------------
*/

// Route::middleware(['auth', 'developer'])->prefix('developer')->name('developer.')->group(function () {
//
//     /*
//     |--------------------------------------------------------------------------
//     | Beranda
//     |--------------------------------------------------------------------------
//     */
//     Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
//
//     /*
//     |--------------------------------------------------------------------------
//     | Audit Logs
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
//         Route::get('/', [AuditLogController::class, 'index'])->name('index');
//         Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | API Keys
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('api-keys')->name('api-keys.')->group(function () {
//         Route::get('/', [ApiKeyController::class, 'index'])->name('index');
//         Route::post('/generate', [ApiKeyController::class, 'generate'])->name('generate');
//         Route::post('/revoke', [ApiKeyController::class, 'revoke'])->name('revoke');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | Settings
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('settings')->name('settings.')->group(function () {
//         Route::get('/', [SettingsController::class, 'index'])->name('index');
//         Route::get('/{group}', [SettingsController::class, 'show'])->name('show');
//         Route::put('/{group}', [SettingsController::class, 'update'])->name('update');
//         Route::post('/{group}/test', [SettingsController::class, 'testSmtp'])->name('test');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | Notifications
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('notifications')->name('notifications.')->group(function () {
//         Route::get('/', [NotificationController::class, 'index'])->name('index');
//         Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
//         Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
//         Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | My Company Management (Scoped to user's company)
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('company')->name('company.')->group(function () {
//         Route::get('/', [SuperadminCompanyController::class, 'show'])->name('index');
//         Route::get('/my', [SuperadminCompanyController::class, 'show'])->name('my.show');
//         Route::get('/edit', [SuperadminCompanyController::class, 'edit'])->name('edit');
//         Route::put('/', [SuperadminCompanyController::class, 'update'])->name('update');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | Staff Management
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('staff')->name('staff.')->group(function () {
//         Route::get('/', [SuperadminCompanyController::class, 'staff'])->name('index');
//         Route::get('/create', [SuperadminCompanyController::class, 'createStaff'])->name('create');
//         Route::post('/', [SuperadminCompanyController::class, 'storeStaff'])->name('store');
//         Route::get('/{user}/edit', [SuperadminCompanyController::class, 'editStaff'])->name('edit');
//         Route::put('/{user}', [SuperadminCompanyController::class, 'updateStaff'])->name('update');
//         Route::delete('/{user}', [SuperadminCompanyController::class, 'destroyStaff'])->name('destroy');
//         Route::get('/{user}/permissions', [SuperadminCompanyController::class, 'permissions'])->name('permissions');
//         Route::put('/{user}/permissions', [SuperadminCompanyController::class, 'updatePermissions'])->name('update-permissions');
//     });
//
//     /*
//     |--------------------------------------------------------------------------
//     | Developer Profile
//     |--------------------------------------------------------------------------
//     */
//     Route::prefix('profile')->name('profile.')->group(function () {
//         Route::get('/', [ProfileController::class, 'index'])->name('index');
//         Route::put('/', [ProfileController::class, 'update'])->name('update');
//         Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
//     });
// });
