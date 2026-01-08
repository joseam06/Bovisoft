<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bovisoft - Gestión Inteligente Ganadera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #7c2d12 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #450a0a 100%);
            background-attachment: fixed;
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            border-radius: 12px;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }
    </style>
</head>
<body class="text-gray-800">

    <!-- HEADER / NAV -->
    <header class="fixed w-full top-0 left-0 bg-white/95 backdrop-blur-md shadow-lg z-50 border-b border-red-100">
        <div class="max-w-7xl mx-auto flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                <img src="/images/logo.png" alt="Logo Bovisoft" class="w-12 h-12 object-contain" />
                <h1 class="text-2xl font-bold bg-gradient-to-r from-red-800 to-red-600 bg-clip-text text-transparent">Bovisoft</h1>
            </div>
            <nav class="hidden md:flex gap-8 text-gray-700 font-medium">
                <a href="#inicio" class="hover:text-red-600 transition-colors">Inicio</a>
                <a href="#beneficios" class="hover:text-red-600 transition-colors">Beneficios</a>
                <a href="#caracteristicas" class="hover:text-red-600 transition-colors">Características</a>
                <a href="#contacto" class="hover:text-red-600 transition-colors">Contacto</a>
            </nav>
            <button type="button" data-open="login"
                class="px-6 py-2.5 bg-gradient-to-r from-red-800 to-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:from-red-900 hover:to-red-800 transition-all">
                Iniciar sesión
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
                <h2 class="text-5xl md:text-6xl font-extrabold leading-tight">
                    La plataforma <span class="text-red-200">inteligente</span> para la gestión ganadera
                </h2>
                <p class="text-xl text-red-50 leading-relaxed">
                    Bovisoft te permite administrar tu finca, animales, producción, ventas y reportes desde un solo lugar de manera eficiente y profesional.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <button type="button" data-open="register"
                        class="px-8 py-4 bg-white text-red-900 font-bold rounded-xl shadow-2xl hover:shadow-red-500/50 hover:scale-105 transition-all">
                        Comenzar ahora →
                    </button>
                    <a href="#beneficios" class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/20 transition-all">
                        Ver más
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
    <section id="beneficios" class="py-24 bg-white/95 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl shadow-2xl border-4 border-white/50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold mb-4">
                    Ventajas competitivas
                </span>
                <h3 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">¿Por qué usar Bovisoft?</h3>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
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
    <section id="caracteristicas" class="py-24 bg-white/95 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl shadow-2xl border-4 border-white/50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-2 bg-white text-red-800 rounded-full text-sm font-semibold mb-4 shadow-sm">
                    Funcionalidades completas
                </span>
                <h3 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Características principales</h3>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Todo lo que necesitas para gestionar tu operación ganadera
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="flex items-start group">
                        <span class="feature-icon">📋</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Registro de ganado</h5>
                            <p class="text-gray-600">Control detallado de cada animal con historial completo</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">💉</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Gestión de salud y vacunación</h5>
                            <p class="text-gray-600">Seguimiento de tratamientos y calendario de vacunas</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">🥛</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Control de producción lechera</h5>
                            <p class="text-gray-600">Monitoreo diario de producción por animal</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">💰</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Registro de ventas y compras</h5>
                            <p class="text-gray-600">Control financiero completo de tu operación</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">📊</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Dashboard intuitivo</h5>
                            <p class="text-gray-600">Visualiza toda tu información de un vistazo</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start group">
                        <span class="feature-icon">👥</span>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Roles y permisos</h5>
                            <p class="text-gray-600">Control de acceso para tu equipo de trabajo</p>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-red-400/20 to-purple-400/20 rounded-3xl blur-2xl"></div>
                    <img src="https://cdn.pixabay.com/photo/2019/07/19/09/35/cow-4345955_1280.jpg" 
                         class="relative rounded-3xl shadow-2xl border-4 border-white" 
                         alt="Ganado" />
                </div>
            </div>
        </div>
    </section>

    <!-- Separador -->
    <div class="section-divider my-8"></div>

    <!-- CONTACTO -->
    <section id="contacto" class="py-24 bg-white/95 backdrop-blur-sm mx-4 md:mx-8 rounded-3xl shadow-2xl border-4 border-white/50">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <div class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold mb-6">
                ¿Listo para comenzar?
            </div>
            <h3 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">Contáctanos</h3>
            <p class="text-xl text-gray-600 mb-10">
                ¿Tienes dudas? ¿Quieres una demo? Estamos listos para ayudarte a transformar tu gestión ganadera.
            </p>
            <a href="mailto:contacto@bovisoft.com" 
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
            <div class="flex items-center justify-center gap-2 mb-4">
                <img src="/images/logo.png" alt="Logo Bovisoft" class="w-8 h-8 object-contain" />
                <span class="font-bold text-lg">Bovisoft</span>
            </div>
            <p class="text-gray-400">© 2025 Bovisoft - Gestión Ganadera Inteligente</p>
            <p class="text-sm text-gray-500 mt-2">Desarrollado para ganaderos colombianos</p>
        </div>
    </footer>

