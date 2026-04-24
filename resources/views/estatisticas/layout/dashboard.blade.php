{{-- resources/views/estatisticas/layout/create.blade.php --}}
@extends('estatisticas.layout.app')

@section('main-content')
    @php
        use Carbon\Carbon;

        // entradas esperadas do controller
        $countries = $countries ?? [];
        $seasons = $seasons ?? [];
        $leagueId = $leagueId ?? null;
        $season = $season ?? now()->year;
        $country = $country ?? null;
        $fixtureId = $fixtureId ?? null;
        $teamId = $teamId ?? null;
        $playerId = $playerId ?? null;

        $currentYear = now()->year;
        $years = $years ?? range($currentYear, $currentYear - 5);
        $selectedSeason = old('season', $season);

        /**
         * ✅ Evita "Array to string conversion" / "htmlspecialchars() expects parameter 1 to be string"
         * quando a API devolve value como array (ex.: {total, percentage}) ou object.
         */
        $fmtStatValue = function ($v) {
            if ($v === null || $v === '') return '-';

            if (is_bool($v)) return $v ? 'Sim' : 'Não';

            if (is_numeric($v)) return $v;

            if (is_array($v)) {
                // padrão comum: {total: X, percentage: Y}
                if (array_key_exists('total', $v) || array_key_exists('percentage', $v)) {
                    $total = $v['total'] ?? null;
                    $pct   = $v['percentage'] ?? null;

                    $parts = [];
                    if ($total !== null && $total !== '') $parts[] = $total;
                    if ($pct !== null && $pct !== '') $parts[] = $pct;

                    return count($parts) ? implode(' (', $parts) . (count($parts) > 1 ? ')' : '') : '-';
                }

                return json_encode($v, JSON_UNESCAPED_UNICODE);
            }

            if (is_object($v)) {
                return json_encode($v, JSON_UNESCAPED_UNICODE);
            }

            return (string) $v;
        };
    @endphp

    {{-- estilo local (pode mover para CSS global) --}}
    <style>
        .country-flag { width:20px; height:auto; margin-right:8px; vertical-align:middle; }
        .league-logo { width:28px; height:28px; object-fit:contain; margin-right:8px; }
        .badge-muted { background: #f1f3f5; color:#333; border-radius: 6px; padding: 3px 6px; font-size: 0.8rem; }
        .accordion-button { padding: 6px 12px; font-size: .95rem; }
        .list-group-item { cursor:pointer; font-size:.9rem; }
        .player-photo { width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:8px; }
        .player-card { cursor:pointer; transition:.2s; }
        .player-card:hover { background:#f8f9fa; }
        .small-muted { color:#6c757d; font-size:.9rem; }
        .spinner-inline { width:1rem;height:1rem;border-width:.15rem;vertical-align:middle;margin-right:.4rem; }
        .player-basic dt { font-weight:600; }
        .team-selected { box-shadow: 0 0 0 2px rgba(220,53,69,0.08); border-color: #dc3545 !important; }
        .alert-warning.text-white { background: linear-gradient(0deg,#ffb74d,#ff9800); color:#fff; border:0; }
        .team-stat-list li span:first-child { display:inline-block; max-width:70%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .sync-meta { font-size: .85rem; }
        .today-badge { background:#198754; color:#fff; border-radius:6px; font-size:.7rem; padding:2px 6px; }
        /* toast custom */
        #syncToastContainer { z-index: 10850; }
    </style>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-3">
            <h1 class="h5 mb-0">📊 Estatísticas</h1>
            <a href="{{ route('admin.estatisticas.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
        </div>

        <div class="row g-3">
            {{-- Coluna esquerda: Países & Ligas --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Países & Ligas</div>
                        <div>
                            <select id="filterSeasonBox" class="form-select form-select-sm" style="width:120px;">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ (int)$y === (int)$selectedSeason ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <input id="countrySearch" class="form-control form-control-sm mb-2" placeholder="Procurar país..." type="search">

                        <div class="accordion" id="countriesAccordion" style="max-height:56vh; overflow:auto;">
                            @foreach($countries as $c)
                                @php
                                    $countryName = data_get($c,'name', $c);
                                    $countryCode = data_get($c,'code', '');
                                    $collapseId = 'country-' . \Illuminate\Support\Str::slug($countryName);
                                @endphp

                                <div class="accordion-item country-item" data-country-name="{{ strtolower($countryName) }}">
                                    <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}" aria-expanded="false"
                                                aria-controls="{{ $collapseId }}" data-country="{{ $countryName }}">
                                            @if($countryCode)
                                                <img src="https://media.api-sports.io/flags/{{ strtolower($countryCode) }}.svg" alt="{{ $countryCode }}" class="country-flag">
                                            @endif
                                            {{ $countryName }}
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="heading-{{ $collapseId }}" data-bs-parent="#countriesAccordion">
                                        <div class="accordion-body p-2">
                                            <div class="leagues-list small text-muted">Clique para carregar ligas…</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coluna direita: Estatísticas --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="mb-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold mb-1">Liga selecionada:</div>
                                <span id="selectedLeagueBadge" class="badge bg-light text-dark">
                                    {{ $leagueId ? "ID: $leagueId" : 'Nenhuma' }}
                                </span>
                                <span id="selectedCountryBadge" class="badge bg-light text-dark d-none"></span>
                            </div>

                            <div class="text-end small-muted">
                                <small>Filtro época:</small>
                                <div><small class="text-muted">Mostrando: {{ $selectedSeason }}</small></div>
                            </div>
                        </div>

                        {{-- Jogos / Fixtures --}}
                        @if(!empty($fixtures) && is_array($fixtures))
                            <div class="mb-3">
                                <h6><i class="bi bi-calendar-event"></i> Escolhe um jogo</h6>
                                <div class="list-group" id="fixturesList">
                                    @foreach($fixtures as $f)
                                        @php
                                            // evita sobrescrever $fixtureId global - usa local
                                            $fixtureLocalId = data_get($f, 'fixture.id', $f['id'] ?? null);
                                            $homeName = data_get($f, 'teams.home.name', $f['home'] ?? '');
                                            $awayName = data_get($f, 'teams.away.name', $f['away'] ?? '');
                                            $homeLogo = data_get($f, 'teams.home.logo', $f['home_logo'] ?? '');
                                            $awayLogo = data_get($f, 'teams.away.logo', $f['away_logo'] ?? '');
                                            $dateRaw = data_get($f, 'fixture.date', $f['date'] ?? null);
                                            $displayDate = $dateRaw ? Carbon::parse($dateRaw)->format('d/m') : ($f['date'] ?? '');
                                            $isToday = $dateRaw ? Carbon::parse($dateRaw)->isToday() : false;
                                        @endphp

                                        <a href="?country={{ urlencode($country ?? '') }}&season={{ $season }}&league={{ $leagueId }}&fixture={{ $fixtureLocalId }}"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ ($fixtureId && $fixtureId == $fixtureLocalId) ? 'active' : '' }}"
                                           data-fixture-id="{{ $fixtureLocalId }}">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $homeLogo }}" class="me-2" style="width:22px;height:22px;" onerror="this.style.display='none'">
                                                {{ $homeName }}
                                                <span class="mx-1">vs</span>
                                                <img src="{{ $awayLogo }}" class="me-2" style="width:22px;height:22px;" onerror="this.style.display='none'">
                                                {{ $awayName }}
                                            </div>
                                            <div class="text-end">
                                                @if($isToday)
                                                    <span class="today-badge">Hoje</span>
                                                @endif
                                                <small class="text-muted ms-2">{{ $displayDate }}</small>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <div class="alert alert-secondary text-white">Sem jogos (fixtures) disponíveis para a liga/época selecionada.</div>
                            </div>
                        @endif

                        {{-- Estatísticas do jogo --}}
                        @if(!empty($matchStats) && is_array($matchStats))
                            <hr>
                            <h5>📊 Estatísticas do Jogo</h5>
                            <div class="row" id="matchStatsWrapper">
                                @foreach($matchStats as $teamStats)
                                    @php $teamIdForPanel = data_get($teamStats, 'team.id'); @endphp
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 mb-2 team-stats-card" data-team-id="{{ $teamIdForPanel }}">
                                            <div class="fw-bold mb-2 d-flex align-items-center">
                                                <img src="{{ data_get($teamStats, 'team.logo') }}" class="me-1" style="width:24px; height:24px;" onerror="this.style.display='none'">
                                                {{ data_get($teamStats, 'team.name') }}
                                            </div>

                                            <ul class="list-unstyled small team-stat-list">
                                                @foreach((array) data_get($teamStats, 'statistics', []) as $stat)
                                                    @php
                                                        $type = data_get($stat, 'type', '-');
                                                        $val  = data_get($stat, 'value', null);
                                                    @endphp
                                                    <li class="d-flex justify-content-between border-bottom py-1">
                                                        <span class="text-truncate">{{ $type }}</span>
                                                        <span>{{ $fmtStatValue($val) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>

                                            <div class="d-flex gap-2 mt-2">
                                                <button type="button"
                                                        data-team-id="{{ $teamIdForPanel }}"
                                                        class="btn btn-outline-danger btn-sm w-50 btn-load-team-players">
                                                    <i class="bi bi-bar-chart"></i> Ver estatísticas da equipa
                                                </button>

                                                <button type="button"
                                                        data-team-id="{{ $teamIdForPanel }}"
                                                        data-league-id="{{ $leagueId ?? '' }}"
                                                        class="btn btn-outline-primary btn-sm w-50 btn-sync-team">
                                                    <i class="bi bi-cloud-arrow-up"></i> Sincronizar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Estatísticas da Equipa (carregado pelo servidor quando ?team=) --}}
                        @if(!empty($teamStatsData))
                            <hr>
                            <h5>📘 Estatísticas da Equipa: {{ data_get($teamStatsData,'team.name') }}</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-2">
                                        <strong>Forma recente:</strong>
                                        <div class="d-flex gap-1 mb-2 flex-wrap">
                                            @php
                                                $formStr = (string) data_get($teamStatsData,'form','');
                                                $lastChars = array_slice(str_split($formStr), -10);
                                            @endphp
                                            @foreach($lastChars as $sym)
                                                @php
                                                    $color = $sym === 'W' ? 'success' : ($sym === 'L' ? 'danger' : 'secondary');
                                                    $title = $sym === 'W' ? 'Vitória' : ($sym === 'L' ? 'Derrota' : 'Empate');
                                                @endphp
                                                <span class="badge bg-{{ $color }}" title="{{ $title }}">{{ $sym }}</span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">🟩 Vitória &nbsp; 🟥 Derrota &nbsp; 🟪 Empate</small>
                                        <div>Total Jogos: {{ data_get($teamStatsData,'fixtures.played.total',0) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-2">
                                        <strong>Golos:</strong>
                                        <div>Média Marcados: {{ data_get($teamStatsData,'goals.for.average.total','-') }}</div>
                                        <div>Média Sofridos: {{ data_get($teamStatsData,'goals.against.average.total','-') }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Jogadores (vai ser preenchido/atualizado ao carregar equipa) --}}
                        <div id="playersWrapper">
                            @if(!empty($playersData) && empty($matchStats))
                                <h6 class="mt-2"><i class="bi bi-people-fill"></i> Jogadores</h6>
                                <div id="playersList" class="row row-cols-2 row-cols-md-3 g-2 mb-3">
                                    @foreach($playersData as $p)
                                        @php $pl = $p['player'] ?? []; @endphp
                                        <div class="col player-col" data-player-id="{{ $pl['id'] ?? '' }}">
                                            <a href="?country={{ urlencode($country ?? '') }}&league={{ $leagueId }}&season={{ $season }}&fixture={{ $fixtureId }}&team={{ $teamId }}&player={{ $pl['id'] }}"
                                               class="list-group-item list-group-item-action d-flex align-items-center player-card {{ $playerId == ($pl['id'] ?? null) ? 'active' : '' }}"
                                               data-player-id="{{ $pl['id'] ?? '' }}">
                                                <img src="{{ $pl['photo'] ?? 'https://via.placeholder.com/40x40?text=👤' }}" class="player-photo" onerror="this.src='https://via.placeholder.com/40x40?text=👤'">
                                                <div>
                                                    <div class="fw-semibold">{{ $pl['name'] }}</div>
                                                    <div class="text-muted small">
                                                        {{ $pl['age'] ?? '-' }} anos — {{ $pl['position'] ?? '-' }}
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Estatísticas do Jogador (container substituível via AJAX) --}}
                        <div id="playerStatsContainer">
                            @if(!empty($playerId))
                                <h6><i class="bi bi-person-lines-fill"></i> Estatísticas do Jogador</h6>
                                @if(!empty($playerStats) && count($playerStats) > 0)
                                    @foreach($playerStats as $ps)
                                        @php
                                            $pl = data_get($ps, 'player');
                                            $stats = data_get($ps, 'statistics.0');
                                            $birthDate = data_get($pl, 'birth.date') ?: null;
                                        @endphp
                                        @if($pl)
                                            <div class="border rounded p-3 mb-3">
                                                <div class="d-flex align-items-center mb-3">
                                                    <img src="{{ $pl['photo'] ?? 'https://via.placeholder.com/40x40?text=👤' }}" class="player-photo me-2" onerror="this.src='https://via.placeholder.com/40x40?text=👤'">
                                                    <div>
                                                        <strong style="font-size:1.05rem">{{ $pl['name'] }}</strong><br>
                                                        <span class="text-muted small">{{ $pl['age'] ?? '-' }} anos — {{ $pl['nationality'] ?? '-' }}</span>
                                                    </div>
                                                </div>

                                                @if($stats)
                                                    <ul class="list-unstyled small mb-0">
                                                        <li><strong>Posição:</strong> {{ data_get($stats,'games.position') }}</li>
                                                        <li><strong>Jogos:</strong> {{ data_get($stats,'games.appearences',0) }}</li>
                                                        <li><strong>Minutos:</strong> {{ data_get($stats,'games.minutes',0) }}</li>
                                                        <li><strong>Golos:</strong> {{ data_get($stats,'goals.total',0) }}</li>
                                                        <li><strong>Assistências:</strong> {{ data_get($stats,'goals.assists',0) }}</li>
                                                        <li><strong>Amarelos:</strong> {{ data_get($stats,'cards.yellow',0) }}</li>
                                                        <li><strong>Vermelhos:</strong> {{ data_get($stats,'cards.red',0) }}</li>
                                                    </ul>
                                                @else
                                                    <div class="alert alert-warning text-white mb-3" role="alert">
                                                        ⚠️ Sem estatísticas disponíveis para este jogador na época {{ $season }}.
                                                    </div>

                                                    <dl class="row player-basic small mb-0">
                                                        <div class="col-md-6">
                                                            <dt>Nome</dt><dd>{{ $pl['name'] }}</dd>
                                                            <dt>Idade</dt><dd>{{ $pl['age'] ?? '-' }} anos</dd>
                                                            <dt>Nacionalidade</dt><dd>{{ $pl['nationality'] ?? '-' }}</dd>
                                                        </div>
                                                        <div class="col-md-6">
                                                            @if($birthDate)
                                                                <dt>Nascimento</dt><dd>{{ \Carbon\Carbon::parse($birthDate)->format('d/m/Y') }}</dd>
                                                            @endif
                                                            <dt>Altura</dt><dd>{{ $pl['height'] ? $pl['height'] . (is_numeric($pl['height']) ? ' cm' : '') : '-' }}</dd>
                                                            <dt>Peso</dt><dd>{{ $pl['weight'] ? $pl['weight'] . (is_numeric($pl['weight']) ? ' kg' : '') : '-' }}</dd>
                                                            <dt>Posição</dt><dd>{{ $pl['position'] ?? '-' }}</dd>
                                                            @if(!empty($pl['birth']['place']))
                                                                <dt>Naturalidade</dt><dd>{{ $pl['birth']['place'] }}</dd>
                                                            @endif
                                                        </div>
                                                    </dl>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="alert alert-warning text-white">
                                        ⚠️ Sem estatísticas disponíveis para este jogador na época {{ $season }}.
                                    </div>
                                @endif
                            @else
                                <div id="playerStatsPlaceholder" class="border rounded p-3 text-muted small">
                                    Selecciona um jogador para ver o perfil e estatísticas sem sair da página.
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TOAST container (Bootstrap 5) --}}
    <div id="syncToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:10850;">
        {{-- toasts são injectados dinamicamente --}}
    </div>

    {{-- SCRIPT: AJAX handlers, progressive enhancement, sync, toast feedback --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // helper
            function qs(name) { return new URLSearchParams(window.location.search).get(name); }
            function escapeHtml(unsafe) {
                if (unsafe === undefined || unsafe === null) return '';
                return String(unsafe)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // CSRF token: prefer meta from layout, fallback to inline blade token
            const metaCsrf = document.querySelector('meta[name="csrf-token"]');
            const CSRF_TOKEN = metaCsrf ? metaCsrf.getAttribute('content') : "{{ csrf_token() }}";

            // showToast(type, title, message) using Bootstrap Toast (type: 'success'|'danger'|'info')
            function showToast(type, title, message, autohide = true, delay = 4000) {
                const container = document.getElementById('syncToastContainer');
                if (!container) return;
                const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random()*1000);
                const bgClass = (type === 'success') ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-white text-dark border');
                const icon = (type === 'success') ? '<i class="bi bi-check-circle-fill"></i>' : (type === 'danger' ? '<i class="bi bi-x-circle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>');

                const toastHtml = `
                    <div id="${toastId}" class="toast ${autohide ? '' : 'show'}" role="alert" aria-live="polite" aria-atomic="true" data-bs-autohide="${autohide ? 'true' : 'false'}" data-bs-delay="${delay}">
                      <div class="toast-header ${bgClass}">
                        <strong class="me-auto">${icon} &nbsp; ${escapeHtml(title)}</strong>
                        <small class="text-white-50 ms-2"></small>
                        <button type="button" class="btn-close btn-close-white ms-2 mb-1" data-bs-dismiss="toast" aria-label="Fechar"></button>
                      </div>
                      <div class="toast-body">
                        ${escapeHtml(message)}
                      </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', toastHtml);
                const el = document.getElementById(toastId);
                try {
                    const toast = new bootstrap.Toast(el, { delay });
                    toast.show();
                    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
                } catch (e) {
                    setTimeout(() => el.remove(), delay + 500);
                }
            }

            // 1) filtro de países (live)
            const searchBox = document.getElementById('countrySearch');
            if (searchBox) {
                searchBox.addEventListener('input', function () {
                    const term = this.value.toLowerCase().trim();
                    document.querySelectorAll('.country-item').forEach(item => {
                        const name = item.dataset.countryName || '';
                        item.style.display = (!term || name.includes(term)) ? '' : 'none';
                    });
                });
            }

            // 2) season change
            const seasonBox = document.getElementById('filterSeasonBox');
            if (seasonBox) {
                seasonBox.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    url.searchParams.set('season', this.value);
                    url.searchParams.delete('player');
                    url.searchParams.delete('team');
                    window.location.href = url.toString();
                });
            }

            // 3) carregar ligas por país (AJAX para rota admin)
            document.querySelectorAll('.accordion-collapse').forEach(collapse => {
                collapse.addEventListener('show.bs.collapse', function (ev) {
                    const container = ev.target.querySelector('.leagues-list');
                    if (!container) return;

                    const country = ev.target.previousElementSibling.querySelector('button').dataset.country;
                    const season  = document.getElementById('filterSeasonBox').value;

                    container.innerHTML = '<div class="text-center small text-muted">A carregar ligas…</div>';

                    fetch("{{ route('admin.estatisticas.leaguesByCountry') }}?country=" + encodeURIComponent(country) + "&season=" + encodeURIComponent(season), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.leagues || json.leagues.length === 0) {
                                container.innerHTML = '<div class="text-muted small">Sem ligas disponíveis</div>';
                                return;
                            }

                            let html = '<div class="list-group">';
                            json.leagues.forEach(l => {
                                html += `
                                    <a href="?country=${encodeURIComponent(country)}&season=${encodeURIComponent(season)}&league=${l.id}"
                                       data-league-id="${l.id}"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center league-item">
                                        <div class="d-flex align-items-center">
                                            <img src="${l.logo}" class="league-logo" onerror="this.style.display='none'">
                                            <span>${escapeHtml(l.name)}</span>
                                        </div>
                                        <span class="badge badge-muted">${escapeHtml(l.type ?? '')}</span>
                                    </a>`;
                            });
                            html += '</div>';
                            container.innerHTML = html;
                        })
                        .catch(err => {
                            console.error(err);
                            container.innerHTML = '<div class="text-danger small">Erro ao carregar ligas</div>';
                        });
                });
            });

            // manage active team styling + badge
            let activeTeamId = qs('team') || null;
            function setActiveTeam(teamId, pushHistory = false) {
                activeTeamId = teamId ? String(teamId) : null;
                document.querySelectorAll('.team-stats-card').forEach(card => {
                    if (String(card.dataset.teamId) === String(activeTeamId)) card.classList.add('team-selected');
                    else card.classList.remove('team-selected');
                });

                const badge = document.getElementById('selectedCountryBadge');
                if (badge) {
                    if (activeTeamId) {
                        badge.classList.remove('d-none');
                        badge.textContent = 'Team: ' + activeTeamId;
                    } else {
                        badge.classList.add('d-none');
                    }
                }

                if (pushHistory) {
                    try {
                        const url = new URL(window.location.href);
                        if (teamId) url.searchParams.set('team', teamId);
                        else url.searchParams.delete('team');
                        history.pushState({}, '', url.toString());
                    } catch (e) { console.warn(e); }
                }
            }
            setActiveTeam(activeTeamId, false);

            // 4) Click "Ver estatísticas da equipa" -> ativa equipa e carrega players + team_stats (rota admin)
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-load-team-players');
                if (!btn) return;

                const teamIdLocal = btn.getAttribute('data-team-id');
                const season = document.getElementById('filterSeasonBox').value || "{{ $season }}";
                if (!teamIdLocal) return;

                setActiveTeam(teamIdLocal, true);

                const wrapper = document.getElementById('playersWrapper');
                if (wrapper) wrapper.innerHTML = '<div class="text-center py-3 text-muted"><span class="spinner-border spinner-inline" role="status" aria-hidden="true"></span> A carregar informação da equipa…</div>';

                // adiciona league param se disponível
                const leagueParam = "{{ $leagueId ?? '' }}";
                const url = "{{ route('admin.estatisticas.playersByTeam') }}" + "?team=" + encodeURIComponent(teamIdLocal) + "&season=" + encodeURIComponent(season) + (leagueParam ? "&league=" + encodeURIComponent(leagueParam) : "");

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(json => {
                        const players = json.players || [];
                        const teamStats = json.team_stats || null;
                        let html = '';

                        if (teamStats) {
                            html += `<div id="teamPanelAjax" class="mb-3">
                                        <h6 class="mb-2"><i class="bi bi-book-half"></i> Estatísticas da Equipa: ${escapeHtml(teamStats.team?.name ?? '')}</h6>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="border rounded p-2">
                                                    <strong>Forma recente:</strong>
                                                    <div class="d-flex gap-1 mb-2 flex-wrap">`;
                            const form = String(teamStats.form ?? '');
                            const last = form.slice(-10).split('');
                            last.forEach(function(ch) {
                                const color = (ch === 'W') ? 'success' : ((ch === 'L') ? 'danger' : 'secondary');
                                const title = (ch === 'W') ? 'Vitória' : ((ch === 'L') ? 'Derrota' : 'Empate');
                                html += `<span class="badge bg-${color}" title="${title}">${escapeHtml(ch)}</span>`;
                            });
                            html += `         </div>
                                                    <small class="text-muted">🟩 Vitória &nbsp; 🟥 Derrota &nbsp; 🟪 Empate</small>
                                                    <div>Total Jogos: ${teamStats.fixtures?.played?.total ?? 0}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-2">
                                                    <strong>Golos:</strong>
                                                    <div>Média Marcados: ${teamStats.goals?.for?.average?.total ?? '-'}</div>
                                                    <div>Média Sofridos: ${teamStats.goals?.against?.average?.total ?? '-'}</div>
                                                </div>
                                            </div>
                                        </div>
                                     </div>`;
                        }

                        if (players.length === 0) {
                            html += '<div class="alert alert-secondary text-white">Sem jogadores disponíveis para esta equipa/época.</div>';
                        } else {
                            html += '<h6 class="mt-2"><i class="bi bi-people-fill"></i> Jogadores</h6>';
                            html += '<div id="playersList" class="row row-cols-2 row-cols-md-3 g-2 mb-3">';
                            players.forEach(function(p) {
                                const pl = p.player || {};
                                const href = new URL(window.location.href);
                                href.searchParams.set('player', pl.id ?? '');
                                href.searchParams.set('team', teamIdLocal);
                                href.searchParams.set('season', season);

                                html += `<div class="col player-col" data-player-id="${pl.id ?? ''}">
                                            <a href="${href.toString()}" class="list-group-item list-group-item-action d-flex align-items-center player-card" data-player-id="${pl.id ?? ''}">
                                                <img src="${pl.photo ?? 'https://via.placeholder.com/40x40?text=👤'}" class="player-photo" onerror="this.src='https://via.placeholder.com/40x40?text=👤'">
                                                <div>
                                                    <div class="fw-semibold">${escapeHtml(pl.name ?? '-')}</div>
                                                    <div class="text-muted small">${pl.age ?? '-'} anos — ${pl.position ?? '-'}</div>
                                                </div>
                                            </a>
                                         </div>`;
                            });
                            html += '</div>';
                        }

                        if (wrapper) wrapper.innerHTML = html;
                    })
                    .catch(err => {
                        console.error(err);
                        if (wrapper) wrapper.innerHTML = '<div class="alert alert-danger">Erro ao carregar informação da equipa.</div>';
                    });
            });

            // 5) Interceptar clique em jogador: fetch da MESMA ROTA com ?player=... e substituir #playerStatsContainer
            document.addEventListener('click', function (e) {
                const el = e.target.closest('.player-card');
                if (!el) return;

                // permite abrir em nova aba com ctrl/meta
                if (e.ctrlKey || e.metaKey || e.shiftKey) return;

                e.preventDefault();

                const href = el.getAttribute('href');
                if (!href) return;

                // define equipa activa se presente no href
                try {
                    const urlObj = new URL(href, window.location.origin);
                    const teamFromHref = urlObj.searchParams.get('team');
                    if (teamFromHref) setActiveTeam(teamFromHref, true);
                } catch (_) {}

                const statsContainer = document.getElementById('playerStatsContainer');
                if (statsContainer) statsContainer.innerHTML = '<div class="text-center py-3 text-muted"><span class="spinner-border spinner-inline" role="status" aria-hidden="true"></span> A carregar estatísticas do jogador…</div>';

                try { history.pushState({}, '', href); } catch (err) {}

                fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(htmlText => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlText, 'text/html');
                        const newStats = doc.getElementById('playerStatsContainer');
                        if (newStats && statsContainer) {
                            statsContainer.innerHTML = newStats.innerHTML;
                            document.querySelectorAll('.player-card').forEach(a => a.classList.remove('active'));
                            el.classList.add('active');
                            statsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            if (statsContainer) statsContainer.innerHTML = '<div class="alert alert-warning">Resposta inesperada do servidor.</div>';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (statsContainer) statsContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar as estatísticas do jogador.</div>';
                    });
            });

            // 6) History popstate: sincroniza team/player quando Back/Forward
            window.addEventListener('popstate', function () {
                const params = new URLSearchParams(window.location.search);
                const teamParam = params.get('team');
                setActiveTeam(teamParam, false);

                const statsContainer = document.getElementById('playerStatsContainer');
                if (!statsContainer) return;

                const playerParam = params.get('player');
                if (playerParam) {
                    statsContainer.innerHTML = '<div class="text-center py-3 text-muted"><span class="spinner-border spinner-inline" role="status" aria-hidden="true"></span> A carregar estatísticas do jogador…</div>';
                    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.text())
                        .then(htmlText => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(htmlText, 'text/html');
                            const newStats = doc.getElementById('playerStatsContainer');
                            if (newStats) statsContainer.innerHTML = newStats.innerHTML;
                        })
                        .catch(()=>{});
                } else {
                    statsContainer.innerHTML = '<div id="playerStatsPlaceholder" class="border rounded p-3 text-muted small">Selecciona um jogador para ver o perfil e estatísticas sem sair da página.</div>';
                    document.querySelectorAll('.player-card').forEach(a => a.classList.remove('active'));
                }
            });

            // 7) Sync team -> envia POST para gravar no BD (rota admin) com feedback via toast
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-sync-team');
                if (!btn) return;

                const teamIdLocal = btn.getAttribute('data-team-id');
                const leagueIdLocal = btn.getAttribute('data-league-id') || null;
                const season = document.getElementById('filterSeasonBox').value || "{{ $season }}";

                if (!teamIdLocal) return;

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-inline" role="status" aria-hidden="true"></span> A sincronizar...';
                fetch("{{ route('admin.estatisticas.syncTeam') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        team: teamIdLocal,
                        league: leagueIdLocal || null,
                        season: season || null
                    })
                })
                    .then(async response => {
                        let json = {};
                        try { json = await response.json(); } catch(_) { json = { ok: false, message: 'Resposta inválida' }; }
                        if (!response.ok || !json.ok) {
                            throw new Error(json.message || 'Erro ao sincronizar.');
                        }

                        const saved = json.saved || {};
                        const card = document.querySelector(`.team-stats-card[data-team-id="${teamIdLocal}"]`);
                        if (card) {
                            let metaDiv = card.querySelector('.sync-meta');
                            if (!metaDiv) {
                                metaDiv = document.createElement('div');
                                metaDiv.className = 'sync-meta small text-muted mt-2';
                                card.appendChild(metaDiv);
                            }
                            const syncedAt = saved.synced_at ?? (new Date()).toLocaleString();
                            const rows = saved.rows_saved ? ` — ${saved.rows_saved} registos` : '';
                            metaDiv.innerHTML = `Sincronizado: <strong>${escapeHtml(syncedAt)}</strong>${rows}`;

                            showToast('success', 'Sincronização concluída', json.message ?? 'Equipa sincronizada com sucesso');

                            card.classList.add('border', 'border-2', 'border-success');
                            setTimeout(() => card.classList.remove('border','border-2','border-success'), 1800);
                        } else {
                            showToast('success', 'Sincronização concluída', json.message ?? 'Equipa sincronizada com sucesso');
                        }

                        btn.innerHTML = '<i class="bi bi-check-circle"></i> Sincronizado';
                        setTimeout(()=>{ btn.innerHTML = originalHtml; btn.disabled = false; }, 1400);
                    })
                    .catch(err => {
                        console.error(err);
                        const errMsg = err?.message || 'Erro ao sincronizar';
                        showToast('danger', 'Erro na sincronização', errMsg);
                        btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Erro';
                        setTimeout(()=>{ btn.innerHTML = originalHtml; btn.disabled = false; }, 2000);
                    });
            });

        });
    </script>
@endsection
