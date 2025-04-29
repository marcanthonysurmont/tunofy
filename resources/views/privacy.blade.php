<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Privacy Policy | Tunofy</title>

    <meta name="description"
        content="Read Tunofy's Privacy Policy to learn how we collect, use, and protect your personal data. We value your privacy and ensure transparency.">

    <!-- Open Graph -->
    <meta property="og:title" content="Privacy Policy | Tunofy">
    <meta property="og:description"
        content="Learn how Tunofy collects and uses your data in compliance with privacy regulations.">
    <meta property="og:url" content="https://tunofy.com/privacy">

    <!-- Twitter Card -->
    <meta name="twitter:title" content="Privacy Policy | Tunofy">
    <meta name="twitter:description"
        content="Understand how Tunofy protects your personal data and respects your privacy.">

    <!-- Canonical -->
    <link rel="canonical" href="https://tunofy.com/privacy">

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
            <h1 class="font-medium text-4xl md:text-5xl">Privacy Policy</h1>
            <p class="text-l md:text-xl font-nohemi text-zinc-400">Last updated: Apr 29, 2025</p>
        </div>
        <hr class="mb-8 text-zinc-700 rounded-lg">
        <div class="mb-8 copy">
            <p>Welcome to Tunofy! (“we”, “us” or “Tunofy”). We offer tools to help you monitor, analyze, and enhance
                your performance or content across platforms. To provide our services—whether you’re browsing
                https://tunofy.com/ (the “Site”) or using our dashboard and features (the “Platform”)—we need to collect
                and process some of your personal information. This Privacy Policy (the “Privacy Policy”) explains how
                we handle that data.</p>

            <p> By using our services, you agree to this Privacy Policy, which should be read in combination with our
                Terms of Use. If any term is undefined here, it follows the meaning in our Terms.</p>

            <p> We may update this Privacy Policy as legal requirements or our practices change. The latest version will
                always be available on our Site. If we make significant changes, we’ll do our best to notify you in
                advance. What counts as a “significant change” is at our discretion. Continued use of our services after
                updates means you agree to the revised policy. If not, please stop using Tunofy.</p>

            <p> This Privacy Policy only applies to personal data processed by Tunofy. It does not cover how third-party
                platforms or tools handle your data.</p>

            <p> We don’t knowingly collect data from anyone under 18. If you're under 18, please don’t use our services
                or share any information with us. If we become aware that we've collected data from someone under 18
                without verified parental consent, we’ll delete it immediately.</p>
        </div>
        <div class="copy mb-8">
            <h2 class="font-medium text-2xl md:text-3xl mb-2">Who processes your data</h1>
                <p>Your data is being processed by our company Tunofy.</p>

                <p>To learn more about data management or if you have any other questions, please contact us at
                    <strong>hello@tunofy.com</strong>.
                </p>
        </div>

        <div class="copy mb-8">
            <h2 class="font-medium text-2xl md:text-3xl mb-2">What data is processed</h2>
            <p>We may collect the following types of information about you:</p>
            <h3 class="text-md md:text-lg">Personal Identifiable Information</h3>
            <p>
                Through our Services, we may collect and process information that can be used to identify or contact
                you as an individual known as your personal identifiable information (“PII”), including but not
                limited to:
            </p>

            <ul class="list-disc pl-8 mb-8">
                <li>first and second names</li>
                <li>email address</li>
                <li>professional experience</li>
                <li>and other PII provided by you voluntarily when you use our Services</li>
            </ul>

            <h3 class="text-md md:text-lg">Technical Information</h3>
            <p>
                We and/or our authorised external service providers may automatically collect technical data when you
                visit or interact with our Services. Technical data may include, in particular:
            </p>
            <ul class="list-disc pl-8 mb-8">
                <li>the URL of the site visited before using our Service</li>
                <li>the time and date of user visits</li>
                <li>IP address</li>
                <li>the browser name and type</li>
                <li>the type of computer or device accessing our Service</li>
                <li>time spent on the Service and other similar technical information</li>
            </ul>
            <p>In a limited number of cases it is possible to use technical data and identify you as an individual, thus
                making them PII, however, we never use technical data to identify you as an individual.</p>
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
