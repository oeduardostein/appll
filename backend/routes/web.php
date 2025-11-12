<?php

use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CreditManagementController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::view('/politica-de-privacidade', 'privacy-policy')->name('privacy-policy');
Route::view('/suporte', 'support')->name('support');
Route::get('/excluir-conta', [AccountDeletionController::class, 'show'])->name('account-deletion.form');
Route::post('/excluir-conta', [AccountDeletionController::class, 'destroy'])->name('account-deletion.submit');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/forgot', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendCode'])->name('password.email');
    Route::get('/password/reset', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/relatorios/exportar', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/configuracoes', [SettingsController::class, 'show'])->name('settings.index');
        Route::post('/configuracoes/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/configuracoes/api-key', [SettingsController::class, 'updateApiKey'])->name('settings.api-key');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk/status', [UserController::class, 'bulkUpdateStatus'])->name('users.bulk-status');
        Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/gestao-creditos', [CreditManagementController::class, 'index'])->name('payments.index');
        Route::post('/gestao-creditos/{user}/marcar-pago', [CreditManagementController::class, 'markPaid'])->name('payments.mark-paid');
        Route::post('/gestao-creditos/{user}/inativar', [CreditManagementController::class, 'deactivate'])->name('payments.deactivate');
    });
});
