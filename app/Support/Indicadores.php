<?php

namespace App\Support;

use App\Models\Institucion;
use App\Models\MuestraEntregada;
use App\Models\Producto;
use App\Models\User;
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

    // --- Datos en memoria (cada consulta a Supabase cuesta un viaje por internet) ---

    private ?Collection $visitasCargadas = null;

    private ?Collection $muestrasCargadas = null;

    private ?Collection $colegiosCargados = null;

    /** Todas las visitas hasta la fecha final, ordenadas; se consultan una sola vez. */
    private function visitasHasta(): Collection
    {
        return $this->visitasCargadas ??= $this->visitasHastaFin()
            ->orderBy('fecha_visita')->orderBy('id')
            ->get(['id', 'institucion_id', 'user_id', 'fecha_visita', 'estado_adopcion']);
    }

    private function visitasPeriodo(): Collection
    {
        return $this->visitasHasta()->filter(fn ($v) => $v->fecha_visita->gte($this->desde))->values();
    }

    /** Muestras del período con su serie, asesor y colegio (una consulta más sus relaciones). */
    private function muestras(): Collection
    {
        return $this->muestrasCargadas ??= $this->muestrasDelPeriodo()
            ->with(['producto:id,serie', 'visita:id,user_id,institucion_id'])
            ->get(['id', 'visita_id', 'producto_id', 'cantidad']);
    }

    /** Colegios que aparecen en las visitas cargadas. */
    private function colegios(): Collection
    {
        return $this->colegiosCargados ??= Institucion::whereIn('id', $this->visitasHasta()->pluck('institucion_id')->unique())
            ->get(['id', 'nombre', 'codigo_amie', 'provincia', 'ciudad', 'tipo'])
            ->keyBy('id');
    }

    /**
     * Último estado de adopción de cada colegio según sus visitas (ya ordenadas por fecha).
     *
     * @return Collection<int, object{institucion_id:int, estado:string, fecha:Carbon, user_id:int, visitas:int, alguna_vez_adopto:bool}>
     */
    private function ultimoEstadoPorColegio(Collection $visitas): Collection
    {
        return $visitas->groupBy('institucion_id')
            ->map(fn (Collection $deColegio) => (object) [
                'institucion_id' => $deColegio->last()->institucion_id,
                'estado' => $deColegio->last()->estado_adopcion,
                'fecha' => $deColegio->last()->fecha_visita,
                'user_id' => $deColegio->last()->user_id,
                'visitas' => $deColegio->count(),
                'alguna_vez_adopto' => $deColegio->contains('estado_adopcion', 'adoptado'),
            ]);
    }

    // --- KPIs ---

    public function kpis(): array
    {
        $estadosPeriodo = $this->ultimoEstadoPorColegio($this->visitasPeriodo());
        $visitados = $estadosPeriodo->count();
        $adoptados = $estadosPeriodo->where('estado', 'adoptado')->count();

        return [
            'visitas' => $this->visitasPeriodo()->count(),
            'colegios_activos' => Institucion::activas()->when($this->provincia, fn ($q) => $q->where('provincia', $this->provincia))->count(),
            'colegios_visitados' => $visitados,
            'tasa_adopcion' => $visitados > 0 ? round($adoptados * 100 / $visitados, 1) : 0.0,
            'colegios_adoptados' => $adoptados,
            'churn_rate' => $this->churnRate(),
            'muestras' => (int) $this->muestras()->sum('cantidad'),
        ];
    }

    /** Churn rate (%): colegios que adoptaron y cuyo último estado es "desertado". */
    public function churnRate(?Carbon $hasta = null): float
    {
        $visitas = $hasta ? $this->visitasHasta()->filter(fn ($v) => $v->fecha_visita->lte($hasta)) : $this->visitasHasta();
        $estados = $this->ultimoEstadoPorColegio($visitas)->where('alguna_vez_adopto', true);
        $desertados = $estados->where('estado', 'desertado')->count();

        return $estados->count() > 0 ? round($desertados * 100 / $estados->count(), 1) : 0.0;
    }

    // --- Datos para gráficos ---

    /** Línea: visitas por mes del período. */
    public function visitasPorMes(): array
    {
        $porMes = $this->visitasPeriodo()->countBy(fn ($v) => $v->fecha_visita->format('Y-m'));

        $meses = [];
        for ($mes = $this->desde->copy()->startOfMonth(); $mes <= $this->hasta; $mes->addMonth()) {
            $meses[ucfirst($mes->copy()->locale('es')->isoFormat('MMM YYYY'))] = $porMes[$mes->format('Y-m')] ?? 0;
        }

        return $meses;
    }

    /** Pastel: colegios del período según su último estado. */
    public function estadosDeColegios(): array
    {
        $conteo = $this->ultimoEstadoPorColegio($this->visitasPeriodo())->countBy('estado');

        return collect(Visita::ESTADOS_ADOPCION)
            ->mapWithKeys(fn ($e) => [ucfirst($e) => $conteo[$e] ?? 0])
            ->all();
    }

    /** Barras: colegios adoptados y desertados por provincia (zona). */
    public function adopcionesPorProvincia(): array
    {
        $colegios = $this->colegios();

        return $this->ultimoEstadoPorColegio($this->visitasPeriodo())
            ->groupBy(fn ($e) => $colegios[$e->institucion_id]->provincia ?? 'Sin provincia')
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
        $porSerie = $this->muestras()
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
        return $this->visitasHasta()->groupBy(fn ($v) => $v->fecha_visita->year)
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
        $visitas = $this->visitasPeriodo();
        $nombres = User::whereIn('id', $visitas->pluck('user_id')->unique())->pluck('name', 'id');
        $muestras = $this->muestras()
            ->groupBy(fn ($m) => $m->visita->user_id)
            ->map(fn ($g) => (int) $g->sum('cantidad'));

        return $visitas->groupBy('user_id')
            ->map(fn ($g, $userId) => (object) [
                'asesor' => $nombres[$userId] ?? '—',
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
        $estados = $this->ultimoEstadoPorColegio($this->visitasPeriodo());
        $colegios = $this->colegios();
        $asesores = User::whereIn('id', $estados->pluck('user_id')->unique())->pluck('name', 'id');
        $muestras = $this->muestras()
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
