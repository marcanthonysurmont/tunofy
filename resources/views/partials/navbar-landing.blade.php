<header class="fixed top-0 left-1/2 px-4 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
    <nav x-data="{ open: false }"
        class="mx-auto px-4 py-4 w-full bg-navbar-background/60 backdrop-blur-lg max-w-[1728px] shadow-xl border-navbar-stroke border-2 rounded-lg">
        <div class="flex items-center justify-between">
            <a class="flex flex-row items-center space-x-4" href="/">
                <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Tunofy Logo"
                    class="md:w-10 md:h-10 h-8 w-8">
                <p class="text-2xl md:text-3xl font-medium font-headings">Tunofy</p>
            </a>

            <!-- Desktop menu -->
            <ul class="hidden lg:flex pl-8 space-x-6 text-xl">
                <li class="custom-item-hover"><a href="#how-it-works" class="font-headings font-normal">How it
                        works</a></li>
                <li class="custom-item-hover"><a href="#key-features" class="font-headings font-normal">Key
                        features</a></li>
                <li class="custom-item-hover"><a href="#faq" class="font-headings font-normal">FAQ</a></li>
            </ul>

            <div class="hidden lg:flex flex-row items-center gap-4 text-xl">
                @auth
                    <a href="{{ route('app') }}"
                        class="font-headings font-normal bg-primary rounded-md px-4 pb-2 pt-2.5 flex items-center justify-center custom-item-hover">Go
                        to App</a>
                @else
                    <div class="flex items-center flex-row bg-primary rounded-md px-4">
                        <img src="{{ asset('images/logos/spotify-logo-white.png') }}" class="mr-2 size-6">
                        <a href="{{ route('login') }}" class="font-headings font-normal custom-item-hover pb-2 pt-2.5">Sign
                            In</a>
                    </div>
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
            <a href="#how-it-works" class="font-headings font-normal" @click="open = !open">How it works</a>
            <a href="#key-features" class="font-headings font-normal" @click="open = !open">Key features</a>
            <a href="#faq" class="font-headings font-normal" @click="open = !open">FAQ</a>
            <hr class="border-t border-navbar-stroke">
            @auth
                <a href="{{ route('app') }}"
                    class="font-headings font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center custom-item-hover">Go
                    to App</a>
            @else
                <a href="{{ route('login') }}"
                    class="font-headings font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center custom-item-hover">
                    <img src="{{ asset('images/logos/spotify-logo-white.png') }}" class="mr-2 size-6">Sign
                    In</a>
            @endauth
        </div>
    </nav>

</header>
