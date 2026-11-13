<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocenteController;
use App\Http\Controllers\Web\InstitucionController;
use App\Http\Controllers\Web\VisitaController;
use Illuminate\Support\Facades\Route;

// La raíz lleva al acceso del portal CRM; si ya hay sesión, el middleware "guest" envía al dashboard.
Route::redirect('/', '/login');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Sprint 3: gestión del directorio B2B (solo administrador).
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::resource('instituciones', InstitucionController::class)
        ->parameters(['instituciones' => 'institucion']);
    Route::resource('docentes', DocenteController::class)->except('show');
});

// Sprint 4: visitas comerciales (administrador audita todas, asesor ve las suyas).
Route::middleware(['auth', 'verified', 'role:Administrador|Asesor'])->group(function () {
    Route::resource('visitas', VisitaController::class)->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/auth.php';
