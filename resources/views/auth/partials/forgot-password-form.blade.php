<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf

    <p class="text-sm text-white/70 leading-relaxed">
        ¿Olvidaste tu contraseña? No hay problema. Ingresa tu correo y te enviaremos un enlace para restablecerla.
    </p>

    @if (session('status'))
        <div class="bg-green-500/20 border border-green-500/40 rounded-lg px-4 py-3 text-sm text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <div>
        <x-label for="forgot_email" value="Correo electrónico" class="text-white/90" />
        <x-input
            id="forgot_email"
            name="email"
            type="email"
            required
            autofocus
            autocomplete="username"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
            value="{{ old('email') }}"
        />
    </div>

    <div class="flex items-center justify-between pt-2">
        <button type="button" data-open="login"
                class="text-sm text-red-600 hover:text-white underline">
            ← Volver al login
        </button>
        <button type="submit"
                class="px-5 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-white font-semibold shadow">
            ENVIAR ENLACE
        </button>
    </div>
</form>