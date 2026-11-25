<?php

namespace App\Support;

use App\Models\Institucion;
use App\Models\MuestraEntregada;
use App\Models\Producto;
use App\Models\Visita;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sprint 7 - Indicadores de gestión comercial y retención (RF-04, CU-W02).
 *
 * El estado comercial de un colegio es el estado de adopción registrado en su
 * visita más reciente. Con eso se calculan:
 *  - Tasa de adopción: % de colegios visitados en el período cuyo último estado
 *    del período es "adoptado".
 *  - Churn rate (tasa de deserción): de los colegios que alguna vez adoptaron
 *    textos (hasta la fecha final), % cuyo último estado es "desertado".
 * Los cálculos se hacen en tiempo real con lo último sincronizado por los asesores.
 */
class Indicadores
{
    public readonly Carbon $desde;

    public readonly Carbon $hasta;

    public readonly ?string $provincia;

    public readonly ?int $asesorId;

    public readonly ?string $serie;

    public function __construct(array $filtros = [])
    {
        $this->hasta = isset($filtros['hasta']) && $filtros['hasta'] !== ''
            ? Carbon::parse($filtros['hasta'])->endOfDay()
            : now()->endOfDay();
        $this->desde = isset($filtros['desde']) && $filtros['desde'] !== ''
            ? Carbon::parse($filtros['desde'])->startOfDay()
            : $this->hasta->copy()->startOfYear();
        $this->provincia = ($filtros['provincia'] ?? '') !== '' ? $filtros['provincia'] : null;
        $this->asesorId = ! empty($filtros['asesor_id']) ? (int) $filtros['asesor_id'] : null;
        $this->serie = in_array($filtros['serie'] ?? null, Producto::SERIES, true) ? $filtros['serie'] : null;
    }

    /** Filtros normalizados para formularios y enlaces de exportación. */
    public function filtros(): array
    {
        return array_filter([
            'desde' => $this->desde->toDateString(),
            'hasta' => $this->hasta->toDateString(),
            'provincia' => $this->provincia,
            'asesor_id' => $this->asesorId,
            'serie' => $this->serie,
        ], fn ($v) => $v !== null);
    }

    // --- Consultas base ---

    /** Visitas hasta la fecha final con los filtros de zona y asesor (sin fecha inicial). */
    private function visitasHastaFin()
    {
        return Visita::query()
            ->where('fecha_visita', '<=', $this->hasta)
            ->when($this->asesorId, fn ($q) => $q->where('user_id', $this->asesorId))
            ->when($this->provincia, fn ($q) => $q->whereHas('institucion', fn ($i) => $i->where('provincia', $this->provincia)));
    }

    /** Visitas del período filtrado. */
    public function visitasDelPeriodo()
    {
        return $this->visitasHastaFin()->where('fecha_visita', '>=', $this->desde);
    }

    /** Muestras entregadas en el período (la serie solo filtra las muestras). */
    public function muestrasDelPeriodo()
    {
        return MuestraEntregada::query()
            ->whereHas('visita', function ($v) {
                $v->whereBetween('fecha_visita', [$this->desde, $this->hasta])
                    ->when($this->asesorId, fn ($q) => $q->where('user_id', $this->asesorId))
                    ->when($this->provincia, fn ($q) => $q->whereHas('institucion', fn ($i) => $i->where('provincia', $this->provincia)));
            })
            ->when($this->serie, fn ($q) => $q->whereHas('producto', fn ($p) => $p->where('serie', $this->serie)));
    }

