@extends('layouts.app')

@section('content')
    <div class="container my-5">

        {{-- Chart.js (CDN) --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        {{-- ✅ TOASTS Bootstrap 5 --}}
        <div aria-live="polite" aria-atomic="true" class="position-relative">
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
                @if(session('success'))
                    <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                    data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="toast align-items-center text-white bg-danger border-0 show" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                    data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="toast align-items-center text-white bg-warning border-0 show" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                                    data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 🧍‍♂️ Dados básicos -->
        <div class="card shadow-lg border-0">
            <div class="card-header bg-danger text-white text-center py-3">
                <h2 class="mb-0">{{ $player->name }}</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <img src="{{ $player->photo ?? '/images/default-player.png' }}"
                             class="img-fluid rounded shadow-sm border"
                             style="max-height: 220px; object-fit: cover;">
                    </div>

                    <div class="col-md-8">
                        <form id="playerProfileForm" method="POST" action="{{ route('players.profile.store', $player->id) }}">
                            @csrf
                            <dl class="row mb-0">
                                <dt class="col-sm-4">ID:</dt>
                                <dd class="col-sm-8">{{ $player->id }}</dd>

                                <dt class="col-sm-4">Idade:</dt>
                                <dd class="col-sm-8">{{ $player->age ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Nacionalidade:</dt>
                                <dd class="col-sm-8">{{ $player->nationality ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Altura:</dt>
                                <dd class="col-sm-8">{{ $player->height ? $player->height . ' cm' : 'N/A' }}</dd>

                                <dt class="col-sm-4">Peso:</dt>
                                <dd class="col-sm-8">{{ $player->weight ? $player->weight . ' kg' : 'N/A' }}</dd>

                                <dt class="col-sm-4">Equipa:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-primary px-3 py-2">{{ $player->team_name ?? 'N/A' }}</span>
                                </dd>

                                <dt class="col-sm-4">Posição:</dt>
                                <dd class="col-sm-8">
                                    @if($player->position)
                                        @php
                                            $translations = [
                                                'Goalkeeper' => 'Guarda Redes',
                                                'Defender' => 'Defesa',
                                                'Midfielder' => 'Médio',
                                                'Attacker' => 'Atacante',
                                            ];
                                            $posLabel = $translations[$player->position] ?? $player->position;
                                        @endphp
                                        <span class="badge bg-secondary px-3 py-2 text-uppercase">{{ $posLabel }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Rating:</dt>
                                <dd class="col-sm-8">
                                    @if($player->rating)
                                        <span class="badge {{ $player->rating >= 7 ? 'bg-success' : ($player->rating >= 6 ? 'bg-warning text-dark' : 'bg-danger') }} px-3 py-2">
                                            {{ number_format($player->rating, 2) }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>

                                {{-- Valor de Mercado --}}
                                <dt class="col-sm-4">Valor de Mercado (€):</dt>
                                <dd class="col-sm-8 d-flex align-items-center gap-2">
                                    <input type="number" step="0.01" name="market_value"
                                           value="{{ old('market_value', $profile->market_value ?? '') }}"
                                           class="form-control form-control-sm w-50">
                                </dd>

                                {{-- Fim de Contrato --}}
                                <dt class="col-sm-4">Fim de Contrato:</dt>
                                <dd class="col-sm-8 d-flex align-items-center gap-2">
                                    <input type="date" name="contract_end"
                                           value="{{ old('contract_end', isset($profile->contract_end) ? \Carbon\Carbon::parse($profile->contract_end)->format('Y-m-d') : '') }}"
                                           class="form-control form-control-sm w-50">
                                </dd>

                                {{-- Rentabilidade / Potencial (badges atualizáveis) --}}
                                <dt class="col-sm-4">Rentabilidade (média):</dt>
                                <dd class="col-sm-8">
                                    <span id="avgRentBadge" class="badge {{ ($profile->scoutalent_rentabilidade ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ number_format($profile->scoutalent_rentabilidade ?? 0, 2) }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Potencial (média):</dt>
                                <dd class="col-sm-8">
                                    <span id="avgPotBadge" class="badge {{ ($profile->scoutalent_potencial ?? 0) >= 0 ? 'bg-primary' : 'bg-warning text-dark' }}">
                                        {{ number_format($profile->scoutalent_potencial ?? 0, 2) }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Aparições:</dt>
                                <dd class="col-sm-8">{{ $player->appearances ?? 0 }}</dd>

                                <dt class="col-sm-4">Minutos:</dt>
                                <dd class="col-sm-8">{{ $player->minutes ?? 0 }}</dd>

                                <dt class="col-sm-4">Golos:</dt>
                                <dd class="col-sm-8"><span class="badge bg-success">{{ $player->goals ?? 0 }}</span></dd>

                                <dt class="col-sm-4">Amarelos:</dt>
                                <dd class="col-sm-8"><span class="badge bg-warning text-dark">{{ $player->yellow_cards ?? 0 }}</span></dd>

                                <dt class="col-sm-4">Vermelhos:</dt>
                                <dd class="col-sm-8"><span class="badge bg-danger">{{ $player->red_cards ?? 0 }}</span></dd>
                            </dl>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-outline-danger btn-sm px-4 py-1 shadow-sm">
                                    💾 Guardar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dados Estatísticos (não editáveis) -->
        <div class="card mt-4 shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Dados Estatísticos</h5>
                <span class="small text-light">Leitura apenas</span>
            </div>

            <div class="card-body">
                <div class="row">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="col-md-6 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-semibold w-50">
                                    {{ old("stat{$i}_label", $profile->{"stat{$i}_label"} ?? "Estatística {$i}") }}
                                </span>
                                <input type="text"
                                       class="form-control text-center fw-bold"
                                       readonly
                                       value="{{ old("stat{$i}_value", $profile->{"stat{$i}_value"} ?? '—') }}">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Perfil ScouTalent (gráfico baseado nos indicadores mais escolhidos) -->
        <div class="card mt-4 shadow-sm border-0" id="scout-card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Perfil ScouTalent</h5>
                <span class="small text-light">Indicadores mais escolhidos (agregado dos relatórios)</span>
            </div>

            <div class="card-body" id="scout-card-body">
                @php
                    $reports = $reports ?? collect();

                    $hasIndicators = $reports->filter(function($r){
                        return !empty($r->indicators) && is_iterable($r->indicators);
                    })->count() > 0;
                @endphp

                @if($hasIndicators)
                    <div class="chart-sm mx-auto">
                        <canvas id="scoutalentChart" height="220"></canvas>
                    </div>
                @else
                    <p class="text-muted fst-italic mb-0 text-center py-3" id="scout-no-data">
                        Sem dados suficientes para gerar o gráfico — crie relatórios com indicadores para ver os indicadores mais escolhidos.
                    </p>
                @endif
            </div>
        </div>

        <!-- Relatórios -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span>Relatórios do Jogador</span>
                <div>
                    <button id="headerReportBtn" class="btn btn-sm btn-outline-light">Criar Relatório</button>
                </div>
            </div>

            <div class="card-body">
                {{-- Form container (hidden until click) --}}
                <div id="reportFormContainer" style="display:none;">
                    <form id="reportForm" method="POST" action="{{ route('players.reports.store', $player->id) }}">
                        @csrf
                        {{-- hidden para id no client (usado só pelo JS) --}}
                        <input type="hidden" id="reportId" name="report_id" value="">
                        {{-- hidden _method para Laravel: default POST, when editing set to PUT --}}
                        <input type="hidden" id="_method" name="_method" value="POST">

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Data relatório</label>
                                <input type="date" name="report_date" id="report_date" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label>Autor</label>
                                <input type="text" name="author" class="form-control" value="{{ Auth::user()->nome_tecnico }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label>Função</label>
                                @php
                                    $posRaw = $player->position ?? '';
                                    $posNormalized = strtolower($posRaw);
                                @endphp
                                <select id="roleSelect" name="role" class="form-control">
                                    <option value="">-- Mostrar todos / Selecionar função --</option>

                                    @if(str_contains($posNormalized, 'goal') || str_contains($posNormalized, 'keeper'))
                                        <optgroup label="Guarda Redes">
                                            <option value="GR_tradicional">Guarda Redes Tradicional</option>
                                            <option value="GR_libero">Guarda Redes Líbero</option>
                                        </optgroup>
                                    @elseif(str_contains($posNormalized, 'mid') || str_contains($posNormalized, 'medio') || str_contains($posNormalized, 'midfielder'))
                                        <optgroup label="Médio">
                                            <option value="trinco">Trinco</option>
                                            <option value="medio_defensivo">Médio Defensivo</option>
                                            <option value="medio_centro">Médio Centro</option>
                                            <option value="medio_centro_universal">Médio Centro Universal</option>
                                            <option value="medio_ofensivo">Médio Ofensivo</option>
                                            <option value="medio_ofensivo_universal">Médio Ofensivo Universal</option>
                                        </optgroup>
                                    @elseif(str_contains($posNormalized, 'def') || str_contains($posNormalized, 'defender'))
                                        <optgroup label="Defesa">
                                            <option value="defesa_central">Defesa Central</option>
                                            <option value="libero">Líbero</option>
                                            <option value="defesa_central_universal">Defesa Central Universal</option>
                                            <option value="defesa_lateral">Defesa Lateral</option>
                                            <option value="lateral">Lateral</option>
                                            <option value="ala">Ala</option>
                                        </optgroup>
                                    @elseif(str_contains($posNormalized, 'att') || str_contains($posNormalized, 'forward') || str_contains($posNormalized, 'striker'))
                                        <optgroup label="Atacante">
                                            <option value="extremo">Extremo</option>
                                            <option value="avancado">Avançado</option>
                                            <option value="ponta_lanca">Ponta de Lança</option>
                                        </optgroup>
                                    @else
                                        <optgroup label="Guarda Redes">
                                            <option value="GR_tradicional">Guarda Redes Tradicional</option>
                                            <option value="GR_libero">Guarda Redes Líbero</option>
                                        </optgroup>
                                        <optgroup label="Defesa">
                                            <option value="defesa_central">Defesa Central</option>
                                            <option value="libero">Líbero</option>
                                            <option value="defesa_central_universal">Defesa Central Universal</option>
                                            <option value="defesa_lateral">Defesa Lateral</option>
                                            <option value="lateral">Lateral</option>
                                            <option value="ala">Ala</option>
                                        </optgroup>
                                        <optgroup label="Médio">
                                            <option value="trinco">Trinco</option>
                                            <option value="medio_defensivo">Médio Defensivo</option>
                                            <option value="medio_centro">Médio Centro</option>
                                            <option value="medio_centro_universal">Médio Centro Universal</option>
                                            <option value="medio_ofensivo">Médio Ofensivo</option>
                                            <option value="medio_ofensivo_universal">Médio Ofensivo Universal</option>
                                        </optgroup>
                                        <optgroup label="Atacante">
                                            <option value="extremo">Extremo</option>
                                            <option value="avancado">Avançado</option>
                                            <option value="ponta_lanca">Ponta de Lança</option>
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Indicadores (serão populados pelo JS) --}}
                        <div id="indicatorsContainer" class="mb-3" style="display:none;">
                            <label class="form-label fw-bold">Indicadores (avalie cada item)</label>
                            <div id="indicatorsContent" class="border rounded p-3 bg-light">
                                {{-- JS popula aqui radio groups em 3 colunas --}}
                            </div>
                            <div class="small text-muted mt-1">
                                Selecionados: <span id="selectedCount">0</span> /
                                Escolha pelo menos <strong id="minRequiredText">10</strong> indicadores
                                (ou o número máximo disponível para a função).
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Observações</label>
                            <textarea name="observations" id="observations" class="form-control" maxlength="250"></textarea>
                            <div class="form-text small text-muted">
                                <span id="obsCount">0</span>/250
                            </div>
                        </div>

                        {{-- Rentabilidade / Potencial (dropdowns com labels somente) --}}
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label>Rentabilidade</label>
                                @php
                                    $rpMap = [
                                        '-1' => '-1 (mínimo)',
                                        '1'  => '1',
                                        '1+' => '1+',
                                        '-2' => '-2',
                                        '2'  => '2',
                                        '2+' => '2+',
                                        '-3' => '-3',
                                        '3'  => '3',
                                        '3+' => '3+',
                                        '-4' => '-4',
                                        '4'  => '4',
                                        '4+' => '4+',
                                        '-5' => '-5',
                                        '5'  => '5',
                                        '5+' => '5+ (Máximo)'
                                    ];
                                @endphp
                                <select name="scoutalent_rentabilidade_label" id="rent" class="form-control">
                                    <option value="">-- Selecionar --</option>
                                    @foreach($rpMap as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ (old('scoutalent_rentabilidade_label', $report->scoutalent_rentabilidade_label ?? '') == $val) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Potencial</label>
                                <select name="scoutalent_potencial_label" id="pot" class="form-control">
                                    <option value="">-- Selecionar --</option>
                                    @foreach($rpMap as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ (old('scoutalent_potencial_label', $report->scoutalent_potencial_label ?? '') == $val) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="rentPotError" class="small text-danger mb-3" style="display:none;">
                            A rentabilidade não pode ser superior ao potencial (segundo a ordem definida).
                        </div>

                        {{-- hidden fields que o servidor pode usar (legacy numeric 1..5) e score completo 1..15 + label --}}
                        <input type="hidden" name="scoutalent_rentabilidade" id="scoutalent_rentabilidade_normalized" value="">
                        <input type="hidden" name="scoutalent_rentabilidade_score" id="scoutalent_rentabilidade_score" value="">
                        <input type="hidden" name="scoutalent_potencial" id="scoutalent_potencial_normalized" value="">
                        <input type="hidden" name="scoutalent_potencial_score" id="scoutalent_potencial_score" value="">

                        <div class="d-flex justify-content-end gap-2 mb-4">
                            <button type="button" id="btnFormCancel" class="btn btn-outline-secondary">Cancelar</button>
                            <button type="submit" id="btnSubmitInternal" class="d-none">Guardar</button>
                        </div>
                    </form>
                </div>

                {{-- Reports list (history visible by default) --}}
                <ul class="list-group mt-4" id="reports-list">
                    @php
                        $scoreMap = [6=>'Excelente',5=>'Muito bom',4=>'Bom',3=>'Satisfatório',2=>'Baixo',1=>'Muito baixo'];
                    @endphp

                    @forelse($reports as $report)
                        @php
                            // prefer label if exists (front-end will also receive label/score in JSON)
                            $rentLabel = $report->scoutalent_rentabilidade_label ?? $report->scoutalent_rentabilidade ?? '';
                            $potLabel = $report->scoutalent_potencial_label ?? $report->scoutalent_potencial ?? '';
                        @endphp
                        <li class="list-group-item p-4 mb-3 shadow-sm rounded border-0 fade-in"
                            data-id="{{ $report->id }}" style="background-color:#fafafa;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1 fw-bold text-dark">
                                        {{ $report->report_date ? \Carbon\Carbon::parse($report->report_date)->format('d/m/Y') : 'Sem data' }}
                                        <span class="text-muted small d-block">
                                            {{ $report->author ?? 'Autor não definido' }}
                                            @if(!empty($report->role)) — <em>{{ $report->role }}</em>@endif
                                        </span>
                                    </h5>
                                </div>
                                <div>
                                    <span class="badge px-3 py-2 me-2 fw-semibold {{ (is_numeric($report->scoutalent_rentabilidade) && $report->scoutalent_rentabilidade>0) ? 'bg-success' : (is_numeric($report->scoutalent_rentabilidade) && $report->scoutalent_rentabilidade<0 ? 'bg-danger' : 'bg-secondary') }}">
                                        Rentabilidade: {{ $rentLabel }}
                                    </span>
                                    <span class="badge px-3 py-2 fw-semibold {{ (is_numeric($report->scoutalent_potencial) && $report->scoutalent_potencial>0) ? 'bg-primary' : (is_numeric($report->scoutalent_potencial) && $report->scoutalent_potencial<0 ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                        Potencial: {{ $potLabel }}
                                    </span>
                                </div>
                            </div>

                            @if(!empty($report->observations))
                                <p class="mt-3 mb-2 text-muted fst-italic border-start ps-3">{{ $report->observations }}</p>
                            @endif

                            {{-- Indicadores (se o relatório tiver indicators) --}}
                            @if(!empty($report->indicators) && is_iterable($report->indicators))
                                <div class="mt-2">
                                    <strong>Indicadores:</strong>
                                    <div class="row mt-2">
                                        @foreach($report->indicators as $indLabel => $val)
                                            <div class="col-md-6 mb-2">
                                                <span class="d-block small">
                                                    <span class="fw-semibold">{{ $indLabel }}</span>:
                                                    <span class="text-muted">
                                                        {{ $scoreMap[$val] ?? $val }}
                                                    </span>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="text-end mt-3 d-flex justify-content-end gap-2">
                                <button class="btn btn-outline-dark btn-sm rounded-pill px-3 btn-edit"
                                        data-id="{{ $report->id }}"
                                        data-date="{{ $report->report_date ? \Carbon\Carbon::parse($report->report_date)->format('Y-m-d') : '' }}"
                                        data-report='@json($report)'
                                        data-rent-label="{{ $report->scoutalent_rentabilidade_label ?? '' }}"
                                        data-pot-label="{{ $report->scoutalent_potencial_label ?? '' }}"
                                        data-rent-score="{{ $report->scoutalent_rentabilidade_score ?? '' }}"
                                        data-pot-score="{{ $report->scoutalent_potencial_score ?? '' }}"
                                        data-role="{{ $report->role ?? '' }}"
                                        data-obs="{{ $report->observations ?? '' }}">
                                    ✏️ Editar
                                </button>
                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-delete"
                                        data-id="{{ $report->id }}">🗑️ Apagar</button>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-4 border-0 bg-light fst-italic">
                            Nenhum relatório registado ainda.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card-footer bg-light text-end mt-4">
            <a href="{{ route('players.followed') }}" class="btn btn-danger">
                ← Voltar aos Favoritos
            </a>
        </div>
    </div>

    {{-- JSON seguro dos relatórios --}}
    @php
        $reportsJson = $reports->map(function($r){
            return [
                'id' => $r->id,
                'report_date' => $r->report_date ? \Carbon\Carbon::parse($r->report_date)->format('Y-m-d') : null,
                'scoutalent_rentabilidade' => $r->scoutalent_rentabilidade ?? null, // normalized legacy (1..5)
                'scoutalent_potencial' => $r->scoutalent_potencial ?? null,       // normalized legacy (1..5)
                'scoutalent_rentabilidade_label' => $r->scoutalent_rentabilidade_label ?? null,
                'scoutalent_potencial_label' => $r->scoutalent_potencial_label ?? null,
                'scoutalent_rentabilidade_score' => $r->scoutalent_rentabilidade_score ?? null, // 1..15 rank
                'scoutalent_potencial_score' => $r->scoutalent_potencial_score ?? null,       // 1..15 rank
                'observations' => $r->observations ?? null,
                'author' => $r->author ?? null,
                'role' => $r->role ?? null,
                'indicators' => $r->indicators ?? null,
            ];
        })->toJson();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const headerBtn = document.getElementById('headerReportBtn');
            const formContainer = document.getElementById('reportFormContainer');
            const form = document.getElementById('reportForm');
            const btnFormCancel = document.getElementById('btnFormCancel');
            const idInput = document.getElementById('reportId');
            const methodInput = document.getElementById('_method');
            const list = document.getElementById('reports-list');
            const playerId = {{ $player->id }};
            const avgRentBadge = document.getElementById('avgRentBadge');
            const avgPotBadge = document.getElementById('avgPotBadge');
            const roleSelect = document.getElementById('roleSelect');
            const indicatorsContainer = document.getElementById('indicatorsContainer');
            const indicatorsContent = document.getElementById('indicatorsContent');
            const minRequiredText = document.getElementById('minRequiredText');
            const selectedCountEl = document.getElementById('selectedCount');

            const observationsEl = document.getElementById('observations');
            const obsCountEl = document.getElementById('obsCount');

            const rentSelect = document.getElementById('rent');
            const potSelect = document.getElementById('pot');
            const rentPotError = document.getElementById('rentPotError');

            // hidden fields for submit
            const rentNormalizedInput = document.getElementById('scoutalent_rentabilidade_normalized');
            const rentScoreInput = document.getElementById('scoutalent_rentabilidade_score');
            const potNormalizedInput = document.getElementById('scoutalent_potencial_normalized');
            const potScoreInput = document.getElementById('scoutalent_potencial_score');

            // client-side reports data
            let reportsData = {!! $reportsJson !!} || [];
            let currentMinRequired = 10;

            // ORDER (exactly the order you requested)
            const labelOrder = ['-1','1','1+','-2','2','2+','-3','3','3+','-4','4','4+','-5','5','5+'];

            (function(){
                // merge helper
                function mergeUnique(a, b) {
                    const out = [];
                    (a || []).forEach(i => { if (!out.includes(i)) out.push(i); });
                    (b || []).forEach(i => { if (!out.includes(i)) out.push(i); });
                    return out;
                }

                const allFieldIndicators = [
                    "Condição Física Natural","Fiabilidade Estrutural e Clínica","Eficácia da Velocidade Pura",
                    "Eficácia da Aceleração Explosiva","Resistência Aeróbia","Eficácia da Recepção","Eficácia do Primeiro Toque",
                    "Eficácia Passe Curto","Eficácia Passe Intermédio","Eficácia Passe Longo","Eficácia Variação de jogo",
                    "Eficácia Passe Ruptura","Técnica de Condução","Eficácia de Ultrapassagem","Força Funcional",
                    "Equilíbrio e Estabilidade","Noção Posicional Espacial","Técnica do Cruzamento","Eficácia de Cruzamento",
                    "Técnica de Lançamento","Eficácia de Lançamento","Técnica do Remate","Eficácia do Remate",
                    "Técnica Cabeceamento","Eficácia de Cabeceamento","Técnica no Desarme","Eficácia no Desarme",
                    "Eficácia de Marcação","Eficácia de Interceção","Eficácia de Bloqueio","Eficácia de Duelo Aéreo",
                    "Cobertura Espacial","Volume de Movimentação Tática","Eficácia de Movimentos de Apoio",
                    "Eficácia de Movimentos de Rutura","Capacidade de Visão","Capacidade de Criação",
                    "Eficácia de Decisão no Meio Campo Defensivo","Eficácia de Decisão no Meio Campo Ofensivo",
                    "Eficácia de Decisão no Último Terço","Precisão no Timing","Compostura na decisão",
                    "Inteligência na decisão","Agressividade e Compromisso no Duelo","Fiabilidade na Performance",
                    "Eficácia Comunicacional","Versatilidade Posicional","Técnica Penalidades","Eficácia Penalidades",
                    "Técnica Cantos","Eficácia Cantos","Técnica Livres Diretos","Eficácia Livres Diretos",
                    "Técnica Livres Indiretos","Eficácia Livres Indiretos"
                ];

                const gkSpecific = [
                    'Aptidão Manual','Encaixe Frontal','Domínio Aéreo','Velocidade Deslocamento Curto',
                    'Eficácia da Impulsão','Velocidade de Saída Curta','Potência Explosiva Projeção',
                    'Eficácia de Posicionamento Espacial','Eficácia de Antecipação Tática',
                    'Eficácia Defesa Grandes Penalidades','Qualidade de Execução (Pontapé de Baliza)',
                    'Impacto Tático (Pontapé de Baliza)','Estabilidade Encaixe sob Pressão',
                    'Eficácia em Situações de 1vs1','Condição Física Natural','Fiabilidade Estrutural e Clínica',
                    'Eficácia da Recepção','Eficácia do Primeiro Toque','Eficácia de Interceção',
                    'Compostura na decisão','Fiabilidade na Performance','Eficácia Comunicacional'
                ];

                const defesaCentralBase = [
                    'Noção Posicional Espacial','Eficácia de Antecipação Tática','Técnica de Condução',
                    'Eficácia de Ultrapassagem','Força Funcional','Equilíbrio e Estabilidade',
                    'Eficácia da Recepção','Eficácia Passe Curto','Eficácia Passe Intermédio',
                    'Eficácia Passe Longo','Eficácia Passe Ruptura','Eficácia de Lançamento'
                ];

                const defesaLateralBase = [
                    'Técnica de Condução','Eficácia Passe Curto','Eficácia da Recepção','Eficácia de Ultrapassagem','Eficácia de Cruzamento'
                ];

                const medioBase = [
                    'Eficácia do Primeiro Toque','Eficácia Passe Curto','Eficácia Passe Intermédio','Noção Posicional Espacial','Técnica de Condução'
                ];

                const ofensivoBase = [
                    'Técnica do Remate','Eficácia de Posicionamento Espacial','Técnica de Condução','Eficácia de Ultrapassagem','Técnica de Cruzamento'
                ];

                window.indicatorsByRole = {
                    'GR_tradicional': mergeUnique(gkSpecific, [
                        'Condição Física Natural','Fiabilidade Estrutural e Clínica','Eficácia Comunicacional','Fiabilidade na Performance'
                    ]),
                    'GR_libero': mergeUnique(gkSpecific, [
                        'Eficácia de Posicionamento Espacial','Eficácia de Antecipação Tática','Estabilidade Encaixe sob Pressão','Condição Física Natural'
                    ]),

                    'defesa_central': mergeUnique(defesaCentralBase, allFieldIndicators),
                    'libero': mergeUnique(['Noção Posicional Espacial','Eficácia de Antecipação Tática','Eficácia Passe Ruptura','Técnica de Condução'], allFieldIndicators),
                    'defesa_central_universal': mergeUnique(['Noção Posicional Espacial','Eficácia de Antecipação Tática','Força Funcional','Equilíbrio e Estabilidade'], allFieldIndicators),
                    'defesa_lateral': mergeUnique(defesaLateralBase, allFieldIndicators),
                    'lateral': mergeUnique(['Técnica de Condução','Eficácia Passe Curto','Eficácia de Cruzamento','Resistência Aeróbia'], allFieldIndicators),
                    'ala': mergeUnique(['Técnica de Condução','Eficácia Passe Curto','Eficácia de Cruzamento','Eficácia de Ultrapassagem'], allFieldIndicators),

                    'trinco': mergeUnique(['Resistência Aeróbia','Eficácia Passe Curto','Eficácia Passe Intermédio','Noção Posicional Espacial','Técnica de Condução'], allFieldIndicators),
                    'medio_defensivo': mergeUnique(['Eficácia de Antecipação Tática','Noção Posicional Espacial','Eficácia Passe Curto','Resistência Aeróbia'], allFieldIndicators),
                    'medio_centro': mergeUnique(['Eficácia do Primeiro Toque','Eficácia Passe Curto','Eficácia Passe Intermédio','Noção Posicional Espacial'], allFieldIndicators),
                    'medio_centro_universal': mergeUnique(['Eficácia do Primeiro Toque','Eficácia Passe Curto','Eficácia Passe Longo','Técnica de Condução'], allFieldIndicators),
                    'medio_ofensivo': mergeUnique(['Eficácia do Primeiro Toque','Eficácia Passe Ruptura','Eficácia Variação de jogo','Técnica do Remate'], allFieldIndicators),
                    'medio_ofensivo_universal': mergeUnique(['Eficácia do Primeiro Toque','Eficácia Passe Ruptura','Eficácia Passe Longo','Eficácia Variação de jogo'], allFieldIndicators),

                    'extremo': mergeUnique(['Eficácia da Velocidade Pura','Eficácia da Aceleração Explosiva','Técnica de Condução','Eficácia de Ultrapassagem','Técnica de Cruzamento'], allFieldIndicators),
                    'avancado': mergeUnique(['Técnica do Remate','Eficácia de Posicionamento Espacial','Técnica de Condução','Eficácia de Ultrapassagem'], allFieldIndicators),
                    'ponta_lanca': mergeUnique(['Técnica do Remate','Eficácia de Posicionamento Espacial','Impacto Tático (Pontapé de Baliza)','Eficácia Passe Ruptura'], allFieldIndicators),

                    // 'all' mostra todos os indicadores (útil antes de selecionar função)
                    'all': allFieldIndicators
                };
            })();

            // util slug
            function slugify(text){
                return String(text).replace(/[^a-z0-9]/gi,'_').toLowerCase();
            }

            // === Funções utilitárias para labels / rank / normalized ===
            function labelToRank(label) {
                if (!label && label !== '') return null;
                const idx = labelOrder.indexOf(String(label));
                return idx === -1 ? null : (idx + 1); // rank 1..15
            }
            function rankToNormalized(rank) {
                // normalizar 1..15 -> 1..5 (legacy). Cada 3 posições => 1..5
                if (!rank && rank !== 0) return null;
                return Math.min(5, Math.max(1, Math.ceil(rank / 3)));
            }

            function parseSelectLabelValue(sel) {
                if (!sel) return { label: null, rank: null, normalized: null };
                const label = sel.value || null;
                const rank = label ? labelToRank(label) : null;
                const normalized = rank ? rankToNormalized(rank) : null;
                return { label, rank, normalized };
            }

            // === Robust radio "toggle off" (uncheck on second click) ===
            document.addEventListener('pointerdown', (e) => {
                let input = e.target.closest ? e.target.closest('input[type="radio"]') : null;
                if (!input) {
                    const label = e.target.closest ? e.target.closest('label') : null;
                    if (label) {
                        const fid = label.htmlFor;
                        input = fid ? document.getElementById(fid) : label.querySelector('input[type="radio"]');
                    }
                }
                if (input) input.dataset.waschecked = input.checked ? 'true' : 'false';
            }, { passive: true });

            document.addEventListener('click', (e) => {
                let input = e.target.closest ? e.target.closest('input[type="radio"]') : null;
                if (!input) {
                    const label = e.target.closest ? e.target.closest('label') : null;
                    if (label) {
                        const fid = label.htmlFor;
                        input = fid ? document.getElementById(fid) : label.querySelector('input[type="radio"]');
                    }
                }
                if (!input) return;
                setTimeout(() => {
                    if (input.dataset.waschecked === 'true') {
                        input.checked = false;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    input.dataset.waschecked = 'false';
                }, 0);
            });

            document.addEventListener('change', (e) => {
                if (!e.target) return;
                if (e.target.matches && e.target.matches('input[type="radio"]')) {
                    updateSelectedCount();
                }
            });

            // helper para dividir em n colunas mantendo equilibrio
            function chunkArray(arr, n) {
                const out = Array.from({length:n}, () => []);
                arr.forEach((v, i) => {
                    out[i % n].push(v);
                });
                return out;
            }

            function populateIndicatorsForRole(roleKey) {
                indicatorsContent.innerHTML = '';

                // se função vazia ou 'all' mostramos todos
                let indicators = [];
                if (!roleKey || roleKey === '') indicators = window.indicatorsByRole['all'] || [];
                else indicators = window.indicatorsByRole[roleKey] || [];

                const count = indicators.length;
                const minRequired = Math.min(10, count === 0 ? 10 : count);
                currentMinRequired = minRequired;
                minRequiredText.textContent = minRequired;

                if (!indicators.length) {
                    indicatorsContainer.style.display = 'none';
                    selectedCountEl.textContent = 0;
                    return;
                }

                indicatorsContainer.style.display = 'block';

                const cols = chunkArray(indicators, 3); // 3 colunas
                const row = document.createElement('div');
                row.className = 'row';

                cols.forEach((colIndicators) => {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-4';
                    colIndicators.forEach((ind) => {
                        const slug = 'ind_' + Math.abs(hashCode(ind)) + '_' + slugify(ind);
                        const groupDiv = document.createElement('div');
                        groupDiv.className = 'mb-3 indicator-group';

                        const label = document.createElement('label');
                        label.className = 'fw-semibold d-block mb-1 indicator-label';
                        label.textContent = ind;
                        groupDiv.appendChild(label);

                        const options = [
                            { val: 6, txt: 'Excelente' },
                            { val: 5, txt: 'Muito bom' },
                            { val: 4, txt: 'Bom' },
                            { val: 3, txt: 'Satisfatório' },
                            { val: 2, txt: 'Baixo' },
                            { val: 1, txt: 'Muito baixo' },
                        ];

                        const rowOpt = document.createElement('div');
                        rowOpt.className = 'd-flex gap-2 flex-wrap';

                        options.forEach(opt => {
                            const wrap = document.createElement('div');
                            wrap.className = 'form-check form-check-inline mb-1';

                            const input = document.createElement('input');
                            input.className = 'form-check-input';
                            input.type = 'radio';
                            input.name = `indicators[${slug}]`;
                            input.value = opt.val;
                            input.id = `${slug}_${opt.val}`;

                            const lab = document.createElement('label');
                            lab.className = 'form-check-label small mb-0';
                            lab.htmlFor = `${slug}_${opt.val}`;
                            lab.textContent = opt.txt;

                            wrap.appendChild(input);
                            wrap.appendChild(lab);
                            rowOpt.appendChild(wrap);
                        });

                        groupDiv.appendChild(rowOpt);
                        col.appendChild(groupDiv);
                    });
                    row.appendChild(col);
                });

                indicatorsContent.appendChild(row);
                updateSelectedCount();
            }

            function hashCode(str) {
                let h = 0;
                for (let i = 0; i < str.length; i++) {
                    h = (h<<5) - h + str.charCodeAt(i);
                    h |= 0;
                }
                return h;
            }

            function collectSelectedIndicators() {
                const result = [];
                const groups = indicatorsContent.querySelectorAll('.indicator-group');
                groups.forEach((g, idx) => {
                    const labelEl = g.querySelector('.indicator-label');
                    const label = labelEl ? labelEl.textContent.trim() : 'Indicador ' + (idx+1);
                    const checked = g.querySelector('input[type="radio"]:checked');
                    if (checked) {
                        result.push({ label, value: Number(checked.value) });
                    }
                });
                return result;
            }

            function updateSelectedCount() {
                const sel = collectSelectedIndicators().length;
                selectedCountEl.textContent = sel;
                minRequiredText.textContent = currentMinRequired;
            }

            // AGGREGATE (mantive a tua implementação)
            function aggregateIndicatorsFromReports(data) {
                const map = {};
                data.forEach(r => {
                    if (r.indicators && typeof r.indicators === 'object') {
                        Object.keys(r.indicators).forEach(label => {
                            const val = Number(r.indicators[label]);
                            if (!map[label]) map[label] = { count: 0, sum: 0 };
                            map[label].count += 1;
                            map[label].sum += isNaN(val) ? 0 : val;
                        });
                    }
                });
                const arr = Object.keys(map).map(label => ({
                    label,
                    count: map[label].count,
                    avg: map[label].count ? (map[label].sum / map[label].count) : 0
                }));
                arr.sort((a,b) => b.count - a.count);
                return arr;
            }

            function wrapLabel(label, maxLen = 14) {
                if (!label || typeof label !== 'string') return label;
                if (label.length <= maxLen) return label;
                const parts = label.match(new RegExp('.{1,' + maxLen + '}', 'g')) || [label];
                return parts.join('\n');
            }

            // chart + renderReportLi: adaptei para usar label se existir
            const scoreMap = {6:'Excelente',5:'Muito bom',4:'Bom',3:'Satisfatório',2:'Baixo',1:'Muito baixo'};
            let scoutChart = null;

            function initChartIfNeeded() {
                const canvas = document.getElementById('scoutalentChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                const indicatorsAgg = aggregateIndicatorsFromReports(reportsData);

                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 8, right: 12, left: 8, bottom: 8 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 6, font: { size: 11 } } },
                        title: { display: true, text: indicatorsAgg.length > 0 ? 'Indicadores mais escolhidos (Top)' : 'Evolução dos Indicadores ScouTalent', font: { size: 14 } },
                        tooltip: { mode: 'index', intersect: false }
                    }
                };

                if (indicatorsAgg.length > 0) {
                    const top = indicatorsAgg.slice(0, 8);
                    const labels = top.map(i => wrapLabel(i.label));
                    const counts = top.map(i => i.count);
                    const avgs = top.map(i => parseFloat(i.avg.toFixed(2)));

                    scoutChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Ocorrências', data: counts, yAxisID: 'y_counts', backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                                { label: 'Média (pontuação)', data: avgs, type: 'line', yAxisID: 'y_avg', borderColor: 'rgba(255,159,64,0.95)', tension: 0.2, fill: false, pointRadius: 3 }
                            ]
                        },
                        options: Object.assign({}, commonOptions, {
                            scales: {
                                x: { ticks: { autoSkip: true, maxRotation: 0, minRotation: 0, font: { size: 11 } }, grid: { display: false } },
                                y_counts: { type: 'linear', position: 'left', title: { display: true, text: 'Ocorrências', font: { size: 12 } }, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } },
                                y_avg: { type: 'linear', position: 'right', title: { display: true, text: 'Média (1-6)', font: { size: 12 } }, beginAtZero: true, min: 0, max: 6, ticks: { font: { size: 11 } } }
                            }
                        })
                    });
                } else {
                    const labels = reportsData.map(r => wrapLabel(r.report_date ?? '—', 18));
                    // average uses normalized legacy (1..5) when available
                    const rents = reportsData.map(r => {
                        if (r.scoutalent_rentabilidade !== undefined && r.scoutalent_rentabilidade !== null) return Number(r.scoutalent_rentabilidade) || 0;
                        if (r.scoutalent_rentabilidade_score) { return Math.ceil(Number(r.scoutalent_rentabilidade_score)/3); }
                        return 0;
                    });
                    const pots  = reportsData.map(r => {
                        if (r.scoutalent_potencial !== undefined && r.scoutalent_potencial !== null) return Number(r.scoutalent_potencial) || 0;
                        if (r.scoutalent_potencial_score) { return Math.ceil(Number(r.scoutalent_potencial_score)/3); }
                        return 0;
                    });

                    scoutChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Rentabilidade', data: rents, backgroundColor: 'rgba(54, 162, 235, 0.7)' },
                                { label: 'Potencial', data: pots, backgroundColor: 'rgba(255, 159, 64, 0.8)' }
                            ]
                        },
                        options: Object.assign({}, commonOptions, {
                            scales: {
                                x: { ticks: { autoSkip: true, maxRotation: 0, minRotation: 0, font: { size: 11 } }, grid: { display: false } },
                                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, title: { display: true, text: 'Pontuação (normalized 1..5)', font: { size: 12 } } }
                            }
                        })
                    });
                }
            }

            function renderReportLi(r) {
                const li = document.createElement('li');
                li.className = 'list-group-item p-4 mb-3 shadow-sm rounded border-0 fade-in';
                li.style.backgroundColor = '#fafafa';
                li.dataset.id = r.id;

                let indicatorsHtml = '';
                if (r.indicators && typeof r.indicators === 'object') {
                    indicatorsHtml += '<div class="mt-2"><strong>Indicadores:</strong><div class="row mt-2">';
                    Object.keys(r.indicators).forEach((label, i) => {
                        const val = r.indicators[label];
                        const txt = (scoreMap[val] || val);
                        indicatorsHtml += `<div class="col-md-6 mb-2"><span class="d-block small"><span class="fw-semibold">${label}</span>: <span class="text-muted">${txt}</span></span></div>`;
                    });
                    indicatorsHtml += '</div></div>';
                }

                // prefer label if present
                const rentLabel = r.scoutalent_rentabilidade_label || r.scoutalent_rentabilidade || '';
                const potLabel = r.scoutalent_potencial_label || r.scoutalent_potencial || '';

                li.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">
                                ${r.report_date ? (new Date(r.report_date)).toLocaleDateString('pt-PT') : 'Sem data'}
                                <span class="text-muted small d-block">${r.author ?? '{{ Auth::user()->name }}'} ${r.role ? ' — ' + r.role : ''}</span>
                            </h5>
                        </div>
                        <div>
                            <span class="badge px-3 py-2 me-2 fw-semibold ${( (r.scoutalent_rentabilidade>0) || (r.scoutalent_rentabilidade_score && Number(r.scoutalent_rentabilidade_score)>0) ) ? 'bg-success' : ((r.scoutalent_rentabilidade<0)?'bg-danger':'bg-secondary')}">
                                Rentabilidade: ${rentLabel}
                            </span>
                            <span class="badge px-3 py-2 fw-semibold ${( (r.scoutalent_potencial>0) || (r.scoutalent_potencial_score && Number(r.scoutalent_potencial_score)>0) ) ? 'bg-primary' : ((r.scoutalent_potencial<0)?'bg-warning text-dark':'bg-secondary')}">
                                Potencial: ${potLabel}
                            </span>
                        </div>
                    </div>
                    ${r.observations ? `<p class="mt-3 mb-2 text-muted fst-italic border-start ps-3">${r.observations}</p>` : ''}
                    ${indicatorsHtml}
                    <div class="text-end mt-3 d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-dark btn-sm rounded-pill px-3 btn-edit"
                            data-id="${r.id}" data-date="${r.report_date ?? ''}" data-report='${JSON.stringify(r)}' data-rent-label="${r.scoutalent_rentabilidade_label ?? ''}" data-pot-label="${r.scoutalent_potencial_label ?? ''}" data-rent-score="${r.scoutalent_rentabilidade_score ?? ''}" data-pot-score="${r.scoutalent_potencial_score ?? ''}" data-role="${r.role ?? ''}" data-obs="${r.observations ?? ''}">✏️ Editar</button>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-delete" data-id="${r.id}">🗑️ Apagar</button>
                    </div>
                `;
                return li;
            }

            // role change handler -> populate indicators
            roleSelect.addEventListener('change', () => {
                const roleKey = roleSelect.value;
                populateIndicatorsForRole(roleKey);
            });

            // Observations counter
            if (observationsEl) {
                obsCountEl.textContent = (observationsEl.value || '').length;
                observationsEl.addEventListener('input', () => {
                    const len = observationsEl.value.length;
                    obsCountEl.textContent = len;
                });
            }

            // validate rent/pot by rank (1..15)
            function validateRentPotShowError() {
                const rentParsed = parseSelectLabelValue(rentSelect);
                const potParsed = parseSelectLabelValue(potSelect);

                const rentRank = rentParsed.rank;
                const potRank = potParsed.rank;

                // only compare if both selected
                if ((rentRank !== null) && (potRank !== null)) {
                    // rule: rent must NOT be > pot (i.e., rent cannot be better than pot)
                    if (rentRank > potRank) {
                        rentPotError.style.display = 'block';
                        headerBtn.disabled = true;
                        return false;
                    } else {
                        rentPotError.style.display = 'none';
                        headerBtn.disabled = false;
                        return true;
                    }
                }

                rentPotError.style.display = 'none';
                headerBtn.disabled = false;
                return true;
            }

            [rentSelect, potSelect].forEach(el => {
                if (!el) return;
                el.addEventListener('change', () => {
                    // update hidden inputs immediately for UX
                    const rentParsed = parseSelectLabelValue(rentSelect);
                    const potParsed = parseSelectLabelValue(potSelect);

                    if (rentParsed.rank) {
                        rentScoreInput.value = rentParsed.rank;
                        rentNormalizedInput.value = rentParsed.normalized;
                    } else {
                        rentScoreInput.value = '';
                        rentNormalizedInput.value = '';
                    }
                    if (potParsed.rank) {
                        potScoreInput.value = potParsed.rank;
                        potNormalizedInput.value = potParsed.normalized;
                    } else {
                        potScoreInput.value = '';
                        potNormalizedInput.value = '';
                    }

                    validateRentPotShowError();
                });
            });

            // header button toggle/submit with extra check for rent <= pot
            headerBtn.addEventListener('click', () => {
                const visible = formContainer.style.display !== 'none' && formContainer.style.display !== '';
                if (!visible) {
                    // abrir form para criação
                    formContainer.style.display = 'block';
                    headerBtn.textContent = 'Guardar Relatório';
                    headerBtn.classList.remove('btn-outline-light');
                    headerBtn.classList.add('btn-success');
                    form.reset();
                    idInput.value = '';
                    methodInput.value = 'POST';
                    indicatorsContainer.style.display = 'none';
                    indicatorsContent.innerHTML = '';
                    selectedCountEl.textContent = 0;
                    minRequiredText.textContent = 10;
                    currentMinRequired = 10;

                    obsCountEl.textContent = 0;
                    rentPotError.style.display = 'none';
                    rentScoreInput.value = '';
                    potScoreInput.value = '';
                    rentNormalizedInput.value = '';
                    potNormalizedInput.value = '';
                    headerBtn.disabled = false;
                    window.scrollTo({ top: formContainer.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
                } else {
                    // já está aberto: faz validações e submete
                    const roleKey = roleSelect.value;
                    if (!roleKey) {
                        alert('Por favor selecione uma função antes de guardar o relatório.');
                        return;
                    }
                    const indicators = window.indicatorsByRole[roleKey] || [];
                    const minRequired = Math.min(10, indicators.length === 0 ? 10 : indicators.length);
                    const selectedIndicators = collectSelectedIndicators();
                    if (selectedIndicators.length < minRequired) {
                        alert(`Tem de escolher pelo menos ${minRequired} indicadores para a função selecionada antes de guardar o relatório.`);
                        return;
                    }

                    const rentParsed = parseSelectLabelValue(rentSelect);
                    const potParsed = parseSelectLabelValue(potSelect);

                    if (!rentParsed.rank || !potParsed.rank) {
                        alert('Selecione rentabilidade e potencial antes de guardar (ambos são obrigatórios).');
                        return;
                    }

                    // valida pelo rank (1..15)
                    if (rentParsed.rank > potParsed.rank) {
                        alert('A rentabilidade não pode ser superior ao potencial (segundo a ordem definida). Ajuste os valores antes de guardar.');
                        return;
                    }

                    headerBtn.disabled = true;
                    form.requestSubmit();
                    setTimeout(()=> headerBtn.disabled = false, 1000);
                }
            });

            btnFormCancel.addEventListener('click', () => {
                form.reset();
                idInput.value = '';
                methodInput.value = 'POST';
                formContainer.style.display = 'none';
                headerBtn.textContent = 'Criar Relatório';
                headerBtn.classList.remove('btn-success','btn-warning');
                headerBtn.classList.add('btn-outline-light');
                rentPotError.style.display = 'none';
                rentScoreInput.value = '';
                potScoreInput.value = '';
                rentNormalizedInput.value = '';
                potNormalizedInput.value = '';
            });

            // submit handler (AJAX) — sempre POST + _method override quando for update
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const roleKey = roleSelect.value;
                if (!roleKey) {
                    alert('Escolha a função antes de guardar.');
                    return;
                }
                const indicators = window.indicatorsByRole[roleKey] || [];
                const minRequired = Math.min(10, indicators.length === 0 ? 10 : indicators.length);
                const selectedIndicators = collectSelectedIndicators();
                if (selectedIndicators.length < minRequired) {
                    alert(`Tem de escolher pelo menos ${minRequired} indicadores para a função selecionada antes de guardar o relatório.`);
                    return;
                }

                const rentParsed = parseSelectLabelValue(rentSelect);
                const potParsed = parseSelectLabelValue(potSelect);

                if (!rentParsed.rank || !potParsed.rank) {
                    alert('Selecione rentabilidade e potencial antes de guardar (ambos são obrigatórios).');
                    return;
                }

                if (rentParsed.rank > potParsed.rank) {
                    alert('A rentabilidade não pode ser superior ao potencial.');
                    return;
                }

                const id = idInput.value;
                const url = id ? `/players/${playerId}/reports/${id}` : `/players/${playerId}/reports`;
                const formData = new FormData(form);

                // garantir que o _method está definido conforme a intenção
                if (methodInput && methodInput.value) {
                    formData.set('_method', methodInput.value);
                }

                const indicatorsObj = {};
                selectedIndicators.forEach(si => indicatorsObj[si.label] = si.value);
                formData.set('indicators_json', JSON.stringify(indicatorsObj));

                // set fields: label, score (1..15), normalized (1..5)
                if (rentParsed.label !== null) {
                    formData.set('scoutalent_rentabilidade_label', rentParsed.label);
                    formData.set('scoutalent_rentabilidade_score', rentParsed.rank);
                    formData.set('scoutalent_rentabilidade', rentParsed.normalized); // legacy numeric <=5
                }
                if (potParsed.label !== null) {
                    formData.set('scoutalent_potencial_label', potParsed.label);
                    formData.set('scoutalent_potencial_score', potParsed.rank);
                    formData.set('scoutalent_potencial', potParsed.normalized); // legacy numeric <=5
                }

                const obsVal = (formData.get('observations') || '').toString();
                if (obsVal.length > 250) {
                    alert('Observações não pode exceder 250 caracteres.');
                    return;
                }

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With':'XMLHttpRequest'
                        },
                        body: formData
                    });

                    let data;
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) data = await res.json();
                    else {
                        const txt = await res.text();
                        console.error('Resposta não-JSON do servidor:', txt);
                        alert('Resposta inesperada do servidor. Ver console.');
                        return;
                    }

                    if (!data.success) {
                        if (data.errors) {
                            const msgs = Object.values(data.errors).flat().join('\n');
                            alert('Erros:\n' + msgs);
                        } else {
                            alert(data.message || 'Erro ao guardar relatório.');
                        }
                        return;
                    }

                    const r = data.report;
                    // Build client representation: prefer server-returned label/score if present, else use submitted values
                    const clientIndicators = (r.indicators && Object.keys(r.indicators || {}).length) ? r.indicators : indicatorsObj;
                    const clientR = {
                        id: r.id,
                        report_date: r.report_date ? r.report_date : (formData.get('report_date') || null),
                        scoutalent_rentabilidade: (r.scoutalent_rentabilidade !== undefined && r.scoutalent_rentabilidade !== null) ? r.scoutalent_rentabilidade : formData.get('scoutalent_rentabilidade'),
                        scoutalent_potencial: (r.scoutalent_potencial !== undefined && r.scoutalent_potencial !== null) ? r.scoutalent_potencial : formData.get('scoutalent_potencial'),
                        scoutalent_rentabilidade_label: r.scoutalent_rentabilidade_label ?? formData.get('scoutalent_rentabilidade_label') ?? '',
                        scoutalent_potencial_label: r.scoutalent_potencial_label ?? formData.get('scoutalent_potencial_label') ?? '',
                        scoutalent_rentabilidade_score: r.scoutalent_rentabilidade_score ?? formData.get('scoutalent_rentabilidade_score') ?? '',
                        scoutalent_potencial_score: r.scoutalent_potencial_score ?? formData.get('scoutalent_potencial_score') ?? '',
                        observations: r.observations ?? formData.get('observations') ?? '',
                        author: r.author ?? formData.get('author') ?? '{{ Auth::user()->name }}',
                        role: r.role ?? formData.get('role') ?? '',
                        indicators: clientIndicators
                    };

                    if (id) {
                        const idx = reportsData.findIndex(x => String(x.id) === String(clientR.id));
                        if (idx !== -1) {
                            reportsData[idx] = clientR;
                            const existingLi = list.querySelector(`li[data-id="${clientR.id}"]`);
                            if (existingLi) list.replaceChild(renderReportLi(clientR), existingLi);
                        } else {
                            reportsData.unshift(clientR);
                            list.prepend(renderReportLi(clientR));
                        }
                    } else {
                        reportsData.unshift(clientR);
                        const noData = document.getElementById('scout-no-data');
                        if (noData) noData.remove();
                        if (!document.getElementById('scoutalentChart')) {
                            const scBody = document.getElementById('scout-card-body');
                            if (scBody) scBody.innerHTML = '<div class="chart-sm mx-auto"><canvas id="scoutalentChart" height="220"></canvas></div>';
                        }
                        list.prepend(renderReportLi(clientR));
                    }

                    form.reset();
                    idInput.value = '';
                    methodInput.value = 'POST';
                    indicatorsContainer.style.display = 'none';
                    formContainer.style.display = 'none';
                    headerBtn.textContent = 'Criar Relatório';
                    headerBtn.classList.remove('btn-success','btn-warning');
                    headerBtn.classList.add('btn-outline-light');
                    rentPotError.style.display = 'none';
                    rentScoreInput.value = '';
                    potScoreInput.value = '';
                    rentNormalizedInput.value = '';
                    potNormalizedInput.value = '';

                    if (scoutChart) { scoutChart.destroy(); scoutChart = null; }
                    initChartIfNeeded();
                    updateAveragesUI();
                } catch (err) {
                    console.error(err);
                    alert('Erro ao guardar relatório (ver console).');
                }
            });

            // edit/delete handlers
            list.addEventListener('click', (e) => {
                const editBtn = e.target.closest ? e.target.closest('.btn-edit') : null;
                if (editBtn) {
                    const reportId = editBtn.dataset.id;
                    let r = null;
                    if (editBtn.dataset.report) {
                        try {
                            r = JSON.parse(editBtn.dataset.report);
                        } catch (err) {
                            r = reportsData.find(x => String(x.id) === String(reportId)) || null;
                        }
                    } else {
                        r = reportsData.find(x => String(x.id) === String(reportId)) || null;
                    }

                    formContainer.style.display = 'block';
                    headerBtn.textContent = 'Atualizar Relatório';
                    headerBtn.classList.remove('btn-outline-light','btn-success');
                    headerBtn.classList.add('btn-warning');

                    idInput.value = reportId;
                    methodInput.value = 'PUT';

                    // preencher campos
                    const dateFromDataset = editBtn.dataset.date || (r && r.report_date ? r.report_date : '');
                    document.getElementById('report_date').value = dateFromDataset || '';

                    document.getElementById('observations').value = r && r.observations ? r.observations : '';
                    obsCountEl.textContent = (r && r.observations) ? r.observations.length : 0;

                    // set selects using label (prefer label)
                    const rentLabel = r && (r.scoutalent_rentabilidade_label || r.scoutalent_rentabilidade) ? (r.scoutalent_rentabilidade_label || r.scoutalent_rentabilidade) : '';
                    const potLabel = r && (r.scoutalent_potencial_label || r.scoutalent_potencial) ? (r.scoutalent_potencial_label || r.scoutalent_potencial) : '';

                    if (rentLabel) document.getElementById('rent').value = rentLabel;
                    else document.getElementById('rent').value = '';

                    if (potLabel) document.getElementById('pot').value = potLabel;
                    else document.getElementById('pot').value = '';

                    // populate hidden inputs for edit
                    // if the label exists we can parse it; fallback to server score if present
                    const rentParsed = parseSelectLabelValue(rentSelect);
                    const potParsed = parseSelectLabelValue(potSelect);
                    rentScoreInput.value = (r && r.scoutalent_rentabilidade_score) ? r.scoutalent_rentabilidade_score : (rentParsed.rank || '');
                    rentNormalizedInput.value = (r && (r.scoutalent_rentabilidade !== undefined && r.scoutalent_rentabilidade !== null)) ? r.scoutalent_rentabilidade : (rentParsed.normalized || '');
                    potScoreInput.value = (r && r.scoutalent_potencial_score) ? r.scoutalent_potencial_score : (potParsed.rank || '');
                    potNormalizedInput.value = (r && (r.scoutalent_potencial !== undefined && r.scoutalent_potencial !== null)) ? r.scoutalent_potencial : (potParsed.normalized || '');

                    if (r && r.role) {
                        roleSelect.value = r.role;
                        populateIndicatorsForRole(r.role);
                        if (r.indicators && typeof r.indicators === 'object') {
                            // depois de popular os inputs, marcar os radios conforme r.indicators (label => value)
                            setTimeout(() => {
                                const groups = indicatorsContent.querySelectorAll('.indicator-group');
                                groups.forEach(g => {
                                    const labelEl = g.querySelector('.indicator-label');
                                    if (!labelEl) return;
                                    const labelText = labelEl.textContent.trim();
                                    if (r.indicators[labelText] !== undefined) {
                                        const val = r.indicators[labelText];
                                        const input = g.querySelector(`input[type="radio"][value="${val}"]`);
                                        if (input) input.checked = true;
                                    }
                                });
                                updateSelectedCount();
                            }, 80);
                        }
                    } else {
                        // função vazia: mostramos todos indicadores e marcamos os existentes (se houver)
                        roleSelect.value = '';
                        populateIndicatorsForRole('');
                        if (r && r.indicators && typeof r.indicators === 'object') {
                            setTimeout(() => {
                                const groups = indicatorsContent.querySelectorAll('.indicator-group');
                                groups.forEach(g => {
                                    const labelEl = g.querySelector('.indicator-label');
                                    if (!labelEl) return;
                                    const labelText = labelEl.textContent.trim();
                                    if (r.indicators[labelText] !== undefined) {
                                        const val = r.indicators[labelText];
                                        const input = g.querySelector(`input[type="radio"][value="${val}"]`);
                                        if (input) input.checked = true;
                                    }
                                });
                                updateSelectedCount();
                            }, 80);
                        }
                    }

                    btnFormCancel.classList.remove('d-none');
                    validateRentPotShowError();
                    window.scrollTo({ top: formContainer.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
                    return;
                }

                // delete
                const delBtn = e.target.closest ? e.target.closest('.btn-delete') : null;
                if (delBtn) {
                    if (!confirm('Tem a certeza que quer eliminar este relatório?')) return;
                    const id = delBtn.dataset.id;
                    fetch(`/players/${playerId}/reports/${id}`, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}
                    }).then(async res => {
                        const ct = res.headers.get('content-type') || '';
                        if (ct.includes('application/json')) return res.json();
                        const txt = await res.text();
                        console.error('Resposta delete não-JSON:', txt);
                        throw new Error('Resposta inesperada');
                    }).then(data => {
                        if (data.success) {
                            const li = list.querySelector(`li[data-id="${id}"]`);
                            if (li) li.remove();
                            reportsData = reportsData.filter(r => String(r.id) !== String(id));
                            updateAveragesUI();
                            if (scoutChart) { scoutChart.destroy(); scoutChart = null; }
                            if (reportsData.length) initChartIfNeeded();
                            else {
                                const scBody = document.getElementById('scout-card-body');
                                if (scBody) scBody.innerHTML = '<p class="text-muted fst-italic mb-0 text-center py-3" id="scout-no-data">Sem dados suficientes para gerar o gráfico — crie relatórios para ver indicadores.</p>';
                            }
                        } else {
                            alert('Erro ao apagar relatório.');
                        }
                    }).catch(err => {
                        console.error(err);
                        alert('Erro ao apagar relatório.');
                    });
                }
            });

            // averages: compute using normalized legacy (1..5) when available, else compute from score
            function computeAverages() {
                if (!reportsData.length) return { rent: 0, pot: 0 };
                const rent = reportsData.reduce((s, r) => {
                    if (r.scoutalent_rentabilidade !== undefined && r.scoutalent_rentabilidade !== null) return s + (parseFloat(r.scoutalent_rentabilidade) || 0);
                    if (r.scoutalent_rentabilidade_score) return s + Math.ceil(Number(r.scoutalent_rentabilidade_score)/3);
                    return s + 0;
                }, 0) / reportsData.length;

                const pot = reportsData.reduce((s, r) => {
                    if (r.scoutalent_potencial !== undefined && r.scoutalent_potencial !== null) return s + (parseFloat(r.scoutalent_potencial) || 0);
                    if (r.scoutalent_potencial_score) return s + Math.ceil(Number(r.scoutalent_potencial_score)/3);
                    return s + 0;
                }, 0) / reportsData.length;

                return { rent, pot };
            }
            function updateAveragesUI() {
                const av = computeAverages();
                if (avgRentBadge) {
                    avgRentBadge.textContent = (isNaN(av.rent) ? 0 : av.rent).toFixed(2);
                    avgRentBadge.className = 'badge ' + (av.rent >= 0 ? 'bg-success' : 'bg-danger');
                }
                if (avgPotBadge) {
                    avgPotBadge.textContent = (isNaN(av.pot) ? 0 : av.pot).toFixed(2);
                    avgPotBadge.className = 'badge ' + (av.pot >= 0 ? 'bg-primary' : 'bg-warning text-dark');
                }
            }
            updateAveragesUI();

            if (document.getElementById('scoutalentChart') && reportsData.length > 0) initChartIfNeeded();
        });
    </script>

    <style>
        .fade-in { transition: opacity .5s ease-in; opacity: 1; }

        /* tamanho um pouco maior do gráfico */
        .chart-sm { max-width: 760px; }
        .chart-sm canvas { width: 100% !important; height: 220px !important; display:block; }

        /* estilos para o layout em coluna dos indicadores */
        #indicatorsContent .indicator-group { border-bottom: 1px dashed rgba(0,0,0,0.04); padding-bottom: .6rem; }
        #indicatorsContent .indicator-label { font-size: .95rem; }
        #indicatorsContent .form-check { margin-right: .45rem; }
        @media (max-width: 767.98px) {
            /* mobile: mantém empilhado com bom espaçamento */
            #indicatorsContent .indicator-group { padding-bottom: .8rem; }
        }
    </style>
@endsection
