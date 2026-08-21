<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\AvailmentController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\BeneficiaryMergeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DilpGroupController;
use App\Http\Controllers\DilpProjectController;
use App\Http\Controllers\DuplicateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.perform');
});

// Authenticated Routes
Route::middleware(['auth', 'track.activity', 'check.password.reset'])->group(function () {

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // Password Change
    Route::get('change-password', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('change-password', [PasswordController::class, 'change'])->name('password.update');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Beneficiary Program Availments
    Route::post('beneficiaries/{beneficiary}/availments', [AvailmentController::class, 'store'])->name('beneficiaries.availments.store');
    Route::put('availments/{availment}', [AvailmentController::class, 'update'])->name('availments.update');
    Route::delete('availments/{availment}', [AvailmentController::class, 'destroy'])->name('availments.destroy');

    // Beneficiaries CRUD, Merge & Search
    Route::post('beneficiaries/merge', [BeneficiaryMergeController::class, 'merge'])->name('beneficiaries.merge');
    Route::get('beneficiaries/search-candidates', [BeneficiaryMergeController::class, 'searchCandidates'])->name('beneficiaries.search-candidates');
    Route::post('beneficiaries/bulk-delete', [BeneficiaryController::class, 'bulkDestroy'])->name('beneficiaries.bulk-delete');
    Route::match(['get', 'post'], 'beneficiaries/check-duplicate', [BeneficiaryController::class, 'checkDuplicate'])->name('beneficiaries.check-duplicate');
    Route::resource('beneficiaries', BeneficiaryController::class);

    // Export Options
    Route::get('export/csv', [ExportController::class, 'exportCsv'])->name('export.csv');
    Route::get('export/pdf', [ExportController::class, 'exportPdf'])->name('export.pdf');

    // Duplicates Review (Admin, Validator)
    Route::middleware('role:Admin|Validator')->group(function () {
        Route::get('duplicates', [DuplicateController::class, 'index'])->name('duplicates.index');
        Route::post('duplicates/{flag}/resolve', [DuplicateController::class, 'resolve'])->name('duplicates.resolve');
    });

    // DILP Management
    Route::post('dilp-groups/{group}/import-members', [DilpGroupController::class, 'importCoPartnerMembers'])->name('dilp.groups.import-members');
    Route::post('dilp/groups/{group}/import-members', [DilpGroupController::class, 'importCoPartnerMembers']);
    Route::resource('dilp/groups', DilpGroupController::class)->names('dilp.groups');
    Route::resource('dilp/projects', DilpProjectController::class)->names('dilp.projects');

    // Import Data (Admin, Encoder)
    Route::middleware('role:Admin|Encoder')->group(function () {
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import', [ImportController::class, 'store'])->name('import.store');
        Route::get('import/{importLog}', [ImportController::class, 'show'])->name('import.show');
        Route::delete('import/{importLog}', [ImportController::class, 'destroy'])->name('import.destroy');
    });

    // Admin Only: Users, Audit Trail & Settings
    Route::middleware('role:Admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('audit-logs', [AuditController::class, 'index'])->name('audit.index');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
