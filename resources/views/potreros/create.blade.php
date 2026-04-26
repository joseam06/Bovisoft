@extends('layouts.dashboard')

@section('title', 'Registrar Nuevo Potrero')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('potreros.index') }}" class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center mr-4 transition-all backdrop-blur-sm border-2 border-white/30">
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-3xl font-bold text-white">Registrar Nuevo Potrero</h1>
    </div>

    <!-- Formulario -->
    <div class="glass-effect rounded-2xl shadow-xl p-8 border-4 border-white/50">
        <form action="{{ route('potreros.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Código -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-barcode mr-2"></i>Código <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="codigo" value="{{ old('codigo', $codigo) }}" readonly
                        class="w-full px-4 py-3 bg-gray-100 border-2 border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
                    <p class="text-gray-500 text-xs mt-1">Código generado automáticamente</p>
                    @error('codigo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-tag mr-2"></i>Nombre del Potrero <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                        placeholder="Ej: Potrero Norte"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('nombre')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Finca -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-map-marker-alt mr-2"></i>Finca <span class="text-red-600">*</span>
                    </label>
                    <select name="finca_id" required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">Selecciona una finca</option>
                        @foreach($fincas as $finca)
                            <option value="{{ $finca->id }}" {{ old('finca_id') == $finca->id ? 'selected' : '' }}>
                                {{ $finca->nombre }} ({{ $finca->codigo }})
                            </option>
                        @endforeach
                    </select>
                    @error('finca_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Área -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-ruler-combined mr-2"></i>Área (Hectáreas) <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <input type="number" name="area" value="{{ old('area') }}" step="0.01" min="0"
                        placeholder="Ej: 15.5"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('area')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipo de Pasto -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-leaf mr-2"></i>Tipo de Pasto <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <select name="tipo_pasto"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">Selecciona un tipo</option>
                        @foreach($tipos_pasto as $key => $tipo)
                            <option value="{{ $key }}" {{ old('tipo_pasto') == $key ? 'selected' : '' }}>
                                {{ $tipo }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipo_pasto')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Capacidad de Animales -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-cow mr-2"></i>Capacidad de Animales <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <input type="number" name="capacidad_animales" value="{{ old('capacidad_animales') }}" min="0"
                        placeholder="Ej: 50"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-gray-500 text-xs mt-1">¿Cuántos animales puede albergar?</p>
                    @error('capacidad_animales')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-info-circle mr-2"></i>Estado <span class="text-red-600">*</span>
                    </label>
                    <select name="estado" required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @foreach($estados as $key => $estado)
                            <option value="{{ $key }}" {{ old('estado', 'activo') == $key ? 'selected' : '' }}>
                                {{ $estado }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Días de Descanso -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-calendar-days mr-2"></i>Días de Descanso <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <input type="number" name="dias_descanso" value="{{ old('dias_descanso', 30) }}" min="0" max="365"
                        placeholder="Ej: 30"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-gray-500 text-xs mt-1">Días que debe descansar el potrero entre rotaciones</p>
                    @error('dias_descanso')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha Última Rotación -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-calendar mr-2"></i>Fecha Última Rotación <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <input type="date" name="fecha_ultima_rotacion" value="{{ old('fecha_ultima_rotacion') }}" max="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-gray-500 text-xs mt-1">¿Cuándo fue la última rotación de ganado?</p>
                    @error('fecha_ultima_rotacion')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Observaciones -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fa-solid fa-notes-medical mr-2"></i>Observaciones <span class="text-gray-500">(Opcional)</span>
                    </label>
                    <textarea name="observaciones" rows="4"
                        placeholder="Información adicional sobre el potrero (tipo de suelo, fuentes de agua, etc.)"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-4 mt-8">
                <button type="submit" class="flex-1 px-6 py-4 bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800 rounded-xl transition-all font-bold text-lg shadow-xl">
                    <i class="fa-solid fa-save mr-2"></i>Registrar Potrero
                </button>
                <a href="{{ route('potreros.index') }}" class="flex-1 px-6 py-4 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-xl transition-all font-bold text-lg shadow-xl text-center">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection