<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-mark class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">

                <!-- Teams Dropdown -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="ms-3 relative">
                        <x-dropdown align="right" width="60">
                            <x-slot name="trigger">
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                        {{ Auth::user()->currentTeam->name }}
                                        <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                        </svg>
                                    </button>
                                </span>
                            </x-slot>

                            <x-slot name="content">
                                <div class="w-60">
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ __('Manage Team') }}
                                    </div>
                                    <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                        {{ __('Team Settings') }}
                                    </x-dropdown-link>
                                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                        <x-dropdown-link href="{{ route('teams.create') }}">
                                            {{ __('Create New Team') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @if (Auth::user()->allTeams()->count() > 1)
                                        <div class="border-t border-gray-200"></div>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Switch Teams') }}
                                        </div>
                                        @foreach (Auth::user()->allTeams() as $team)
                                            <x-switchable-team :team="$team" />
                                        @endforeach
                                    @endif
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endif

                <!-- Dropdown de Usuario -->
                <div class="ms-3 relative" x-data="{ abierto: false }">
                    <!-- Trigger -->
                    <button
                        @click="abierto = !abierto"
                        @keydown.escape.window="abierto = false"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-gray-600 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out"
                    >
                        @if (Auth::user()->foto_perfil)
                            <img
                                src="{{ asset('storage/' . Auth::user()->foto_perfil) }}"
                                alt="{{ Auth::user()->name }}"
                                class="size-8 rounded-full object-cover ring-2 ring-red-600"
                            />
                        @else
                            <div class="size-8 rounded-full bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center ring-2 ring-red-200">
                                <span class="text-white text-xs font-bold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif

                        <span class="text-sm font-medium text-gray-700 max-w-32 truncate">
                            {{ Auth::user()->name }}
                        </span>

                        <svg
                            class="size-4 text-gray-400 transition-transform duration-150"
                            :class="{ 'rotate-180': abierto }"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <!-- Panel -->
                    <div
                        x-show="abierto"
                        @click.outside="abierto = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-xl shadow-lg ring-1 ring-black/5 z-50 overflow-hidden"
                        style="display: none;"
                    >
                        <!-- Encabezado -->
                        <div class="px-4 py-3 bg-gradient-to-r from-red-600 to-red-800">
                            <div class="flex items-center gap-3">
                                @if (Auth::user()->foto_perfil)
                                    <img
                                        src="{{ asset('storage/' . Auth::user()->foto_perfil) }}"
                                        alt="{{ Auth::user()->name }}"
                                        class="size-10 rounded-full object-cover ring-2 ring-white/50"
                                    />
                                @else
                                    <div class="size-10 rounded-full bg-white/20 flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-red-200 truncate">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones -->
                        <div class="py-1">
                            <a
                                href="{{ route('profile.show') }}"
                                @click="abierto = false"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors duration-100"
                            >
                                <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                Mi Perfil
                            </a>

                            <a
                                href="{{ route('profile.show') }}#configuracion"
                                @click="abierto = false"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors duration-100"
                            >
                                <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Configuracion de la Cuenta
                            </a>

                            <div class="border-t border-gray-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <button
                                    type="submit"
                                    @click.prevent="$root.submit()"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-100"
                                >
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                    Cerrar Sesion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                @if (Auth::user()->foto_perfil)
                    <div class="shrink-0 me-3">
                        <img class="size-10 rounded-full object-cover ring-2 ring-red-400" src="{{ asset('storage/' . Auth::user()->foto_perfil) }}" alt="{{ Auth::user()->name }}" />
                    </div>
                @else
                    <div class="shrink-0 me-3">
                        <div class="size-10 rounded-full bg-gradient-to-br from-red-600 to-red-800 flex items-center justify-center">
                            <span class="text-white text-sm font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                @endif
                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    Mi Perfil
                </x-responsive-nav-link>

                <x-responsive-nav-link href="{{ route('profile.show') }}#configuracion" :active="false">
                    Configuracion de la Cuenta
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                        Cerrar Sesion
                    </x-responsive-nav-link>
                </form>

                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                    <div class="border-t border-gray-200"></div>
                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Team') }}
                    </div>
                    <x-responsive-nav-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}" :active="request()->routeIs('teams.show')">
                        {{ __('Team Settings') }}
                    </x-responsive-nav-link>
                    @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                        <x-responsive-nav-link href="{{ route('teams.create') }}" :active="request()->routeIs('teams.create')">
                            {{ __('Create New Team') }}
                        </x-responsive-nav-link>
                    @endcan
                    @if (Auth::user()->allTeams()->count() > 1)
                        <div class="border-t border-gray-200"></div>
                        <div class="block px-4 py-2 text-xs text-gray-400">
                            {{ __('Switch Teams') }}
                        </div>
                        @foreach (Auth::user()->allTeams() as $team)
                            <x-switchable-team :team="$team" component="responsive-nav-link" />
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</nav>