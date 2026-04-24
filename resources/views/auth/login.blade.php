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

            {{-- FLASH MESSAGES --}}
            <div class="w-100" style="max-width:540px;">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif
            </div>

            {{-- form --}}
            <div class="w-100" style="max-width:540px;">
                <form method="POST" action="{{ route('login') }}" class="text-start">
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

                    {{-- password --}}
                    <div class="mb-3">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password:"
                            class="form-control custom-input @error('password') is-invalid @enderror"
                        >
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ações principais --}}
                    <div class="d-flex gap-3 align-items-center mb-3">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="btn btn-lg btn-outline-red flex-grow-1">
                                Recuperar Senha
                            </a>
                        @endif

                        <button type="submit" class="btn btn-lg btn-red flex-grow-1">
                            Aceder
                        </button>
                    </div>

                    {{-- texto + pedir acesso --}}
                    <p class="text-center text-muted small mb-3">
                        Se ainda não fazes parte desta equipa podes pedir um acesso. Para isso basta clicares no
                        botão seguinte, preencher o formulário e aguardar que os teus dados sejam validados.
                    </p>

                    <div class="d-flex justify-content-center">
                       <a href="#" class="btn btn-red btn-lg px-4" style="pointer-events: none; cursor: not-allowed; opacity: 0.6;">
                        Pedir Acesso
                       </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        body { background:#fff; }

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

        .btn-red{
            background-color:#d60c0c;
            border-color:#d60c0c;
            color:#fff;
            border-radius: 12px;
            height:56px;
            font-weight:600;
        }
        .btn-red:hover{ background-color:#b70a0a; border-color:#b70a0a; color:#fff; }

        .btn-outline-red{
            background:#fff;
            border: 2px solid #e10600;
            color:#e10600;
            border-radius:12px;
            height:56px;
            font-weight:600;
        }
        .btn-outline-red:hover{ background:#ffeaea; color:#c40808; border-color:#c40808; }

        .card, .card-header{ border:0; background:transparent; }
        .text-muted{ color:#808080 !important; }
    </style>
@endpush

@push('scripts')
    <script>
        // Auto-fecha as flash messages ao fim de 4s
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.querySelector('.alert');
            if (el && window.bootstrap) {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
                    bsAlert.close();
                }, 4000);
            }
        });
    </script>
@endpush
