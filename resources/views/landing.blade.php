<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tunofy - The music platform you need</title>
    @vite(['resources/js/landingpage.js'])
    {{-- fonts for headers --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<body class="bg-background-page text-dark-white mx-auto">

    <header class="fixed top-0 left-1/2 px-4 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
        <nav x-data="{ open: false }"
            class="mx-auto px-4 py-4 w-full bg-navbar-background/30 backdrop-blur-lg max-w-[1585px] border-navbar-stroke border-2 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex flex-row items-center space-x-4">
                    <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Logo" class="w-10 h-10">
                    <a href="#" class="text-3xl font-medium font-nohemi">Tunofy</a>
                </div>

                <!-- Desktop menu -->
                <ul class="hidden lg:flex pl-8 space-x-6 text-xl">
                    <li><a href="#how-it-works" class="font-nohemi font-normal">How it works</a></li>
                    <li><a href="#key-features" class="font-nohemi font-normal">Key features</a></li>
                    <li><a href="#faq" class="font-nohemi font-normal">FAQ</a></li>
                </ul>

                <div class="hidden lg:flex flex-row items-center gap-4 text-xl">
                    <a href="/app" class="font-nohemi font-normal">Key features</a>
                    <a href="/app"
                        class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center">Get
                        Started</a>
                </div>

                <!-- Mobile hamburger button -->
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

            <!-- Mobile menu -->
            <div x-show="open" class="lg:hidden mt-4 flex flex-col space-y-4"
                x-transition:enter="transition-all duration-300 ease-in-out"
                x-transition:enter-start="max-h-0 opacity-0" x-transition:enter-end="max-h-[500px] opacity-100"
                x-transition:leave="transition-all duration-300 ease-in-out"
                x-transition:leave-start="max-h-[500px] opacity-100" x-transition:leave-end="max-h-0 opacity-0">
                <a href="#how-it-works" class="font-nohemi font-normal">How it works</a>
                <a href="#key-features" class="font-nohemi font-normal">Key features</a>
                <a href="#faq" class="font-nohemi font-normal">FAQ</a>
                <a href=""
                    class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center">Get
                    Started</a>
            </div>
        </nav>

    </header>

    <section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col bg-hero flex items-center justify-center">
        <section class="items-center flex flex-col gap-8 justify-center h-[600px] w-max-[875px] mx-8">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-nohemi text-center max-w-[875px]">The music platform you
                <span class="text-primary custom-underline">need</span>
            </h1>
            <p class="font-nohemi text-2xl sm:text-3xl md:text-4xl max-w-[953px] text-center">Join the ultimate music
                experience —
                create, vote
                on tracks,
                and control the
                music together.</p>
            <div class="flex flex-row gap-4 items-center justify-center mt-8">
                <a href=""
                    class="hover:-translate-y-0.5 transition-transform duration-150 ease-in-out font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center text-lg md:text-2xl">Join
                    now</a>
                <a href=""
                    class="font-nohemi font-normal text-lg md:text-2xl flex items-center gap-2 hover:-translate-y-0.5 transition-transform duration-150 ease-in-out">View
                    features<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </section>
    </section>
    <section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col flex items-center justify-center mb-32 px-8">
        <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
            <h2 class="font-medium text-5xl md:text-6xl">How it works.</h2>
            <p class="md:text-3xl font-nohemi">Trust us, it's as simple as pretending to like your friend's
                playlist.</p>
        </div>
        <div class="flex flex-col lg:grid lg:grid-cols-3 w-full gap-8">
            <div class="bg-card-background p-4 rounded-lg border-2 border-card-stroke">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-full w-16 h-16 flex items-center justify-center text-white">1</span>
                    <h3 class="text-3xl xl:text-4xl">Create a jam</h3>
                </div>
                <p class="text-2xl font-normal">Easily create a new jam session with your Spotify Premium account.
                    Share the unique
                    session code and
                    invite friends to join.</p>
            </div>
            <div class="bg-card-background p-4 rounded-lg border-2 border-card-stroke">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-full w-16 h-16 flex items-center justify-center text-white">2</span>
                    <h3 class="text-3xl xl:text-4xl">Add music & vote</h3>
                </div>
                <p class="text-2xl font-normal">Everyone can add songs via Spotify links and vote for their favorite
                    tracks.</p>
            </div>
            <div class="bg-card-background p-4 rounded-lg border-2 border-card-stroke">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-full w-16 h-16 flex items-center justify-center text-white">3</span>
                    <h3 class="text-3xl xl:text-4xl">Listen & enjoy</h3>
                </div>
                <p class="text-2xl font-normal">The owner or appointed co-DJ plays the music live. Watch votes in
                    real-time, chat with emojis, and discover the best vibes together.</p>
            </div>
        </div>
    </section>

</body>

</html>
