@extends('layouts.app')

@section('content')
    <div class="container my-5">

        {{-- 🟦 FORMULÁRIO DE EDIÇÃO --}}
        <form method="POST" action="{{ route('manual.players.update', $player->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h2 class="mb-0 fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Editar Jogador
                    </h2>
                </div>

                <div class="card-body">
                    <div class="row g-4">
                        {{-- FOTO --}}
                        <div class="col-md-4 text-center">
                            <img id="photoPreview"
                                 src="{{ $player->photo ?? '/images/default-player.png' }}"
                                 class="img-fluid rounded border mb-3 shadow-sm"
                                 style="max-height: 230px; object-fit: cover;">
                            <input type="file" name="photo" id="photoInput"
                                   class="form-control form-control-lg" accept="image/*">
                            <small class="text-muted">Alterar imagem</small>
                        </div>

                        {{-- CAMPOS --}}
                        <div class="col-md-8">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Nome:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="name" class="form-control"
                                           value="{{ old('name', $player->name) }}" required>
                                </dd>

                                <dt class="col-sm-4">Idade:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="age" class="form-control"
                                           value="{{ old('age', $player->age) }}">
                                </dd>

                                <dt class="col-sm-4">Nacionalidade:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="nationality" class="form-control"
                                           value="{{ old('nationality', $player->nationality) }}">
                                </dd>

                                <dt class="col-sm-4">Posição:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="position" class="form-control"
                                           value="{{ old('position', $player->position) }}">
                                </dd>

                                <dt class="col-sm-4">Classificação Média:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="rating" class="form-control"
                                           step="0.01" min="0" max="10"
                                           value="{{ old('rating', $player->rating) }}">
                                </dd>

                                <dt class="col-sm-4">Altura:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="height" class="form-control"
                                           value="{{ old('height', $player->height) }}">
                                </dd>

                                <dt class="col-sm-4">Peso:</dt>
                                <dd class="col-sm-8">
                                    <input type="text" name="weight" class="form-control"
                                           value="{{ old('weight', $player->weight) }}">
                                </dd>

                                <dt class="col-sm-4">Data de Nascimento:</dt>
                                <dd class="col-sm-8">
                                    <input type="date" name="birth_date" class="form-control"
                                           value="{{ old('birth_date', $player->birth_date) }}">
                                </dd>

                                <dt class="col-sm-4">Equipa:</dt>
                                <dd class="col-sm-8">
                                    <select name="team_id" class="form-select">
                                        <option value="">— Nenhuma —</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}"
                                                {{ $team->id == $player->team_id ? 'selected' : '' }}>
                                                {{ $team->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </dd>

                                <dt class="col-sm-4">Aparições:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="appearances" class="form-control"
                                           value="{{ old('appearances', $player->appearances) }}">
                                </dd>

                                <dt class="col-sm-4">Minutos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="minutes" class="form-control"
                                           value="{{ old('minutes', $player->minutes) }}">
                                </dd>

                                <dt class="col-sm-4">Golos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="goals" class="form-control"
                                           value="{{ old('goals', $player->goals) }}">
                                </dd>

                                <dt class="col-sm-4">Cartões Amarelos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="yellow_cards" class="form-control border-warning"
                                           value="{{ old('yellow_cards', $player->yellow_cards) }}">
                                </dd>

                                <dt class="col-sm-4">Cartões Vermelhos:</dt>
                                <dd class="col-sm-8">
                                    <input type="number" name="red_cards" class="form-control border-danger"
                                           value="{{ old('red_cards', $player->red_cards) }}">
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- BOTÕES --}}
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-dark px-5 py-2 shadow-sm">
                            Guardar Alterações
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="text-end">
            <a href="{{ route('manual.players.show', $player->id) }}" class="btn btn-outline-secondary me-2">
                ← Voltar
            </a>
        </div>

    </div>

    {{-- SCRIPT DE PREVIEW DA FOTO --}}
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
                }
            });
        });
    </script>
@endsection
