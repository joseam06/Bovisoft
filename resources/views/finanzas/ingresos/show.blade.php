@extends('layouts.dashboard')

@section('title', 'Ingreso — ' . $ingreso->codigo)

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
                <h1 class="text-3xl font-bold text-white">{{ $ingreso->codigo }}</h1>
                <p class="text-red-100 mt-1">Detalle del ingreso</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('finanzas.ingresos.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition-all">
                <i class="fa-solid fa-plus"></i> Nuevo ingreso
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
                <span class="w-9 h-9 bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl flex items-center justify-center shadow">
                    <i class="fa-solid fa-circle-plus text-white text-sm"></i>
                </span>
                Datos del Ingreso
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div>
                    <dt class="text-gray-500 font-medium">Código</dt>
                    <dd class="font-mono font-bold text-gray-800 mt-0.5">{{ $ingreso->codigo }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Tipo</dt>
                    <dd class="mt-0.5">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            {{ $ingreso->tipo_formateado }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Finca</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $ingreso->finca->nombre ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Fecha</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $ingreso->fecha->format('d/m/Y') }}</dd>
                </div>
                @if($ingreso->descripcion)
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 font-medium">Descripción</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $ingreso->descripcion }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500 font-medium">Registrado</dt>
                    <dd class="text-gray-700 mt-0.5">{{ $ingreso->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Registrado por</dt>
                    <dd class="text-gray-700 mt-0.5">{{ $ingreso->user->name ?? '—' }}</dd>
                </div>
            </dl>

            @if($ingreso->observaciones)
            <div class="mt-6 p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                <p class="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Observaciones</p>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $ingreso->observaciones }}</p>
            </div>
            @endif
        </div>

        {{-- Panel monto + acciones --}}
        <div class="space-y-5">
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Monto registrado</p>
                <div class="flex items-end justify-center gap-1 mb-2">
                    <span class="text-xl font-bold text-gray-500 mb-2">$</span>
                    <span class="text-5xl font-extrabold text-emerald-700">
                        {{ number_format($ingreso->monto, 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">COP — {{ $ingreso->fecha->format('d/m/Y') }}</p>
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
                    <a href="{{ route('finanzas.ingresos.create') }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-plus w-4 text-center"></i> Nuevo ingreso
                    </a>
                    @if($ingreso->animal)
                    <a href="{{ route('animales.show', $ingreso->animal) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-red-50 hover:bg-red-100 text-red-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-cow w-4 text-center"></i> Ver ficha del animal
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Datos del comprador (si existe) --}}
    @if($ingreso->comprador_nombre || $ingreso->comprador_telefono || $ingreso->comprador_documento)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-3">
            <span class="w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow">
                <i class="fa-solid fa-user text-white text-sm"></i>
            </span>
            Datos del Comprador
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
            <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Nombre / Razón social</p>
                <p class="font-bold text-gray-800">{{ $ingreso->comprador_nombre ?? '—' }}</p>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Teléfono</p>
                <p class="font-bold text-gray-800">{{ $ingreso->comprador_telefono ?? '—' }}</p>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Documento (CC / NIT)</p>
                <p class="font-bold text-gray-800">{{ $ingreso->comprador_documento ?? '—' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Datos del animal vendido (si es venta_animal) --}}
    @if($ingreso->animal)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-3">
            <span class="w-9 h-9 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow">
                <i class="fa-solid fa-cow text-white text-sm"></i>
            </span>
            Animal Asociado
            @if($ingreso->tipo === 'venta_animal')
                <span class="ml-2 px-3 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full border border-red-200">Vendido</span>
            @endif
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Código</p>
                <p class="font-mono font-bold text-gray-800">{{ $ingreso->animal->codigo }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Nombre</p>
                <p class="font-bold text-gray-800">{{ $ingreso->animal->nombre ?? 'Sin nombre' }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Tipo / Raza</p>
                <p class="font-bold text-gray-800">{{ ucfirst($ingreso->animal->tipo) }} — {{ $ingreso->animal->raza ?? 'N/A' }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Estado</p>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                    {{ $ingreso->animal->estado === 'vendido' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                    {{ ucfirst($ingreso->animal->estado) }}
                </span>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('animales.show', $ingreso->animal) }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-bold rounded-xl shadow hover:from-red-700 hover:to-red-800 transition-all">
                <i class="fa-solid fa-eye"></i> Ver ficha completa del animal
            </a>
        </div>
    </div>
    @endif

    {{-- Eliminación --}}
    <div class="glass-effect border-4 border-red-200/60 rounded-2xl shadow-xl p-5 flex items-center justify-between">
        <div>
            <p class="font-bold text-gray-800 text-sm">Eliminar este ingreso</p>
            <p class="text-xs text-gray-500 mt-0.5">
                Esta acción no se puede deshacer.
                @if($ingreso->tipo === 'venta_animal' && $ingreso->animal)
                    El animal <strong>{{ $ingreso->animal->nombre ?? $ingreso->animal->codigo }}</strong> volverá a estado <strong>Activo</strong>.
                @endif
            </p>
        </div>
        <form action="{{ route('finanzas.ingresos.destroy', $ingreso->id) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este ingreso? {{ $ingreso->tipo === 'venta_animal' && $ingreso->animal ? 'El animal volverá a estado Activo.' : '' }}')">
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