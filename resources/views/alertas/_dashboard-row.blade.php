            <div data-dashboard-alert-row class="flex items-center gap-3 p-3 {{ $cc }} border-2 rounded-xl">
                <i class="fa-solid {{ $alerta['icono'] }} text-lg flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold truncate"><span data-alert-field="titulo">{{ $alerta['titulo'] }}</span></p>
                    <p class="text-xs opacity-80 truncate"><span data-alert-field="animal">{{ $alerta['animal'] }}</span></p>
                </div>
                <a href="{{ $alerta['url'] ?? route('salud.show', $alerta['salud_id']) }}"
                   class="text-xs font-bold underline flex-shrink-0">Ver</a>
            </div>
