@extends('reports.layout.app')

@section('main-content')
    <div class="container my-5">

        {{-- 🟥 Cabeçalho principal --}}
        <div class="text-center mb-5 pb-3 border-bottom border-2 border-danger">
            <h1 class="fw-bold text-danger mb-1">
                <i class="fas fa-clipboard-list me-2"></i> Relatórios dos Scouts
            </h1>
            <p class="text-muted mb-0">
                Total de <span class="fw-semibold text-danger">{{ $reports->count() }}</span> relatórios registados
            </p>
        </div>

        {{-- ⚠️ Caso sem relatórios --}}
        @if($reports->isEmpty())
            <div class="alert alert-light text-center border border-danger-subtle shadow-sm py-4">
                <i class="fas fa-info-circle me-2 text-danger"></i>
                <span class="text-muted fw-semibold">Ainda não existem relatórios registados.</span>
            </div>
        @else

            {{-- 🏟️ Grelha de relatórios --}}
            <div class="row g-4">
                @foreach($reports as $r)
                    @php
                        $rent = $r->scoutalent_rentabilidade ?? '—';
                        $pot = $r->scoutalent_potencial ?? '—';
                        $steps = ['1','1+','2','2+','3','3+','4','4+','5','5+'];
                    @endphp

                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-0 rounded-4 h-100 overflow-hidden hover-shadow transition">

                            {{-- 🔺 Topo --}}
                            <div class="bg-danger text-white px-3 py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">{{ $r->player->name ?? '—' }}</span>
                                <small class="opacity-75">
                                    {{ $r->report_date ? $r->report_date->format('d/m/Y') : '—' }}
                                </small>
                            </div>

                            {{-- 🏃 Corpo --}}
                            <div class="card-body p-4">

                                {{-- Foto + Dados curtos --}}
                                <div class="d-flex align-items-center mb-3">
                                    <img src="{{ $r->player->photo ?? $scoutPhoto }}"
                                         alt="Foto do jogador"
                                         class="rounded-circle border border-2 border-danger-subtle me-3"
                                         width="70" height="70" style="object-fit: cover;">
                                    <div>
                                        <p class="mb-0 small text-muted lh-sm">
                                            <strong>Equipa:</strong> {{ $r->player->team_name ?? '—' }}<br>
                                            <strong>Posição:</strong> {{ $r->role ?? '—' }}<br>
                                            <strong>Idade:</strong> {{ $r->player->age ?? '—' }} anos
                                        </p>
                                    </div>
                                </div>

                                {{-- Dados estatísticos --}}
                                <div class="small text-muted mb-3">
                                    <p class="mb-1"><strong>🌍 Nacionalidade:</strong> {{ $r->player->nationality ?? '—' }}</p>
                                    <p class="mb-1"><strong>📏 Altura:</strong> {{ $r->player->height ?? '—' }} |
                                        <strong>⚖️ Peso:</strong> {{ $r->player->weight ?? '—' }}</p>
                                    <p class="mb-1"><strong>⭐ Rating:</strong>
                                        <span class="fw-semibold text-danger">{{ $r->player->rating ?? '—' }}</span></p>
                                    <p class="mb-0"><strong>⚽ Golos:</strong> {{ $r->player->goals ?? 0 }} |
                                        <strong>🟨 Amarelos:</strong> {{ $r->player->yellow_cards ?? 0 }} |
                                        <strong>🟥 Vermelhos:</strong> {{ $r->player->red_cards ?? 0 }}</p>
                                </div>

                                {{-- 🔴 Barras de Classificação --}}
                                <div class="mt-4">
                                    <p class="small fw-semibold text-muted mb-1">Classificação Actual:</p>
                                    <div class="rating-bar">
                                        @foreach($steps as $s)
                                            <span class="rating-step {{ $rent == $s ? 'active' : '' }}" data-bs-toggle="tooltip" title="Classificação {{ $s }} de 5+">{{ $s }}</span>
                                        @endforeach
                                    </div>

                                    <p class="small fw-semibold text-muted mt-3 mb-1">Classificação Potencial:</p>
                                    <div class="rating-bar">
                                        @foreach($steps as $s)
                                            <span class="rating-step {{ $pot == $s ? 'active' : '' }}" data-bs-toggle="tooltip" title="Classificação {{ $s }} de 5+">{{ $s }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Observações --}}
                                <div class="mt-4 small">
                                    <strong>🗒️ Observações:</strong>
                                    <div class="border-start border-3 border-danger ps-2 mt-1 text-muted fst-italic">
                                        {{ $r->observations ?? 'Sem observações.' }}
                                    </div>
                                </div>

                                {{-- Indicadores --}}
                                @if(!empty($r->indicators))
                                    <div class="mt-3 small">
                                        <strong>📊 Indicadores:</strong>
                                        <ul class="mt-2 ps-3 mb-0">
                                            @foreach($r->indicators as $key => $value)
                                                <li>{{ ucfirst($key) }}:
                                                    <span class="fw-semibold text-danger">{{ $value }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            {{-- 🔻 Rodapé --}}
                            <div class="card-footer bg-light small text-muted border-top">
                                <strong>👤 Scout:</strong>
                                <span class="text-dark">{{ $r->user->nome_tecnico ?? $r->author ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 🎨 Estilos personalizados --}}
    <style>
        .hover-shadow:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.75rem 1.25rem rgba(220, 53, 69, 0.15);
        }
        .transition {
            transition: all 0.2s ease-in-out;
        }

        /* 🔴 Barras de Classificação */
        .rating-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #dc3545;
            border-radius: 50px;
            padding: 4px 10px;
            height: 28px;
            overflow: hidden;
        }
        .rating-step {
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            cursor: default;
        }
        .rating-step.active {
            background-color: #fff;
            color: #dc3545;
            box-shadow: 0 0 0 2px #dc3545 inset;
        }
    </style>

    {{-- 🧠 Tooltip Bootstrap --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
@endsection
