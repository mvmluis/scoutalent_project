{{-- resources/views/auth/pedir-acesso.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="min-vh-100 d-flex flex-column justify-content-start justify-content-lg-center">

        {{-- 🟥 LOGO CENTRADO --}}
        <div class="d-flex justify-content-center align-items-center mt-5 mb-4" style="min-height: 140px;">
            <img src="{{ asset('imagens/LOGO.png') }}"
                 alt="ScoutTalent"
                 style="max-width:260px; height:auto; display:block; margin:auto;">
        </div>

        <div class="container d-flex flex-column align-items-center mt-4">
            <div class="w-100" style="max-width:540px;">

                {{-- status (mensagens flash) --}}
                @if (session('status'))
                    <div class="alert alert-success text-center">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- FORM: Pedido de Acesso (sem password) --}}
                <form method="POST" action="{{ route('access.request.store') }}" class="text-start" novalidate>
                    @csrf

                    {{-- Nome --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold me-2" for="name">Nome:</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required
                               class="form-control custom-input @error('name') is-invalid @enderror"
                               placeholder="O seu nome completo">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold me-2" for="email">Email:</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                               class="form-control custom-input @error('email') is-invalid @enderror"
                               placeholder="o.seu@email.com">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- País (preview com bandeira à esquerda + select) --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold me-2" for="country">País:</label>
                        <div class="input-group country-group">
                            <span class="input-group-text bg-white border-2-red rounded-start-14 pe-2">
                                {{-- bandeira inicial --}}
                                <img id="flagPreview" src="{{ asset('imagens/portugal.png') }}" alt="PT" width="26" height="18"
                                     style="object-fit:cover;border:1px solid #eee;border-radius:2px;">
                            </span>

                            <select id="country" name="country"
                                    class="form-select custom-select-red @error('country') is-invalid @enderror"
                                    data-placeholder="Selecione o país">
                                @php $c = old('country','Portugal'); @endphp

                                <option value="Portugal" data-flag="{{ asset('imagens/portugal.png') }}" {{ $c=='Portugal'?'selected':'' }}>Portugal</option>
                                <option value="França"   data-flag="{{ asset('imagens/fr.svg') }}"     {{ $c=='França'?'selected':'' }}>França</option>
                                <option value="Alemanha" data-flag="{{ asset('imagens/de.svg') }}"     {{ $c=='Alemanha'?'selected':'' }}>Alemanha</option>
                                <option value="Itália"   data-flag="{{ asset('imagens/it.svg') }}"     {{ $c=='Itália'?'selected':'' }}>Itália</option>
                                <option value="Irlanda"  data-flag="{{ asset('imagens/ie.svg') }}"     {{ $c=='Irlanda'?'selected':'' }}>Irlanda</option>
                                <option value="Bélgica"  data-flag="{{ asset('imagens/be.svg') }}"     {{ $c=='Bélgica'?'selected':'' }}>Bélgica</option>
                                <option value="Países Baixos" data-flag="{{ asset('imagens/nl.svg') }}" {{ $c=='Países Baixos'?'selected':'' }}>Países Baixos</option>
                                <option value="Luxemburgo" data-flag="{{ asset('imagens/lu.svg') }}"   {{ $c=='Luxemburgo'?'selected':'' }}>Luxemburgo</option>
                                <option value="Suíça"    data-flag="{{ asset('imagens/ch.svg') }}"     {{ $c=='Suíça'?'selected':'' }}>Suíça</option>
                                <option value="Emirados Árabes Unidos" data-flag="{{ asset('imagens/ae.svg') }}" {{ $c=='Emirados Árabes Unidos'?'selected':'' }}>Emirados Árabes Unidos</option>
                            </select>

                            @error('country') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- termos --}}
                    <p class="text-center text-muted small mb-3">
                        Ao clicar em "<strong>Pedir Acesso</strong>" autoriza o envio deste pedido para a equipa responsável da <strong>Scoutalent</strong>.
                    </p>

                    {{-- botões --}}
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                        <a href="{{ route('login') }}" class="btn btn-outline-red btn-lg px-4">Voltar ao Login</a>
                        <button type="submit" class="btn btn-red btn-lg px-5">Pedir Acesso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script no próprio ficheiro --}}
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const sel = document.getElementById('country');
            const img = document.getElementById('flagPreview');

            function updateFlag() {
                const opt = sel.options[sel.selectedIndex];
                const flag = opt ? opt.getAttribute('data-flag') : null;
                if (flag) img.src = flag;
            }

            updateFlag(); // aplica logo ao carregar
            sel.addEventListener('change', updateFlag);
            sel.addEventListener('input', updateFlag);
        });
    </script>
@endsection

@push('styles')
    <style>
        body { background:#fff; }

        .custom-input{
            height:56px;
            border:2px solid #e10600;
            border-radius:14px;
            padding:0 16px;
            background:#fff;
            font-size:16px;
        }
        .custom-input:focus{
            border-color:#e10600;
            box-shadow:0 0 0 .2rem rgba(225,6,0,.12);
        }

        .custom-select-red{
            height:56px;
            border:2px solid #e10600;
            border-left:0;
            border-radius:0 14px 14px 0;
            background:#fff;
            padding:0 40px 0 12px;
        }
        .custom-select-red:focus{
            border-color:#e10600;
            box-shadow:0 0 0 .2rem rgba(225,6,0,.12);
        }

        .border-2-red{ border:2px solid #e10600; border-right:0; }
        .rounded-start-14{ border-radius:14px 0 0 14px !important; }

        .btn-red{
            background-color:#d60c0c;
            border-color:#d60c0c;
            color:#fff;
            border-radius:12px;
            height:56px;
            font-weight:700;
        }
        .btn-red:hover{ background:#b70a0a; border-color:#b70a0a; color:#fff; }

        .btn-outline-red{
            background:#fff;
            border:2px solid #e10600;
            color:#e10600;
            border-radius:12px;
            height:56px;
            font-weight:700;
        }
        .btn-outline-red:hover{
            background:#ffeaea;
            color:#c40808;
            border-color:#c40808;
        }

        .card, .card-header{ border:0; background:transparent; }
    </style>
@endpush
