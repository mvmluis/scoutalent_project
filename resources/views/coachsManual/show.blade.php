@extends('layouts.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <div class="container my-5">

        {{-- 🔴 CABEÇALHO --}}
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-danger text-white text-center py-3">
                <h2 class="mb-0 fw-bold">
                    <i class="bi bi-person-badge me-2"></i>Detalhes do Treinador
                </h2>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    {{-- FOTO --}}
                    <div class="col-md-4 text-center">
                        <img src="{{ $coach->photo ?? '/images/default-player.png' }}"
                            class="img-fluid rounded border shadow-sm mb-3" style="max-height: 250px; object-fit: cover;">

                        <h5 class="fw-bold mt-3 mb-1">{{ $coach->name ?? '—' }}</h5>

                        <div class="d-flex justify-content-center align-items-center gap-2">
                            @if (!empty($coach->team_logo))
                                <img src="{{ $coach->team_logo }}" alt="{{ $coach->team_name }}"
                                    style="width:30px;height:30px;object-fit:contain;border-radius:6px;border:1px solid #eee;background:#fff;padding:2px;">
                            @endif
                            <span class="text-muted">{{ $coach->team_name ?? '—' }}</span>
                        </div>

                        <div class="mt-2 d-flex justify-content-center align-items-center gap-2">
                            @if (!empty($coach->league_logo))
                                <img src="{{ $coach->league_logo }}" alt="{{ $coach->league_name }}"
                                    style="width:30px;height:30px;object-fit:contain;border-radius:6px;border:1px solid #eee;background:#fff;padding:2px;">
                            @endif
                            <span class="text-muted">{{ $coach->league_name ?? '—' }}</span>
                        </div>

                        <div class="mt-2 d-flex justify-content-center align-items-center gap-2">
                            @if (!empty($coach->country_flag))
                                <img src="{{ $coach->country_flag }}" alt="{{ $coach->league_country }}"
                                    style="width:30px;height:20px;object-fit:cover;border-radius:4px;border:1px solid #eee;">
                            @endif
                            <span class="text-muted">{{ $coach->league_country ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- DADOS --}}
                    <div class="col-md-8">
                        <dl class="row mb-0">

                            <dt class="col-sm-4">Idade:</dt>
                            <dd class="col-sm-8">{{ $coach->age ?? '—' }}</dd>

                            <dt class="col-sm-4">Nacionalidade:</dt>
                            <dd class="col-sm-8">{{ $coach->nationality ?? '—' }}</dd>

                            <dt class="col-sm-4">Data de Nascimento:</dt>
                            <dd class="col-sm-8">{{ $coach->birth_date ?? '—' }}</dd>

                            <dt class="col-sm-4">Equipa:</dt>
                            <dd class="col-sm-8">{{ $coach->team_name ?? '—' }}</dd>

                            <dt class="col-sm-4">Liga:</dt>
                            <dd class="col-sm-8">{{ $coach->league_name ?? '—' }}</dd>

                            <dt class="col-sm-4">País da Liga:</dt>
                            <dd class="col-sm-8">{{ $coach->league_country ?? '—' }}</dd>

                            <dt class="col-sm-4">Criado em:</dt>
                            <dd class="col-sm-8">
                                {{ $coach->created_at ? \Carbon\Carbon::parse($coach->created_at)->format('Y-m-d H:i') : '—' }}
                            </dd>

                            <dt class="col-sm-4">Atualizado em:</dt>
                            <dd class="col-sm-8">
                                {{ $coach->updated_at ? \Carbon\Carbon::parse($coach->updated_at)->format('Y-m-d H:i') : '—' }}
                            </dd>

                        </dl>
                    </div>

                </div>
            </div>
        </div>

        {{-- 🔘 BOTÕES --}}
        <div class="text-end">
            <a href="{{ route('manual.coachs.index', request()->query()) }}" class="btn btn-danger">
                ← Voltar
            </a>
        </div>
    </div>
@endsection
