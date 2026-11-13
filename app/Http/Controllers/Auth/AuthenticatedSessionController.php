<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Como en el prototipo (Figura 9), se listan los usuarios precargados para agilizar las pruebas.
        // En producción no se muestran para no exponer los correos de la fuerza de ventas.
        $precargados = ['Asesor' => collect(), 'Administrador' => collect()];

        if (! app()->isProduction()) {
            foreach (array_keys($precargados) as $rol) {
                // whereHas en lugar de User::role(): no falla si el rol aún no se ha sembrado.
                $precargados[$rol] = User::whereHas('roles', fn ($q) => $q->where('name', $rol))
                    ->orderBy('name')->limit(6)->get(['id', 'name', 'email']);
            }
        }

        return view('auth.login', ['precargados' => $precargados]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
