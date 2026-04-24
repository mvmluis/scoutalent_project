@extends('layouts.app')

@section('content')
    <div class="container my-5">

        {{-- 🔴 CABEÇALHO --}}
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-danger text-white text-center py-3">
                <h2 class="mb-0 fw-bold">
                    <i class="bi bi-person-badge me-2"></i>Detalhes do Jogador
                </h2>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    {{-- FOTO --}}
                    <div class="col-md-4 text-center">
                        <img src="{{ $player->photo ?? '/images/default-player.png' }}"
                            class="img-fluid rounded border shadow-sm mb-3" style="max-height: 250px; object-fit: cover;">
                        <h5 class="fw-bold mt-3">{{ $player->name }}</h5>
                        <p class="text-muted mb-0">{{ $player->position ?? '—' }}</p>
                    </div>

                    {{-- DADOS --}}
                    <div class="col-md-8">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Idade:</dt>
                            <dd class="col-sm-8">{{ $player->age ?? '—' }}</dd>

                            <dt class="col-sm-4">Nacionalidade:</dt>
                            <dd class="col-sm-8">{{ $player->nationality ?? '—' }}</dd>

                            <dt class="col-sm-4">Altura:</dt>
                            <dd class="col-sm-8">{{ $player->height ?? '—' }}</dd>

                            <dt class="col-sm-4">Peso:</dt>
                            <dd class="col-sm-8">{{ $player->weight ?? '—' }}</dd>

                            <dt class="col-sm-4">Data de Nascimento:</dt>
                            <dd class="col-sm-8">{{ $player->birth_date ?? '—' }}</dd>

                            <dt class="col-sm-4">Equipa:</dt>
                            <dd class="col-sm-8">{{ $player->team_name ?? '—' }}</dd>

                            <dt class="col-sm-4">Classificação Média:</dt>
                            <dd class="col-sm-8">
                                {{ $player->rating ? number_format($player->rating, 2) : '—' }}
                            </dd>

                            <dt class="col-sm-4">Aparições:</dt>
                            <dd class="col-sm-8">{{ $player->appearances ?? 0 }}</dd>

                            <dt class="col-sm-4">Minutos:</dt>
                            <dd class="col-sm-8">{{ $player->minutes ?? 0 }}</dd>

                            <dt class="col-sm-4">Golos:</dt>
                            <dd class="col-sm-8">{{ $player->goals ?? 0 }}</dd>

                            <dt class="col-sm-4">Cartões Amarelos:</dt>
                            <dd class="col-sm-8 text-warning fw-bold">{{ $player->yellow_cards ?? 0 }}</dd>

                            <dt class="col-sm-4">Cartões Vermelhos:</dt>
                            <dd class="col-sm-8 text-danger fw-bold">{{ $player->red_cards ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <a href="{{ route('manual.players.index', request()->query()) }}" class="btn btn-danger">← Voltar</a>
        </div>

    </div>
@endsection
