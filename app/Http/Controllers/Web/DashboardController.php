<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Institucion;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $visitas = Visita::query()->when(! $user->esAdministrador(), fn ($q) => $q->where('user_id', $user->id));

        $resumen = [
            'instituciones_activas' => Institucion::activas()->count(),
            'docentes' => Docente::count(),
            'visitas_mes' => (clone $visitas)->where('fecha_visita', '>=', now()->startOfMonth())->count(),
            'interes_alto' => (clone $visitas)->where('nivel_interes', 'alto')->count(),
        ];

        $ultimasVisitas = (clone $visitas)
            ->with(['user:id,name', 'institucion:id,nombre', 'docente:id,nombres,apellidos'])
            ->latest('fecha_visita')
            ->limit(5)
            ->get();

        return view('dashboard', compact('resumen', 'ultimasVisitas'));
    }
}
