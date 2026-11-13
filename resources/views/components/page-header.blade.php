@props(['title', 'subtitle' => null])

{{-- Tarjeta de encabezado como en el panel del prototipo (Figura 11). --}}
<div class="card mb-6 flex flex-wrap items-center justify-between gap-4 px-6 py-5">
    <div>
        <h1 class="text-xl font-bold text-slate-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
