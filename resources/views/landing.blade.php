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

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
    <link rel="manifest" href=" {{ asset('site.webmanifest') }}" />

</head>

<body class="bg-background-page text-dark-white mx-auto">

    <header class="fixed top-0 left-1/2 px-4 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
        <nav x-data="{ open: false }"
            class="mx-auto px-4 py-4 w-full bg-navbar-background/60 backdrop-blur-lg max-w-[1585px] shadow-xl border-navbar-stroke border-2 rounded-lg">
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
                    <a href="/app" class="font-nohemi font-normal">Sign in</a>
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
                <a href="#how-it-works" class="font-nohemi font-normal" @click="open = !open">How it works</a>
                <a href="#key-features" class="font-nohemi font-normal" @click="open = !open">Key features</a>
                <a href="#faq" class="font-nohemi font-normal" @click="open = !open">FAQ</a>
                <hr class="border-t border-navbar-stroke">
                <a href="#faq" class="font-nohemi font-normal">Sign in</a>
                <a href=""
                    class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center">Get
                    Started</a>
            </div>
        </nav>

    </header>

    <section
        class="w-full max-w-[1728px] mt-24 md:xs-32 md:mt-48 mx-auto flex-col bg-hero flex items-center justify-center py-8">
        <section class="items-center flex flex-col gap-8 justify-center h-[600px] w-max-[875px] mx-8">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-nohemi text-center max-w-[875px]">The music platform you
                <span class="text-primary custom-underline">need</span>
            </h1>
            <p class="font-nohemi text-2xl sm:tex   t-3xl md:text-4xl max-w-[953px] text-center text-gradient">
                Join the ultimate music
                experience —
                create, vote
                on tracks,
                and control the
                music together.</p>
            <div class="flex flex-row gap-4 items-center justify-center mt-8">
                <a href=""
                    class="hover:-translate-y-0.5 transition-transform duration-150 ease-in-out font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center text-lg md:text-2xl">Join
                    now</a>
                <a href="#key-features"
                    class="font-nohemi font-normal text-lg md:text-2xl flex items-center gap-2 hover:-translate-y-0.5 transition-transform duration-150 ease-in-out">View
                    features<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </section>
    </section>

    @php
        $steps = [
            [
                'step' => '1',
                'title' => 'Create a jam',
                'description' =>
                    'Easily create a new jam session with your Spotify Premium account. Share the unique session code and invite friends to join.',
            ],
            [
                'step' => '2',
                'title' => 'Add music & vote',
                'description' => 'Everyone can add songs via Spotify links and vote for their favorite tracks.',
            ],
            [
                'step' => '3',
                'title' => 'Listen & enjoy',
                'description' =>
                    'The owner or appointed co-DJ plays the music live. Watch votes in real-time, chat with emojis, and discover the best vibes together.',
            ],
        ];
    @endphp

    <section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col flex items-center justify-center mb-32 px-4"
        id="how-it-works">
        <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
            <h2 class="font-medium text-3xl md:text-4xl">How it works</h2>
            <p class="text-xl md:text-2xl font-nohemi">Trust us, it's as simple as pretending to like your friend's
                playlist.</p>
        </div>
        <div class="flex flex-col lg:grid lg:grid-cols-3 w-full gap-8">
            @foreach ($steps as $step)
                <div class="bg-card-background p-4 rounded-lg border-2 border-card-stroke">
                    <div class="flex flex-row gap-4 items-center mb-4">
                        <span
                            class="font-nohemi text-2xl md:text-3xl bg-primary rounded-full w-12 h-12 flex items-center justify-center text-white">{{ $step['step'] }}</span>
                        <h3 class="text-2xl md:text-3xl">{{ $step['title'] }}</h3>
                    </div>
                    <p class="text-xl font-normal">{{ $step['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>


    <section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col flex items-center justify-center mb-32 px-8"
        id="key-features">
        <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
            <h2 class="font-medium text-3xl md:text-4xl">Key Features</h2>
            <p class="text-xl md:text-2xl font-nohemi">A unique blend of creativity and innovation.</p>
        </div>
        <div class="swiper w-full swiper-no-gutters">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                <div class="swiper-slide">
                    <div class="p-4">
                        <div class="flex flex-row gap-4 items-center mb-6">
                            <span
                                class="font-nohemi text-2xl md:text-3xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-12">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                                </svg>
                            </span>
                            <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                        </div>
                        <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                            sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="p-4">
                        <div class="flex flex-row gap-4 items-stretch mb-6">
                            <span
                                class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-md w-16 h-auto flex items-center justify-center text-white">

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-12">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>

                            </span>
                            <div class="flex flex-col gap-2 flex-grow">
                                <h3 class="text-2xl md:text-3xl">Earning money</h3>
                                <span
                                    class="bg-label-background text-label-text w-fit px-4 py-1 rounded-xl text-sm font-bold">Coming
                                    soon</span>
                            </div>
                        </div>
                        <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                            sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="p-4 ">
                        <div class="flex flex-row gap-4 items-center mb-6">
                            <span
                                class="font-nohemi text-2xl md:text-3xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-12">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                                </svg>
                            </span>
                            <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                        </div>
                        <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                            sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="p-4 ">
                        <div class="flex flex-row gap-4 items-center mb-6">
                            <span
                                class="font-nohemi text-2xl md:text-3xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-12">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                                </svg>
                            </span>
                            <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                        </div>
                        <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                            sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
                    </div>
                </div>
            </div>

            {{-- <div class="swiper-button-prev z-50 transition-opacity ease-in-out"></div>
            <div class="swiper-button-next z-50 transition-opacity ease-in-out"></div> --}}

            <span class="custom-prev-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </span>

            <span class="custom-next-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>

            {{-- <div class="swiper-pagination"></div> --}}
        </div>
        <div class="lg:hidden flex flex-col gap-8">
            <div class="bg-card-background border-2 border-card-stroke rounded-md p-4">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-12">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                        </svg>
                    </span>
                    <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                </div>
                <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                    sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
            </div>
            <div class="bg-card-background border-2 border-card-stroke rounded-md p-4">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-12">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                        </svg>
                    </span>
                    <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                </div>
                <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                    sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
            </div>
            <div class="bg-card-background border-2 border-card-stroke rounded-md p-4">
                <div class="flex flex-row gap-4 items-center mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-md w-16 h-16 flex items-center justify-center text-white"><svg
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-12">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 13.5V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m12-3V3.75m0 9.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 3.75V16.5m-6-9V3.75m0 3.75a1.5 1.5 0 0 1 0 3m0-3a1.5 1.5 0 0 0 0 3m0 9.75V10.5" />
                        </svg>
                    </span>
                    <h3 class="text-2xl md:text-3xl">Advanced Playlists</h3>
                </div>
                <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                    sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
            </div>
            <div class="bg-card-background border-2 border-card-stroke rounded-md p-4">
                <div class="flex flex-row gap-4 items-stretch mb-6">
                    <span
                        class="font-nohemi text-3xl xl:text-4xl bg-primary rounded-md w-16 h-auto flex items-center justify-center text-white">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-12">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>

                    </span>
                    <div class="flex flex-col gap-2 flex-grow">
                        <h3 class="text-2xl md:text-3xl">Earning money</h3>
                        <span
                            class="bg-label-background text-label-text w-fit px-4 py-1 rounded-xl text-sm font-bold">Coming
                            soon</span>
                    </div>
                </div>
                <p class="text-xl font-normal">Non quo aperiam repellendus quas est est. Eos aut dolore aut ut
                    sit nesciunt. Ex tempora quia. Sit nobis consequatur dolores incidunt.</p>
            </div>
        </div>

    </section>

    <section class="w-full max-w-[1728px] mt-32 md:mt-48 mx-auto flex-col flex items-center justify-center mb-32 px-4"
        id="faq">
        <div class="flex flex-col gap-10 items-center justify-center mb-16 text-center">
            <h2 class="font-medium text-3xl md:text-4xl">Frequently Asked Questions (FAQ)</h2>
            <p class="text-xl md:text-2xl font-nohemi">Got doubts and questions? Let’s break it down.</p>
        </div>
        @php
            $faqs = [
                [
                    'question' => 'Do I need Spotify premium to use this platform?',
                    'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
                ],
                [
                    'question' => 'How does voting work during a jam?',
                    'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
                ],
                [
                    'question' => 'Is this web application free?',
                    'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
                ],
                [
                    'question' => 'Can I customize the look and theme of my jam session?',
                    'answer' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
                ],
            ];
        @endphp
        <div class="w-full">
            @foreach ($faqs as $faq)
                <div x-data="{ open: false }"
                    class="border-b rounded-lg border-card-stroke py-4 transition-all duration-500">
                    <button @click="open = !open"
                        class="flex items-center justify-between w-full p-4 text-left font-medium cursor-pointer">
                        <p class="font-nohemi text-xl font-light">{{ $faq['question'] }}</p>
                        <svg :class="{ 'rotate-180': open }" class="w-6 h-6 transition-transform duration-300"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="relative overflow-hidden transition-all max-h-0 duration-400" x-ref="container"
                        x-bind:style="open ? 'max-height: ' + $refs.container.scrollHeight + 'px' : ''">
                        <div class="px-4 pb-4">
                            <p class="text-l font-normal">{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="bg-footer-background w-full flex flex-col justify-between max-h-[277px] h-full py-8 md:px-16 px-8">
        <div class="max-w-[380px]">
            <div class="flex flex-row gap-4 items-center mb-4">
                <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Logo" class="w-12 h-12">
                <h1 class="text-5xl font-medium font-nohemi">Tunofy</h1>
            </div>
            <p class="font-light text-xl">Tunofy enhances your spotify experience by combining music and voting.</p>
        </div>
        <div class="flex flex-row justify-between mt-auto text-sm text-[#666666] items-center flex-wrap gap-4">
            <div class="flex flex-row gap-4 mr-8">
                <a href="#">Terms of use</a>
                <a href="#">Privacy Policy</a>
                <a href="#">GDPR</a>
            </div>
            <div class="flex flex-row items-center">
                <p class="text-sm font-light">© 2025 Tunofy - All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>

</html>
