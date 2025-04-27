<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tunofy - Create.Vote.Listen</title>
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
            class="mx-auto px-4 py-4 w-full bg-navbar-background max-w-[1585px] border-navbar-stroke border-2 rounded-lg">
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

                <div class="hidden lg:flex flex-row items-center gap-4">
                    <a href="/app" class="font-nohemi font-normal">Key features</a>
                    <a href="/app"
                        class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center">Get
                        Started</a>
                </div>

                <!-- Mobile hamburger button -->
                <button @click="open = !open" class="lg:hidden text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
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
                    class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center text-lg md:text-2xl">Join
                    now</a>
                <a href="" class="font-nohemi font-normal text-lg md:text-2xl flex items-center gap-2">View
                    features<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </section>
    </section>

</body>

</html>
