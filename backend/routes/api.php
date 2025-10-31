<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\BaseEstadualController;
use App\Http\Controllers\Api\AnotherBaseStateController;
use App\Http\Controllers\Api\BloqueiosAtivosController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('user', [AuthController::class, 'current']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('captcha', CaptchaController::class);
Route::get('base-estadual', BaseEstadualController::class);
Route::get('another-base-estadual', AnotherBaseStateController::class);
Route::get('bloqueios-ativos', BloqueiosAtivosController::class);
