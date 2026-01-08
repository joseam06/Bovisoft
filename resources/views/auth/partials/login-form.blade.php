<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <x-label for="email" value="Email" class="text-white/90" />
        <x-input
            id="email"
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

    <div>
        <x-label for="password" value="Password" class="text-white/90" />
        <x-input
            id="password"
            name="password"
            type="password"
            required
            autocomplete="current-password"
            class="mt-1 block w-full bg-white/10 text-white placeholder:text-white/60 border-white/20
                   focus:border-red-400 focus:ring-red-400"
        />
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center">
            <input
                type="checkbox"
                name="remember"
                class="rounded border-white/20 bg-white/10 text-red-600 focus:ring-red-500"
                {{ old('remember') ? 'checked' : '' }}
            >
            <span class="ms-2 text-sm text-white/80">Remember me</span>
        </label>

        @if (Route::has('password.request'))
            <a class="text-sm text-white/80 hover:text-white underline" href="{{ route('password.request') }}">
                Forgot your password?
            </a>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" data-open="register" class="text-sm text-white/80 hover:text-white underline">
            Crear cuenta
        </button>

        <button class="px-5 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-white font-semibold shadow">
            LOG IN
        </button>
    </div>
</form>
