@extends('layouts.app')

@section('content')
    <div class="container my-5">

        {{-- 🟩 FORMULÁRIO DO JOGADOR --}}
        <form method="POST" action="{{ route('manual.players.store') }}" enctype="multipart/form-data" id="playerForm">
            @csrf
            <input type="hidden" id="playerId" value="{{ $playerId ?? '' }}">

            {{-- 🔸 CARTÃO PRINCIPAL --}}
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-danger text-white text-center py-3">
                    <h2 class="mb-0 fw-bold">
                        <i class="bi bi-person-badge me-2"></i>Dados do Jogador
                    </h2>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        {{-- FOTO --}}
                        <div class="col-md-4 text-center">
                            <img id="photoPreview" src="/images/default-player.png"
                                 class="img-fluid rounded border mb-3 shadow-sm"
                                 style="max-height: 230px; object-fit: cover;">
                            <input type="file" name="photo" id="photoInput"
                                   class="form-control form-control-lg" accept="image/*">
                            <small class="text-muted">Escolher imagem</small>
                        </div>

                        {{-- CAMPOS --}}
                        <div class="col-md-8">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Nome:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="name" class="form-control" required>
                                </dd>

                                <dt class="col-sm-4">Idade:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="age" class="form-control" min="14" max="60">
                                </dd>

                                <dt class="col-sm-4">Nacionalidade:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="nationality" class="form-control">
                                </dd>

                                <dt class="col-sm-4">Altura:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="height" class="form-control" placeholder="Ex: 179 cm">
                                </dd>

                                <dt class="col-sm-4">Peso:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="weight" class="form-control" placeholder="Ex: 75 kg">
                                </dd>

                                <dt class="col-sm-4">Data de Nascimento:</dt>
                                <dd class="col-sm-8">
                                    <input type="date" name="birth_date" class="form-control">
                                </dd>

                                <dt class="col-sm-4">Equipa:</dt>
                                <dd class="col-sm-8">
                                    <select name="team_id" class="form-select">
                                        <option value="">— Nenhuma —</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </dd>

                                {{-- 🟦 NOVO: POSIÇÃO --}}
                                <dt class="col-sm-4">Posição:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="position" class="form-control" placeholder="Ex: Midfielder, Defender, Forward">
                                </dd>

                                {{-- 🟨 NOVO: RATING --}}
                                <dt class="col-sm-4">Classificação Média:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="rating" class="form-control" step="0.01" min="0" max="10" placeholder="Ex: 7.45">
                                </dd>

                                <dt class="col-sm-4">Aparições:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="appearances" value="0" class="form-control">
                                </dd>

                                <dt class="col-sm-4">Minutos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="minutes" value="0" class="form-control">
                                </dd>

                                <dt class="col-sm-4">Golos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="goals" value="0" class="form-control">
                                </dd>

                                <dt class="col-sm-4">Cartões Amarelos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="yellow_cards" value="0" class="form-control border-warning">
                                </dd>

                                <dt class="col-sm-4">Cartões Vermelhos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="red_cards" value="0" class="form-control border-danger">
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- BOTÃO GUARDAR --}}
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-danger px-5 py-2 shadow-sm">
                            Guardar Jogador
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- 🔙 BOTÃO VOLTAR --}}
        <div class="card-footer bg-light text-end mt-4 border-0">
            <a href="{{ route('manual.players.index') }}" class="btn btn-danger px-4">
                ← Voltar
            </a>
        </div>

    </div>

    {{-- 🧠 SCRIPT: Preview da imagem --}}
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const photoInput = document.getElementById("photoInput");
            const photoPreview = document.getElementById("photoPreview");

            photoInput.addEventListener("change", e => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = ev => (photoPreview.src = ev.target.result);
                    reader.readAsDataURL(file);
                } else {
                    photoPreview.src = "/images/default-player.png";
                }
            });
        });
    </script>
@endsection
