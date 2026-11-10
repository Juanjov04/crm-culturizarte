<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva institución</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('instituciones.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg">
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
