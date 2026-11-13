<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Nuevo docente</h2>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto">
            <form method="POST" action="{{ route('docentes.store') }}" class="card p-6">
                @csrf
                @include('docentes._form')

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Guardar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
