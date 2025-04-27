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
</head>

<body class="bg-background-page text-dark-white mx-auto">

    <header class="fixed top-0 left-1/2 px-4 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
        <nav
            class="mx-auto px-4 py-4 w-full bg-navbar-background max-w-[1585px] border-navbar-stroke border-2 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex flex-row items-center space-x-4">
                    {{-- <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Logo" class="w-10 h-10"> --}}
                    {{-- <a href="#" class="text-2xl font-semibold">Tunofy</a> --}}
                    <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Logo" class="w-10 h-10">
                    {{-- <a href="#" class="text-2xl font-semibold">Tunofy</a> --}}
                    <a href="#" class="text-3xl font-medium font-nohemi">Tunofy</a>

                    <ul class="pl-8 flex space-x-6 text-xl">
                        <li><a href="how-it-works" class="font-nohemi font-normal">How it works</a></li>
                        <li><a href="key-features" class="font-nohemi font-normal">Key features</a></li>
                        <li><a href="faq" class="font-nohemi font-normal">FAQ</a></li>
                    </ul>
                </div>
                <div class="flex flex-row items-center gap-4">
                    <a href="/app" class="font-nohemi font-normal">Key features</a></li>
                    <a href="/app"
                        class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center">Get
                        Started</a>
                </div>
            </div>
        </nav>
    </header>

    <section class="w-full max-w-[1728px] mt-48 mx-auto flex-col bg-hero flex items-center justify-center">
        <section class="items-center flex flex-col gap-8 justify-center h-[600px] w-max-[875px]">
            <h1 class="text-8xl font-nohemi text-center max-w-[875px]">The music platform you <span
                    class="text-primary custom-underline">need</span></h1>
            <p class="font-nohemi text-4xl max-w-[953px] text-center">Join the ultimate music experience — create, vote
                on tracks,
                and control the
                music together.</p>
            <div class="flex flex-row gap-4 items-center justify-center mt-8">
                <a href="/app"
                    class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center text-2xl">Get
                    Started</a>
                <a href="" class="font-nohemi font-normal text-2xl flex items-center gap-2">View features<svg
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </section>
    </section>

</body>

</html>
