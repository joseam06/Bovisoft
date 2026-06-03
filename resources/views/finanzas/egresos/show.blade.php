@extends('layouts.dashboard')

@section('title', 'Egreso — ' . $egreso->codigo)

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('finanzas.index') }}"
                class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $egreso->codigo }}</h1>
                <p class="text-red-100 mt-1">Detalle del egreso</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('finanzas.egresos.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg transition-all">
                <i class="fa-solid fa-plus"></i> Nuevo egreso
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="glass-effect border-4 border-green-400/50 rounded-2xl shadow-xl p-4 flex items-center space-x-3">
        <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
        <p class="text-green-700 font-semibold">{{ session('success') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Datos principales --}}
        <div class="lg:col-span-2 glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span class="w-9 h-9 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow">
                    <i class="fa-solid fa-circle-minus text-white text-sm"></i>
                </span>
                Datos del Egreso
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div>
                    <dt class="text-gray-500 font-medium">Código</dt>
                    <dd class="font-mono font-bold text-gray-800 mt-0.5">{{ $egreso->codigo }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Categoría</dt>
                    <dd class="mt-0.5">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                            {{ $egreso->categoria_formateada }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Finca</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $egreso->finca->nombre ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Fecha</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $egreso->fecha->format('d/m/Y') }}</dd>
                </div>
                @if($egreso->descripcion)
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 font-medium">Descripción</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $egreso->descripcion }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500 font-medium">Registrado</dt>
                    <dd class="text-gray-700 mt-0.5">{{ $egreso->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Registrado por</dt>
                    <dd class="text-gray-700 mt-0.5">{{ $egreso->user->name ?? '—' }}</dd>
                </div>
            </dl>

            @if($egreso->observaciones)
            <div class="mt-6 p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                <p class="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Observaciones</p>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $egreso->observaciones }}</p>
            </div>
            @endif
        </div>

        {{-- Panel monto + acciones --}}
        <div class="space-y-5">
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Monto registrado</p>
                <div class="flex items-end justify-center gap-1 mb-2">
                    <span class="text-xl font-bold text-gray-500 mb-2">$</span>
                    <span class="text-5xl font-extrabold text-red-700">
                        {{ number_format($egreso->monto, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">COP — {{ $egreso->fecha->format('d/m/Y') }}</p>
            </div>

            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-red-600"></i> Acciones
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('finanzas.index') }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-chart-line w-4 text-center"></i> Ver panel de finanzas
                    </a>
                    <a href="{{ route('finanzas.egresos.create') }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-plus w-4 text-center"></i> Nuevo egreso
                    </a>
                    @if($egreso->animal)
                    <a href="{{ route('animales.show', $egreso->animal) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-cow w-4 text-center"></i> Ver ficha del animal
                    </a>
                    @endif
                    @if($egreso->salud)
                    <a href="{{ route('salud.show', $egreso->salud) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-heart-pulse w-4 text-center"></i> Ver registro de salud
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Animal asociado (si existe) --}}
    @if($egreso->animal)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-3">
            <span class="w-9 h-9 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow">
                <i class="fa-solid fa-cow text-white text-sm"></i>
            </span>
            Animal Asociado
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Código</p>
                <p class="font-mono font-bold text-gray-800">{{ $egreso->animal->codigo }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Nombre</p>
                <p class="font-bold text-gray-800">{{ $egreso->animal->nombre ?? 'Sin nombre' }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Tipo / Raza</p>
                <p class="font-bold text-gray-800">{{ ucfirst($egreso->animal->tipo) }} — {{ $egreso->animal->raza ?? 'N/A' }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Estado</p>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                    {{ $egreso->animal->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($egreso->animal->estado) }}
                </span>
            </div>
        </div>
    </div>
    @endif

    {{-- Referencia a salud (si existe) --}}
    @if($egreso->salud)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-3">
            <span class="w-9 h-9 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center shadow">
                <i class="fa-solid fa-heart-pulse text-white text-sm"></i>
            </span>
            Registro de Salud Vinculado
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Código salud</p>
                <p class="font-mono font-bold text-gray-800">{{ $egreso->salud->codigo }}</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Producto</p>
                <p class="font-bold text-gray-800">{{ $egreso->salud->nombre_producto }}</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Fecha aplicación</p>
                <p class="font-bold text-gray-800">{{ $egreso->salud->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Costo en salud</p>
                <p class="font-bold text-gray-800">
                    {{ $egreso->salud->costo ? '$' . number_format($egreso->salud->costo, 0, ',', '.') : '—' }}
                </p>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('salud.show', $egreso->salud) }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 text-white text-sm font-bold rounded-xl shadow hover:from-purple-700 hover:to-purple-800 transition-all">
                <i class="fa-solid fa-eye"></i> Ver registro de salud completo
            </a>
        </div>
    </div>
    @endif

    {{-- Eliminación --}}
    <div class="glass-effect border-4 border-red-200/60 rounded-2xl shadow-xl p-5 flex items-center justify-between">
        <div>
            <p class="font-bold text-gray-800 text-sm">Eliminar este egreso</p>
            <p class="text-xs text-gray-500 mt-0.5">Esta acción no se puede deshacer.</p>
        </div>
        <form action="{{ route('finanzas.egresos.destroy', $egreso->id) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este egreso?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-xl border-2 border-red-300 transition-all text-sm">
                <i class="fa-solid fa-trash"></i> Eliminar
            </button>
        </form>
    </div>

</div>
@endsection