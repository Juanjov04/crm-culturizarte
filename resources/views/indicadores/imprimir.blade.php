<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Indicadores de gestión comercial · Culturizarte</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/indicadores.js'])
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            .no-imprimir { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .card { box-shadow: none !important; }
        }
    </style>
</head>
{{-- RF-04: vista del reporte para guardarla como PDF desde el navegador. --}}
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    <main class="mx-auto max-w-6xl p-6">
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo-culturizarte.png') }}" alt="Culturizarte" class="h-12">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Indicadores de gestión comercial</h1>
                    <p class="text-sm text-slate-500">
                        Del {{ $indicadores->desde->format('d/m/Y') }} al {{ $indicadores->hasta->format('d/m/Y') }}
                        · {{ $filtros['provincia'] ?? 'Todas las provincias' }}
                        · {{ isset($filtros['asesor_id']) ? ($asesores->firstWhere('id', $filtros['asesor_id'])?->name ?? 'Asesor') : 'Todos los asesores' }}
                        · {{ isset($filtros['serie']) ? 'Serie '.$filtros['serie'] : 'Todas las series' }}
                    </p>
                    <p class="text-xs text-slate-400">Generado el {{ now()->format('d/m/Y H:i') }} por {{ Auth::user()->name }}</p>
                </div>
            </div>
            <div class="no-imprimir flex gap-2">
                <button type="button" onclick="window.print()" class="btn-primary">Guardar como PDF</button>
                <a href="{{ route('indicadores.index', $filtros) }}" class="btn-secondary">Volver</a>
            </div>
        </header>

        @include('indicadores._contenido', ['impresion' => true])
    </main>
</body>
</html>
