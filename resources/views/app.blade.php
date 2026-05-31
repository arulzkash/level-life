<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        @php
            $isHomepage = request()->is('/');
            $homepageTitle = 'Level Life — The Player’s Handbook';
            $homepageDescription = 'Turn your life into an RPG. Build consistency, track quests, habits, goals, XP, streaks, and personal progress with Level Life.';
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $isHomepage ? $homepageTitle : config('app.name', 'Level Life') }}</title>
        @if ($isHomepage)
            <meta name="description" content="{{ $homepageDescription }}">
            <meta property="og:title" content="{{ $homepageTitle }}">
            <meta property="og:description" content="{{ $homepageDescription }}">
            <meta property="og:type" content="website">
            <meta property="og:url" content="{{ url('/') }}">
            <link rel="canonical" href="{{ url('/') }}">
        @endif

        <!-- App metadata -->
        <meta name="application-name" content="Level Life">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Level Life">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="msapplication-TileColor" content="#0f172a">
        <meta name="theme-color" content="#0f172a">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>

    <body class="font-sans antialiased bg-slate-900 text-gray-100">
        @inertia
    </body>

</html>
