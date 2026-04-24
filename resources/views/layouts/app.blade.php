<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'scoutalent')</title>

    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagens/novologo.png') }}">
    @vite(['resources/css/app.css','resources/js/app.js'])
    {{-- IMPORTANTE para receber o CSS do @push('styles') --}}
    @stack('styles')
</head>
<body>
<div id="app">
    @auth
        <nav class="navbar navbar-light bg-white shadow-sm">
            <div class="container">
                <div class="ms-auto">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="py-4">
        @yield('content')
    </main>
</div>
</body>
</html>
