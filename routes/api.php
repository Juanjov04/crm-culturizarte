<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
| API RESTful v1 consumida por la app móvil del asesor comercial.
| Todas las rutas (excepto login) exigen un token de Sanctum (RNF-04).
*/
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.logout');
    });
});
