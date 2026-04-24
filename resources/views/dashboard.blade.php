@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="text-center mb-4">
                    <img src="{{ asset('imagens/LOGO.png') }}" alt="Scout7Talent" class="mb-4 mb-lg-5" style="max-width:260px;height:auto">
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">
                        Dashboard
                    </div>

                    <div class="card-body">
                        <p class="mb-4">
                            Olá, <strong>{{ Auth::user()->name ?? Auth::user()->email }}</strong>! Estás autenticado.
                        </p>

                        <div class="d-flex gap-2">
                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                Página inicial
                            </a>

                            {{-- Botão de Logout (tem de ser POST) --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mt-3 text-center">
                    Esta é uma página base “normal”, sem o vídeo. Personaliza à vontade.
                </p>
            </div>
        </div>
    </div>
@endsection
