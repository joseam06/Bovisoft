<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="/favicon.ico">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verifica tu correo - BoviSoft</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/vendor/inter/inter.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bovi-auth min-h-screen"
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

                <h1 class="text-xl font-bold text-white mb-3">Verifica tu correo</h1>
                <p class="text-sm text-white/80 mb-5">Enviamos un enlace a {{ auth()->user()->email }}. Confirma que la dirección es tuya para proteger el acceso a tu información.</p>
                @if(session('status')==='verification-link-sent')<p role="status" class="text-green-200 mb-4">Enviamos un nuevo enlace. Revisa también la carpeta de correo no deseado.</p>@endif
                @if($errors->any())<div role="alert" class="text-red-100 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <form action="{{ route('verification.send') }}" method="POST" class="mb-5">@csrf<button type="submit" class="w-full px-5 py-3 bg-red-700 text-white font-bold rounded-xl">Reenviar correo de verificación</button></form>
                <a href="{{ route('profile.edit') }}#seguridad" class="block text-white underline py-3">Corregir una dirección mal escrita o revisar mi cuenta</a>
                @if(auth()->user()->legacy_email_access)<a href="/dashboard" class="block text-white underline py-3">Continuar con mis registros durante la transición</a>@endif
                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="text-white underline py-3">Cerrar sesión</button></form>
            </div>
        </div>
    </div>
</body>
</html>
