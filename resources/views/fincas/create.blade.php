@extends('layouts.dashboard')

@section('title', 'Registrar Finca')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-map-marked text-red-700 text-2xl"></i>
                </span>
                Registrar Nueva Finca
            </h1>
            <p class="text-red-100 mt-2 ml-16">Complete la información de la finca</p>
        </div>
        <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver al Dashboard
        </a>
    </div>

    <!-- Formulario -->
    <div class="glass-effect rounded-2xl shadow-2xl p-8 border-4 border-white/50">
        <form action="{{ route('fincas.store') }}" method="POST">
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
                            Nombre de la Finca <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-signature text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   value="{{ old('nombre') }}" 
                                   required
                                   placeholder="Ej: La Esperanza"
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
           value="{{ old('area') }}" 
           step="0.01"
           min="0"
           placeholder="Ej: 50.5 (opcional)"
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
                                      class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('descripcion') }}</textarea>
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
                                   value="{{ old('direccion') }}" 
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
                                   value="{{ old('municipio') }}" 
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
                                   value="{{ old('departamento') }}" 
                                   placeholder="Ej: Córdoba"
                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                        @error('departamento')
                            <p class="text-red-600 text-sm mt-2 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Botón de búsqueda automática -->
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

                <!-- Mensaje de ayuda -->
                <div class="mb-4 p-4 bg-blue-50 border-2 border-blue-200 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-info-circle text-blue-600 text-xl mt-1"></i>
                        <div>
                            <p class="text-sm font-bold text-gray-800 mb-1">¿Cómo funciona?</p>
                            <ul class="text-xs text-gray-600 space-y-1">
                                <li>• Escribe el <strong>municipio y departamento</strong> de la finca</li>
                                <li>• Haz clic en <strong>"Buscar en Mapa"</strong> para centrar el mapa en esa ubicación</li>
                                <li>• Luego <strong>haz clic en el mapa</strong> para seleccionar la ubicación exacta</li>
                                <li>• Puedes <strong>arrastrar el marcador</strong> para ajustar la posición</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Mapa Interactivo -->
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Ubicación en el Mapa <span class="text-red-600">*</span>
                        <span class="text-gray-500 font-normal text-xs ml-2">
                            (Haz clic en el mapa para seleccionar la ubicación)
                        </span>
                    </label>
                    <div id="map" class="rounded-xl border-4 border-gray-300 shadow-lg" style="height: 400px;"></div>
                    
                    <div class="mt-3 grid grid-cols-2 gap-4">
                        <div class="p-3 bg-blue-50 border-2 border-blue-200 rounded-xl">
                            <p class="text-xs text-gray-600 mb-1">Latitud:</p>
                            <p class="font-mono font-bold text-gray-800" id="latitud-display">Sin seleccionar</p>
                        </div>
                        <div class="p-3 bg-blue-50 border-2 border-blue-200 rounded-xl">
                            <p class="text-xs text-gray-600 mb-1">Longitud:</p>
                            <p class="font-mono font-bold text-gray-800" id="longitud-display">Sin seleccionar</p>
                        </div>
                    </div>

                    @error('latitud')
                        <p class="text-red-600 text-sm mt-2 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                    @error('longitud')
                        <p class="text-red-600 text-sm mt-2 flex items-center">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Coordenadas (Ocultos) -->
                <input type="hidden" name="latitud" id="latitud" value="{{ old('latitud') }}">
                <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud') }}">
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-6 border-t-2 border-red-200">
                <a href="{{ route('dashboard') }}" class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit" 
                        class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fa-solid fa-save mr-2"></i>Registrar Finca
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Coordenadas por defecto (Montería, Córdoba)
    const defaultLat = 8.7479;
    const defaultLng = -75.8814;
    
    // Inicializar mapa
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    // Agregar capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);
    
    // Variable para el marcador
    let marker = null;
    let searchCircle = null;
    
    // Función para actualizar coordenadas
    function updateCoordinates(lat, lng) {
        document.getElementById('latitud').value = lat.toFixed(8);
        document.getElementById('longitud').value = lng.toFixed(8);
        document.getElementById('latitud-display').textContent = lat.toFixed(8);
        document.getElementById('longitud-display').textContent = lng.toFixed(8);
    }
    
    // Función para crear/actualizar marcador
    function createMarker(lat, lng, popup = true) {
        // Si ya existe un marcador, eliminarlo
        if (marker) {
            map.removeLayer(marker);
        }
        
        // Crear nuevo marcador
        marker = L.marker([lat, lng], {
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
        
        // Actualizar coordenadas
        updateCoordinates(lat, lng);
        
        // Agregar popup si se solicita
        if (popup) {
            marker.bindPopup(`
                <div class="p-2 text-center">
                    <p class="font-bold text-red-700 mb-1">Ubicación seleccionada</p>
                    <p class="text-xs text-gray-600">Lat: ${lat.toFixed(6)}</p>
                    <p class="text-xs text-gray-600">Lng: ${lng.toFixed(6)}</p>
                    <p class="text-xs text-blue-600 mt-1">
                        <i class="fa-solid fa-hand-pointer"></i> Arrastra para ajustar
                    </p>
                </div>
            `).openPopup();
        }
        
        // Permitir arrastrar el marcador
        marker.on('dragend', function(event) {
            const position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
            
            marker.setPopupContent(`
                <div class="p-2 text-center">
                    <p class="font-bold text-red-700 mb-1">Ubicación seleccionada</p>
                    <p class="text-xs text-gray-600">Lat: ${position.lat.toFixed(6)}</p>
                    <p class="text-xs text-gray-600">Lng: ${position.lng.toFixed(6)}</p>
                </div>
            `);
        });
    }
    
    // Evento click en el mapa
    map.on('click', function(e) {
        createMarker(e.latlng.lat, e.latlng.lng);
    });
    
    // GEOCODIFICACIÓN - Buscar ubicación automáticamente
    document.getElementById('buscar-ubicacion').addEventListener('click', async function() {
        const municipio = document.getElementById('municipio').value.trim();
        const departamento = document.getElementById('departamento').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        
        // Validar que al menos municipio y departamento estén llenos
        if (!municipio || !departamento) {
            alert('⚠️ Por favor, ingresa al menos el Municipio y Departamento para buscar en el mapa.');
            return;
        }
        
        // Construir consulta de búsqueda
        let query = '';
        if (direccion) {
            query = `${direccion}, ${municipio}, ${departamento}, Colombia`;
        } else {
            query = `${municipio}, ${departamento}, Colombia`;
        }
        
        // Mostrar loading
        const boton = this;
        const textoOriginal = document.getElementById('buscar-texto').textContent;
        document.getElementById('buscar-texto').textContent = 'Buscando...';
        document.getElementById('buscar-spinner').classList.remove('hidden');
        boton.disabled = true;
        
        try {
            // Llamar a la API de Nominatim (OpenStreetMap)
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=co&limit=1`,
                {
                    headers: {
                        'User-Agent': 'BovisoftApp/1.0'
                    }
                }
            );
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                const result = data[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                // Remover círculo de búsqueda anterior si existe
                if (searchCircle) {
                    map.removeLayer(searchCircle);
                }
                
                // Agregar círculo de área de búsqueda (5km de radio)
                searchCircle = L.circle([lat, lng], {
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    radius: 5000,
                    weight: 2,
                    dashArray: '5, 5'
                }).addTo(map);
                
                // Centrar mapa en la ubicación encontrada
                map.setView([lat, lng], 14);
                
                // Mostrar notificación de éxito
                const notification = document.createElement('div');
                notification.className = 'fixed top-24 right-6 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-50 animate-slide-up';
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-check-circle text-2xl"></i>
                        <div>
                            <p class="font-bold">¡Ubicación encontrada!</p>
                            <p class="text-sm">Haz clic en el mapa para marcar la ubicación exacta</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
                
            } else {
                // No se encontró la ubicación
                alert(`❌ No se encontró la ubicación "${query}". Por favor, verifica los datos o selecciona manualmente en el mapa.`);
            }
            
        } catch (error) {
            console.error('Error en geocodificación:', error);
            alert('❌ Error al buscar la ubicación. Por favor, intenta nuevamente o selecciona manualmente en el mapa.');
        } finally {
            // Restaurar botón
            document.getElementById('buscar-texto').textContent = textoOriginal;
            document.getElementById('buscar-spinner').classList.add('hidden');
            boton.disabled = false;
        }
    });
    
    // Si hay valores antiguos (por validación fallida), mostrar marcador
    const oldLat = parseFloat(document.getElementById('latitud').value);
    const oldLng = parseFloat(document.getElementById('longitud').value);
    
    if (oldLat && oldLng && oldLat !== 0 && oldLng !== 0) {
        createMarker(oldLat, oldLng, false);
        map.setView([oldLat, oldLng], 15);
    }
    
    // Validación del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const lat = parseFloat(document.getElementById('latitud').value);
        const lng = parseFloat(document.getElementById('longitud').value);
        
        if (!lat || !lng || lat === 0 || lng === 0) {
            e.preventDefault();
            alert('⚠️ Por favor, selecciona la ubicación de la finca en el mapa haciendo clic sobre él.');
            
            // Scroll al mapa
            document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Resaltar el mapa
            document.getElementById('map').style.border = '4px solid #dc2626';
            setTimeout(() => {
                document.getElementById('map').style.border = '4px solid #d1d5db';
            }, 2000);
        }
    });
});
</script>
@endsection