<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-bold text-white">Acceso al Portal CRM</h1>
        <p class="mt-1 text-sm text-slate-400">Gestión de Visitas B2B e Inteligencia de Negocios</p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="block w-full rounded-xl border-white/10 bg-navy-950 text-sm text-white placeholder-slate-500 focus:border-brand-500 focus:ring-brand-500"
                placeholder="usuario@culturizarte.com">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
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
    </form>
</x-guest-layout>
