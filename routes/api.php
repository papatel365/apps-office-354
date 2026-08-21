<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CLEANED
|--------------------------------------------------------------------------
|
| Kept API routes for:
| - Master Data (HRD)
| - Company Identity
| - User search
|
| Removed:
| - Projects & Tasks (disabled module)
| - Assets (disabled module)
| - Comments/Attachments (used by disabled modules)
| - Reports (assets/projects reports disabled)
| - Client search (clients module disabled)
| - Beranda stats (DashboardController not implemented in API)
|
*/

// Test Endpoint
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working!',
        'timestamp' => now()->toISOString()
    ]);
})->name('api.test');

// ============ SEARCH ============

Route::get('/search/users', [\App\Http\Controllers\CRM\UserController::class, 'search'])->name('api.search.users');

// ============ MASTER DATA (HRD) ============

use App\Http\Controllers\CRM\MasterDataController;

// Data & Options routes (require web session auth)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/master-data/data', [MasterDataController::class, 'getData'])->name('api.master-data.data');
    Route::get('/master-data/options', [MasterDataController::class, 'getOptions'])->name('api.master-data.options');
});

// CRUD operations (Protected - require auth)
Route::middleware(['web', 'auth'])->group(function () {
    // Departments
    Route::post('/master-data/departments', [MasterDataController::class, 'storeDepartment'])->name('api.master.departments.store');
    Route::put('/master-data/departments/{department}', [MasterDataController::class, 'updateDepartment'])->name('api.master.departments.update');
    Route::patch('/master-data/departments/{department}/toggle', [MasterDataController::class, 'toggleDepartment'])->name('api.master.departments.toggle');
    Route::delete('/master-data/departments/{department}', [MasterDataController::class, 'destroyDepartment'])->name('api.master.departments.destroy');

    // Divisions
    Route::post('/master-data/divisions', [MasterDataController::class, 'storeDivision'])->name('api.master.divisions.store');
    Route::put('/master-data/divisions/{division}', [MasterDataController::class, 'updateDivision'])->name('api.master.divisions.update');
    Route::patch('/master-data/divisions/{division}/toggle', [MasterDataController::class, 'toggleDivision'])->name('api.master.divisions.toggle');
    Route::delete('/master-data/divisions/{division}', [MasterDataController::class, 'destroyDivision'])->name('api.master.divisions.destroy');

    // Positions
    Route::post('/master-data/positions', [MasterDataController::class, 'storePosition'])->name('api.master.positions.store');
    Route::put('/master-data/positions/{position}', [MasterDataController::class, 'updatePosition'])->name('api.master.positions.update');
    Route::patch('/master-data/positions/{position}/toggle', [MasterDataController::class, 'togglePosition'])->name('api.master.positions.toggle');
    Route::delete('/master-data/positions/{position}', [MasterDataController::class, 'destroyPosition'])->name('api.master.positions.destroy');

    // Employee Types
    Route::post('/master-data/employee-types', [MasterDataController::class, 'storeEmployeeType'])->name('api.master.employee-types.store');
    Route::put('/master-data/employee-types/{employeeType}', [MasterDataController::class, 'updateEmployeeType'])->name('api.master.employee-types.update');
    Route::patch('/master-data/employee-types/{employeeType}/toggle', [MasterDataController::class, 'toggleEmployeeType'])->name('api.master.employee-types.toggle');
    Route::delete('/master-data/employee-types/{employeeType}', [MasterDataController::class, 'destroyEmployeeType'])->name('api.master.employee-types.destroy');

    // Locations
    Route::post('/master-data/locations', [MasterDataController::class, 'storeLocation'])->name('api.master.locations.store');
    Route::put('/master-data/locations/{location}', [MasterDataController::class, 'updateLocation'])->name('api.master.locations.update');
    Route::patch('/master-data/locations/{location}/toggle', [MasterDataController::class, 'toggleLocation'])->name('api.master.locations.toggle');
    Route::delete('/master-data/locations/{location}', [MasterDataController::class, 'destroyLocation'])->name('api.master.locations.destroy');
});

// ============ COMPANY IDENTITY ============

use App\Http\Controllers\CRM\CompanyController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/company/current', [CompanyController::class, 'current'])->name('api.company.current');
    Route::post('/company/update-identity', [CompanyController::class, 'updateIdentity'])->name('api.company.update-identity');
});
