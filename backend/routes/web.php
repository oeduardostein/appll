<?php

use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CreditManagementController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\PlacasZeroKmController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestePlanilhaController;
use App\Http\Controllers\Admin\TestePlanilhaGravameController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/politica-de-privacidade', 'privacy-policy')->name('privacy-policy');
Route::view('/suporte', 'support')->name('support');
Route::view('/login', 'login')->name('login');
Route::view('/home', 'home')->name('home');
Route::view('/perfil', 'profile')->name('profile');
Route::view('/resultado-base-estadual', 'base-estadual-result')->name('base-estadual-result');
Route::view('/resultado-base-outros-estados', 'base-outros-estados-result')->name('base-outros-estados-result');
Route::view('/resultado-gravame', 'gravame-result')->name('gravame-result');
Route::view('/resultado-renainf', 'renainf-result')->name('renainf-result');
Route::view('/resultado-bin', 'bin-result')->name('bin-result');
Route::view('/resultado-bloqueios-ativos', 'bloqueios-result')->name('bloqueios-result');
Route::view('/resultado-ecrv', 'ecrv-result')->name('ecrv-result');
Route::view('/resultado-atpv', 'atpv-result')->name('atpv-result');
Route::view('/emissao-atpv/formulario', 'atpv-form')->name('atpv.form');
Route::get('/excluir-conta', [AccountDeletionController::class, 'show'])->name('account-deletion.form');
Route::post('/excluir-conta', [AccountDeletionController::class, 'destroy'])->name('account-deletion.submit');

// Rotas de teste sem autenticação
Route::get('/testeplanilha', [TestePlanilhaController::class, 'index'])->name('teste-planilha.index');
Route::post('/testeplanilha/consultar', [TestePlanilhaController::class, 'consultar'])->name('teste-planilha.consultar');
Route::post('/testeplanilha/exportar', [TestePlanilhaController::class, 'exportar'])->name('teste-planilha.exportar');
Route::get('/testeplanilha-gravame', [TestePlanilhaGravameController::class, 'index'])->name('teste-planilha-gravame.index');
Route::post('/testeplanilha-gravame/consultar', [TestePlanilhaGravameController::class, 'consultar'])->name('teste-planilha-gravame.consultar');
Route::post('/testeplanilha-gravame/exportar', [TestePlanilhaGravameController::class, 'exportar'])->name('teste-planilha-gravame.exportar');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/forgot', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'sendCode'])->name('password.email');
    Route::get('/password/reset', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/testeplanilha', [TestePlanilhaController::class, 'index'])->name('teste-planilha.index');
        Route::post('/testeplanilha/consultar', [TestePlanilhaController::class, 'consultar'])->name('teste-planilha.consultar');
        Route::post('/testeplanilha/exportar', [TestePlanilhaController::class, 'exportar'])->name('teste-planilha.exportar');
        Route::get('/testeplanilha-gravame', [TestePlanilhaGravameController::class, 'index'])->name('teste-planilha-gravame.index');
        Route::post('/testeplanilha-gravame/consultar', [TestePlanilhaGravameController::class, 'consultar'])->name('teste-planilha-gravame.consultar');
        Route::post('/testeplanilha-gravame/exportar', [TestePlanilhaGravameController::class, 'exportar'])->name('teste-planilha-gravame.exportar');
        Route::get('/placas-0km', [PlacasZeroKmController::class, 'index'])->name('placas-0km.index');
        Route::post('/placas-0km/consultar', [PlacasZeroKmController::class, 'consultar'])->name('placas-0km.consultar');
        Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clientes/{user}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clientes/{user}/editar', [ClientController::class, 'edit'])->name('clients.edit');
        Route::post('/clientes/{user}/exportar', [ClientController::class, 'export'])->name('clients.export');
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
