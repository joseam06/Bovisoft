@extends('layouts.Dashboard')

@section('title', 'Registrar Animal')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-cow text-red-700 text-2xl"></i>
                </span>
                Registrar Nuevo Animal
            </h1>
            <p class="text-red-100 mt-2 ml-16">Complete la información del animal</p>
        </div>
        <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver al Dashboard
        </a>
    </div>

    <!-- Formulario -->
    <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50">
        <form action="{{ route('animales.store') }}" method="POST">
            @csrf

            <!-- Información Básica -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-info-circle text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Información Básica</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Código -->
                    <div>
                        <label for="codigo" class="block text-sm font-bold text-gray-700 mb-2">
                            Código <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-barcode text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="codigo" 
                                   id="codigo" 
                                   value="{{ old('codigo', $codigo) }}" 
                                   readonly
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed font-mono text-lg">
                        </div>
                        @error('codigo')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
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
                                   value="{{ old('nombre') }}" 
                                   placeholder="Ej: La Morena, Manchada..."
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
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
                            <select name="tipo" 
                                    id="tipo" 
                                    required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white">
                                <option value="">Seleccione un tipo</option>
                                @foreach($tipos as $key => $valor)
                                    <option value="{{ $key }}" {{ old('tipo') == $key ? 'selected' : '' }}>
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
                            <select name="sexo" 
                                    id="sexo" 
                                    required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white">
                                <option value="">Seleccione el sexo</option>
                                <option value="hembra" {{ old('sexo') == 'hembra' ? 'selected' : '' }}>♀ Hembra</option>
                                <option value="macho" {{ old('sexo') == 'macho' ? 'selected' : '' }}>♂ Macho</option>
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
                        <label for="raza" class="block text-sm font-bold text-gray-700 mb-2">
                            Raza
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-dna text-gray-400"></i>
                            </div>
                            <select name="raza" 
                                    id="raza" 
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white">
                                <option value="">Seleccione una raza</option>
                                @foreach($razas as $key => $valor)
                                    <option value="{{ $key }}" {{ old('raza') == $key ? 'selected' : '' }}>
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
                        <label for="color" class="block text-sm font-bold text-gray-700 mb-2">
                            Color
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-palette text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="color" 
                                   id="color" 
                                   value="{{ old('color') }}" 
                                   placeholder="Ej: Negro con blanco, Marrón..."
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
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
                                   value="{{ old('fecha_nacimiento') }}" 
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
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
                                   value="{{ old('peso_actual') }}" 
                                   step="0.01"
                                   min="0"
                                   placeholder="Ej: 450.50"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
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

            <!-- Ubicación -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-map-marker-alt text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Ubicación</h2>
                </div>

                <div>
                    <label for="finca_id" class="block text-sm font-bold text-gray-700 mb-2">
                        Finca <span class="text-red-600">*</span>
                    </label>
                    @if($fincas->count() > 0)
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-home text-gray-400"></i>
                            </div>
                            <select name="finca_id" 
                                    id="finca_id" 
                                    required
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none bg-white">
                                <option value="">Seleccione una finca</option>
                                @foreach($fincas as $finca)
                                    <option value="{{ $finca->id }}" {{ old('finca_id') == $finca->id ? 'selected' : '' }}>
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
                    @else
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl p-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                    <i class="fa-solid fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-800 mb-1">No tienes fincas registradas</p>
                                    <p class="text-gray-600 text-sm">Debes registrar al menos una finca antes de crear animales</p>
                                </div>
                                <a href="{{ route('fincas.create') }}" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
                                    <i class="fa-solid fa-plus mr-2"></i>Crear Finca
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Campo Potrero (después del campo Finca) -->
<div>
    <label class="block text-gray-700 text-sm font-bold mb-2">
        <i class="fa-solid fa-map mr-2"></i>Potrero (Opcional)
    </label>
    <select name="potrero_id" id="potrero_id" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800">
        <option value="">Selecciona un potrero</option>
    </select>
    <p id="potrero-message" class="text-gray-500 text-xs mt-1">Primero selecciona una finca</p>
</div>

<script>
document.querySelector('select[name="finca_id"]').addEventListener('change', function() {
    const fincaId = this.value;
    const potreroSelect = document.getElementById('potrero_id');
    const message = document.getElementById('potrero-message');
    
    potreroSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (fincaId) {
        fetch(`/api/fincas/${fincaId}/potreros`)
            .then(r => r.json())
            .then(data => {
                potreroSelect.innerHTML = '<option value="">Sin potrero</option>';
                if (data.length > 0) {
                    data.forEach(p => {
                        potreroSelect.innerHTML += `<option value="${p.id}">${p.nombre} (${p.disponibilidad} disponibles)</option>`;
                    });
                    message.textContent = `${data.length} potrero(s) disponible(s)`;
                } else {
                    message.textContent = 'Esta finca no tiene potreros registrados';
                }
            });
    } else {
        potreroSelect.innerHTML = '<option value="">Selecciona un potrero</option>';
        message.textContent = 'Primero selecciona una finca';
    }
});
</script>

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
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <p class="text-red-600 text-sm mt-2 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-6 border-t-2 border-red-200">
                <a href="{{ route('dashboard') }}" class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit" 
                        class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1"
                        @if($fincas->count() === 0) disabled @endif>
                    <i class="fa-solid fa-save mr-2"></i>Registrar Animal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection