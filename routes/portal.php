<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocSignController;
use App\Http\Controllers\Portal\MenuPermissionController;
use App\Http\Controllers\Portal\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| VOS Portal Routes
|--------------------------------------------------------------------------
| Include this file in your main routes/web.php:
|
|   require __DIR__ . '/portal.php';
|
*/

Route::prefix('portal')->name('portal.')->group(function () {

    // ── Public (unauthenticated) ──────────────────────────────────────────
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // ── Authenticated ─────────────────────────────────────────────────────
    Route::middleware(['portal.auth'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Force password change (for is_password_flushed users)
        Route::get('/password/change',  [\App\Http\Controllers\Portal\ChangePasswordController::class, 'show'])->name('password.change');
        Route::post('/password/change', [\App\Http\Controllers\Portal\ChangePasswordController::class, 'update'])->name('password.update');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ── DocSign ───────────────────────────────────────────────────────
        Route::prefix('docsign')->name('docsign.')->group(function () {
            Route::get('/',                              [DocSignController::class, 'index'])->name('index');
            Route::get('/upload',                        [DocSignController::class, 'upload'])->name('upload');
            Route::post('/upload',                       [DocSignController::class, 'store'])->name('store');

            // Upload access requests
            Route::get('/access-approval',               [\App\Http\Controllers\Portal\UploadAccessController::class, 'index'])->name('access-approval');
            Route::post('/access-approval/request',      [\App\Http\Controllers\Portal\UploadAccessController::class, 'store'])->name('access-approval.request');
            Route::post('/access-approval/{accessRequest}/approve', [\App\Http\Controllers\Portal\UploadAccessController::class, 'approve'])->name('access-approval.approve')->middleware('portal.role:administrator,adm2,pengurus');
            Route::post('/access-approval/{accessRequest}/reject',  [\App\Http\Controllers\Portal\UploadAccessController::class, 'reject'])->name('access-approval.reject')->middleware('portal.role:administrator,adm2,pengurus');

            Route::get('/{document}',                    [DocSignController::class, 'show'])->name('show');
            Route::post('/{document}/assign',            [DocSignController::class, 'assign'])->name('assign');
            Route::get('/{document}/pdf',                [DocSignController::class, 'servePdf'])->name('pdf');
            Route::get('/{document}/sign/{signature}',   [DocSignController::class, 'signForm'])->name('sign');
            Route::post('/{document}/sign/{signature}',  [DocSignController::class, 'processSign'])->name('sign.process');
            Route::post('/{document}/reject/{signature}',[DocSignController::class, 'reject'])->name('reject');

            // Verification management (admin only)
            Route::middleware('portal.role:administrator')->group(function () {
                Route::post('/{document}/invalidate', [\App\Http\Controllers\Portal\DocVerificationController::class, 'invalidate'])->name('invalidate');
                Route::post('/{document}/restore',    [\App\Http\Controllers\Portal\DocVerificationController::class, 'restore'])->name('restore');
                Route::post('/{document}/valid-thru', [\App\Http\Controllers\Portal\DocVerificationController::class, 'setValidThru'])->name('validthru');
            });
        });

        // ── Schedule ──────────────────────────────────────────────────────
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/',                          [\App\Http\Controllers\Portal\ScheduleController::class, 'index'])->name('index');
            Route::get('/create',                    [\App\Http\Controllers\Portal\ScheduleController::class, 'create'])->name('create');
            Route::post('/',                         [\App\Http\Controllers\Portal\ScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}/edit',           [\App\Http\Controllers\Portal\ScheduleController::class, 'edit'])->name('edit');
            Route::put('/{schedule}',                [\App\Http\Controllers\Portal\ScheduleController::class, 'update'])->name('update');
            Route::delete('/{schedule}',             [\App\Http\Controllers\Portal\ScheduleController::class, 'destroy'])->name('destroy');
            Route::post('/{schedule}/confirm',       [\App\Http\Controllers\Portal\ScheduleController::class, 'confirm'])->name('confirm');

            // Laporan Kehadiran
            Route::get('/laporan',                   [\App\Http\Controllers\Portal\ScheduleController::class, 'laporan'])->name('laporan');
            Route::get('/laporan/{schedule}',        [\App\Http\Controllers\Portal\ScheduleController::class, 'laporanDetail'])->name('laporan.detail');
            Route::get('/laporan/{schedule}/edit',   [\App\Http\Controllers\Portal\ScheduleController::class, 'laporanEdit'])->name('laporan.edit');
            Route::post('/laporan/{schedule}/edit',  [\App\Http\Controllers\Portal\ScheduleController::class, 'laporanUpdate'])->name('laporan.update');
        });
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/submit',  fn() => view('portal.sales.submit'))->name('submit');
            Route::get('/reports', fn() => view('portal.sales.reports'))->name('reports');
        });

        // ── User search (AJAX — all roles can access for signer autocomplete) ──
        Route::get('/users/search/query', [UserController::class, 'search'])
             ->name('users.search')
             ->middleware('portal.role:administrator,adm2,pengurus,timker,singers');

        // ── User Management ───────────────────────────────────────────────
        Route::middleware('portal.role:administrator,adm2')->group(function () {
            Route::resource('users', UserController::class)
                 ->names([
                     'index'   => 'users.index',
                     'create'  => 'users.create',
                     'store'   => 'users.store',
                     'show'    => 'users.show',
                     'edit'    => 'users.edit',
                     'update'  => 'users.update',
                     'destroy' => 'users.destroy',
                 ]);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        });

        // ── Settings ──────────────────────────────────────────────────────
        Route::middleware('portal.role:administrator')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/menu-permissions',  [MenuPermissionController::class, 'index'])->name('menu-permissions');
            Route::post('/menu-permissions', [MenuPermissionController::class, 'update'])->name('menu-permissions.update');
        });
    });
});
