@extends('layouts.dashboard')

@section('title', 'Registrar Producción')

@section('content')
<div class="animate-slide-up">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-droplet text-purple-700 text-2xl"></i>
                </span>
                Registrar Producción
            </h1>
            <p class="text-red-100 mt-1 ml-16">Ingresa los litros producidos por animal</p>
        </div>
        <a href="{{ route('produccion.index') }}"
            class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver
        </a>
    </div>

    <form action="{{ route('produccion.store') }}" method="POST">
        @csrf
        <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50 space-y-8">

            {{-- SECCIÓN 1: Identificación --}}
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
                            Animal <span class="text-red-600">*</span>
                            <span class="text-gray-400 font-normal text-xs ml-1">(vacas y novillas)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-cow text-gray-400"></i>
                            </div>
                            <select name="animal_id" id="animal_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('animal_id') border-red-500 @enderror">
                                <option value="">Seleccionar animal...</option>
                                @foreach($animales as $a)
                                    <option value="{{ $a->id }}" data-finca="{{ $a->finca_id }}"
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

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <select name="finca_id" id="finca_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('finca_id') border-red-500 @enderror">
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

            {{-- SECCIÓN 2: Datos de Producción --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-droplet text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Datos de Producción</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Fecha <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-calendar text-gray-400"></i>
                            </div>
                            <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('fecha') border-red-500 @enderror">
                        </div>
                        @error('fecha') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Sesión <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-clock text-gray-400"></i>
                            </div>
                            <select name="sesion"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-700 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                                <option value="">Sin especificar</option>
                                @foreach($sesiones as $k => $v)
                                    <option value="{{ $k }}" {{ old('sesion') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Litros <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-droplet text-purple-400"></i>
                            </div>
                            <input type="number" name="litros" value="{{ old('litros') }}"
                                step="0.01" min="0.01" max="999.99" placeholder="0.00"
                                class="w-full pl-12 pr-16 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('litros') border-red-500 @enderror">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold">L</span>
                            </div>
                        </div>
                        @error('litros') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Calidad <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-star text-gray-400"></i>
                            </div>
                            <select name="calidad"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-700 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                                <option value="">Sin clasificar</option>
                                @foreach($calidades as $k => $v)
                                    <option value="{{ $k }}" {{ old('calidad') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 3: Observaciones --}}
            <div>
                <div class="flex items-center mb-4 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clipboard text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Observaciones</h2>
                </div>
                <textarea name="observaciones" rows="3" placeholder="Notas adicionales sobre esta producción..."
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('observaciones') }}</textarea>
                @error('observaciones') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-4 pt-4 border-t-2 border-red-200">
                <a href="{{ route('produccion.index') }}"
                    class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar Registro
                </button>
            </div>

        </div>
    </form>
</div>

<script>
document.getElementById('animal_id').addEventListener('change', function () {
    const fincaId = this.options[this.selectedIndex].getAttribute('data-finca');
    if (fincaId) document.getElementById('finca_id').value = fincaId;
});
</script>
@endsection