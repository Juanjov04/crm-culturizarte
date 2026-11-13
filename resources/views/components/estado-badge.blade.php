@props(['activo' => true])

@if ($activo)
    <span class="pill bg-green-50 text-green-700 ring-1 ring-green-200"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Activa</span>
@else
    <span class="pill bg-slate-100 text-slate-600 ring-1 ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Inactiva</span>
@endif
