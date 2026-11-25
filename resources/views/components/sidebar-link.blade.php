@props(['active' => false, 'icon'])

@php
    $icons = [
        'dashboard' => 'M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm2 5h6',
        'pin' => 'M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11zm0-8a3 3 0 100-6 3 3 0 000 6z',
        'users' => 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H2v-2a4 4 0 014-4h3a4 4 0 014 4v2H9zm3-11a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z',
        'clipboard' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h4',
        'book' => 'M12 6.25v13m0-13C10.83 5.48 9.25 5 7.5 5S4.17 5.48 3 6.25v13C4.17 18.48 5.75 18 7.5 18s3.33.48 4.5 1.25m0-13C13.17 5.48 14.75 5 16.5 5s3.33.48 4.5 1.25v13C19.83 18.48 18.25 18 16.5 18s-3.33.48-4.5 1.25',
        'chart' => 'M4 19h16M7 16V10m5 6V6m5 10v-4',
        'box' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
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
