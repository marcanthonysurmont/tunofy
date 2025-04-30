<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Data Processing Agreement | Tunofy</title>
    <!-- SEO Meta -->
    <meta name="description"
        content="Read Tunofy's Data Processing Agreement to understand how we comply with GDPR and process personal data securely and transparently.">

    <!-- Open Graph -->
    <meta property="og:title" content="Data Processing Agreement | Tunofy">
    <meta property="og:description" content="Details on how Tunofy handles personal data under GDPR.">
    <meta property="og:url" content="https://tunofy.com/dpa">
    {{-- <meta property="og:image" content="https://yourdomain.com/path-to-image.jpg"> --}}

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Data Processing Agreement | Tunofy">
    <meta name="twitter:description" content="Learn how Tunofy processes data under GDPR compliance.">

    <link rel="canonical" href="https://tunofy.com/dpa">

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

<body class="bg-background-page text-dark-white mx-auto" data-page="gdpr">

    <header class="fixed top-0 left-1/2 px-4 pt-8 translate-x-[-50%] w-full flex justify-center z-50">
        <nav x-data="{ open: false }"
            class="mx-auto px-4 py-4 w-full bg-navbar-background/60 backdrop-blur-lg max-w-[1728px] shadow-xl border-navbar-stroke border-2 rounded-lg">
            <div class="flex items-center justify-between">
                <a class="flex flex-row items-center space-x-4" href="/">
                    <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Tunofy Logo"
                        class="md:w-10 md:h-10 h-8 w-8">
                    <p class="text-2xl md:text-3xl font-medium font-nohemi">Tunofy</p>
                </a>

                <div class="hidden lg:flex flex-row items-center gap-4 text-xl">
                    <a href="/app" class="font-nohemi font-normal custom-item-hover">Sign in</a>
                    <a href="/app"
                        class="font-nohemi font-normal bg-primary rounded-md px-4 py-2 flex items-center justify-center custom-item-hover">Get
                        Started</a>
                </div>

                <div class="flex flex-row items-center lg:hidden">
                    <a href="/app"
                        class="font-nohemi font-normal bg-primary rounded-md px-3 text-sm py-2 flex items-center justify-center custom-item-hover">Get
                        Started</a>
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
                x-transition:enter="transition-all duration-300 ease-in-out"
                x-transition:enter-start="max-h-0 opacity-0" x-transition:enter-end="max-h-[500px] opacity-100"
                x-transition:leave="transition-all duration-300 ease-in-out"
                x-transition:leave-start="max-h-[500px] opacity-100" x-transition:leave-end="max-h-0 opacity-0">
                <hr class="border-t border-navbar-stroke">
                <a href="#faq" class="font-nohemi font-normal">Sign in</a>
            </div>
        </nav>

    </header>

    <section class="w-full max-w-[1728px] mt-44 md:mt-48 mx-auto flex-col flex mb-32 px-4">
        <div class="flex flex-col gap-2 mb-8 text-left">
            <h1 class="font-medium text-4xl md:text-5xl">Data Processing Agreement (WIP)</h1>
            <p class="text-l md:text-xl font-nohemi text-zinc-400">Last updated: Apr 29, 2025</p>
        </div>
        <hr class="mb-8 text-zinc-700 rounded-lg">
        <div class="mb-16">
            <p>This Data Processing Agreement ("DPA") is entered into by the Tunofy customer identified on the
                applicable Tunofy ordering or registration document ("Customer") and Tunofy, the company providing
                the
                platform and services. It governs the processing of personal data that the Customer uploads or
                otherwise
                provides to Tunofy in connection with the use of the Tunofy platform, as well as any personal data
                that
                Tunofy uploads or provides to the Customer in connection with the services.</p>
        </div>
        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">1. Definitions</h1>
            <p>"Account Data" means Personal Data that relates to Customer’s relationship with Tunofy,
                including access to Customer’s account and billing information, identity verification, maintaining or
                improving
                performance of the Services, providing support, investigating and preventing system abuse, or fulfilling
                legal
                obligations.</p>

            <p>"Applicable Data Protection Legislation" refers to laws and regulations applicable to Tunofy's
                processing of personal data under the Agreement, including (a) the GDPR, (b) the GDPR as saved into
                United Kingdom law by virtue of section 3 of the United Kingdom's European Union (Withdrawal) Act 2019
                ("UK GDPR") and the Data Protection Act 2018 (together, "UK Laws"), (c) the Swiss Federal Data
                Protection Act and its implementing regulations ("Swiss DPA"), and (d) CCPA, in each case, as may be
                amended, superseded or replaced.</p>

            <p>"Customer Personal Data" means Personal Data Tunofy processes as a Processor on behalf of
                Customer.</p>

            <p>"CCPA" means the California Consumer Privacy Act of 2018 including regulations adopted in following
                years, including the California Privacy Rights Act of 2020.</p>
        </div>

        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">2. Nature of Data Processing</h1>
            <p>Tunofy processes Customer Personal Data solely for providing its services, including access to
                Customer’s account and billing information, identity verification, maintaining or improving performance,
                providing support, investigating and preventing abuse, and fulfilling legal obligations.</p>
        </div>

        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">3. Compliance with Laws</h1>
            <p>The parties shall each comply with their respective obligations under all Applicable Data Protection
                Legislation.</p>
        </div>
        <div class="copy mb-8">
            <h1 class="font-medium text-2xl md:text-3xl mb-2">4. Customer Obligations</h1>
            <p>Customer agrees to:</p>
            <ol class="list-decimal pl-5">
                <li>Provide instructions to Tunofy and determine the purposes and general means of Tunofy’s processing
                    of Customer Personal Data in accordance with the DPA;</li>
                <li>Comply with its protection, security, and other obligations with respect to Customer Personal Data
                    prescribed by Applicable Data Protection Legislation for data controllers by:
                    <ul class="list-inside list-disc pl-5">
                        <li>Establishing and maintaining a procedure for the exercise of the rights of the individuals
                            whose Customer Personal Data are processed on behalf of Customer;</li>
                        <li>Processing only data that has been lawfully and validly collected and ensuring that such
                            data will be relevant and proportionate to the respective uses;</li>
                        <li>Ensuring compliance with the provisions of this DPA by its personnel or by any third-party
                            accessing or using Customer Personal Data on its behalf.</li>
                    </ul>
                </li>
            </ol>
        </div>



    </section>


    <footer class="bg-footer-background w-full flex flex-col justify-between max-h-[277px] h-full py-8 md:px-16 px-8">
        <div class="max-w-[380px]">
            <div class="flex flex-row gap-4 items-center mb-4">
                <img src="{{ asset('images/logos/tunofy-logo-small.png') }}" alt="Tunofy Logo" class="w-12 h-12">
                <h1 class="text-5xl font-medium font-nohemi">Tunofy</h1>
            </div>
            <p class="font-light text-xl">Tunofy enhances your spotify experience by combining music and voting.</p>
        </div>
        <div class="flex flex-row justify-between mt-auto text-sm text-[#666666] items-center flex-wrap gap-4">
            <div class="flex flex-row gap-4 mr-8">
                <a href="/terms">Terms of use</a>
                <a href="/privacy">Privacy Policy</a>
                <a href="/dpa">GDPR</a>
            </div>
            <div class="flex flex-row items-center">
                <p class="text-sm font-light">© 2025 Tunofy - All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
