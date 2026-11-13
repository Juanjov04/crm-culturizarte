@props(['active' => false, 'icon'])

@php
    $icons = [
        'dashboard' => 'M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm2 5h6',
        'pin' => 'M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11zm0-8a3 3 0 100-6 3 3 0 000 6z',
        'users' => 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 014-4h3a4 4 0 014 4v2H9zm3-11a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z',
        'clipboard' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h4',
    ];
@endphp

<a {{ $attributes->class([
    'flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
    'bg-brand-500 text-white shadow-lg shadow-brand-900/30' => $active,
    'text-slate-300 hover:bg-white/5 hover:text-white' => ! $active,
]) }}>
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['dashboard'] }}"/></svg>
    <span class="flex-1">{{ $slot }}</span>
    @if ($active)
        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
    @endif
</a>
