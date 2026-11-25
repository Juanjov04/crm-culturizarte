@php
    $impresion = $impresion ?? false;
    $tarjetas = [
        ['titulo' => 'Visitas del período', 'valor' => number_format($kpis['visitas']), 'nota' => $kpis['colegios_visitados'].' colegios visitados', 'color' => 'text-slate-900'],
        ['titulo' => 'Colegios activos', 'valor' => number_format($kpis['colegios_activos']), 'nota' => $filtros['provincia'] ?? 'Todo el país', 'color' => 'text-slate-900'],
        ['titulo' => 'Tasa de adopción', 'valor' => $kpis['tasa_adopcion'].' %', 'nota' => $kpis['colegios_adoptados'].' colegios adoptaron textos', 'color' => 'text-green-600'],
        ['titulo' => 'Churn rate', 'valor' => $kpis['churn_rate'].' %', 'nota' => 'Colegios que adoptaron y desertaron', 'color' => $kpis['churn_rate'] > 0 ? 'text-brand-600' : 'text-slate-900'],
        ['titulo' => 'Muestras entregadas', 'valor' => number_format($kpis['muestras']), 'nota' => isset($filtros['serie']) ? 'Serie '.$filtros['serie'] : 'Unidades de todas las series', 'color' => 'text-slate-900'],
    ];
    $estadoColor = [
        'adoptado' => 'bg-green-50 text-green-700 ring-green-200',
        'interesado' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'prospecto' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'desertado' => 'bg-red-50 text-red-700 ring-red-200',
    ];
    $animar = $impresion ? 'no' : 'si';
@endphp

{{-- KPIs (Figura 11: tarjetas de resumen) --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ($tarjetas as $t)
        <div class="card p-5 break-inside-avoid">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $t['titulo'] }}</p>
            <p class="mt-2 text-3xl font-bold {{ $t['color'] }}">{{ $t['valor'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $t['nota'] }}</p>
        </div>
    @endforeach
</div>

{{-- Gráficos --}}
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card p-5 lg:col-span-2 break-inside-avoid">
        <h3 class="font-bold text-slate-900">Visitas por mes</h3>
        <p class="text-xs text-slate-500">Gestión de campo registrada por los asesores.</p>
        <div class="mt-4 h-64"><canvas data-grafico="visitasPorMes" data-animar="{{ $animar }}" data-datos='@json($graficos['visitasPorMes'])' aria-label="Visitas por mes"></canvas></div>
    </div>
    <div class="card p-5 break-inside-avoid">
        <h3 class="font-bold text-slate-900">Estado de los colegios</h3>
        <p class="text-xs text-slate-500">Según la última visita del período.</p>
        <div class="mt-4 h-64"><canvas data-grafico="estados" data-animar="{{ $animar }}" data-datos='@json($graficos['estados'])' aria-label="Estado de los colegios"></canvas></div>
    </div>
    <div class="card p-5 lg:col-span-2 break-inside-avoid">
        <h3 class="font-bold text-slate-900">Adopción y deserción por provincia</h3>
        <p class="text-xs text-slate-500">Colegios cuyo último estado es adoptado o desertado.</p>
        <div class="mt-4 h-64"><canvas data-grafico="provincias" data-animar="{{ $animar }}" data-datos='@json($graficos['provincias'])' aria-label="Adopción por provincia"></canvas></div>
    </div>
    <div class="card p-5 break-inside-avoid">
        <h3 class="font-bold text-slate-900">Muestras por serie</h3>
        <p class="text-xs text-slate-500">Unidades entregadas a docentes.</p>
        <div class="mt-4 h-64"><canvas data-grafico="series" data-animar="{{ $animar }}" data-datos='@json($graficos['series'])' aria-label="Muestras por serie"></canvas></div>
    </div>
    <div class="card p-5 lg:col-span-3 break-inside-avoid">
        <h3 class="font-bold text-slate-900">Retención interanual: colegios que adoptaron frente a los que desertaron</h3>
        <p class="text-xs text-slate-500">Último estado de cada colegio en cada año (CU-W02).</p>
        <div class="mt-4 h-64"><canvas data-grafico="anual" data-animar="{{ $animar }}" data-datos='@json($graficos['anual'])' aria-label="Comparativa interanual"></canvas></div>
    </div>
</div>

{{-- Ranking de asesores --}}
<div class="card mt-6 overflow-x-auto break-inside-avoid">
    <h3 class="border-b border-slate-100 p-5 font-bold text-slate-900">Gestión por asesor</h3>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
            <tr>
                <th class="px-5 py-3">Asesor</th>
                <th class="px-5 py-3 text-center">Visitas</th>
                <th class="px-5 py-3 text-center">Colegios visitados</th>
                <th class="px-5 py-3 text-center">Muestras entregadas</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($ranking as $fila)
                <tr>
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $fila->asesor }}</td>
                    <td class="px-5 py-3 text-center">{{ $fila->visitas }}</td>
                    <td class="px-5 py-3 text-center">{{ $fila->colegios }}</td>
                    <td class="px-5 py-3 text-center">{{ $fila->muestras }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No hay visitas en el período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Estado comercial de cada colegio --}}
<div class="card mt-6 overflow-x-auto">
    <h3 class="border-b border-slate-100 p-5 font-bold text-slate-900">Estado comercial por colegio</h3>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
            <tr>
                <th class="px-5 py-3">Colegio</th>
                <th class="px-5 py-3">Ubicación</th>
                <th class="px-5 py-3">Estado</th>
                <th class="px-5 py-3 whitespace-nowrap">Última visita</th>
                <th class="px-5 py-3">Asesor</th>
                <th class="px-5 py-3 text-center">Visitas</th>
                <th class="px-5 py-3 text-center">Muestras</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($colegios as $c)
                <tr class="break-inside-avoid">
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900">{{ $c->colegio }}</p>
                        <p class="text-xs text-slate-500">AMIE {{ $c->codigo_amie }} · {{ $c->tipo }}</p>
                    </td>
                    <td class="px-5 py-3">{{ $c->ciudad }}<span class="block text-xs text-slate-400">{{ $c->provincia }}</span></td>
                    <td class="px-5 py-3"><span class="pill ring-1 {{ $estadoColor[$c->estado] ?? '' }}">{{ ucfirst($c->estado) }}</span></td>
                    <td class="px-5 py-3 whitespace-nowrap">{{ $c->ultima_visita->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">{{ $c->asesor }}</td>
                    <td class="px-5 py-3 text-center">{{ $c->visitas }}</td>
                    <td class="px-5 py-3 text-center">{{ $c->muestras }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">No hay colegios visitados en el período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
