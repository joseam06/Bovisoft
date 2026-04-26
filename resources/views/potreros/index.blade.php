@extends('layouts.dashboard')

@section('title', 'Gestión de Potreros')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Gestión de Potreros</h1>
        <a href="{{ route('potreros.create') }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl">
            <i class="fa-solid fa-plus mr-2"></i>Nuevo Potrero
        </a>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg shadow-lg alert-auto-close">
            <div class="flex items-center">
                <i class="fa-solid fa-check-circle text-2xl mr-3"></i>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg shadow-lg alert-auto-close">
            <div class="flex items-center">
                <i class="fa-solid fa-exclamation-circle text-2xl mr-3"></i>
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Total Potreros -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1 font-medium">Total Potreros</p>
                    <p class="text-4xl font-bold text-gray-800">{{ $total }}</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-map text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Potreros Activos -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1 font-medium">Activos</p>
                    <p class="text-4xl font-bold text-gray-800">{{ $activos }}</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- En Descanso -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1 font-medium">En Descanso</p>
                    <p class="text-4xl font-bold text-gray-800">{{ $en_descanso }}</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-pause-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Área Total -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm mb-1 font-medium">Área Total</p>
                    <p class="text-4xl font-bold text-gray-800">{{ number_format($area_total, 1) }}</p>
                    <p class="text-gray-500 text-xs mt-1">hectáreas</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-ruler-combined text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <form method="GET" action="{{ route('potreros.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fa-solid fa-search mr-2"></i>Buscar por nombre o código
                </label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Buscar potrero..." 
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>

            <!-- Filtro por Finca -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fa-solid fa-map-marker-alt mr-2"></i>Finca
                </label>
                <select name="finca_id" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">Todas las fincas</option>
                    @foreach($fincas as $finca)
                        <option value="{{ $finca->id }}" {{ request('finca_id') == $finca->id ? 'selected' : '' }}>
                            {{ $finca->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Estado -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fa-solid fa-filter mr-2"></i>Estado
                </label>
                <select name="estado" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="en_descanso" {{ request('estado') == 'en_descanso' ? 'selected' : '' }}>En Descanso</option>
                    <option value="en_mantenimiento" {{ request('estado') == 'en_mantenimiento' ? 'selected' : '' }}>En Mantenimiento</option>
                </select>
            </div>

            <!-- Botones -->
            <div class="md:col-span-4 flex gap-3">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800 rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-search mr-2"></i>Buscar
                </button>
                <a href="{{ route('potreros.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-times mr-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Potreros -->
    <div class="glass-effect rounded-2xl shadow-xl border-4 border-white/50 overflow-hidden">
        @if($potreros->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-red-600 to-red-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-barcode mr-2"></i>Código
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-tag mr-2"></i>Nombre
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-map-marker-alt mr-2"></i>Finca
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-ruler-combined mr-2"></i>Área
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-cow mr-2"></i>Capacidad
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-percent mr-2"></i>Ocupación
                            </th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-info-circle mr-2"></i>Estado
                            </th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-white uppercase tracking-wider">
                                <i class="fa-solid fa-cog mr-2"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($potreros as $potrero)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-800 font-mono text-sm">{{ $potrero->codigo }}</td>
                                <td class="px-6 py-4 text-gray-800 font-medium">{{ $potrero->nombre }}</td>
                                <td class="px-6 py-4 text-gray-700 text-sm">
                                    <a href="{{ route('fincas.show', $potrero->finca) }}" class="text-red-600 hover:text-red-800 transition-colors font-medium">
                                        <i class="fa-solid fa-map-marker-alt mr-1"></i>{{ $potrero->finca->nombre }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-700 text-sm">
                                    @if($potrero->area)
                                        {{ number_format($potrero->area, 2) }} ha
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-700 text-sm font-medium">
                                    {{ $potrero->animales_actuales }} / {{ $potrero->capacidad_animales ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all
                                             @if($potrero->porcentaje_ocupacion >= 90) bg-red-500
                                             @elseif($potrero->porcentaje_ocupacion >= 70) bg-yellow-500
                                             @else bg-green-500
                                           @endif"
                                         style="--w: {{ min($potrero->porcentaje_ocupacion, 100) }}%"
                                        ></div>
                                        </div>
                                        <span class="text-gray-700 text-sm font-medium">{{ $potrero->porcentaje_ocupacion }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                   <div class="h-2 rounded-full transition-all
                                             @if($potrero->porcentaje_ocupacion >= 90) bg-red-500
                                             @elseif($potrero->porcentaje_ocupacion >= 70) bg-yellow-500
                                             @else bg-green-500
                                           @endif"
                                         style="--w: {{ min($potrero->porcentaje_ocupacion, 100) }}%"
                                        ></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('potreros.show', $potrero) }}" 
                                            class="w-10 h-10 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center justify-center transition-all shadow-lg"
                                            title="Ver detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('potreros.edit', $potrero) }}" 
                                            class="w-10 h-10 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg flex items-center justify-center transition-all shadow-lg"
                                            title="Editar">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                        <form action="{{ route('potreros.destroy', $potrero) }}" method="POST" class="inline"
                                            onsubmit="return confirm('¿Estás seguro de eliminar este potrero?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                class="w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-lg flex items-center justify-center transition-all shadow-lg"
                                                title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $potreros->links() }}
            </div>
        @else
            <div class="text-center py-16 bg-white">
                <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-map text-gray-400 text-4xl"></i>
                </div>
                <p class="text-gray-800 font-medium text-lg mb-2">No hay potreros registrados</p>
                <p class="text-gray-600 mb-6">Comienza registrando tu primer potrero</p>
                <a href="{{ route('potreros.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800 rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-plus mr-2"></i>Nuevo Potrero
                </a>
            </div>
        @endif
    </div>
</div>

<script>
// Auto-close alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
@endsection