<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Nueva institución</h2>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto">
            <form method="POST" action="{{ route('instituciones.store') }}" class="card p-6">
                @csrf
                @include('instituciones._form')

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('instituciones.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Guardar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
