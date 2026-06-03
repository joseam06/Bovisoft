@extends('layouts.dashboard')

@section('title', 'Registrar Ingreso')

@section('content')
<div class="animate-slide-up">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-circle-plus text-emerald-700 text-2xl"></i>
                </span>
                Registrar Ingreso
            </h1>
            <p class="text-red-100 mt-1 ml-16">Registra un nuevo ingreso de dinero</p>
        </div>
        <a href="{{ route('finanzas.index') }}"
            class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
            <i class="fa-solid fa-arrow-left mr-2"></i>Volver
        </a>
    </div>

    <form action="{{ route('finanzas.ingresos.store') }}" method="POST" id="form-ingreso">
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
                            <select name="finca_id" id="finca_id"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('finca_id') border-red-500 @enderror">
                                <option value="">Seleccionar finca...</option>
                                @foreach($fincas as $f)
                                    <option value="{{ $f->id }}" {{ old('finca_id', $animalPresel?->finca_id) == $f->id ? 'selected' : '' }}>
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
                            Tipo de Ingreso <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-tag text-gray-400"></i>
                            </div>
                            <select name="tipo" id="tipo_ingreso"
                                class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('tipo') border-red-500 @enderror">
                                @foreach(\App\Models\Ingreso::getTipos() as $k => $v)
                                    <option value="{{ $k }}" {{ old('tipo', $tipoPresel) == $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        @error('tipo') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 2: Selector de animal (visible solo para venta_animal) ══ --}}
            <div id="seccion-animal" class="{{ old('tipo', $tipoPresel) === 'venta_animal' ? '' : 'hidden' }}">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-cow text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Animal a Vender</h2>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        Seleccionar animal <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-cow text-gray-400"></i>
                        </div>
                        <select name="animal_id" id="animal_id"
                            class="w-full pl-12 pr-10 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 appearance-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all @error('animal_id') border-red-500 @enderror">
                            <option value="">Seleccionar animal...</option>
                            @foreach($animales as $a)
                                <option value="{{ $a->id }}"
                                    data-finca="{{ $a->finca_id }}"
                                    data-tipo="{{ $a->tipo }}"
                                    data-raza="{{ $a->raza ?? 'N/A' }}"
                                    data-codigo="{{ $a->codigo }}"
                                    data-peso="{{ $a->peso_actual ?? 'N/A' }}"
                                    {{ old('animal_id', $animalPresel?->id) == $a->id ? 'selected' : '' }}>
                                    {{ $a->nombre ?? $a->codigo }} — {{ $a->finca->nombre ?? '' }} ({{ ucfirst($a->tipo) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                    @error('animal_id') <p class="text-red-600 text-sm mt-1"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                </div>

                {{-- Ficha del animal seleccionado --}}
                <div id="ficha-animal" class="hidden p-5 bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow">
                            <i class="fa-solid fa-cow text-white"></i>
                        </div>
                        <p class="font-bold text-gray-800">Información del animal</p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">Código</p>
                            <p class="font-bold text-gray-800" id="ficha-codigo">—</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Tipo</p>
                            <p class="font-bold text-gray-800" id="ficha-tipo">—</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Raza</p>
                            <p class="font-bold text-gray-800" id="ficha-raza">—</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Peso actual</p>
                            <p class="font-bold text-gray-800" id="ficha-peso">—</p>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded-lg">
                        <p class="text-xs text-yellow-800 font-semibold">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            Al guardar, este animal quedará marcado como <strong>Vendido</strong> en el sistema.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 3: Datos del ingreso ══ --}}
            <div>
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-money-bill text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Datos del Ingreso</h2>
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
                            <input type="number" name="monto" value="{{ old('monto') }}"
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
                            <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}"
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
                            <input type="text" name="descripcion" value="{{ old('descripcion') }}"
                                placeholder="Ej: Venta 50 litros a Lácteos S.A."
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ SECCIÓN 4: Datos del comprador (venta_animal / venta_leche) ══ --}}
            <div id="seccion-comprador" class="{{ in_array(old('tipo', $tipoPresel), ['venta_animal', 'venta_leche']) ? '' : 'hidden' }}">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-red-200">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-user text-white"></i>
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800">Datos del Comprador <span class="text-gray-400 text-lg font-normal">(Opcional)</span></h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre o razón social</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="comprador_nombre" value="{{ old('comprador_nombre') }}"
                                placeholder="Nombre del comprador"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Teléfono</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-phone text-gray-400"></i>
                            </div>
                            <input type="text" name="comprador_telefono" value="{{ old('comprador_telefono') }}"
                                placeholder="Ej: 310 000 0000"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Documento (CC / NIT)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-id-card text-gray-400"></i>
                            </div>
                            <input type="text" name="comprador_documento" value="{{ old('comprador_documento') }}"
                                placeholder="Número de documento"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
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
                    placeholder="Notas adicionales sobre este ingreso..."
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all resize-none">{{ old('observaciones') }}</textarea>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-4 pt-4 border-t-2 border-red-200">
                <a href="{{ route('finanzas.index') }}"
                    class="px-8 py-4 border-2 border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition-all shadow-lg">
                    <i class="fa-solid fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar Ingreso
                </button>
            </div>

        </div>
    </form>
</div>

<script>
(function () {
    var tipoSelect   = document.getElementById('tipo_ingreso');
    var animalSelect = document.getElementById('animal_id');
    var fincaSelect  = document.getElementById('finca_id');
    var secAnimal    = document.getElementById('seccion-animal');
    var secComprador = document.getElementById('seccion-comprador');
    var fichaAnimal  = document.getElementById('ficha-animal');

    function actualizarSecciones() {
        var tipo = tipoSelect.value;
        secAnimal.classList.toggle('hidden',    tipo !== 'venta_animal');
        secComprador.classList.toggle('hidden', tipo !== 'venta_animal' && tipo !== 'venta_leche');
        if (tipo !== 'venta_animal') {
            fichaAnimal.classList.add('hidden');
        }
    }

    function actualizarFichaAnimal() {
        var opt = animalSelect.options[animalSelect.selectedIndex];
        if (!opt || !opt.value) {
            fichaAnimal.classList.add('hidden');
            return;
        }
        document.getElementById('ficha-codigo').textContent = opt.getAttribute('data-codigo') || '—';
        document.getElementById('ficha-tipo').textContent   = opt.getAttribute('data-tipo')   || '—';
        document.getElementById('ficha-raza').textContent   = opt.getAttribute('data-raza')   || '—';
        document.getElementById('ficha-peso').textContent   = opt.getAttribute('data-peso')   || '—';
        fichaAnimal.classList.remove('hidden');

        // Auto-rellenar finca
        var fincaId = opt.getAttribute('data-finca');
        if (fincaId) fincaSelect.value = fincaId;
    }

    tipoSelect.addEventListener('change', actualizarSecciones);
    animalSelect.addEventListener('change', actualizarFichaAnimal);

    // Inicializar al cargar (si hay old() o presel)
    actualizarSecciones();
    if (animalSelect.value) actualizarFichaAnimal();
})();
</script>
@endsection