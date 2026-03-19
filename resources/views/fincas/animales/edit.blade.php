@extends('layouts.dashboard')

@section('title', 'Editar Animal - ' . $animal->codigo)

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-cow text-red-700 text-2xl"></i>
                </span>
                Editar Animal
            </h1>
            <p class="text-red-100 mt-2 ml-16">
                Modificando: <span class="font-bold">{{ $animal->nombre ?: $animal->codigo }}</span>
            </p>
        </div>
        <a href="{{ route('animales.show', $animal) }}" 
           class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver al Detalle
        </a>
    </div>

    <!-- Formulario -->
    <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50">
        <form action="{{ route('animales.update', $animal) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Información Básica -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-info-circle text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Información Básica</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Código (solo lectura) -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Código
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-barcode text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   value="{{ $animal->codigo }}" 
                                   readonly
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed font-mono text-lg">
                        </div>
                        <p class="text-gray-400 text-xs mt-1">El código no puede modificarse</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label for="nombre" class="block text-sm font-bold text-gray-700 mb-2">
                            Nombre/Apodo <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-tag text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   value="{{ old('nombre', $animal->nombre) }}" 
                                   placeholder="Ej: La Morena, Manchada..."
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('nombre') border-red-500 @enderror">
                        </div>
                        @error('nombre')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label for="tipo" class="block text-sm font-bold text-gray-700 mb-2">
                            Tipo de Animal <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-cow text-gray-400"></i>
                            </div>
                            <select name="tipo" id="tipo" required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white @error('tipo') border-red-500 @enderror">
                                <option value="">Seleccione un tipo</option>
                                @foreach($tipos as $key => $valor)
                                    <option value="{{ $key }}" {{ old('tipo', $animal->tipo) == $key ? 'selected' : '' }}>
                                        {{ $valor }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('tipo')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Sexo -->
                    <div>
                        <label for="sexo" class="block text-sm font-bold text-gray-700 mb-2">
                            Sexo <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-venus-mars text-gray-400"></i>
                            </div>
                            <select name="sexo" id="sexo" required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white @error('sexo') border-red-500 @enderror">
                                <option value="">Seleccione el sexo</option>
                                <option value="hembra" {{ old('sexo', $animal->sexo) == 'hembra' ? 'selected' : '' }}>♀ Hembra</option>
                                <option value="macho" {{ old('sexo', $animal->sexo) == 'macho' ? 'selected' : '' }}>♂ Macho</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('sexo')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Raza -->
                    <div>
                        <label for="raza" class="block text-sm font-bold text-gray-700 mb-2">Raza</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-dna text-gray-400"></i>
                            </div>
                            <select name="raza" id="raza"
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white @error('raza') border-red-500 @enderror">
                                <option value="">Seleccione una raza</option>
                                @foreach($razas as $key => $valor)
                                    <option value="{{ $key }}" {{ old('raza', $animal->raza) == $key ? 'selected' : '' }}>
                                        {{ $valor }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('raza')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Color -->
                    <div>
                        <label for="color" class="block text-sm font-bold text-gray-700 mb-2">Color</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-palette text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="color" 
                                   id="color" 
                                   value="{{ old('color', $animal->color) }}" 
                                   placeholder="Ej: Negro con blanco, Marrón..."
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('color') border-red-500 @enderror">
                        </div>
                        @error('color')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Datos Complementarios -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-chart-line text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Datos Complementarios</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Fecha de Nacimiento -->
                    <div>
                        <label for="fecha_nacimiento" class="block text-sm font-bold text-gray-700 mb-2">
                            Fecha de Nacimiento
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-calendar text-gray-400"></i>
                            </div>
                            <input type="date" 
                                   name="fecha_nacimiento" 
                                   id="fecha_nacimiento" 
                                   value="{{ old('fecha_nacimiento', $animal->fecha_nacimiento ? $animal->fecha_nacimiento->format('Y-m-d') : '') }}" 
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('fecha_nacimiento') border-red-500 @enderror">
                        </div>
                        @error('fecha_nacimiento')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Peso Actual -->
                    <div>
                        <label for="peso_actual" class="block text-sm font-bold text-gray-700 mb-2">
                            Peso Actual (kg)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-weight-scale text-gray-400"></i>
                            </div>
                            <input type="number" 
                                   name="peso_actual" 
                                   id="peso_actual" 
                                   value="{{ old('peso_actual', $animal->peso_actual) }}" 
                                   step="0.01" min="0"
                                   placeholder="Ej: 450.50"
                                   class="w-full pl-12 pr-16 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('peso_actual') border-red-500 @enderror">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-medium">kg</span>
                            </div>
                        </div>
                        @error('peso_actual')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Estado y Ubicación -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-map-marker-alt text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Estado y Ubicación</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block text-sm font-bold text-gray-700 mb-2">
                            Estado <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-circle-check text-gray-400"></i>
                            </div>
                            <select name="estado" id="estado" required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white @error('estado') border-red-500 @enderror">
                                <option value="activo" {{ old('estado', $animal->estado) == 'activo' ? 'selected' : '' }}>✅ Activo</option>
                                <option value="inactivo" {{ old('estado', $animal->estado) == 'inactivo' ? 'selected' : '' }}>❌ Inactivo</option>
                                <option value="vendido" {{ old('estado', $animal->estado) == 'vendido' ? 'selected' : '' }}>💰 Vendido</option>
                                <option value="muerto" {{ old('estado', $animal->estado) == 'muerto' ? 'selected' : '' }}>💀 Muerto</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('estado')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Finca -->
                    <div>
                        <label for="finca_id" class="block text-sm font-bold text-gray-700 mb-2">
                            Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-home text-gray-400"></i>
                            </div>
                            <select name="finca_id" id="finca_id" required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white @error('finca_id') border-red-500 @enderror">
                                <option value="">Seleccione una finca</option>
                                @foreach($fincas as $finca)
                                    <option value="{{ $finca->id }}" {{ old('finca_id', $animal->finca_id) == $finca->id ? 'selected' : '' }}>
                                        {{ $finca->nombre }} ({{ $finca->codigo }}) - {{ $finca->area }} ha
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('finca_id')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-yellow-600 to-yellow-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-clipboard text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Observaciones</h2>
                </div>
                <div>
                    <label for="observaciones" class="block text-sm font-bold text-gray-700 mb-2">
                        Notas Adicionales
                    </label>
                    <textarea name="observaciones" 
                              id="observaciones" 
                              rows="5" 
                              placeholder="Información adicional relevante sobre el animal..."
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none @error('observaciones') border-red-500 @enderror">{{ old('observaciones', $animal->observaciones) }}</textarea>
                    @error('observaciones')
                        <p class="text-red-600 text-sm mt-2 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t-2 border-red-200">
                <!-- Eliminar (izquierda) -->
                <form action="{{ route('animales.destroy', $animal) }}" 
                      method="POST"
                      onsubmit="return confirm('¿Estás seguro de eliminar a {{ addslashes($animal->nombre ?: $animal->codigo) }}? Esta acción no se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-6 py-4 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-xl transition-all border-2 border-red-300 hover:border-red-400 flex items-center">
                        <i class="fa-solid fa-trash mr-2"></i>Eliminar Animal
                    </button>
                </form>

                <!-- Cancelar + Guardar (derecha) -->
                <div class="flex space-x-4">
                    <a href="{{ route('animales.show', $animal) }}" 
                       class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                        <i class="fa-solid fa-times mr-2"></i>Cancelar
                    </a>
                    <button type="submit" 
                            class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                        <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection