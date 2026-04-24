@extends('layouts.app')

@section('content')
    <div class="container my-5">

        {{-- Mensagens --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Dados básicos -->
        <div class="card shadow-lg border-0">
            <div class="card-header bg-danger text-white text-center py-3">
                <h2 class="mb-0">{{ $coach->name }}</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <img src="{{ $coach->photo }}"
                             class="img-fluid rounded shadow-sm border"
                             style="max-height: 220px; object-fit: cover;">
                    </div>
                    <div class="col-md-8">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">ID:</dt><dd class="col-sm-8">{{ $coach->id }}</dd>
                            <dt class="col-sm-4">Idade:</dt><dd class="col-sm-8">{{ $coach->age ?? 'N/A' }}</dd>
                            <dt class="col-sm-4">Nacionalidade:</dt><dd class="col-sm-8">{{ $coach->nationality ?? 'N/A' }}</dd>
                            <dt class="col-sm-4">Data de Nascimento:</dt><dd class="col-sm-8">{{ $coach->birth_date ?? 'N/A' }}</dd>

                            @if(!empty($meta['role']) || !empty($meta['national_team']))
                                <dt class="col-sm-4">Função / Seleção:</dt>
                                <dd class="col-sm-8">
                                    @if(!empty($meta['role']))
                                        <div><strong>Função:</strong> {{ $meta['role'] }}</div>
                                    @endif
                                    @if(!empty($meta['national_team']))
                                        <div><strong>Seleção:</strong> {{ $meta['national_team'] }}</div>
                                    @endif
                                </dd>
                            @endif

                            <dt class="col-sm-4">Equipa:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-primary px-3 py-2">{{ $coach->team_name ?? 'N/A' }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perfil ScouTalent -->
        <div class="card mt-4">
            <div class="card-header bg-dark text-white">Perfil ScouTalent</div>
            <div class="card-body">
                <form method="POST" action="{{ route('coachs.profile.store', $coach->id) }}">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Fim contrato</label>
                            <input type="date" name="contract_end" class="form-control"
                                   value="{{ old('contract_end', isset($profile->contract_end) ? \Carbon\Carbon::parse($profile->contract_end)->format('Y-m-d') : '') }}">
                        </div>
                    </div>

                    <h6 class="mt-4">Dados Estatísticos</h6>
                    <div class="row">
                        @for($i = 1; $i <= 3; $i++)
                            <div class="col-md-6 mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">Título</span>
                                    <input type="text" name="stat{{ $i }}_label" class="form-control"
                                           value="{{ old("stat{$i}_label", $profile->{"stat{$i}_label"} ?? '') }}">
                                    <span class="input-group-text">Valor</span>
                                    <input type="text" name="stat{{ $i }}_value" class="form-control"
                                           value="{{ old("stat{$i}_value", $profile->{"stat{$i}_value"} ?? '') }}">
                                </div>
                            </div>
                        @endfor
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-danger px-5 py-2 shadow-sm">Guardar Perfil</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-footer bg-light text-end mt-4">
            <a href="{{ route('search.index') }}" class="btn btn-danger">← Voltar ao Motor de Busca</a>
        </div>
    </div>
@endsection
