@extends('layouts.dashboard')

@section('title', 'Mi Perfil')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    {{-- ENCABEZADO --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <div class="flex items-center gap-4">
            @if ($user->foto_perfil)
                <img
                    src="{{ asset('storage/' . $user->foto_perfil) }}"
                    alt="{{ $user->name }}"
                    class="size-20 rounded-full object-cover ring-4 ring-red-200"
                />
            @else
                <div class="size-20 rounded-full bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center ring-4 ring-red-200">
                    <span class="text-white text-3xl font-bold">
                        {{ strtoupper(substr($user
                        ->name, 0, 1)) }}
                    </span>
                </div>
            @endif

            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h1>
                <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                @if ($user->telefono)
                    <p class="text-gray-500 text-sm mt-0.5">
                        <i class="fas fa-phone-alt me-1 text-red-500"></i>
                        {{ $user->telefono }}
                    </p>
                @endif
                <p class="text-xs text-gray-400 mt-1">
                    Miembro desde {{ $user->created_at->format('d/m/Y') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ALERTAS DE EXITO --}}
    @if (session('exito_informacion'))
        <div class="flex items-center gap-3 bg-green-50 border-2 border-green-300 rounded-xl px-4 py-3 text-green-700 text-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            {{ session('exito_informacion') }}
        </div>
    @endif

    @if (session('exito_foto'))
        <div class="flex items-center gap-3 bg-green-50 border-2 border-green-300 rounded-xl px-4 py-3 text-green-700 text-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            {{ session('exito_foto') }}
        </div>
    @endif

    @if (session('exito_contrasena'))
        <div class="flex items-center gap-3 bg-green-50 border-2 border-green-300 rounded-xl px-4 py-3 text-green-700 text-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            {{ session('exito_contrasena') }}
        </div>
    @endif

    {{-- SECCION: FOTO DE PERFIL --}}
    <div id="configuracion" class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4">
            <h2 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="fas fa-camera"></i>
                Foto de Perfil
            </h2>
            <p class="text-red-200 text-sm mt-0.5">Sube una imagen para personalizar tu cuenta</p>
        </div>

        <div class="p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                {{-- Preview --}}
                <div class="shrink-0">
                    @if ($user->foto_perfil)
                        <img
                            src="{{ asset('storage/' . $user->foto_perfil) }}"
                            alt="{{ $user->name }}"
                            class="size-24 rounded-full object-cover ring-4 ring-red-200"
                            id="preview-foto"
                        />
                    @else
                        <div class="size-24 rounded-full bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center ring-4 ring-red-200" id="preview-placeholder">
                            <span class="text-white text-4xl font-bold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </div>
                        <img src="" alt="" class="size-24 rounded-full object-cover ring-4 ring-red-200 hidden" id="preview-foto" />
                    @endif
                </div>

                {{-- Formulario foto --}}
                <div class="flex-1 w-full">
                    <form method="POST" action="{{ route('profile.foto') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Seleccionar imagen
                            </label>
                            <input
                                type="file"
                                name="foto_perfil"
                                id="foto_perfil"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                class="block w-full text-sm text-gray-600
                                       file:mr-4 file:py-2 file:px-4
                                       file:rounded-xl file:border-0
                                       file:text-sm file:font-medium
                                       file:bg-red-600 file:text-white
                                       hover:file:bg-red-700
                                       file:cursor-pointer cursor-pointer
                                       bg-white border-2 border-gray-300 rounded-xl
                                       focus:outline-none focus:border-red-500"
                            />
                            @error('foto_perfil')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">JPEG, PNG, JPG o WEBP. Maximo 2 MB.</p>
                        </div>

                        <div class="flex items-center gap-3 flex-wrap">
                            <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl px-5 py-2.5 text-sm hover:from-red-700 hover:to-red-800 transition">
                                <i class="fas fa-upload me-1"></i>
                                Subir Foto
                            </button>

                            @if ($user->foto_perfil)
                                <form method="POST" action="{{ route('profile.foto.eliminar') }}" onsubmit="return confirm('¿Eliminar tu foto de perfil?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-100 border-2 border-red-300 text-red-700 font-bold rounded-xl px-5 py-2 text-sm hover:bg-red-200 transition">
                                        <i class="fas fa-trash me-1"></i>
                                        Eliminar Foto
                                    </button>
                                </form>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCION: INFORMACION PERSONAL --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4">
            <h2 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="fas fa-user-edit"></i>
                Informacion Personal
            </h2>
            <p class="text-red-200 text-sm mt-0.5">Actualiza tu nombre, correo y numero de contacto</p>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('profile.informacion') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Nombre completo --}}
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre Completo <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                                   focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition
                                   @error('name') border-red-500 @enderror"
                            placeholder="Tu nombre completo"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Correo electronico --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Correo Electronico <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                                   focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition
                                   @error('email') border-red-500 @enderror"
                            placeholder="correo@ejemplo.com"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefono --}}
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
                            Telefono
                        </label>
                        <input
                            type="text"
                            name="telefono"
                            id="telefono"
                            value="{{ old('telefono', $user->telefono) }}"
                            class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                                   focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition
                                   @error('telefono') border-red-500 @enderror"
                            placeholder="+57 300 000 0000"
                        />
                        @error('telefono')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl px-6 py-2.5 text-sm hover:from-red-700 hover:to-red-800 transition">
                        <i class="fas fa-save me-1"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>  

    {{-- SECCION: CAMBIAR CONTRASENA --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4">
            <h2 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="fas fa-lock"></i>
                Cambiar Contrasena
            </h2>
            <p class="text-red-200 text-sm mt-0.5">Asegurate de usar una contrasena larga y segura</p>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('profile.contrasena') }}" class="space-y-4" autocomplete="off">
                @csrf
                @method('PATCH')

                <div>
                    <label for="contrasena_actual" class="block text-sm font-medium text-gray-700 mb-1">
                        Contrasena Actual <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="password"
                        name="contrasena_actual"
                        id="contrasena_actual"
                        class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                               focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition
                               @error('contrasena_actual') border-red-500 @enderror"
                        placeholder="Ingresa tu contrasena actual"
                    />
                    @error('contrasena_actual')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="contrasena" class="block text-sm font-medium text-gray-700 mb-1">
                            Nueva Contrasena <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            name="contrasena"
                            id="contrasena"
                            class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                                   focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition
                                   @error('contrasena') border-red-500 @enderror"
                            placeholder="Minimo 8 caracteres"
                        />
                        @error('contrasena')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contrasena_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar Contrasena <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="password"
                            name="contrasena_confirmation"
                            id="contrasena_confirmation"
                            class="w-full bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 px-4 py-2.5
                                   focus:ring-2 focus:ring-red-500 focus:border-red-500 focus:outline-none transition"
                            placeholder="Repite la nueva contrasena"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl px-6 py-2.5 text-sm hover:from-red-700 hover:to-red-800 transition">
                        <i class="fas fa-key me-1"></i>
                        Actualizar Contrasena
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.getElementById('foto_perfil')?.addEventListener('change', function (e) {
    const archivo = e.target.files[0];
    if (!archivo) return;

    const reader = new FileReader();
    reader.onload = function (event) {
        const preview = document.getElementById('preview-foto');
        const placeholder = document.getElementById('preview-placeholder');

        preview.src = event.target.result;
        preview.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(archivo);
});
</script>

@endsection