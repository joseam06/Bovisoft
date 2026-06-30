@extends('layouts.dashboard')

@section('title', 'Mi Perfil')

@section('content')
<div class="max-w-4xl mx-auto animate-slide-up space-y-6">

    {{-- Encabezado --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-red-600 to-red-900 flex items-center justify-center shadow-lg overflow-hidden">
                @if($user->profile_photo_path)
                    <img src="{{ $user->foto_url }}" alt="Foto de perfil" class="w-full h-full object-cover">
                @else
                    <i class="fa-solid fa-user text-white text-2xl"></i>
                @endif
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Hola, bienvenido a tu perfil</h1>
                <p class="text-gray-500 text-sm">Gestiona tu información personal y seguridad de cuenta</p>
            </div>
        </div>
    </div>

    {{-- Mensajes de éxito --}}
    @if(session('success'))
        <div class="glass-effect border-4 border-green-400/50 rounded-2xl shadow-xl p-4 flex items-center space-x-3">
            <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
            <p class="text-green-700 font-semibold">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('success_password'))
        <div class="glass-effect border-4 border-green-400/50 rounded-2xl shadow-xl p-4 flex items-center space-x-3">
            <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
            <p class="text-green-700 font-semibold">{{ session('success_password') }}</p>
        </div>
    @endif

    {{-- SECCIÓN: Foto de Perfil --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center space-x-2">
            <i class="fa-solid fa-camera text-red-600"></i>
            <span>Foto de Perfil</span>
        </h2>
        <p class="text-gray-500 text-sm mb-6">Toma una foto con tu cámara o selecciona una desde tu dispositivo. Máximo 5 MB.</p>

        @error('photo')
            <div class="mb-4 p-3 bg-red-100 border-2 border-red-300 rounded-xl text-red-700 text-sm font-medium">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $message }}
            </div>
        @enderror

        <div class="flex flex-col sm:flex-row items-center gap-6">
            {{-- Preview de la foto --}}
            <div class="relative flex-shrink-0">
                <div id="photo-preview-container" class="w-32 h-32 rounded-2xl bg-gradient-to-br from-red-100 to-red-200 border-4 border-red-300 overflow-hidden flex items-center justify-center shadow-lg">
                    @if($user->profile_photo_path)
                        <img id="photo-preview" src="{{ $user->foto_url }}" alt="Foto de perfil" class="w-full h-full object-cover">
                    @else
                        <div id="photo-placeholder" class="flex flex-col items-center justify-center text-red-400">
                            <i class="fa-solid fa-user text-4xl mb-1"></i>
                            <span class="text-xs font-medium">Sin foto</span>
                        </div>
                        <img id="photo-preview" src="" alt="Vista previa" class="w-full h-full object-cover hidden">
                    @endif
                </div>
            </div>

            {{-- Controles --}}
            <div class="flex flex-col gap-3 w-full">
                {{-- Botones de origen --}}
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="abrirCamara()"
                        class="flex items-center space-x-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                        <i class="fa-solid fa-camera"></i>
                        <span>Tomar foto</span>
                    </button>
                    <button type="button" onclick="document.getElementById('file-input').click()"
                        class="flex items-center space-x-2 px-4 py-2.5 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 transition-all">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Buscar archivo</span>
                    </button>
                    @if($user->profile_photo_path)
                        <form method="POST" action="{{ route('profile.photo.delete') }}" onsubmit="return confirm('¿Eliminar foto de perfil?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center space-x-2 px-4 py-2.5 bg-red-100 border-2 border-red-300 text-red-700 font-semibold rounded-xl hover:bg-red-200 transition-all">
                                <i class="fa-solid fa-trash"></i>
                                <span>Eliminar</span>
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Input de archivo oculto --}}
                <input type="file" id="file-input" accept="image/*" class="hidden" onchange="procesarArchivo(event)">

                {{-- Info de tamaño --}}
                <p id="file-info" class="text-xs text-gray-500 hidden"></p>

                {{-- Formulario de subida (base64) --}}
                <form method="POST" action="{{ route('profile.photo') }}" id="form-foto">
                    @csrf
                    <input type="hidden" name="photo" id="photo-base64">
                    <button type="submit" id="btn-guardar-foto"
                        class="hidden items-center space-x-2 px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Guardar foto</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Modal de cámara --}}
        <div id="modal-camara" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-lg">
                <div class="bg-gradient-to-r from-red-600 to-red-800 p-4 flex items-center justify-between">
                    <h3 class="text-white font-bold text-lg flex items-center space-x-2">
                        <i class="fa-solid fa-camera"></i>
                        <span>Tomar foto</span>
                    </h3>
                    <button onclick="cerrarCamara()" class="text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    <video id="video-camara" autoplay playsinline class="w-full rounded-xl bg-black aspect-video object-cover"></video>
                    <canvas id="canvas-foto" class="hidden"></canvas>
                    <div id="camara-error" class="hidden p-3 bg-red-100 border-2 border-red-300 rounded-xl text-red-700 text-sm font-medium">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        <span id="camara-error-msg"></span>
                    </div>
                    <div class="flex gap-3 justify-center">
                        <button type="button" onclick="capturarFoto()"
                            class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                            <i class="fa-solid fa-camera-retro"></i>
                            <span>Capturar</span>
                        </button>
                        <button type="button" onclick="cerrarCamara()"
                            class="flex items-center space-x-2 px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all">
                            <i class="fa-solid fa-xmark"></i>
                            <span>Cancelar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIÓN: Información Personal --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center space-x-2">
            <i class="fa-solid fa-user-pen text-red-600"></i>
            <span>Información Personal</span>
        </h2>
        <p class="text-gray-500 text-sm mb-6">Actualiza tu nombre y correo electrónico.</p>

        @if($errors->has('name') || $errors->has('email'))
            <div class="mb-4 p-3 bg-red-100 border-2 border-red-300 rounded-xl text-red-700 text-sm font-medium space-y-1">
                @error('name') <p><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                @error('email') <p><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-user text-red-500 mr-1"></i> Nombre completo
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                    placeholder="Tu nombre completo">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-envelope text-red-500 mr-1"></i> Correo electrónico
                </label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                    placeholder="tu@correo.com">
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Guardar cambios</span>
                </button>
            </div>
        </form>
    </div>

    {{-- SECCIÓN: Cambiar Contraseña --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center space-x-2">
            <i class="fa-solid fa-lock text-red-600"></i>
            <span>Cambiar Contraseña</span>
        </h2>
        <p class="text-gray-500 text-sm mb-6">Asegúrate de usar una contraseña segura de al menos 8 caracteres.</p>

        @if($errors->has('current_password') || $errors->has('password'))
            <div class="mb-4 p-3 bg-red-100 border-2 border-red-300 rounded-xl text-red-700 text-sm font-medium space-y-1">
                @error('current_password') <p><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $message }}</p> @enderror
                @error('password') <p><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ $message }}</p> @enderror
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-key text-red-500 mr-1"></i> Contraseña actual
                </label>
                <input type="password" id="current_password" name="current_password"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                    placeholder="Tu contraseña actual">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-lock text-red-500 mr-1"></i> Nueva contraseña
                </label>
                <input type="password" id="password" name="password"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                    placeholder="Mínimo 8 caracteres">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fa-solid fa-lock text-red-500 mr-1"></i> Confirmar nueva contraseña
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                    placeholder="Repite la nueva contraseña">
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Actualizar contraseña</span>
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    let streamCamara = null;

    // ---- CÁMARA ----
    async function abrirCamara() {
        const modal = document.getElementById('modal-camara');
        const video = document.getElementById('video-camara');
        const errorDiv = document.getElementById('camara-error');
        const errorMsg = document.getElementById('camara-error-msg');

        errorDiv.classList.add('hidden');
        modal.classList.remove('hidden');

        try {
            streamCamara = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = streamCamara;
        } catch (err) {
            errorMsg.textContent = 'No se pudo acceder a la cámara. Verifica los permisos del navegador.';
            errorDiv.classList.remove('hidden');
            video.classList.add('hidden');
        }
    }

    function cerrarCamara() {
        const modal = document.getElementById('modal-camara');
        const video = document.getElementById('video-camara');
        modal.classList.add('hidden');
        video.classList.remove('hidden');
        if (streamCamara) {
            streamCamara.getTracks().forEach(track => track.stop());
            streamCamara = null;
        }
    }

    function capturarFoto() {
        const video  = document.getElementById('video-camara');
        const canvas = document.getElementById('canvas-foto');

        canvas.width  = video.videoWidth  || 640;
        canvas.height = video.videoHeight || 480;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        aplicarFotoPreview(dataUrl);
        cerrarCamara();
    }

    // ---- ARCHIVO ----
    function procesarArchivo(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen no puede superar los 5 MB.');
            event.target.value = '';
            return;
        }

        const info = document.getElementById('file-info');
        info.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        info.classList.remove('hidden');

        const reader = new FileReader();
        reader.onload = function(e) {
            aplicarFotoPreview(e.target.result);
        };
        reader.readAsDataURL(file);
    }

    // ---- PREVIEW COMPARTIDO ----
    function aplicarFotoPreview(dataUrl) {
        const preview     = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');
        const base64Input = document.getElementById('photo-base64');
        const btnGuardar  = document.getElementById('btn-guardar-foto');

        preview.src = dataUrl;
        preview.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');

        base64Input.value = dataUrl;
        btnGuardar.classList.remove('hidden');
        btnGuardar.classList.add('flex');
    }
</script>
@endsection