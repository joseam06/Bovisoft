@extends('layouts.dashboard')

@section('title', 'Gestión de Animales')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-cow text-red-700 text-2xl"></i>
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
    <div id="success-alert" class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg shadow-lg animate-slide-up flex items-center justify-between">
        <div class="flex items-center">
            <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
        <button onclick="cerrarAlert()" class="text-green-700 hover:text-green-900 transition-colors">
            <i class="fa-solid fa-times text-xl"></i>
        </button>
    </div>

    <script>
    function cerrarAlert() {
        document.getElementById('success-alert').style.display = 'none';
    }

    setTimeout(function() {
        const alert = document.getElementById('success-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }
    }, 5000);
    </script>
    @endif

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <!-- Total Animales -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Total</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-red-600 to-red-800 bg-clip-text text-transparent">
                        {{ $animales->count() }}
                    </h3>
                    <p class="text-red-600 text-xs mt-2 font-medium">Activos</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-cow text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Vacas -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Vacas</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-pink-600 to-pink-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'vaca')->count() }}
                    </h3>
                    <p class="text-pink-600 text-xs mt-2 font-medium">Hembras</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-pink-600 to-pink-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-venus text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Toros -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Toros</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'toro')->count() }}
                    </h3>
                    <p class="text-blue-600 text-xs mt-2 font-medium">Machos</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-mars text-white text-2xl"></i>
                </div>
            </div>
        </div>

       
        <!-- Novillas -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Novillas</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-purple-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'novilla')->count() }}
                    </h3>
                    <p class="text-purple-600 text-xs mt-2 font-medium">Jóvenes</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-heart text-white text-2xl"></i>
                </div>
            </div>
        </div>
         <!-- Terneros -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Terneros</p>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-green-800 bg-clip-text text-transparent">
                        {{ $animales->where('tipo', 'ternero')->count() }}
                    </h3>
                    <p class="text-green-600 text-xs mt-2 font-medium">Crías</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-baby text-white text-2xl"></i>
                </div>
            </div>
        </div>

    </div>

    

    <!-- Búsqueda y Filtros -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Búsqueda -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </div>
                <input type="text" 
                       id="busqueda" 
                       placeholder="Buscar por nombre o código..." 
                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
            </div>

            <!-- Filtro por Tipo -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fa-solid fa-filter text-gray-400"></i>
                </div>
                <select id="filtro-tipo" 
                        class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none">
                    <option value="">Todos los tipos</option>
                    <option value="vaca">Vacas</option>
                    <option value="toro">Toros</option>
                    <option value="ternero">Terneros</option>
                    <option value="novilla">Novillas</option>
                </select>
            </div>

            <!-- Filtro por Finca -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fa-solid fa-home text-gray-400"></i>
                </div>
                <select id="filtro-finca" 
                        class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none">
                    <option value="">Todas las fincas</option>
                    @foreach($animales->pluck('finca')->unique() as $finca)
                        <option value="{{ $finca->id }}">{{ $finca->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Lista de Animales -->
    <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden border-4 border-white/50">
        @if($animales->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full" id="tabla-animales">
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
                            <tr class="hover:bg-red-50 transition-all animal-row" 
                                data-nombre="{{ strtolower($animal->nombre) }}" 
                                data-codigo="{{ strtolower($animal->codigo) }}"
                                data-tipo="{{ $animal->tipo }}"
                                data-finca="{{ $animal->finca_id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-gray-700 to-gray-900 text-white shadow-md">
                                        {{ $animal->codigo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-red-200 rounded-xl flex items-center justify-center mr-3">
                                            <i class="fa-solid fa-cow text-red-700"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900">
                                            {{ $animal->nombre ?: 'Sin nombre' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold shadow-md
                                        @if($animal->tipo === 'vaca') bg-gradient-to-r from-pink-600 to-pink-800 text-white
                                        @elseif($animal->tipo === 'toro') bg-gradient-to-r from-blue-600 to-blue-800 text-white
                                        @elseif($animal->tipo === 'ternero') bg-gradient-to-r from-green-600 to-green-800 text-white
                                        @else bg-gradient-to-r from-purple-600 to-purple-800 text-white
                                        @endif">
                                        <i class="fas fa-{{ $animal->tipo === 'vaca' ? 'venus' : ($animal->tipo === 'toro' ? 'mars' : 'baby') }} mr-1"></i>
                                        {{ ucfirst($animal->tipo) }}
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
                                        <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                                        {{ $animal->finca->nombre }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ $animal->edad ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($animal->peso_actual)
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-orange-100 text-orange-800">
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

            <!-- Mensaje sin resultados (oculto por defecto) -->
            <div id="sin-resultados" class="hidden text-center py-16 px-6">
                <div class="w-24 h-24 bg-gray-100 rounded-3xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-gray-400 text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No se encontraron resultados</h3>
                <p class="text-gray-600">Intenta con otros términos de búsqueda o filtros</p>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const busqueda = document.getElementById('busqueda');
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroFinca = document.getElementById('filtro-finca');
    const filas = document.querySelectorAll('.animal-row');
    const sinResultados = document.getElementById('sin-resultados');
    const tbody = document.querySelector('#tabla-animales tbody');

    function filtrarAnimales() {
        const textoBusqueda = busqueda.value.toLowerCase();
        const tipoSeleccionado = filtroTipo.value;
        const fincaSeleccionada = filtroFinca.value;
        let visibles = 0;

        filas.forEach(function(fila) {
            const nombre = fila.getAttribute('data-nombre');
            const codigo = fila.getAttribute('data-codigo');
            const tipo = fila.getAttribute('data-tipo');
            const finca = fila.getAttribute('data-finca');

            const coincideBusqueda = nombre.includes(textoBusqueda) || codigo.includes(textoBusqueda);
            const coincideTipo = !tipoSeleccionado || tipo === tipoSeleccionado;
            const coincideFinca = !fincaSeleccionada || finca === fincaSeleccionada;

            if (coincideBusqueda && coincideTipo && coincideFinca) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        if (visibles === 0) {
            tbody.style.display = 'none';
            sinResultados.classList.remove('hidden');
        } else {
            tbody.style.display = '';
            sinResultados.classList.add('hidden');
        }
    }

    busqueda.addEventListener('keyup', filtrarAnimales);
    filtroTipo.addEventListener('change', filtrarAnimales);
    filtroFinca.addEventListener('change', filtrarAnimales);
});
</script>
@endsection