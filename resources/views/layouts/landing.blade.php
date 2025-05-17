<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>@yield('title', 'Tunofy - The music platform you need')</title>

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'Tunofy lets you create collaborative Spotify mix sessions.')">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', 'Tunofy - Host the Ultimate Spotify mix Session')">
    <meta property="og:description" content="@yield('og_description', 'Start Spotify mix sessions with Tunofy...')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="@yield('og_url', url('/'))">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'Tunofy - Your Music, Your Party, Your Rules')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Collaborate on playlists, vote for tracks...')">
    @hasSection('twitter_image')
        <meta name="twitter:image" content="@yield('twitter_image')">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap"
        rel="stylesheet">

    {{-- Vite --}}
    @vite(['resources/js/landingpage.js'])

    {{-- AlpineJS --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Favicons --}}
    <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
    <link rel="shortcut icon" href="/favicons/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Tunofy" />

    {{-- Manifest --}}
    <link rel="manifest" href="/favicons/site.webmanifest" />


    {{-- cookie stuff --}}
    <script>
        function acceptCookies(acceptAll) {
            document.cookie = "cookies_accepted=" + (acceptAll ? "all" : "essential") + "; path=/; max-age=" + 60 * 60 *
                24 * 365;
            document.getElementById('cookie-consent').remove();
        }

        // Hide if already accepted
        window.addEventListener('DOMContentLoaded', () => {
            if (document.cookie.includes("cookies_accepted")) {
                const el = document.getElementById('cookie-consent');
                if (el) el.remove();
            }
        });
    </script>


    @stack('head')
</head>

<body class="bg-background-page text-dark-white mx-auto" data-page="@yield('page_id', 'home')">
    @yield('content')
    @include('partials.cookie-banner')
</body>

</html>
