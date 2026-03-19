@extends('layouts.dashboard')

@section('title', 'Editar Finca')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-edit text-red-700 text-2xl"></i>
                </span>
                Editar Finca
            </h1>
            <p class="text-red-100 mt-2 ml-16">{{ $finca->nombre }} ({{ $finca->codigo }})</p>
        </div>
        <a href="{{ route('fincas.show', $finca) }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver
        </a>
    </div>

    <!-- Formulario -->
    <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50">
        <form action="{{ route('fincas.update', $finca) }}" method="POST">
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
                    <!-- Código (Solo lectura) -->
                    <div>
                        <label for="codigo" class="block text-sm font-bold text-gray-700 mb-2">
                            Código
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-barcode text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   value="{{ $finca->codigo }}" 
                                   readonly
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 cursor-not-allowed font-mono text-lg">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">El código no se puede modificar</p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label for="nombre" class="block text-sm font-bold text-gray-700 mb-2">
                            Nombre de la Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-signature text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   value="{{ old('nombre', $finca->nombre) }}" 
                                   required
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        @error('nombre')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Área -->
                    <div>
                        <label for="area" class="block text-sm font-bold text-gray-700 mb-2">
                            Área (Hectáreas) <span class="text-gray-400 font-normal text-xs">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-ruler-combined text-gray-400"></i>
                            </div>
                            <input type="number" 
                                   name="area" 
                                   id="area" 
                                   value="{{ old('area', $finca->area) }}" 
                                   step="0.01"
                                   min="0"
                                   placeholder="Ej: 50.5"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-medium">ha</span>
                            </div>
                        </div>
                        @error('area')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-bold text-gray-700 mb-2">
                            Descripción
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-0 pl-4 pointer-events-none">
                                <i class="fa-solid fa-align-left text-gray-400"></i>
                            </div>
                            <textarea name="descripcion" 
                                      id="descripcion" 
                                      rows="3"
                                      placeholder="Breve descripción de la finca..."
                                      class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('descripcion', $finca->descripcion) }}</textarea>
                        </div>
                        @error('descripcion')
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Dirección -->
                    <div>
                        <label for="direccion" class="block text-sm font-bold text-gray-700 mb-2">
                            Dirección <span class="text-gray-400 font-normal text-xs">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-location-dot text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="direccion" 
                                   id="direccion" 
                                   value="{{ old('direccion', $finca->direccion) }}" 
                                   placeholder="Ej: Vereda El Retiro, Km 5"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        @error('direccion')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Municipio -->
                    <div>
                        <label for="municipio" class="block text-sm font-bold text-gray-700 mb-2">
                            Municipio
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-city text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="municipio" 
                                   id="municipio" 
                                   value="{{ old('municipio', $finca->municipio) }}" 
                                   placeholder="Ej: Montería"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        @error('municipio')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Departamento -->
                    <div>
                        <label for="departamento" class="block text-sm font-bold text-gray-700 mb-2">
                            Departamento
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-flag text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="departamento" 
                                   id="departamento" 
                                   value="{{ old('departamento', $finca->departamento) }}" 
                                   placeholder="Ej: Córdoba"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        @error('departamento')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Botón de búsqueda -->
                    <div class="flex items-end">
                        <button type="button" 
                                id="buscar-ubicacion" 
                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center">
                            <i class="fa-solid fa-search-location mr-2"></i>
                            <span id="buscar-texto">Buscar en Mapa</span>
                            <i class="fa-solid fa-spinner fa-spin ml-2 hidden" id="buscar-spinner"></i>
                        </button>
                    </div>
                </div>

                <!-- Mapa -->
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Ubicación en el Mapa <span class="text-red-600">*</span>
                        <span class="text-gray-500 font-normal text-xs ml-2">
                            (Haz clic en el mapa para ajustar la ubicación)
                        </span>
                    </label>
                    <div id="map" class="rounded-xl border-4 border-gray-300 shadow-lg" style="height: 400px;"></div>
                    
                    <div class="mt-3 grid grid-cols-2 gap-4">
                        <div class="p-3 bg-blue-50 border-2 border-blue-200 rounded-xl">
                            <p class="text-xs text-gray-600 mb-1">Latitud:</p>
                            <p class="font-mono font-bold text-gray-800" id="latitud-display">{{ $finca->latitud }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 border-2 border-blue-200 rounded-xl">
                            <p class="text-xs text-gray-600 mb-1">Longitud:</p>
                            <p class="font-mono font-bold text-gray-800" id="longitud-display">{{ $finca->longitud }}</p>
                        </div>
                    </div>

                    @error('latitud')
                        <p class="text-red-600 text-sm mt-2 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <input type="hidden" name="latitud" id="latitud" value="{{ old('latitud', $finca->latitud) }}">
                <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud', $finca->longitud) }}">
            </div>

            <!-- Estado -->
            <div class="mb-8">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-toggle-on text-white text-lg"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Estado de la Finca</h2>
                </div>

                <div class="flex items-center p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                    <input type="checkbox" 
                           name="activa" 
                           id="activa" 
                           value="1"
                           {{ old('activa', $finca->activa) ? 'checked' : '' }}
                           class="w-6 h-6 text-green-600 rounded focus:ring-2 focus:ring-green-500">
                    <label for="activa" class="ml-3 text-sm font-bold text-gray-800">
                        Finca Activa
                        <span class="block text-xs text-gray-600 font-normal mt-1">
                            Las fincas inactivas no aparecerán en los reportes ni estadísticas
                        </span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-6 border-t-2 border-red-200">
                <a href="{{ route('fincas.show', $finca) }}" class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit" 
                        class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        var fincaData = <?php echo json_encode([
            'lat' => floatval($finca->latitud),
            'lng' => floatval($finca->longitud)
        ]); ?>;
        
        var map = L.map('map').setView([fincaData.lat, fincaData.lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);
        
        var marker = L.marker([fincaData.lat, fincaData.lng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }),
            draggable: true
        }).addTo(map);
        
        var searchCircle = null;
        
        function updateCoordinates(lat, lng) {
            document.getElementById('latitud').value = lat.toFixed(8);
            document.getElementById('longitud').value = lng.toFixed(8);
            document.getElementById('latitud-display').textContent = lat.toFixed(8);
            document.getElementById('longitud-display').textContent = lng.toFixed(8);
        }
        
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
        });
        
        marker.on('dragend', function(event) {
            var position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });
        
        document.getElementById('buscar-ubicacion').addEventListener('click', function() {
            var municipio = document.getElementById('municipio').value.trim();
            var departamento = document.getElementById('departamento').value.trim();
            var direccion = document.getElementById('direccion').value.trim();
            
            if (!municipio || !departamento) {
                alert('⚠️ Por favor, ingresa al menos el Municipio y Departamento para buscar en el mapa.');
                return;
            }
            
            var query = direccion ? direccion + ', ' + municipio + ', ' + departamento + ', Colombia' : municipio + ', ' + departamento + ', Colombia';
            
            var boton = this;
            document.getElementById('buscar-texto').textContent = 'Buscando...';
            document.getElementById('buscar-spinner').classList.remove('hidden');
            boton.disabled = true;
            
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&countrycodes=co&limit=1', {
                headers: { 'User-Agent': 'BovisoftApp/1.0' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    var result = data[0];
                    var lat = parseFloat(result.lat);
                    var lng = parseFloat(result.lon);
                    
                    if (searchCircle) {
                        map.removeLayer(searchCircle);
                    }
                    
                    searchCircle = L.circle([lat, lng], {
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.1,
                        radius: 5000,
                        weight: 2,
                        dashArray: '5, 5'
                    }).addTo(map);
                    
                    map.setView([lat, lng], 14);
                    marker.setLatLng([lat, lng]);
                    updateCoordinates(lat, lng);
                    
                    alert('✅ Ubicación encontrada! Ajusta el marcador si es necesario.');
                } else {
                    alert('❌ No se encontró la ubicación. Por favor, ajusta manualmente en el mapa.');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('❌ Error al buscar la ubicación. Por favor, intenta nuevamente.');
            })
            .finally(function() {
                document.getElementById('buscar-texto').textContent = 'Buscar en Mapa';
                document.getElementById('buscar-spinner').classList.add('hidden');
                boton.disabled = false;
            });
        });
    });
})();
</script>
@endsection