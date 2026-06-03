@extends('layouts.dashboard')

@section('title', 'Registrar Egreso')

@section('content')
<div class="animate-slide-up">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-circle-minus text-red-700 text-2xl"></i>
                </span>
                Registrar Egreso
            </h1>
            <p class="text-red-100 mt-1 ml-16">Registra un nuevo gasto u egreso operativo</p>
        </div>
        <a href="{{ route('finanzas.index') }}"
            class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver
        </a>
    </div>

    {{-- Aviso si viene pre-llenado desde salud --}}
    @if($saludPresel)
    <div class="mb-6 p-4 bg-purple-50 border-2 border-purple-300 rounded-xl flex items-center gap-3">
        <i class="fa-solid fa-heart-pulse text-purple-600 text-xl"></i>
        <div>
            <p class="text-purple-800 font-bold text-sm">Importando costo desde Salud Animal</p>
            <p class="text-purple-600 text-xs">
                Registro {{ $saludPresel->codigo }} —
                {{ $saludPresel->nombre_producto }} —
                Costo: ${{ number_format($saludPresel->costo, 0, ',', '.') }} COP
            </p>
        </div>
    </div>
    @endif

    <form action="{{ route('finanzas.egresos.store') }}" method="POST">
        @csrf
        @if($saludPresel)
            <input type="hidden" name="salud_id" value="{{ $saludPresel->id }}">
        @endif

        <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50 space-y-8">

            {{-- ══ SECCIÓN 1: Identificación ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-id-card text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Identificación</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Código</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-barcode text-gray-400"></i>
                            </div>
                            <input type="text" value="{{ $codigo }}" readonly
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 border-2 border-gray-300 rounded-xl text-gray-600 font-mono cursor-not-allowed">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Generado automáticamente</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <select name="finca_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('finca_id') border-red-500 @enderror">
                                <option value="">Seleccionar finca...</option>
                                @foreach($fincas as $f)
                                    <option value="{{ $f->id }}"
                                        {{ old('finca_id', $saludPresel?->finca_id) == $f->id ? 'selected' : '' }}>
                                        {{ $f->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('finca_id') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Categoría <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-tag text-gray-400"></i>
                            </div>
                            <select name="categoria" id="categoria_egreso"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('categoria') border-red-500 @enderror">
                                @php
                                    $catPresel = old('categoria', $saludPresel ? 'salud_animal' : '');
                                @endphp
                                @foreach(\App\Models\Egreso::getCategorias() as $k => $v)
                                    <option value="{{ $k }}" {{ $catPresel == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('categoria') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 2: Animal asociado (opcional) ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-cow text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">
                        Animal Asociado <span class="text-gray-400 text-lg font-normal">(Opcional)</span>
                    </h2>
                </div>
                <p class="text-gray-500 text-sm mb-4">Asocia el gasto a un animal específico si aplica (Ej: compra de medicamento para una vaca en particular).</p>
                <div class="relative max-w-lg">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-cow text-gray-400"></i>
                    </div>
                    <select name="animal_id"
                        class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        <option value="">Sin animal asociado</option>
                        @foreach($animales as $a)
                            <option value="{{ $a->id }}"
                                {{ old('animal_id', $saludPresel?->animal_id) == $a->id ? 'selected' : '' }}>
                                {{ $a->nombre ?? $a->codigo }} — {{ $a->finca->nombre ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 3: Datos del egreso ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-money-bill text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Datos del Egreso</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Monto (COP) <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-dollar-sign text-gray-400"></i>
                            </div>
                            <input type="number" name="monto"
                                value="{{ old('monto', $saludPresel?->costo) }}"
                                step="1" min="0" placeholder="0"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('monto') border-red-500 @enderror">
                        </div>
                        @error('monto') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Fecha <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-calendar text-gray-400"></i>
                            </div>
                            <input type="date" name="fecha"
                                value="{{ old('fecha', $saludPresel ? $saludPresel->fecha_aplicacion->format('Y-m-d') : date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('fecha') border-red-500 @enderror">
                        </div>
                        @error('fecha') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Descripción <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-align-left text-gray-400"></i>
                            </div>
                            <input type="text" name="descripcion"
                                value="{{ old('descripcion', $saludPresel ? $saludPresel->nombre_producto : '') }}"
                                placeholder="Ej: Compra de concentrado, pago jornalero..."
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 4: Observaciones ══ --}}
            <div>
                <div class="flex items-center mb-4 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clipboard text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Observaciones</h2>
                </div>
                <textarea name="observaciones" rows="3"
                    placeholder="Notas adicionales sobre este gasto..."
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('observaciones') }}</textarea>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-4 pt-4 border-t-2 border-red-200">
                <a href="{{ route('finanzas.index') }}"
                    class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar Egreso
                </button>
            </div>

        </div>
    </form>
</div>
@endsection