    /**
     * Último estado de adopción de cada colegio según sus visitas.
     *
     * @return Collection<int, object{institucion_id:int, estado:string, fecha:Carbon, user_id:int, visitas:int}>
     */
    private function ultimoEstadoPorColegio($consulta): Collection
    {
        return $consulta->orderBy('fecha_visita')->orderBy('id')
            ->get(['id', 'institucion_id', 'user_id', 'fecha_visita', 'estado_adopcion'])
            ->groupBy('institucion_id')
            ->map(fn (Collection $visitas) => (object) [
                'institucion_id' => $visitas->last()->institucion_id,
                'estado' => $visitas->last()->estado_adopcion,
                'fecha' => $visitas->last()->fecha_visita,
                'user_id' => $visitas->last()->user_id,
                'visitas' => $visitas->count(),
                'alguna_vez_adopto' => $visitas->contains('estado_adopcion', 'adoptado'),
            ]);
    }

    // --- KPIs ---

    public function kpis(): array
    {
        $estadosPeriodo = $this->ultimoEstadoPorColegio($this->visitasDelPeriodo());
        $visitados = $estadosPeriodo->count();
        $adoptados = $estadosPeriodo->where('estado', 'adoptado')->count();

        return [
            'visitas' => $this->visitasDelPeriodo()->count(),
            'colegios_activos' => Institucion::activas()->when($this->provincia, fn ($q) => $q->where('provincia', $this->provincia))->count(),
            'colegios_visitados' => $visitados,
            'tasa_adopcion' => $visitados > 0 ? round($adoptados * 100 / $visitados, 1) : 0.0,
            'colegios_adoptados' => $adoptados,
            'churn_rate' => $this->churnRate(),
            'muestras' => (int) $this->muestrasDelPeriodo()->sum('cantidad'),
        ];
    }

    /** Churn rate (%): colegios que adoptaron y cuyo último estado es "desertado". */
    public function churnRate(?Carbon $hasta = null): float
    {
        $consulta = $this->visitasHastaFin();
        if ($hasta) {
            $consulta->where('fecha_visita', '<=', $hasta);
        }

        $estados = $this->ultimoEstadoPorColegio($consulta)->where('alguna_vez_adopto', true);
        $desertados = $estados->where('estado', 'desertado')->count();

        return $estados->count() > 0 ? round($desertados * 100 / $estados->count(), 1) : 0.0;
    }

    // --- Datos para gráficos ---

    /** Línea: visitas por mes del período. */
    public function visitasPorMes(): array
    {
        $porMes = $this->visitasDelPeriodo()->get(['fecha_visita'])
            ->countBy(fn ($v) => $v->fecha_visita->format('Y-m'));

        $meses = [];
        for ($mes = $this->desde->copy()->startOfMonth(); $mes <= $this->hasta; $mes->addMonth()) {
            $meses[ucfirst($mes->copy()->locale('es')->isoFormat('MMM YYYY'))] = $porMes[$mes->format('Y-m')] ?? 0;
        }

        return $meses;
    }

    /** Pastel: colegios del período según su último estado. */
    public function estadosDeColegios(): array
    {
        $conteo = $this->ultimoEstadoPorColegio($this->visitasDelPeriodo())->countBy('estado');

        return collect(Visita::ESTADOS_ADOPCION)
            ->mapWithKeys(fn ($e) => [ucfirst($e) => $conteo[$e] ?? 0])
            ->all();
    }

    /** Barras: colegios adoptados y desertados por provincia (zona). */
    public function adopcionesPorProvincia(): array
    {
        $estados = $this->ultimoEstadoPorColegio($this->visitasDelPeriodo());
        $provincias = Institucion::whereIn('id', $estados->keys())->pluck('provincia', 'id');

        return $estados->groupBy(fn ($e) => $provincias[$e->institucion_id] ?? 'Sin provincia')
            ->map(fn ($grupo) => [
                'adoptados' => $grupo->where('estado', 'adoptado')->count(),
                'desertados' => $grupo->where('estado', 'desertado')->count(),
            ])
            ->sortKeys()
            ->all();
    }

    /** Barras: unidades de muestras entregadas por serie de libros. */
    public function muestrasPorSerie(): array
    {
        $porSerie = $this->muestrasDelPeriodo()->with('producto:id,serie')->get(['producto_id', 'cantidad'])
            ->groupBy(fn ($m) => $m->producto->serie)
            ->map(fn ($g) => (int) $g->sum('cantidad'));

        return collect(Producto::SERIES)->mapWithKeys(fn ($s) => [$s => $porSerie[$s] ?? 0])->all();
    }

