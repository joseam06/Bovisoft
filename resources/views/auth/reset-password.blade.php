<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Restablecer Contraseña - Bovisoft</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen text-gray-800"
      style="background: linear-gradient(135deg, #7c2d12 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #450a0a 100%); background-attachment: fixed;">

    {{-- Fondo con blur igual al backdrop del overlay --}}
    <div class="fixed inset-0 bg-black/80 backdrop-blur-md"></div>

    {{-- Panel centrado --}}
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

                {{-- Subtítulo --}}
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-white mb-1">Restablecer contraseña</h2>
                    <p class="text-sm text-white/60">Ingresa tu nueva contraseña para continuar</p>
                </div>

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="bg-red-500/20 border-2 border-red-500/50 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3 text-white">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <div class="flex-1">
                                <p class="font-bold mb-1">Corrige los siguientes errores</p>
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-100">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Formulario --}}
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Correo --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-white/90 mb-1">
                            Correo electrónico
                        </label>
                        <input
                            id="email" name="email" type="email"
                            required autofocus autocomplete="username"
                            value="{{ old('email', $request->email) }}"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white
                                   border border-white/20 focus:border-red-400 focus:ring-1
                                   focus:ring-red-400 outline-none transition"
                        />
                    </div>

                    {{-- Nueva contraseña --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-white/90 mb-1">
                            Nueva contraseña
                        </label>
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="new-password"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white
                                   border border-white/20 focus:border-red-400 focus:ring-1
                                   focus:ring-red-400 outline-none transition"
                        />
                    </div>

                    {{-- Confirmar contraseña --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-white/90 mb-1">
                            Confirmar contraseña
                        </label>
                        <input
                            id="password_confirmation" name="password_confirmation" type="password"
                            required autocomplete="new-password"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white
                                   border border-white/20 focus:border-red-400 focus:ring-1
                                   focus:ring-red-400 outline-none transition"
                        />
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ url('/') }}"
                           class="text-sm text-white/70 hover:text-white underline transition">
                            ← Volver al inicio
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 bg-gradient-to-r from-red-700 to-red-800 text-white
                                       font-bold rounded-xl shadow-lg hover:from-red-800 hover:to-red-900
                                       hover:scale-105 transition-all">
                            <i class="fas fa-lock mr-2"></i>Restablecer
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>
</html>