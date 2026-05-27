@php
$colores = [
    'red'    => ['bg' => 'bg-red-100',    'icon' => 'text-red-600',    'badge' => 'bg-red-100 text-red-700 border-red-200'],
    'orange' => ['bg' => 'bg-orange-100', 'icon' => 'text-orange-600', 'badge' => 'bg-orange-100 text-orange-700 border-orange-200'],
    'yellow' => ['bg' => 'bg-yellow-100', 'icon' => 'text-yellow-600', 'badge' => 'bg-yellow-100 text-yellow-700 border-yellow-200'],
    'blue'   => ['bg' => 'bg-blue-100',   'icon' => 'text-blue-600',   'badge' => 'bg-blue-100 text-blue-700 border-blue-200'],
    'purple' => ['bg' => 'bg-purple-100', 'icon' => 'text-purple-600', 'badge' => 'bg-purple-100 text-purple-700 border-purple-200'],
    'teal'   => ['bg' => 'bg-teal-100',   'icon' => 'text-teal-600',   'badge' => 'bg-teal-100 text-teal-700 border-teal-200'],
];
$c = $colores[$alerta['color']] ?? $colores['blue'];

$categoriasLabel = [
    'preventivo'  => 'Preventivo',
    'clinico'     => 'Clínico',
    'reproductivo'=> 'Reproductivo',
    'seguimiento' => 'Seguimiento',
];
@endphp

<div class="flex items-center px-6 py-4 {{ $bgHover ?? 'hover:bg-gray-50' }} transition-all">
    {{-- Ícono --}}
    <div class="w-10 h-10 {{ $c['bg'] }} rounded-xl flex items-center justify-center flex-shrink-0 mr-4">
        <i class="fa-solid {{ $alerta['icono'] }} {{ $c['icon'] }}"></i>
    </div>

    {{-- Contenido --}}
    <div class="flex-1 min-w-0">
        <p class="font-semibold text-gray-800 text-sm truncate">{{ $alerta['titulo'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $alerta['descripcion'] }}</p>
        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
            <span class="text-xs text-gray-400 flex items-center">
                <i class="fa-solid fa-map-location-dot mr-1"></i>{{ $alerta['finca'] }}
            </span>
            @if($alerta['categoria'])
                <span class="text-xs px-2 py-0.5 rounded-full border {{ $c['badge'] }} font-medium">
                    {{ $categoriasLabel[$alerta['categoria']] ?? $alerta['categoria'] }}
                </span>
            @endif
        </div>
    </div>

    {{-- Fecha y acción --}}
    <div class="flex items-center gap-3 ml-4 flex-shrink-0">
        <div class="text-right hidden sm:block">
            <p class="text-xs font-semibold text-gray-600">{{ $alerta['fecha'] }}</p>
            <p class="text-xs text-gray-400">Fecha clave</p>
        </div>
        <a href="{{ route('salud.show', $alerta['salud_id']) }}"
           class="flex items-center space-x-1 px-3 py-1.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-xs font-bold rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-right"></i>
            <span>Ver</span>
        </a>
    </div>
</div>