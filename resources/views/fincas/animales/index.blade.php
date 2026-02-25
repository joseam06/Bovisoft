@extends('layouts.app')

@section('title', 'Gestión de Animales')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-paw text-red-700 text-2xl"></i>
                </span>
                Gestión de Animales
            </h1>
            <p class="text-red-100 mt-2 ml-16">Administra tu inventario ganadero</p>
        </div>
        <a href="{{ route('animales.create') }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
            <i class="fa-solid fa-plus mr-2"></i>Nuevo Animal
        </a>
    </div>

    <!-- Mensajes de éxito -->
    @if(session('success'))
        <div class="glass-effect rounded-xl p-4 mb-6 border-2 border-green-300 bg-green-50">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-check-circle text-white text-lg"></i>
                </div>
                <p class="text-green-800 font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Total Animales -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Total Animales</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                        {{ $animales->count() }}
                    </h3>
                    <p class="text-blue-600 text-sm mt-2 font-medium">
                        <i class="fa-solid fa-arrow-up mr-1"></i>Activos
                    </p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-cow text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Vacas -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Vacas</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-pink-600 to-pink-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'vaca')->count() }}
                    </h3>
                    <p class="text-pink-600 text-sm mt-2 font-medium">
                        <i class="fa-solid fa-venus mr-1"></i>Hembras
                    </p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-pink-600 to-pink-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-female text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Toros -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Toros</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-purple-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'toro')->count() }}
                    </h3>
                    <p class="text-purple-600 text-sm mt-2 font-medium">
                        <i class="fa-solid fa-mars mr-1"></i>Machos
                    </p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-male text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Terneros -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Terneros</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-yellow-600 to-yellow-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'ternero')->count() }}
                    </h3>
                    <p class="text-yellow-600 text-sm mt-2 font-medium">
                        <i class="fa-solid fa-baby mr-1"></i>Crías
                    </p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-baby text-white text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Animales -->
    <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden border-4 border-white/50">
        @if($animales->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-red-700 to-red-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-barcode mr-2"></i>Código
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-tag mr-2"></i>Nombre
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-cow mr-2"></i>Tipo
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-venus-mars mr-2"></i>Sexo
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-dna mr-2"></i>Raza
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-home mr-2"></i>Finca
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-calendar mr-2"></i>Edad
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-weight-scale mr-2"></i>Peso
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-cog mr-2"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y-2 divide-gray-200">
                        @foreach($animales as $animal)
                            <tr class="hover:bg-red-50 transition-all">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-md">
                                        {{ $animal->codigo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-200 rounded-xl flex items-center justify-center mr-3">
                                            <i class="fa-solid fa-cow text-gray-600"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900">
                                            {{ $animal->nombre ?: 'Sin nombre' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold shadow-md
                                        @if($animal->tipo === 'vaca') bg-gradient-to-r from-pink-600 to-pink-800 text-white
                                        @elseif($animal->tipo === 'toro') bg-gradient-to-r from-purple-600 to-purple-800 text-white
                                        @elseif($animal->tipo === 'ternero') bg-gradient-to-r from-yellow-600 to-yellow-800 text-white
                                        @else bg-gradient-to-r from-green-600 to-green-800 text-white
                                        @endif">
                                        <i class="fas fa-{{ $animal->tipo === 'vaca' ? 'female' : ($animal->tipo === 'toro' ? 'male' : 'baby') }} mr-1"></i>
                                        {{ $animal->tipo_formateado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-700">
                                        <i class="fas fa-{{ $animal->sexo === 'macho' ? 'mars text-blue-600' : 'venus text-pink-600' }} mr-1"></i>
                                        {{ ucfirst($animal->sexo) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ $animal->raza ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center text-sm font-medium text-gray-700">
                                        <i class="fas fa-home mr-2 text-green-600"></i>
                                        {{ $animal->finca->nombre }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ $animal->edad ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($animal->peso_actual)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-gray-200 text-gray-800">
                                            {{ $animal->peso_actual }} kg
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('animales.show', $animal) }}" 
                                           class="w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center justify-center transition-all shadow-md hover:shadow-lg"
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('animales.edit', $animal) }}" 
                                           class="w-9 h-9 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg flex items-center justify-center transition-all shadow-md hover:shadow-lg"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('animales.destroy', $animal) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('¿Estás seguro de eliminar este animal?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="w-9 h-9 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center justify-center transition-all shadow-md hover:shadow-lg"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-16 px-6">
                <div class="w-32 h-32 bg-gradient-to-br from-red-600 to-red-800 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                    <i class="fas fa-cow text-white text-6xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">No hay animales registrados</h3>
                <p class="text-gray-600 mb-6">Comienza agregando tu primer animal al sistema</p>
                <a href="{{ route('animales.create') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>Registrar Primer Animal
                </a>
            </div>
        @endif
    </div>
</div>
@endsection