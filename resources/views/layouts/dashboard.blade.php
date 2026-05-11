<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Bovisoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Degradado ROJO CON NEGRO igual al welcome */
        .bg-gradient-main {
            background: linear-gradient(135deg, #7c2d12 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #450a0a 100%);
        }
        
        .bg-gradient-sidebar {
            background: linear-gradient(180deg, #450a0a 0%, #7c2d12 50%, #000000 100%);
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3) 50%, transparent);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.98) 100%);
            backdrop-filter: blur(10px);
        }
        
        .sidebar-link {
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #dc2626;
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }
        
        .sidebar-link:hover::before,
        .sidebar-link.active::before {
            transform: scaleY(1);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        #map {
            height: 400px;
            border-radius: 1rem;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: #dc2626;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #991b1b;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
            }
            50% {
                box-shadow: 0 0 30px rgba(220, 38, 38, 0.8);
            }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7c2d12',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gradient-main min-h-screen">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="group fixed left-0 top-0 h-screen bg-gradient-sidebar shadow-2xl transition-all duration-300 ease-in-out w-20 hover:w-64 z-40 overflow-hidden border-r-4 border-red-700/50">
        
        <!-- Logo / Brand -->
        <div class="p-4 flex items-center space-x-3 border-b border-red-900/50">
            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg p-1">
                <img src="{{ asset('images/logo.png') }}" alt="Bovisoft Logo" class="w-full h-full object-contain">
            </div>
            <span class="text-white font-bold text-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">Bovisoft</span>
        </div>

        <!-- Navigation Menu -->
        <nav class="mt-6 px-3 space-y-2">
            <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center px-4 py-3 text-white rounded-xl hover:bg-red-700/30 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-red-700/30 active' : '' }}">
                <i class="fa-solid fa-gauge-high text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Inicio</span>
            </a>

            <a href="{{ route('animales.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-cow text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Ganado</span>
            </a>

            <a href="{{ route('fincas.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-map-location-dot text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Fincas</span>
            </a>

            <a href="{{ route('salud.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-heart-pulse text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Salud</span>

            </a>  
            

            <a href="{{ route('produccion.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-chart-line text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Producción</span>
            </a>

            <a href="{{ route('finanzas.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-money-bill-trend-up text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Finanzas</span>
            </a>

            <a href="{{ route('alertas.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-bell text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Alertas</span>
            </a>

            <a href="{{ route('reportes.index') }}" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-file-alt text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Reportes</span>
            </a>
        </nav>

        <!-- Settings at Bottom -->
         <!-- <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-red-900/50 space-y-2">
            <a href="#" class="sidebar-link flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-gear text-xl w-6 flex-shrink-0"></i>
                <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Configuración</span>
            </a>-->
            
            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <button type="submit" class="sidebar-link w-full flex items-center px-4 py-3 text-white/70 rounded-xl hover:bg-red-700/30 hover:text-white transition-all duration-200">
                    <i class="fa-solid fa-right-from-bracket text-xl w-6 flex-shrink-0"></i>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap font-medium">Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="ml-20 min-h-screen transition-all duration-300">
        
        <!-- TOP HEADER -->
        <header class="glass-effect sticky top-0 z-30 shadow-xl">
            <div class="flex items-center justify-between px-6 py-4">
                <!-- Search Bar -->
                <div class="flex-1 max-w-lg">
                    <div class="relative">
                        <input type="text" placeholder="Buscar animal, finca, reporte..." 
                               class="w-full pl-12 pr-4 py-3 bg-white border-2 border-red-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all">
                        <i class="fa-solid fa-search absolute left-4 top-4 text-red-400 text-lg"></i>
                    </div>
                </div>

                <!-- Right Side Icons -->
                <div class="flex items-center space-x-4 ml-6">
                    <!-- Notifications -->
                    <button class="relative p-3 text-red-700 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                        <i class="fa-solid fa-bell text-xl"></i>
                        <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 rounded-full pulse-glow"></span>
                    </button>

                    <!-- User Profile -->
                    <div class="flex items-center space-x-3 pl-4 border-l-2 border-red-200">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-gray-700">{{ auth()->user()->name ?? 'Usuario' }}</p>
                            <p class="text-xs text-gray-500">Administrador</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center cursor-pointer hover:shadow-lg transition-all shadow-md">
                            <i class="fa-solid fa-user text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="p-6">
            @yield('content')
        </main>
        
        <!-- FOOTER -->
        <footer class="glass-effect mt-8 py-6 text-center border-t-2 border-red-200">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img src="{{ asset('images/logo.png') }}" alt="Bovisoft Logo" class="w-8 h-8 object-contain">
                <span class="font-bold text-lg text-gray-800">Bovisoft</span>
            </div>
            <p class="text-gray-600 text-sm">© 2025 Bovisoft - Gestión Ganadera Inteligente</p>
            <p class="text-xs text-gray-500 mt-1">Desarrollado para ganaderos colombianos</p>
        </footer>
    </div>

</body>
</html>