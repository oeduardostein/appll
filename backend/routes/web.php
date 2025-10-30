<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/clientes', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk/status', [UserController::class, 'bulkUpdateStatus'])->name('users.bulk-status');
        Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
