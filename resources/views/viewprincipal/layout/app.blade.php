<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ScoutTalent Dashboard</title>

    <!-- Ícone e Fontes -->
    <link rel="icon" type="image/png" href="{{ asset('imagens/novologo.png') }}">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">

    <!-- Nucleo e Material Dashboard -->
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <link id="pagestyle" href="{{ asset('assets/css/material-dashboard.css?v=3.1.0') }}" rel="stylesheet" />

    @yield('styles')

    <style>
        body {
            background-color: #f5f6fa;
            overflow-x: hidden;
        }

        /* 🔹 Garantir que o main não é coberto pelo sidebar */
        main.main-content {
            background: #f7f8fa;
            min-height: 100vh;
            padding: 1.5rem;
        }

        /* 🔹 Sidebar em coluna flex */
        .d-flex {
            align-items: flex-start;
        }

        /* 🔹 Ajuste em ecrãs pequenos */
        @media (max-width: 992px) {
            .d-flex {
                flex-direction: column;
            }

            aside {
                width: 100% !important;
                min-width: unset !important;
            }

            main.main-content {
                margin-left: 0 !important;
                padding-top: 1rem;
            }
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-200">

<div class="d-flex">
    {{-- 🟥 Sidebar (menu lateral) --}}
    <aside style="flex:0 0 260px; min-width:260px;">
        @include('viewprincipal.layout.sidebar')
    </aside>

    {{-- 🟦 Conteúdo principal --}}
    <main class="flex-grow-1 ms-3 main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        @include('viewprincipal.layout.header')
        @yield('main-content')
    </main>
</div>

<!-- Core JS -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/material-dashboard.min.js?v=3.1.0') }}"></script>

@stack('custom-scripts')
</body>
</html>
