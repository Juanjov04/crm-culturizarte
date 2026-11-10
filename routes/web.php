<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DocenteController;
use App\Http\Controllers\Web\InstitucionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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

require __DIR__.'/auth.php';
