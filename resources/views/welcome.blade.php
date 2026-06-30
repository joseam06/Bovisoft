<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BoviSoft - Gestión Inteligente Ganadera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #7c2d12 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #450a0a 100%);
            background-attachment: fixed;
        }
        
        /* Navbar flotante */
        .navbar-scrolled {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3) 50%, transparent);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 14px;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.3);
        }
        
        .stat-number {
            background: linear-gradient(135deg, #ffffff 0%, #fee2e2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Animación de scroll suave */
        html {
            scroll-behavior: smooth;
        }
        
        /* Gradiente animado */
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .animated-gradient {
            background: linear-gradient(270deg, #dc2626, #991b1b, #7c2d12, #450a0a);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        }
    </style>
</head>
<body class="text-gray-800">

    <!-- HEADER / NAV (con efecto flotante) -->
    <header id="mainNav" class="fixed w-full top-0 left-0 bg-white/95 backdrop-blur-md shadow-md z-50 border-b border-red-100 transition-all duration-300">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
        
        <div class="flex items-center gap-3">
            <img src="/images/logored.png" alt="Logo Bovisoft" class="w-12 h-12 object-contain" />
                <div>
                    <h1 class="text-2xl font-extrabold bg-gradient-to-r from-red-800 to-red-600 bg-clip-text text-transparent">Bovisoft</h1>
                    <p class="text-xs text-gray-500 font-medium">Gestión Ganadera</p>
                </div>
            </div>
            <nav class="hidden md:flex gap-8 text-gray-700 font-semibold">
                <a href="#inicio" class="hover:text-red-600 transition-colors relative group">
                    Inicio
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-600 group-hover:w-full transition-all"></span>
                </a>
                <a href="#beneficios" class="hover:text-red-600 transition-colors relative group">
                    Beneficios
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-600 group-hover:w-full transition-all"></span>
                </a>
                <a href="#caracteristicas" class="hover:text-red-600 transition-colors relative group">
                    Características
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-600 group-hover:w-full transition-all"></span>
                </a>
                <a href="#contacto" class="hover:text-red-600 transition-colors relative group">
                    Contacto
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-red-600 group-hover:w-full transition-all"></span>
                </a>
            </nav>
            <button type="button" data-open="login"
                class="px-6 py-2.5 bg-gradient-to-r from-red-700 to-red-800 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-red-800 hover:to-red-900 transition-all transform hover:scale-105">
                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar sesión
            </button>
        </div>
    </header>

   
    <!-- HERO -->
    <section id="inicio" class="pt-32 pb-24 bg-gradient-to-r from-red-700 to-black text-white relative overflow-hidden">
        <!-- Decorative elements -->
        <div class="absolute top-20 right-10 w-72 h-72 bg-red-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-10 w-96 h-96 bg-red-900/10 rounded-full blur-3xl"></div>
        
        <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center px-6 relative z-10">
            <div class="space-y-6">
                <div class="inline-block px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm font-semibold border border-white/20">
                    🐄 Solución integral para ganaderos
                </div>
                <h2 class="text-6xl md:text-7xl font-black leading-tight">
                    La plataforma <span class="stat-number">inteligente</span> para la gestión ganadera
                </h2>
                <p class="text-xl text-red-50 leading-relaxed">
                    Bovisoft te permite administrar tu finca, animales, producción, ventas y reportes desde un solo lugar de manera eficiente y profesional.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <button type="button" data-open="register"
                        class="px-8 py-4 bg-white text-red-900 font-bold rounded-xl shadow-2xl hover:shadow-red-500/50 hover:scale-105 transition-all">
                        Comenzar ahora →
                    </button>
                    <a href="#beneficios"class="px-8 py-4 bg-red-900 border-2 border-red-400/40 text-white font-bold rounded-xl shadow-2xl hover:shadow-red-500/50 hover:scale-105 transition-all">
                        <i class="fas fa-info-circle mr-2"></i>Ver más
                    </a>
                   
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-white/20">
                    <div>
                        <div class="text-3xl font-bold">100+</div>
                        <div class="text-sm text-red-100">Fincas activas</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">5K+</div>
                        <div class="text-sm text-red-100">Animales registrados</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">99%</div>
                        <div class="text-sm text-red-100">Satisfacción</div>
                    </div>
                </div>
            </div>
            
            <div class="hidden md:block relative">
                <div class="absolute inset-0 bg-gradient-to-r from-red-600/20 to-transparent rounded-3xl transform rotate-3"></div>
                <img src="/images/ganaderia.jpg" alt="Ganadería" class="relative rounded-3xl shadow-2xl transform -rotate-2 hover:rotate-0 transition-transform duration-500" />
            </div>
        </div>
    </section>
    

    <!-- Separador -->
    <div class="section-divider my-8"></div>
    <!-- BENEFICIOS -->   
    <!-- pt (espacio debajo del separador o inicio de la seccion) --> <!-- pt (espacio antes del separador o final de la seccion) -->                   
    <section id="beneficios" class="pt-20 pb-20 bg-gradient-to-r from-red-700 to-black text-white relative overflow-hidden">

    <div class="py-16 bg-white/10 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl border-2 border-white/20 shadow-2xl">
        <div class="max-w-7xl mx-auto px-6">

            <div class="text-center mb-16">
                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-base font-semibold mb-4">
                    Ventajas competitivas
                </span>
                <h3 class="text-4xl md:text-5xl font-bold text-white mb-4">¿Por qué usar Bovisoft?</h3>
                <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                    Optimiza tu operación ganadera con tecnología de punta
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl card-hover border border-green-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold mb-3 text-gray-900">Fácil de usar</h4>
                    <p class="text-gray-700 leading-relaxed">
                        Una interfaz intuitiva que cualquier administrador puede manejar sin capacitación compleja. Diseñada pensando en el usuario.
                    </p>
                </div>
                
                <div class="p-8 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl card-hover border border-blue-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold mb-3 text-gray-900">Control total</h4>
                    <p class="text-gray-700 leading-relaxed">
                        Registro completo de animales, salud, nacimientos, producción y ventas. Todo centralizado en un solo sistema.
                    </p>
                </div>
                
                <div class="p-8 bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl card-hover border border-purple-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold mb-3 text-gray-900">Reportes inteligentes</h4>
                    <p class="text-gray-700 leading-relaxed">
                        Obtén información en tiempo real para tomar mejores decisiones basadas en datos actualizados y precisos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Separador -->
    <div class="section-divider my-8"></div>

    <!-- CARACTERISTICAS -->
        <section id="caracteristicas" class="pt-20 pb-20 bg-gradient-to-r from-red-700 to-black text-white relative overflow-hidden">

    <div class="py-16 bg-white/10 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl border-2 border-white/20 shadow-2xl">
        <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-base font-semibold mb-4">
                Características principales
            </span>
                <h3 class="text-4xl md:text-5xl font-bold text-white mb-4">Funciones de Bovisoft</h3>
                <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                    Todo lo que necesitas para gestionar tu operación ganadera
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="flex items-start group">
                        <span class="feature-icon">📋</span>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-2">Registro de ganado</h5>
                            <p class="text-gray-300">Control detallado de cada animal con historial completo</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">💉</span>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-2">Gestión de salud y vacunación</h5>
                            <p class="text-gray-300">Seguimiento de tratamientos y calendario de vacunas</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">🥛</span>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-2">Control de producción lechera</h5>
                            <p class="text-gray-300">Monitoreo diario de producción por animal</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">💰</span>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-2">Registro de ventas y compras</h5>
                            <p class="text-gray-300">Control financiero completo de tu operación</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">📊</span>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-2">Dashboard intuitivo</h5>
                            <p class="text-gray-300">Visualiza toda tu información de un vistazo</p>
                        </div>
                    </div>
                    
                </div>
                
              <div class="relative">
    <div class="absolute inset-0 bg-gradient-to-r from-red-400 to-purple-300/55 rounded-2xl blur-2xl"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-red-800 to-transparent rounded-3xl transform rotate-3"></div>
    <img src="{{ asset('images/caracteristicas.png') }}" 
         class="relative rounded-3xl shadow-2xl border-4 border-white w-full h-auto object-cover transform -rotate-2 hover:rotate-0 transition-transform duration-500" 
         alt="Dashboard BoviSoft - Gestión Ganadera" 
         loading="lazy" />
</div>
            </div>
        </div>
    </section>

    <!-- Separador -->
    <div class="section-divider my-8"></div>

    <!-- CONTACTO -->
        <section id="contacto" class="pt-20 pb-16 bg-gradient-to-r from-red-700 to-black text-white relative overflow-hidden">
        <div class="py-16 bg-white/10 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl border-2 border-white/20 shadow-2xl">
      
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold mb-6">
                ¿Listo para comenzar?
            </div>
            <h3 class="text-4xl md:text-5xl font-bold mb-6 text-white">Contáctanos</h3>
            <p class="text-xl text-gray-300 mb-10">
                ¿Tienes dudas? ¿Quieres una demo? Estamos listos para ayudarte a transformar tu gestión ganadera.
            </p>
            <a href="mailto:monterrosa@bovisoft.com" 
               class="inline-block px-10 py-4 bg-gradient-to-r from-green-600 to-green-500 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl hover:from-green-700 hover:to-green-600 transition-all transform hover:scale-105">
                📧 Enviar correo
            </a>
            
            <!-- Contact cards -->
            <div class="grid md:grid-cols-3 gap-6 mt-16">
                <div class="p-6 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="text-3xl mb-3">📞</div>
                    <h5 class="font-bold text-gray-900 mb-2">Teléfono</h5>
                    <p class="text-gray-600">+57 321 761 7349</p>
                </div>
                <div class="p-6 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="text-3xl mb-3">✉️</div>
                    <h5 class="font-bold text-gray-900 mb-2">Email</h5>
                    <p class="text-gray-600">monterrosa@bovisoft.com</p>
                </div>
                <div class="p-6 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="text-3xl mb-3">📍</div>
                    <h5 class="font-bold text-gray-900 mb-2">Ubicación</h5>
                    <p class="text-gray-600">Montería, Colombia</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Separador -->
    <div class="section-divider my-8"></div>

    <!-- FOOTER -->
    <footer class="py-12 bg-black/60 backdrop-blur-md text-white text-center border-t-4 border-white/20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-center gap-1 mb-4">
                <img src="/images/logowhite.png" alt="Logo Bovisoft" class="w-8 h-8 object-contain" />
                <span class="font-bold text-lg">BoviSoft</span>
            </div>
            <p class="text-gray-400">© 2025 BoviSoft - Gestión Ganadera Inteligente</p>
            <p class="text-sm text-gray-500 mt-2">Desarrollado para ganaderos colombianos</p>
        </div>
    </footer>

    <!-- OVERLAY AUTH MEJORADO -->
    <div id="authOverlay" class="fixed inset-0 z-[999] hidden" aria-hidden="true">
        <!-- Fondo -->
        <div id="authBackdrop" class="absolute inset-0 bg-black/80 backdrop-blur-md opacity-0 transition-opacity duration-300"></div>

        <!-- Contenido centrado -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div id="authPanel"
                 class="w-full max-w-md rounded-3xl border-2 border-white/20
                        bg-gradient-to-br from-red-800/40 via-black/60 to-gray-900/40
                        backdrop-blur-2xl shadow-2xl
                        transform scale-95 translate-y-4 opacity-0
                        transition-all duration-300">

                <div class="p-8">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-3">
                 <img src="/images/logored.png" alt="Logo Bovisoft" class="w-12 h-12 object-contain" />
                 <div class="flex flex-col">
                <h1 class="text-2xl font-bold bg-gradient-to-r from-red-800 to-red-600 bg-clip-text text-transparent">Bovisoft</h1>
                <p class="text-xs text-gray-400 font-medium">Gestión Ganadera</p>
                </div>
             <div>      </div>
             <div>      </div>
                <h5 id="authSubtitle" class="text-white/80 text-sm font-medium">Accede a tu cuenta</h5>
          
            </div>
            
                        </div>
                        <button id="authClose" class="text-white/70 hover:text-white text-3xl leading-none transition-colors">&times;</button>
                    </div>

                    <!-- Tabs MEJORADOS -->
                    <div class="flex gap-3 mb-8 bg-white/5 p-1.5 rounded-xl">
                        <button id="tabLogin"
                                class="flex-1 px-4 py-3 rounded-lg text-sm font-bold transition-all
                                       bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </button>
                        <button id="tabRegister"
                                class="flex-1 px-4 py-3 rounded-lg text-sm font-bold
                                       bg-transparent text-white/70 hover:text-white transition-all">
                                <i class="fas fa-user-plus mr-2"></i>Registro
                        </button>
                    </div>

                    <!-- Mensajes de error (NO se cierra el overlay) -->
                    <div class="mb-6">
                        @if ($errors->any())
                        <div class="bg-red-500/20 border-2 border-red-500/50 rounded-xl p-4 backdrop-blur-sm">
                            <div class="flex items-center gap-3 text-white">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                                <div class="flex-1">
                                    <p class="font-bold mb-1">Credenciales incorrectas</p>
                                    @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-100">{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Contenido de formularios -->
                    <div id="panelLogin" class="block">
                        @include('auth.partials.login-form')

                         <!-- Separador -->
    <div class="flex items-center gap-3 my-5">
        <div class="flex-1 h-px bg-white/20"></div>
        <span class="text-white/50 text-xs font-medium uppercase tracking-widest">O</span>
        <div class="flex-1 h-px bg-white/20"></div>
    </div>

    <!-- Botón Google -->
    <a href="{{ route('auth.google') }}"
       class="flex items-center justify-center gap-3 w-full py-3 px-4
              bg-white hover:bg-gray-50 text-gray-800 font-semibold
              rounded-xl shadow-md hover:shadow-lg
              transition-all duration-200 hover:scale-[1.02]">
        <svg class="w-5 h-5" viewBox="0 0 48 48">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            <path fill="none" d="M0 0h48v48H0z"/>
        </svg>
        Continúa con Google
    </a>
                    </div>

<div id="panelRegister" class="hidden">
    @include('auth.partials.register-form')

    <div class="flex items-center gap-3 my-5">
        <div class="flex-1 h-px bg-white/20"></div>
        <span class="text-white/50 text-xs font-medium uppercase tracking-widest">O</span>
        <div class="flex-1 h-px bg-white/20"></div>
    </div>

    <a href="{{ route('auth.google') }}"
       class="flex items-center justify-center gap-3 w-full py-3 px-4
              bg-white hover:bg-gray-50 text-gray-800 font-semibold
              rounded-xl shadow-md hover:shadow-lg
              transition-all duration-200 hover:scale-[1.02]">
        <svg class="w-5 h-5" viewBox="0 0 48 48">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            <path fill="none" d="M0 0h48v48H0z"/>
        </svg>
        Continúa con Google
    </a>
</div>
                    
                    <div id="panelForgot" class="hidden">
                        @include('auth.partials.forgot-password-form')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Navbar flotante 
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 80) {
            nav.classList.add('navbar-scrolled');
        } else {
            nav.classList.remove('navbar-scrolled');
        }
    });

    // Overlay mejorado con tabs activos
    (function () {
        const overlay = document.getElementById('authOverlay');
        const backdrop = document.getElementById('authBackdrop');
        const panel = document.getElementById('authPanel');
        const closeBtn = document.getElementById('authClose');

        const tabLogin = document.getElementById('tabLogin');
        const tabRegister = document.getElementById('tabRegister');
        const panelLogin = document.getElementById('panelLogin');
        const panelRegister = document.getElementById('panelRegister');
        const panelForgot   = document.getElementById('panelForgot');
        const subtitle = document.getElementById('authSubtitle');
        const tabsContainer = document.querySelector('#authPanel .flex.gap-3.mb-8');
        
        function setTab(which) {
    // Ocultar todos los paneles
    panelLogin.classList.add('hidden');
    panelRegister.classList.add('hidden');
    panelForgot.classList.add('hidden');

    // Mostrar el panel activo
    if (which === 'login')        panelLogin.classList.remove('hidden');
    else if (which === 'register') panelRegister.classList.remove('hidden');
    else if (which === 'forgot')   panelForgot.classList.remove('hidden');

    if (which === 'forgot') {
        tabsContainer.classList.add('hidden');
    } else {
        tabsContainer.classList.remove('hidden');
    }
    // Estilos de tabs (forgot no activa ningún tab, ambos quedan inactivos)
    const activeTab   = 'flex-1 px-4 py-3 rounded-lg text-sm font-bold transition-all bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg';
    const inactiveTab = 'flex-1 px-4 py-3 rounded-lg text-sm font-bold bg-transparent text-white/70 hover:text-white transition-all';

    tabLogin.className    = which === 'login'    ? activeTab : inactiveTab;
    tabRegister.className = which === 'register' ? activeTab : inactiveTab;

    // Subtítulo dinámico
    if (which === 'login')         subtitle.textContent = 'Accede a tu cuenta';
    else if (which === 'register') subtitle.textContent = 'Crea tu cuenta gratis';
    else if (which === 'forgot')   subtitle.textContent = 'Recuperar contraseña';
}

        function openOverlay(which) {
            overlay.classList.remove('hidden');
            overlay.setAttribute('aria-hidden', 'false');
            setTab(which);

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
                panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
            });

            document.addEventListener('keydown', onEsc);
        }

        function closeOverlay() {
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
            panel.classList.add('opacity-0', 'scale-95', 'translate-y-4');
            document.removeEventListener('keydown', onEsc);

            setTimeout(() => {
                overlay.classList.add('hidden');
                overlay.setAttribute('aria-hidden', 'true');
            }, 300);
        }

        function onEsc(e) {
            if (e.key === 'Escape') closeOverlay();
        }

        // Abrir con data-open
        document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-open]');
    if (!btn) return;

    const which = btn.getAttribute('data-open');
    if (which === 'login' || which === 'register' || which === 'forgot') {
        e.preventDefault();
        openOverlay(which);
    }
});

        // Tabs
        tabLogin.addEventListener('click', () => setTab('login'));
        tabRegister.addEventListener('click', () => setTab('register'));

        // Cerrar
        closeBtn.addEventListener('click', closeOverlay);
        backdrop.addEventListener('click', closeOverlay);

       // Abrir overlay automáticamente si hay errores
        @if ($errors->any())
            openOverlay('login');
        @endif

        // Reabrir forgot si se procesó el envío del enlace
        @if (session('status'))
            openOverlay('forgot');
        @endif

        window.openOverlay = openOverlay;
    })();
    </script>

</body>
</html>