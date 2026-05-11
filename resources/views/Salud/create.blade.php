@extends('layouts.dashboard')

@section('title', 'Nuevo Registro de Salud')

@section('content')
<div class="animate-slide-up">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-heart-pulse text-red-700 text-2xl"></i>
                </span>
                Nuevo Registro de Salud
            </h1>
            <p class="text-red-100 mt-1 ml-16">Registra vacunaciones, tratamientos y eventos sanitarios</p>
        </div>
        <a href="{{ route('salud.index') }}"
           class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver
        </a>
    </div>

    <form action="{{ route('salud.store') }}" method="POST">
        @csrf
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

                    {{-- Código --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Código</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-barcode text-gray-400"></i>
                            </div>
                            <input type="text" value="{{ $codigo }}" readonly
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100
                                       text-gray-600 font-mono cursor-not-allowed">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Generado automáticamente</p>
                    </div>

                    {{-- Animal --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Animal <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-cow text-gray-400"></i>
                            </div>
                            <select name="animal_id" id="animal_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('animal_id') border-red-500 @enderror">
                                <option value="">Seleccionar animal...</option>
                                @foreach($animales as $a)
                                    <option value="{{ $a->id }}"
                                        data-finca="{{ $a->finca_id }}"
                                        {{ old('animal_id', $animalPresel?->id) == $a->id ? 'selected' : '' }}>
                                        {{ $a->nombre ?? $a->codigo }} — {{ $a->finca->nombre ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('animal_id') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Finca --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-home text-gray-400"></i>
                            </div>
                            <select name="finca_id" id="finca_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('finca_id') border-red-500 @enderror">
                                <option value="">Seleccionar finca...</option>
                                @foreach($fincas as $f)
                                    <option value="{{ $f->id }}"
                                        {{ old('finca_id', $animalPresel?->finca_id) == $f->id ? 'selected' : '' }}>
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
                </div>
            </div>

            {{-- ══ SECCIÓN 2: Evento ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-syringe text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Evento Sanitario</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Tipo --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-tag text-gray-400"></i>
                            </div>
                            <select name="tipo"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('tipo') border-red-500 @enderror">
                                @foreach($tipos as $k => $v)
                                    <option value="{{ $k }}" {{ old('tipo','vacunacion') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('tipo') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Estado <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-circle-check text-gray-400"></i>
                            </div>
                            <select name="estado"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('estado') border-red-500 @enderror">
                                @foreach($estados as $k => $v)
                                    <option value="{{ $k }}" {{ old('estado','completado') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('estado') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Producto --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Nombre del producto / medicamento <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-pills text-gray-400"></i>
                            </div>
                            <input type="text" name="nombre_producto" value="{{ old('nombre_producto') }}"
                                placeholder="Ej: Ivermectina, Triple Bacterina..."
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('nombre_producto') border-red-500 @enderror">
                        </div>
                        @error('nombre_producto') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Enfermedad --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Enfermedad prevenida / tratada <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-virus text-gray-400"></i>
                            </div>
                            <input type="text" name="enfermedad_prevenida" value="{{ old('enfermedad_prevenida') }}"
                                placeholder="Ej: Fiebre aftosa, Carbón sintomático..."
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>

                    {{-- Fecha aplicación --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Fecha de aplicación <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-calendar text-gray-400"></i>
                            </div>
                            <input type="date" name="fecha_aplicacion"
                                value="{{ old('fecha_aplicacion', date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('fecha_aplicacion') border-red-500 @enderror">
                        </div>
                        @error('fecha_aplicacion') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Próxima aplicación --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Próxima aplicación <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-calendar-plus text-gray-400"></i>
                            </div>
                            <input type="date" name="proxima_aplicacion"
                                value="{{ old('proxima_aplicacion') }}"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Genera alerta automática 7 días antes</p>
                    </div>
                </div>

                {{-- Diagnóstico (campo completo) --}}
                <div class="mt-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Diagnóstico veterinario <span class="text-gray-400 font-normal">(Opcional)</span>
                    </label>
                    <textarea name="diagnostico" rows="3"
                        placeholder="Describe el diagnóstico o hallazgos clínicos del veterinario..."
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                               placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500
                               transition-all resize-none">{{ old('diagnostico') }}</textarea>
                    @error('diagnostico') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ══ SECCIÓN 3: Dosis y Aplicación ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-flask text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Dosis y Aplicación</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    {{-- Dosis --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Dosis <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-droplet text-gray-400"></i>
                            </div>
                            <input type="number" name="dosis" value="{{ old('dosis') }}"
                                step="0.01" min="0" placeholder="0.00"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>

                    {{-- Unidad --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Unidad <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-ruler text-gray-400"></i>
                            </div>
                            <select name="unidad_dosis"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-700
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                                <option value="">Seleccionar...</option>
                                @foreach($unidades as $u)
                                    <option value="{{ $u }}" {{ old('unidad_dosis') == $u ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Vía --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Vía de aplicación <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-route text-gray-400"></i>
                            </div>
                            <select name="via_aplicacion"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-700
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                                <option value="">Seleccionar...</option>
                                @foreach($vias as $k => $v)
                                    <option value="{{ $k }}" {{ old('via_aplicacion') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Lote --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Lote del medicamento <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-hashtag text-gray-400"></i>
                            </div>
                            <input type="text" name="lote_medicamento" value="{{ old('lote_medicamento') }}"
                                placeholder="Ej: LOT-2024-001"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>

                    {{-- Laboratorio --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Laboratorio / Fabricante <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-building text-gray-400"></i>
                            </div>
                            <input type="text" name="laboratorio" value="{{ old('laboratorio') }}"
                                placeholder="Ej: MSD Animal Health"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>

                    {{-- Veterinario --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Veterinario responsable <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user-doctor text-gray-400"></i>
                            </div>
                            <input type="text" name="veterinario" value="{{ old('veterinario') }}"
                                placeholder="Nombre del veterinario"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 4: Carencia y Costo ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clock text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Carencia y Costo</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Días carencia --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Días de carencia <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-hourglass-half text-gray-400"></i>
                            </div>
                            <input type="number" name="dias_carencia" value="{{ old('dias_carencia', 0) }}"
                                min="0" placeholder="0"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Días sin comercializar leche ni carne. La fecha de fin se calcula automáticamente.</p>
                    </div>

                    {{-- Costo --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Costo (COP) <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-dollar-sign text-gray-400"></i>
                            </div>
                            <input type="number" name="costo" value="{{ old('costo') }}"
                                step="0.01" min="0" placeholder="0.00"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 5: Observaciones ══ --}}
            <div>
                <div class="flex items-center mb-4 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clipboard text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Observaciones</h2>
                </div>
                <textarea name="observaciones" rows="3"
                    placeholder="Notas adicionales sobre el tratamiento o vacunación..."
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                           placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500
                           transition-all resize-none">{{ old('observaciones') }}</textarea>
                @error('observaciones') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            {{-- ══ Botones ══ --}}
            <div class="flex justify-end gap-4 pt-4 border-t-2 border-red-200">
                <a href="{{ route('salud.index') }}"
                   class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800
                           text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-2"></i>Guardar Registro
                </button>
            </div>

        </div>{{-- fin card --}}
    </form>
</div>

@push('scripts')
<script>
    // Auto-rellenar finca al seleccionar animal
    document.getElementById('animal_id').addEventListener('change', function () {
        const fincaId = this.options[this.selectedIndex].getAttribute('data-finca');
        if (fincaId) document.getElementById('finca_id').value = fincaId;
    });
</script>
@endpush
@endsection