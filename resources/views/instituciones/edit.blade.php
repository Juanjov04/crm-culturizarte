<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar institución</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('instituciones.update', $institucion) }}" class="bg-white p-6 shadow-sm sm:rounded-lg">
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
