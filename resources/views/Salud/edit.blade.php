@extends('layouts.dashboard')

@section('title', 'Editar Registro — ' . $registro->codigo)

@section('content')
<div class="animate-slide-up">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-pen text-red-700 text-xl"></i>
                </span>
                Editar {{ $registro->codigo }}
            </h1>
            <p class="text-red-100 mt-1 ml-16">Modificar registro de salud</p>
        </div>
        <a href="{{ route('salud.show', $registro) }}"
           class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver al Detalle
        </a>
    </div>

    <form action="{{ route('salud.update', $registro) }}" method="POST">
        @csrf @method('PUT')
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
                            <input type="text" value="{{ $registro->codigo }}" readonly
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 text-gray-600 font-mono cursor-not-allowed">
                        </div>
                        <p class="text-gray-400 text-xs mt-1">No puede modificarse</p>
                    </div>

                    {{-- Animal --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Animal <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-cow text-gray-400"></i>
                            </div>
                            <select name="animal_id" id="animal_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('animal_id') border-red-500 @enderror">
                                @foreach($animales as $a)
                                    <option value="{{ $a->id }}" data-finca="{{ $a->finca_id }}"
                                        {{ old('animal_id', $registro->animal_id) == $a->id ? 'selected' : '' }}>
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
                        <label class="block text-sm font-bold text-gray-700 mb-2">Finca <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-home text-gray-400"></i>
                            </div>
                            <select name="finca_id" id="finca_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all
                                       @error('finca_id') border-red-500 @enderror">
                                @foreach($fincas as $f)
                                    <option value="{{ $f->id }}" {{ old('finca_id', $registro->finca_id) == $f->id ? 'selected' : '' }}>
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

                    @php
                        $fieldClass = 'w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all';
                        $inputClass = 'w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                                       placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all';
                    @endphp

                    {{-- Tipo --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-tag text-gray-400"></i></div>
                            <select name="tipo" class="{{ $fieldClass }} @error('tipo') border-red-500 @enderror">
                                @foreach($tipos as $k => $v)
                                    <option value="{{ $k }}" {{ old('tipo', $registro->tipo) == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"><i class="fa-solid fa-chevron-down text-gray-400"></i></div>
                        </div>
                        @error('tipo') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Estado --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Estado <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-circle-check text-gray-400"></i></div>
                            <select name="estado" class="{{ $fieldClass }} @error('estado') border-red-500 @enderror">
                                @foreach($estados as $k => $v)
                                    <option value="{{ $k }}" {{ old('estado', $registro->estado) == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"><i class="fa-solid fa-chevron-down text-gray-400"></i></div>
                        </div>
                        @error('estado') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Producto --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Producto / medicamento <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-pills text-gray-400"></i></div>
                            <input type="text" name="nombre_producto" value="{{ old('nombre_producto', $registro->nombre_producto) }}"
                                class="{{ $inputClass }} @error('nombre_producto') border-red-500 @enderror">
                        </div>
                        @error('nombre_producto') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Enfermedad --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Enfermedad prevenida / tratada <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-virus text-gray-400"></i></div>
                            <input type="text" name="enfermedad_prevenida" value="{{ old('enfermedad_prevenida', $registro->enfermedad_prevenida) }}"
                                class="{{ $inputClass }}">
                        </div>
                    </div>

                    {{-- Fecha aplicación --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha de aplicación <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-calendar text-gray-400"></i></div>
                            <input type="date" name="fecha_aplicacion"
                                value="{{ old('fecha_aplicacion', $registro->fecha_aplicacion?->format('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                class="{{ $inputClass }} @error('fecha_aplicacion') border-red-500 @enderror">
                        </div>
                        @error('fecha_aplicacion') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    {{-- Próxima --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Próxima aplicación <span class="text-gray-400 font-normal">(Opcional)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-calendar-plus text-gray-400"></i></div>
                            <input type="date" name="proxima_aplicacion"
                                value="{{ old('proxima_aplicacion', $registro->proxima_aplicacion?->format('Y-m-d')) }}"
                                class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                {{-- Diagnóstico --}}
                <div class="mt-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Diagnóstico veterinario <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <textarea name="diagnostico" rows="3"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                               placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('diagnostico', $registro->diagnostico) }}</textarea>
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

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Dosis</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-droplet text-gray-400"></i></div>
                        <input type="number" name="dosis" value="{{ old('dosis', $registro->dosis) }}" step="0.01" min="0"
                            class="{{ $inputClass }}"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Unidad</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-ruler text-gray-400"></i></div>
                        <select name="unidad_dosis" class="{{ $fieldClass }}">
                            <option value="">Seleccionar...</option>
                            @foreach($unidades as $u)
                                <option value="{{ $u }}" {{ old('unidad_dosis', $registro->unidad_dosis) == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"><i class="fa-solid fa-chevron-down text-gray-400"></i></div></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Vía de aplicación</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-route text-gray-400"></i></div>
                        <select name="via_aplicacion" class="{{ $fieldClass }}">
                            <option value="">Seleccionar...</option>
                            @foreach($vias as $k => $v)
                                <option value="{{ $k }}" {{ old('via_aplicacion', $registro->via_aplicacion) == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none"><i class="fa-solid fa-chevron-down text-gray-400"></i></div></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Lote medicamento</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-hashtag text-gray-400"></i></div>
                        <input type="text" name="lote_medicamento" value="{{ old('lote_medicamento', $registro->lote_medicamento) }}" class="{{ $inputClass }}"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Laboratorio</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-building text-gray-400"></i></div>
                        <input type="text" name="laboratorio" value="{{ old('laboratorio', $registro->laboratorio) }}" class="{{ $inputClass }}"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Veterinario responsable</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-user-doctor text-gray-400"></i></div>
                        <input type="text" name="veterinario" value="{{ old('veterinario', $registro->veterinario) }}" class="{{ $inputClass }}"></div>
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
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Días de carencia</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-hourglass-half text-gray-400"></i></div>
                        <input type="number" name="dias_carencia" value="{{ old('dias_carencia', $registro->dias_carencia) }}" min="0"
                            class="{{ $inputClass }}"></div>
                        @if($registro->fin_carencia)
                        <p class="text-gray-500 text-xs mt-1">Fin de carencia actual: {{ $registro->fin_carencia->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Costo (COP)</label>
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><i class="fa-solid fa-dollar-sign text-gray-400"></i></div>
                        <input type="number" name="costo" value="{{ old('costo', $registro->costo) }}" step="0.01" min="0"
                            class="{{ $inputClass }}"></div>
                    </div>
                </div>
            </div>

            {{-- ══ Observaciones ══ --}}
            <div>
                <div class="flex items-center mb-4 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clipboard text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Observaciones</h2>
                </div>
                <textarea name="observaciones" rows="3"
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800
                           placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('observaciones', $registro->observaciones) }}</textarea>
            </div>

            {{-- ══ Botones ══ --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t-2 border-red-200">
                <form action="{{ route('salud.destroy', $registro) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar este registro de salud?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="px-6 py-4 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-xl transition-all border-2 border-red-300 flex items-center gap-2">
                        <i class="fa-solid fa-trash"></i> Eliminar Registro
                    </button>
                </form>
                <div class="flex gap-4">
                    <a href="{{ route('salud.show', $registro) }}"
                       class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                        <i class="fa-solid fa-times mr-2"></i>Cancelar
                    </a>
                    <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800
                               text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                        <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </div>

        </div>{{-- fin card --}}
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('animal_id').addEventListener('change', function () {
        const fincaId = this.options[this.selectedIndex].getAttribute('data-finca');
        if (fincaId) document.getElementById('finca_id').value = fincaId;
    });
</script>
@endpush
@endsection