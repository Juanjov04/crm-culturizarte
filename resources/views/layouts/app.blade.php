<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CRM Culturizarte</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-800 antialiased">
        <div x-data="{ menu: false }" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:pl-64">
                <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    @isset($header)
                        <div class="card mb-6 px-6 py-5">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
