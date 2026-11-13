@php
    $user = Auth::user();
    $esAdmin = $user->hasRole('Administrador');
    $iniciales = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

{{-- Barra superior --}}
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur lg:pl-64">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" @click="menu = true" class="-ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <x-application-logo class="h-8 lg:hidden" />
            <span class="hidden h-6 w-px bg-slate-200 sm:block lg:hidden"></span>
            <span class="hidden text-sm font-semibold text-slate-800 sm:block">CRM Analítico B2B</span>
        </div>

        <div class="flex items-center gap-3">
            <span class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold md:flex">
                <span @class(['flex items-center gap-1.5 rounded-full px-3 py-1', 'bg-slate-900 text-white' => ! $esAdmin, 'text-slate-500' => $esAdmin])>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                    Asesor
                </span>
                <span @class(['flex items-center gap-1.5 rounded-full px-3 py-1', 'bg-slate-900 text-white' => $esAdmin, 'text-slate-500' => ! $esAdmin])>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/></svg>
                    Gerencia (Web)
                </span>
            </span>

            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 py-1 pl-1 pr-2">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2" title="Mi perfil">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-[11px] font-bold text-white">{{ $iniciales }}</span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-xs font-semibold text-slate-800">{{ $user->name }}</span>
                        <span class="block text-[11px] text-slate-400">{{ $user->getRoleNames()->first() ?? 'Sin rol' }}</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="ml-1 rounded-full p-1.5 text-slate-400 hover:bg-white hover:text-brand-500" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- Fondo oscuro del menú en móvil --}}
<div x-show="menu" x-transition.opacity @click="menu = false" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" style="display: none"></div>

{{-- Barra lateral --}}
<aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-[#1f2633] px-4 py-5 transition-transform lg:translate-x-0">
    <a href="{{ route('dashboard') }}" class="mb-8 block px-2">
        <span class="inline-block rounded-xl bg-white/5 px-3 py-2">
            <x-application-logo class="h-9" />
        </span>
        <span class="mt-3 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">
            {{ $esAdmin ? 'Panel administrativo' : 'Panel del asesor' }}
        </span>
    </a>

    <nav class="space-y-1.5">
        <x-sidebar-link icon="dashboard" :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ $esAdmin ? 'Dashboard' : 'Inicio' }}
        </x-sidebar-link>

        @role('Administrador')
            <p class="px-4 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Directorio B2B</p>
            <x-sidebar-link icon="pin" :href="route('instituciones.index')" :active="request()->routeIs('instituciones.*')">
                Instituciones
            </x-sidebar-link>
            <x-sidebar-link icon="users" :href="route('docentes.index')" :active="request()->routeIs('docentes.*')">
                Docentes
            </x-sidebar-link>
        @endrole

        @hasanyrole('Administrador|Asesor')
            @role('Administrador')
                <p class="px-4 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Gestión comercial</p>
            @endrole
            <x-sidebar-link icon="clipboard" :href="route('visitas.index')" :active="request()->routeIs('visitas.*')">
                {{ $esAdmin ? 'Auditoría de Visitas' : 'Mis visitas' }}
            </x-sidebar-link>
        @endhasanyrole
    </nav>

    <div class="mt-auto rounded-xl bg-white/5 px-4 py-3 text-xs text-slate-400">
        <span class="flex items-center gap-2 font-semibold text-slate-200">
            <span class="h-2 w-2 rounded-full bg-green-400"></span> Servidor activo
        </span>
        <span class="mt-1 block">Editorial Culturizarte · CRM B2B</span>
    </div>
</aside>
