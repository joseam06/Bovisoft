<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recuperar Contraseña - Bovisoft</title>
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

                {{-- Subtítulo --}}
                <div class="mb-6">
                    <h2 class="text-lg font-bold text-white mb-1">Recuperar contraseña</h2>
                    <p class="text-sm text-white/60">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>
                </div>

                {{-- Status --}}
                @session('status')
                    <div class="bg-green-500/20 border border-green-500/40 rounded-xl px-4 py-3 text-sm text-green-300 mb-6">
                        {{ $value }}
                    </div>
                @endsession

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="bg-red-500/20 border-2 border-red-500/50 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3 text-white">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <div class="flex-1">
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm text-red-100">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Formulario --}}
                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-white/90 mb-1">Correo electrónico</label>
                        <input id="email" name="email" type="email" required autofocus autocomplete="username"
                               value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 rounded-xl bg-white/10 text-white border border-white/20
                                      focus:border-red-400 focus:ring-1 focus:ring-red-400 outline-none transition" />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('login') }}" class="text-sm text-white/70 hover:text-white underline transition">
                            ← Volver al login
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 bg-gradient-to-r from-red-700 to-red-800 text-white font-bold
                                       rounded-xl shadow-lg hover:from-red-800 hover:to-red-900 hover:scale-105 transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar enlace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>