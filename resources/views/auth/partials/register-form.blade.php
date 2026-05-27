<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <x-label for="name" value="Nombre" class="text-white/90" />
        <x-input
            id="name"
            name="name"
            type="text"
            required
            autofocus
            autocomplete="name"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
            value="{{ old('name') }}"
        />
    </div>

    <div>
        <x-label for="email" value="Correo electrónico" class="text-white/90" />
        <x-input
            id="email"
            name="email"
            type="email"
            required
            autocomplete="username"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
            value="{{ old('email') }}"
        />
    </div>

    <div>
        <x-label for="password" value="Contraseña" class="text-white/90" />
        <x-input
            id="password"
            name="password"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
        />
    </div>

    <div>
        <x-label for="password_confirmation" value="Confirmar contraseña" class="text-white/90" />
        <x-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
        />
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" data-open="login" class="text-sm text-white/80 hover:text-white underline">
            Ya tengo cuenta
        </button>

        <button class="px-5 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-white font-semibold shadow">
            REGISTRARSE
        </button>
    </div>
</form>
