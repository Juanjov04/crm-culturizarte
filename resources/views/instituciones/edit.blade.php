<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-900">Editar institución</h2>
    </x-slot>

    <div>
        <div class="max-w-3xl mx-auto">
            <x-flash />

            <form method="POST" action="{{ route('instituciones.update', $institucion) }}" class="card p-6">
                @csrf
                @method('PUT')
                @include('instituciones._form')

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('instituciones.show', $institucion) }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>

            <form method="POST" action="{{ route('instituciones.destroy', $institucion) }}" class="mt-6 text-right"
                onsubmit="return confirm('¿Eliminar esta institución y sus docentes?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Eliminar institución</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
