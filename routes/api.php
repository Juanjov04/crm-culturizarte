<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InstitucionController;
use App\Http\Controllers\Api\VisitaController;
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

        // Sprint 3: directorio B2B (solo lectura para la app).
        Route::get('instituciones', [InstitucionController::class, 'index'])->name('api.instituciones.index');
        Route::get('instituciones/{institucion}', [InstitucionController::class, 'show'])->name('api.instituciones.show');
        Route::get('instituciones/{institucion}/docentes', [InstitucionController::class, 'docentes'])->name('api.instituciones.docentes');

        // Sprint 4: registro de visitas comerciales.
        Route::get('visitas', [VisitaController::class, 'index'])->name('api.visitas.index');
        Route::post('visitas', [VisitaController::class, 'store'])->name('api.visitas.store');
        Route::get('visitas/{visita}', [VisitaController::class, 'show'])->name('api.visitas.show');
    });
});