<!-- OVERLAY AUTH (Login/Register) -->
<div id="authOverlay" class="fixed inset-0 z-[999] hidden" aria-hidden="true">

  <!-- Fondo -->
  <div id="authBackdrop"
       class="absolute inset-0 bg-black/70 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>

  <!-- Contenido centrado -->
  <div class="relative min-h-screen flex items-center justify-center p-4">
    <div id="authPanel"
         class="w-full max-w-md rounded-2xl border border-white/15
                bg-gradient-to-r from-red-700/30 to-black/30
                backdrop-blur-2xl shadow-2xl
                transform scale-95 translate-y-3 opacity-0
                transition-all duration-300">

      <div class="p-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 mb-5">
          <div class="flex items-center gap-3">
            <img src="/images/logo.png" class="h-10 w-10 object-contain" alt="Bovisoft">
            <div>
              <h3 class="text-white text-xl font-extrabold leading-tight">Bovisoft</h3>
              <p id="authSubtitle" class="text-white/70 text-sm">Accede a tu cuenta</p>
            </div>
          </div>

          <button id="authClose" class="text-white/80 hover:text-white text-2xl leading-none">&times;</button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-5">
          <button id="tabLogin"
                  class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold bg-white/10 text-white hover:bg-white/15">
            Login
          </button>
          <button id="tabRegister"
                  class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold bg-white/5 text-white/80 hover:bg-white/15">
            Registro
          </button>
        </div>

        <!-- Contenido -->
        <div class="mb-4">
    @include('auth.partials.errors')
</div>

<div id="panelLogin" class="block">
    @include('auth.partials.login-form')
</div>

<div id="panelRegister" class="hidden">
    @include('auth.partials.register-form')
</div>

      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const overlay = document.getElementById('authOverlay');
  const backdrop = document.getElementById('authBackdrop');
  const panel = document.getElementById('authPanel');
  const closeBtn = document.getElementById('authClose');

  const tabLogin = document.getElementById('tabLogin');
  const tabRegister = document.getElementById('tabRegister');
  const panelLogin = document.getElementById('panelLogin');
  const panelRegister = document.getElementById('panelRegister');
  const subtitle = document.getElementById('authSubtitle');

  function setTab(which) {
    const loginActive = which === 'login';
    panelLogin.classList.toggle('hidden', !loginActive);
    panelRegister.classList.toggle('hidden', loginActive);

    tabLogin.classList.toggle('bg-white/10', loginActive);
    tabLogin.classList.toggle('bg-white/5', !loginActive);
    tabLogin.classList.toggle('text-white', loginActive);
    tabLogin.classList.toggle('text-white/80', !loginActive);

    tabRegister.classList.toggle('bg-white/10', !loginActive);
    tabRegister.classList.toggle('bg-white/5', loginActive);
    tabRegister.classList.toggle('text-white', !loginActive);
    tabRegister.classList.toggle('text-white/80', loginActive);

    subtitle.textContent = loginActive ? 'Accede a tu cuenta' : 'Crea tu cuenta';
  }

  function openOverlay(which) {
    overlay.classList.remove('hidden');
    overlay.setAttribute('aria-hidden', 'false');
    setTab(which);

    requestAnimationFrame(() => {
      backdrop.classList.remove('opacity-0');
      backdrop.classList.add('opacity-100');

      panel.classList.remove('opacity-0', 'scale-95', 'translate-y-3');
      panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    });

    document.addEventListener('keydown', onEsc);
  }

  function closeOverlay() {
    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');

    panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
    panel.classList.add('opacity-0', 'scale-95', 'translate-y-3');

    document.removeEventListener('keydown', onEsc);

    setTimeout(() => {
      overlay.classList.add('hidden');
      overlay.setAttribute('aria-hidden', 'true');
    }, 280);
  }

  function onEsc(e) {
    if (e.key === 'Escape') closeOverlay();
  }

  // Abrir con data-open
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-open]');
    if (!btn) return;

    const which = btn.getAttribute('data-open');
    if (which === 'login' || which === 'register') {
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
})();
</script>

</body>
</html>
