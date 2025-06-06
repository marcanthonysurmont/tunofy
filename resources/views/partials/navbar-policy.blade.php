<header class="fixed top-0 left-1/2 px-8 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
    <nav x-data="{ open: false }"
        class="mx-auto px-4 py-4 w-full bg-navbar-background/60 backdrop-blur-lg max-w-[1728px] shadow-xl border-navbar-stroke border-2 rounded-lg">
        <div class="flex items-center justify-between">
            <a class="flex flex-row items-center space-x-3" href="/">
                <img src="{{ asset('images/logos/tunofy-logo-white.png') }}" alt="Tunofy Logo"
                    class="md:w-10 md:h-10 h-8 w-8">
                <p class="text-2xl md:text-3xl font-medium font-headings header-font-middle-alignment">Tunofy</p>
            </a>

            <div class="hidden lg:flex flex-row items-center gap-4 text-xl">
                @auth
                    <a href="{{ route('app') }}"
                        class="font-headings font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center custom-item-hover">Go
                        to App</a>
                @else
                    <a class="flex flex-row justify-center custom-item-hover items-center space-x-3 bg-primary rounded-md px-4 py-2"
                        href="{{ route('login') }}">
                        <img src="{{ asset('images/logos/spotify-logo-white.png') }}" alt="Spotify Logo"
                            class="size-6 mr-2">
                        <p class="font-medium font-headings header-font-middle-alignment">Sign in</p>
                    </a>
                @endauth
            </div>

            <div class="flex flex-row items-center lg:hidden">
                <button @click="open = !open"
                    class="lg:hidden text-white m-2 relative flex items-center justify-center">
                    <div class="relative w-6 h-6">
                        <!-- Line 1 -->
                        <span class="absolute h-0.5 w-6 bg-current rounded-full transition-all duration-300"
                            :class="open ? 'rotate-45 top-3' : 'top-1'"></span>
                        <!-- Line 2 -->
                        <span class="absolute h-0.5 w-6 bg-current rounded-full top-3 transition-all duration-300"
                            :class="open ? 'opacity-0' : 'opacity-100'"></span>
                        <!-- Line 3 -->
                        <span class="absolute h-0.5 w-6 bg-current rounded-full transition-all duration-300"
                            :class="open ? '-rotate-45 top-3' : 'top-5'"></span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="open" class="lg:hidden mt-4 flex flex-col space-y-4"
            x-transition:enter="transition-all duration-300 ease-in-out" x-transition:enter-start="max-h-0 opacity-0"
            x-transition:enter-end="max-h-[500px] opacity-100"
            x-transition:leave="transition-all duration-300 ease-in-out"
            x-transition:leave-start="max-h-[500px] opacity-100" x-transition:leave-end="max-h-0 opacity-0">
            <hr class="border-t border-navbar-stroke">
            @auth
                <a href="{{ route('app') }}"
                    class="font-headings font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center custom-item-hover">Go
                    to App</a>
            @else
                <a class="flex flex-row justify-center custom-item-hover items-center space-x-3 bg-primary rounded-md px-4 py-2"
                    href="{{ route('login') }}">
                    <img src="{{ asset('images/logos/spotify-logo-white.png') }}" alt="Spotify Logo" class="size-6 mr-2">
                    <p class="font-medium font-headings header-font-middle-alignment">Sign in</p>
                </a>
            @endauth
        </div>
    </nav>

</header>
