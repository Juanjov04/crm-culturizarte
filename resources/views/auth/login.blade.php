@php
    $asesores = $precargados['Asesor'] ?? collect();
    $admins = $precargados['Administrador'] ?? collect();
    $rolInicial = $admins->pluck('email')->contains(old('email')) ? 'admin' : 'asesor';
    $emailInicial = old('email', $asesores->first()?->email ?? '');
    $iniciales = fn ($nombre) => collect(explode(' ', $nombre))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $grupos = [
        'asesor' => ['lista' => $asesores, 'titulo' => 'Seleccionar asesor de campo', 'etiqueta' => 'Asesor'],
        'admin' => ['lista' => $admins, 'titulo' => 'Seleccionar administrador', 'etiqueta' => 'Admin'],
    ];
@endphp

<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-bold text-white">Acceso al Portal CRM</h1>
        <p class="mt-1 text-sm text-slate-400">Gestión de Visitas B2B e Inteligencia de Negocios</p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5"
        x-data="{
            rol: @js($rolInicial),
            email: @js($emailInicial),
            listas: { asesor: @js($asesores->pluck('email')), admin: @js($admins->pluck('email')) },
            cambiarRol(r) {
                this.rol = r;
                this.email = this.listas[r].length ? this.listas[r][0] : '';
            },
        }">
        @csrf

        {{-- Selector de perfil --}}
        <div class="grid grid-cols-2 gap-1 rounded-2xl border border-white/10 bg-navy-950 p-1 text-sm font-semibold">
            <button type="button" @click="cambiarRol('asesor')"
                :class="rol === 'asesor' ? 'bg-brand-500 text-white shadow' : 'text-slate-300 hover:text-white'"
                class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                Asesor Móvil
            </button>
            <button type="button" @click="cambiarRol('admin')"
                :class="rol === 'admin' ? 'bg-brand-500 text-white shadow' : 'text-slate-300 hover:text-white'"
                class="flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/></svg>
                Admin Maestro
            </button>
        </div>

        {{-- Usuarios precargados por perfil (solo fuera de producción) --}}
        @foreach ($grupos as $clave => $grupo)
            @if ($grupo['lista']->isNotEmpty())
                <div x-show="rol === '{{ $clave }}'" @if ($clave !== $rolInicial) style="display: none" @endif>
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">{{ $grupo['titulo'] }}</p>
                    <div class="space-y-2">
                        @foreach ($grupo['lista'] as $u)
                            <button type="button" @click="email = @js($u->email)"
                                :class="email === @js($u->email) ? 'border-brand-500/70 bg-brand-500/10' : 'border-white/10 bg-navy-950 hover:border-white/20'"
                                class="flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition">
                                <span :class="email === @js($u->email) ? 'bg-brand-500 text-white' : 'bg-slate-700 text-slate-200'"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold">{{ $iniciales($u->name) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-white">{{ $u->name }}</span>
                                    <span class="block truncate text-xs text-slate-400">{{ $u->email }}</span>
                                </span>
                                <span class="rounded-md bg-slate-700/60 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-300">{{ $grupo['etiqueta'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Correo: se llena al elegir un usuario, o se escribe a mano --}}
        <div>
            <label for="email" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Correo electrónico</label>
            <input id="email" type="email" name="email" x-model="email" value="{{ $emailInicial }}" required autocomplete="username"
                class="block w-full rounded-xl border-white/10 bg-navy-950 text-sm text-white placeholder-slate-500 focus:border-brand-500 focus:ring-brand-500"
                placeholder="usuario@culturizarte.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400"
                x-text="rol === 'asesor' ? 'Contraseña del asesor' : 'Contraseña del administrador'">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" autofocus
                class="block w-full rounded-xl border-white/10 bg-navy-950 text-sm text-white placeholder-slate-500 focus:border-brand-500 focus:ring-brand-500"
                placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="btn-primary w-full py-3 focus:ring-offset-navy-900">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5m5 5H3"/></svg>
            Ingresar al Portal CRM
        </button>

        @if (Route::has('password.request'))
            <p class="text-center">
                <a href="{{ route('password.request') }}" class="text-xs text-slate-400 hover:text-white">¿Olvidaste tu contraseña?</a>
            </p>
        @endif

        <div class="flex gap-3 rounded-2xl border border-white/10 bg-navy-950 px-4 py-3 text-xs leading-relaxed text-slate-400">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
            <span>El asesor registra sus visitas desde la app móvil; el administrador gestiona el directorio B2B y audita las visitas desde este panel web.</span>
        </div>
    </form>
</x-guest-layout>
