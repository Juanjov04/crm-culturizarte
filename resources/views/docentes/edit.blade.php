<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Editar docente</h2>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto">
            <x-flash />

            <form method="POST" action="{{ route('docentes.update', $docente) }}" class="card p-6">
                @csrf
                @method('PUT')
                @include('docentes._form')

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('docentes.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>

            <form method="POST" action="{{ route('docentes.destroy', $docente) }}" class="mt-6 text-right"
                onsubmit="return confirm('¿Eliminar este docente?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Eliminar docente</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
