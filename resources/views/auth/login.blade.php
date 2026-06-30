<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión - Bovisoft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen"
      style="background: linear-gradient(135deg, #7c2d12 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #450a0a 100%); background-attachment: fixed;">

    <div class="fixed inset-0 bg-black/80 backdrop-blur-md"></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl border-2 border-white/20
                    bg-gradient-to-br from-red-800/40 via-black/60 to-gray-900/40
                    backdrop-blur-2xl shadow-2xl">
            <div class="p-8">

                {{-- Header --}}
                <div class="flex items-center gap-3 mb-6">
                    <img src="/images/logored.png" alt="Logo Bovisoft" class="w-12 h-12 object-contain" />
                    <div class="flex flex-col">
                        <h1 class="text-2xl font-extrabold bg-gradient-to-r from-red-800 to-red-600 bg-clip-text text-transparent">Bovisoft</h1>
                        <p class="text-xs text-gray-400 font-medium">Gestión Ganadera</p>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-3 mb-6 bg-white/5 p-1.5 rounded-xl">
                    <a href="{{ route('login') }}"
                       class="flex-1 px-4 py-3 rounded-lg text-sm font-bold text-center transition-all
                              bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex-1 px-4 py-3 rounded-lg text-sm font-bold text-center
                              bg-transparent text-white/70 hover:text-white transition-all">
                        <i class="fas fa-user-plus mr-2"></i>Registro
                    </a>
                </div>

                <p class="text-sm text-white/70 text-center mb-6">Accede a tu cuenta</p>

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="bg-red-500/20 border-2 border-red-500/50 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3 text-white">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <div class="flex-1">
                                <p class="font-bold mb-1">Credenciales incorrectas</p>
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-100">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @session('status')
                    <div class="bg-green-500/20 border border-green-500/40 rounded-lg px-4 py-3 text-sm text-green-300 mb-6">
                        {{ $value }}
                    </div>
                @endsession

                {{-- Formulario --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-white/90 mb-1">Correo electrónico</label>
                        <input id="email" name="email" type="email" required autofocus autocomplete="username"
                               value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white border border-white/20
                                      focus:border-red-400 focus:ring-1 focus:ring-red-400 outline-none transition" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-white/90 mb-1">Contraseña</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white border border-white/20
                                      focus:border-red-400 focus:ring-1 focus:ring-red-400 outline-none transition" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/10 text-red-600 focus:ring-red-500" {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-sm text-white/80">Recordar datos</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-white/70 hover:text-white underline transition">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('register') }}" class="text-sm text-white/70 hover:text-white underline transition">
                            Crear cuenta
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 bg-gradient-to-r from-red-700 to-red-800 text-white font-bold
                                       rounded-xl shadow-lg hover:from-red-800 hover:to-red-900 hover:scale-105 transition-all">
                            INICIAR SESIÓN
                        </button>
                    </div>
                </form>
                </form>

                {{-- Separador Google --}}
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
        </div>
    </div>
</body>
</html>
            </div>
        </div>
    </div>
</body>
</html>