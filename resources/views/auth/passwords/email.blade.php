@extends('layouts.app')

@section('content')
    <div class="min-vh-100 d-flex flex-column justify-content-start justify-content-lg-center">

        {{-- topo com idioma --}}
        <div class="container">
            <div class="d-flex justify-content-end align-items-center pt-3">
                <span class="me-2 fw-semibold text-danger">PT</span>
                <img src="{{ asset('imagens/portugal.png') }}" alt="PT" width="28" height="18">
            </div>
        </div>

        <div class="container d-flex flex-column align-items-center mt-4 mt-lg-0">

            {{-- logo --}}
            <img src="{{ asset('imagens/LOGO.png') }}" alt="Scout7Talent" class="mb-4 mb-lg-5" style="max-width:260px;height:auto">

            {{-- status (sucesso) --}}
            @if (session('status'))
                <div class="alert alert-success w-100 text-center" style="max-width:540px;">
                    {{ session('status') }}
                </div>
            @endif

            {{-- form --}}
            <div class="w-100" style="max-width:540px;">
                <form method="POST" action="{{ route('password.email') }}" class="text-start">
                    @csrf

                    {{-- email --}}
                    <div class="mb-3">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            autofocus
                            placeholder="Email:"
                            class="form-control custom-input @error('email') is-invalid @enderror"
                        >
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ações --}}
                    <div class="d-flex gap-3 align-items-center">
                        <a href="{{ route('login') }}" class="btn btn-lg btn-outline-red flex-grow-1">
                            Voltar ao Login
                        </a>

                        <button type="submit" class="btn btn-lg btn-red flex-grow-1">
                            Recuperar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* fundo clean */
        body { background:#fff; }

        /* inputs com borda vermelha arredondada (igual ao mock) */
        .custom-input{
            height: 56px;
            border: 2px solid #e10600;
            border-radius: 14px;
            padding: 0 16px;
            font-size: 16px;
            background:#fff;
        }
        .custom-input::placeholder{ color:#7a7a7a; }
        .custom-input:focus{
            border-color:#e10600;
            box-shadow: 0 0 0 .2rem rgba(225,6,0,.12);
        }

        /* botões vermelhos */
        .btn-red{
            background-color:#d60c0c;
            border-color:#d60c0c;
            color:#fff;
            border-radius:12px;
            height:56px;
            font-weight:600;
        }
        .btn-red:hover{
            background-color:#b70a0a;
            border-color:#b70a0a;
            color:#fff;
        }

        /* botão contornado */
        .btn-outline-red{
            background:#fff;
            border:2px solid #e10600;
            color:#e10600;
            border-radius:12px;
            height:56px;
            font-weight:600;
        }
        .btn-outline-red:hover{
            background:#ffeaea;
            color:#c40808;
            border-color:#c40808;
        }

        /* remover estilos de card padrão se existirem no layout */
        .card, .card-header{ border:0; background:transparent; }
    </style>
@endpush
