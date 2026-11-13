<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Acceso al Portal CRM · Culturizarte</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-navy-950 font-sans text-slate-200 antialiased">
        <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(ellipse_at_top,_#16213a,_#0a1020_70%)] px-4 py-10">
            <div class="auth-card w-full max-w-md rounded-3xl border border-white/10 bg-navy-900/90 px-6 py-8 shadow-2xl shadow-black/40 sm:px-8">
                <div class="mb-6 flex justify-center">
                    <x-application-logo class="h-16" />
                </div>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
