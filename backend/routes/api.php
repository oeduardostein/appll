<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\BaseEstadualController;
use App\Http\Controllers\Api\AnotherBaseStateController;
use App\Http\Controllers\Api\AtpvPdfController;
use App\Http\Controllers\Api\BinController;
use App\Http\Controllers\Api\BloqueiosAtivosController;
use App\Http\Controllers\Api\CepLookupController;
use App\Http\Controllers\Api\ConsultaIntencaoVendaController;
use App\Http\Controllers\Api\EmitirAtpvController;
use App\Http\Controllers\Api\GravamePesquisaController;
use App\Http\Controllers\Api\FichaCadastralConsultaController;
use App\Http\Controllers\Api\FichaCadastralAndamentoController;
use App\Http\Controllers\Api\RegistrarAssinaturaAtpvController;
use App\Http\Controllers\Api\PesquisaController;
use App\Http\Controllers\Api\ImpressaoCrlvController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\UserPermissionController;
use App\Http\Controllers\Api\RenainfPlacaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('user', [AuthController::class, 'current']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('captcha', CaptchaController::class);
Route::get('base-estadual', BaseEstadualController::class);
Route::get('another-base-estadual', AnotherBaseStateController::class);
Route::get('bin', BinController::class);
Route::get('bloqueios-ativos', BloqueiosAtivosController::class);
Route::get('gravame', GravamePesquisaController::class);
Route::get('ficha-cadastral/consulta', FichaCadastralConsultaController::class);
Route::get('ficha-cadastral/andamento', FichaCadastralAndamentoController::class);
Route::post('intencao-venda', ConsultaIntencaoVendaController::class);
Route::post('emissao-atpv', EmitirAtpvController::class);
Route::post('emissao-atpv/assinatura', RegistrarAssinaturaAtpvController::class);
Route::get('emissao-atpv/pdf', AtpvPdfController::class);
Route::get('emissao-crlv', ImpressaoCrlvController::class);
Route::get('renainf', RenainfPlacaController::class);
Route::get('cep', CepLookupController::class);

Route::get('pesquisas', [PesquisaController::class, 'index']);
Route::get('pesquisas/ultimo-mes', [PesquisaController::class, 'lastMonth']);
Route::post('pesquisas', [PesquisaController::class, 'store']);

Route::post('/password/forgot', [PasswordResetController::class, 'requestCode']);
Route::post('/password/reset',  [PasswordResetController::class, 'resetPassword']);

Route::get('user/permissions', UserPermissionController::class);


Route::get('/smtp-test', function () {
    try {
        Mail::raw('Ping SMTP', function ($m) {
            $m->to('dudustein2024@gmail.com')->subject('SMTP Test');
        });
        return ['status' => 'ok'];
    } catch (\Throwable $e) {
        Log::error('SMTP test falhou', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'erro',
            'error'  => $e->getMessage(),
        ], 500);
    }
});