    /**
     * CU-W02: comparativa interanual de colegios que adoptaron frente a los que
     * desertaron (según el último estado de cada colegio en cada año).
     */
    public function comparativaAnual(): array
    {
        $visitas = Visita::query()
            ->when($this->asesorId, fn ($q) => $q->where('user_id', $this->asesorId))
            ->when($this->provincia, fn ($q) => $q->whereHas('institucion', fn ($i) => $i->where('provincia', $this->provincia)))
            ->where('fecha_visita', '<=', $this->hasta)
            ->orderBy('fecha_visita')->orderBy('id')
            ->get(['id', 'institucion_id', 'fecha_visita', 'estado_adopcion']);

        return $visitas->groupBy(fn ($v) => $v->fecha_visita->year)
            ->map(function (Collection $delAnio) {
                $ultimos = $delAnio->groupBy('institucion_id')->map(fn ($v) => $v->last()->estado_adopcion);

                return ['adoptados' => $ultimos->filter(fn ($e) => $e === 'adoptado')->count(), 'desertados' => $ultimos->filter(fn ($e) => $e === 'desertado')->count()];
            })
            ->sortKeys()
            ->all();
    }

    /** Ranking de asesores del período: visitas, colegios y muestras. */
    public function rankingAsesores(): Collection
    {
        $visitas = $this->visitasDelPeriodo()->with('user:id,name')->get(['id', 'user_id', 'institucion_id']);
        $muestras = $this->muestrasDelPeriodo()->with('visita:id,user_id')->get(['visita_id', 'cantidad'])
            ->groupBy(fn ($m) => $m->visita->user_id)
            ->map(fn ($g) => (int) $g->sum('cantidad'));

        return $visitas->groupBy('user_id')
            ->map(fn ($g, $userId) => (object) [
                'asesor' => $g->first()->user->name,
                'visitas' => $g->count(),
                'colegios' => $g->pluck('institucion_id')->unique()->count(),
                'muestras' => $muestras[$userId] ?? 0,
            ])
            ->sortByDesc('visitas')
            ->values();
    }

    /**
     * Tabla del estado comercial de cada colegio visitado en el período.
     *
     * @return Collection<int, object>
     */
    public function estadoPorColegio(): Collection
    {
        $estados = $this->ultimoEstadoPorColegio($this->visitasDelPeriodo());
        $colegios = Institucion::whereIn('id', $estados->keys())->get(['id', 'nombre', 'codigo_amie', 'provincia', 'ciudad', 'tipo'])->keyBy('id');
        $asesores = \App\Models\User::whereIn('id', $estados->pluck('user_id'))->pluck('name', 'id');
        $muestras = $this->muestrasDelPeriodo()->with('visita:id,institucion_id')->get(['visita_id', 'cantidad'])
            ->groupBy(fn ($m) => $m->visita->institucion_id)
            ->map(fn ($g) => (int) $g->sum('cantidad'));

        return $estados->map(fn ($e) => (object) [
            'institucion_id' => $e->institucion_id,
            'colegio' => $colegios[$e->institucion_id]->nombre ?? '—',
            'codigo_amie' => $colegios[$e->institucion_id]->codigo_amie ?? '',
            'tipo' => $colegios[$e->institucion_id]->tipo ?? '',
            'provincia' => $colegios[$e->institucion_id]->provincia ?? '',
            'ciudad' => $colegios[$e->institucion_id]->ciudad ?? '',
            'estado' => $e->estado,
            'ultima_visita' => $e->fecha,
            'asesor' => $asesores[$e->user_id] ?? '—',
            'visitas' => $e->visitas,
            'muestras' => $muestras[$e->institucion_id] ?? 0,
        ])->sortBy('colegio')->values();
    }
}
