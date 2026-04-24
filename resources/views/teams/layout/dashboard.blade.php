{{-- resources/views/teams/layout/app.blade.php --}}
@extends('teams.layout.app')

@section('main-content')
    @php
        use Illuminate\Support\Facades\Route as R;

        $countries = $countries ?? [];
        $seasons = $seasons ?? null;
        $apiTeams = $apiTeams ?? [];
        $apiError = $apiError ?? null;

        $currentYear = (int) now()->year;
        $years = $seasons && is_iterable($seasons) ? $seasons : range($currentYear, $currentYear - 5);
        $years = array_values(array_unique(array_map('intval', $years)));
        rsort($years);

        $leagueVal = old('league', $league ?? '');
        $seasonVal = old('season', $season ?? $currentYear);

        // 🔹 ROTAS
        $leaguesByCountryRoute = R::has('admin.players.leagues_by_country')
            ? route('admin.players.leagues_by_country')
            : '#';
        $teamsIndexRoute = R::has('admin.teams.index') ? route('admin.teams.index') : '#';
        $teamsSyncRoute = R::has('admin.teams.sync') ? route('admin.teams.sync') : '#';
    @endphp

    <style>
        .country-flag {
            width: 20px;
            height: auto;
            margin-right: 8px;
            vertical-align: middle;
        }

        .leagues-list .list-group-item {
            cursor: pointer;
        }

        .leagues-list .list-group-item:hover {
            background: rgba(13, 110, 253, 0.04);
        }

        .list-group-item.disabled {
            opacity: .55;
            pointer-events: none;
            background: #f8f9fa;
        }

        .controls-row {
            display: flex;
            gap: .5rem;
            align-items: center;
            width: 100%;
        }

        .controls-row .form-control {
            min-width: 0;
            flex: 1;
        }

        #loadingOverlay,
        #syncOverlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
            z-index: 12000;
            background: rgba(0, 0, 0, 0.12);
        }

        #loadingOverlay .box,
        #syncOverlay .box {
            background: #fff;
            padding: 14px 18px;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            align-items: center;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .12);
        }

        .spinner {
            width: 26px;
            height: 26px;
            border: 3px solid rgba(0, 0, 0, .08);
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin .9s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <div class="container py-4">

        {{-- ALERTAS --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show text-white" role="alert">
                <strong>✅ Sucesso:</strong> {!! nl2br(e(session('success'))) !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show text-white" role="alert">
                <strong>❌ Erro:</strong> {!! nl2br(e(session('error'))) !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
            <div>
                <h3 class="mb-0">🏟️ Clubes / Teams
                    <small class="text-muted"> — Liga {{ $leagueVal ?: '—' }} · Época {{ $seasonVal }}</small>
                </h3>
                <div class="small text-muted">Resultados diretos da API — escolhe país → liga ou insere ID manualmente.
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <a href="{{ $teamsIndexRoute }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
        </div>

        <div class="row g-3 mb-3">

            {{-- 🌍 Países & Ligas --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">🌍 Países & Ligas</div>
                        <div>
                            <select id="filterSeasonBox" class="form-select form-select-sm" style="width:120px;">
                                @foreach ($years as $s)
                                    <option value="{{ $s }}" {{ (int) $s === (int) $seasonVal ? 'selected' : '' }}>
                                        {{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mb-2 d-flex gap-2">
                            <input id="countrySearch" class="form-control form-control-sm" placeholder="Procurar país..."
                                type="search">
                            <button id="collapseAllBtn" class="btn btn-sm btn-outline-secondary" type="button"
                                title="Fechar todos">✖</button>
                        </div>

                        <div class="accordion" id="countriesAccordion" style="max-height:56vh; overflow:auto;">
                            @forelse($countries as $c)
                                @php
                                    $countryName = data_get($c, 'name', $c);
                                    $countryCode = data_get($c, 'code', '');
                                    $collapseId = 'country-' . \Illuminate\Support\Str::slug($countryName);
                                @endphp
                                <div class="accordion-item country-item" data-country-name="{{ strtolower($countryName) }}">
                                    <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#{{ $collapseId }}" aria-expanded="false"
                                            aria-controls="{{ $collapseId }}" data-country="{{ $countryName }}">
                                            @if ($countryCode)
                                                <img src="https://media.api-sports.io/flags/{{ strtolower($countryCode) }}.svg"
                                                    alt="{{ $countryCode }}" class="country-flag">
                                            @endif
                                            <span class="ms-1">{{ $countryName }}</span>
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading-{{ $collapseId }}" data-bs-parent="#countriesAccordion">
                                        <div class="accordion-body p-2">
                                            <div class="small text-muted mb-2">Clique numa liga para preencher o campo
                                                "Liga".</div>
                                            <div class="leagues-list" data-country="{{ $countryName }}">
                                                <div class="text-muted small">Clique para carregar ligas deste país…</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted p-2">Lista de países vazia.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ⚽ Equipas --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">

                        {{-- FILTROS --}}
                        <form id="filtersForm" action="{{ $teamsIndexRoute }}" method="GET"
                            class="row g-2 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label">Liga (ID) & Época</label>
                                <div class="d-flex gap-2">
                                    <input name="league" id="leagueInput" type="number" class="form-control"
                                        placeholder="Liga (id)" value="{{ $leagueVal }}">
                                    <select name="season" id="seasonInput" class="form-select" style="width:140px;">
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ (int) $seasonVal === (int) $y ? 'selected' : '' }}>{{ $y }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- hidden: guarda o país seleccionado quando escolhes uma liga --}}
                                <input type="hidden" name="league_country" id="leagueCountryInput" value="">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <div class="controls-row">
                                    <input id="searchInput" name="search" type="text" class="form-control"
                                        placeholder="Procurar equipa..." value="{{ request()->query('search', '') }}">
                                    <button class="btn btn-success" id="btnShow" type="button">📥 Mostrar</button>
                                </div>
                            </div>
                        </form>

                        {{-- BOTÃO SINCRONIZAR --}}
                        <div class="mt-2 d-flex gap-2">
                            <form id="syncForm" action="{{ $teamsSyncRoute }}" method="POST" class="d-inline ms-2">
                                @csrf
                                <input type="hidden" name="league" id="syncLeague" value="">
                                <input type="hidden" name="season" id="syncSeason" value="">
                                {{-- hidden: país também para sync (opcional — útil para validação no controller) --}}
                                <input type="hidden" name="league_country" id="syncLeagueCountry" value="">
                                <button class="btn btn-outline-danger btn-sm" id="btnSync" type="button">🔄
                                    Sincronizar</button>
                            </form>
                            <div class="ms-auto small text-muted">Total (API): <strong
                                    id="totalCount">{{ count($apiTeams) }}</strong></div>
                        </div>

                        {{-- RESULTADOS --}}
                        <div class="mt-3">
                            @if ($apiError)
                                <div class="alert alert-danger text-white fw-semibold"
                                    style="background:#dc3545;border:none;">
                                    {!! nl2br(e($apiError)) !!}
                                </div>
                            @endif


                            @if (!empty($apiTeams))
                                <div class="list-group teams-list" id="teamsList">
                                    @foreach ($apiTeams as $t)
                                        @php $team = $t['team'] ?? $t; @endphp
                                        <div
                                            class="list-group-item d-flex align-items-center justify-content-between team-item">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $team['logo'] ?? "https://media.api-sports.io/football/teams/{$team['id']}.png" }}"
                                                    alt="{{ $team['name'] ?? '—' }}"
                                                    style="width:48px;height:48px;object-fit:contain"
                                                    onerror="this.onerror=null;this.src='https://via.placeholder.com/48?text=⚽'">
                                                <div>
                                                    <div class="team-name"><strong>{{ $team['name'] ?? '—' }}</strong>
                                                    </div>
                                                    <div class="small text-muted">{{ $team['country'] ?? '—' }} ·
                                                        {{ $team['code'] ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted p-3">Insere uma <strong>Liga (ID)</strong> e clica em
                                    <strong>Mostrar</strong> para carregar clubes.</div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- OVERLAYS --}}
    <div id="loadingOverlay">
        <div class="box">
            <div class="spinner"></div>
            <div><strong>A carregar clubes…</strong>
                <div class="small text-muted">Aguarda um momento…</div>
            </div>
        </div>
    </div>

    <div id="syncOverlay">
        <div class="box">
            <div class="spinner"></div>
            <div><strong>Sincronização em curso…</strong>
                <div class="small text-muted">Não feches a página.</div>
            </div>
        </div>
    </div>

    {{-- SCRIPT --}}
   <script>
(function() {
    const leaguesByCountryUrl = "{{ $leaguesByCountryRoute }}";

    function normalize(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnShow = document.getElementById('btnShow');
        const filtersForm = document.getElementById('filtersForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const btnSync = document.getElementById('btnSync');
        const syncForm = document.getElementById('syncForm');
        const syncOverlay = document.getElementById('syncOverlay');
        const countrySearch = document.getElementById('countrySearch');
        const collapseAllBtn = document.getElementById('collapseAllBtn');

        // 🔹 Mostrar Equipas
        btnShow?.addEventListener('click', e => {
            e.preventDefault();
            loadingOverlay.style.display = 'flex';
            setTimeout(() => filtersForm.submit(), 200);
        });

        // 🔹 Sincronizar Equipas
        btnSync?.addEventListener('click', e => {
            e.preventDefault();
            const leagueVal = document.getElementById('leagueInput')?.value || '';
            const seasonVal = document.getElementById('seasonInput')?.value || '';
            const leagueCountryVal = document.getElementById('leagueCountryInput')?.value || '';

            document.getElementById('syncLeague').value = leagueVal;
            document.getElementById('syncSeason').value = seasonVal;
            document.getElementById('syncLeagueCountry').value = leagueCountryVal;

            if (!leagueVal || !seasonVal) {
                alert('Por favor preenche Liga e Época antes de sincronizar.');
                return;
            }

            syncOverlay.style.display = 'flex';
            setTimeout(() => syncForm.submit(), 200);
        });

        // 🔍 Filtro de países
        countrySearch?.addEventListener('input', function() {
            const q = normalize(this.value);
            document.querySelectorAll('.country-item').forEach(item => {
                const name = normalize(item.dataset.countryName);
                item.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        });

        // ❌ Fechar todos os acordeões abertos
        collapseAllBtn?.addEventListener('click', () => {
            document.querySelectorAll('.accordion-collapse.show').forEach(c => {
                const inst = bootstrap.Collapse.getInstance(c);
                inst?.hide();
            });
        });

        // 📡 Carregar ligas por país (dinâmico)
        document.getElementById('countriesAccordion')?.addEventListener('show.bs.collapse', ev => {
            const el = ev.target;
            const list = el.querySelector('.leagues-list');
            const country = (list.dataset.country || '').trim();
            const season = document.getElementById('filterSeasonBox')?.value || '';

            list.innerHTML = '<div class="text-center text-muted small">A carregar ligas…</div>';

            fetch(`${leaguesByCountryUrl}?country=${encodeURIComponent(country)}&season=${season}`)
                .then(r => r.json())
                .then(json => {
                    if (!json.leagues?.length) {
                        list.innerHTML = '<div class="text-muted small">Sem ligas para este país.</div>';
                        return;
                    }

                    const group = document.createElement('div');
                    group.className = 'list-group';

                    json.leagues.forEach(l => {
                        const leagueData = l.league ?? l; // suporte flexível
                        const leagueCountry = leagueData.country || country || '';
                        const leagueId = leagueData.id ?? l.external_id ?? '';

                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                        // 🔸 Lado esquerdo: logo + nome
                        const left = document.createElement('div');
                        left.className = 'd-flex align-items-center gap-2';

                        if (leagueData.logo) {
                            const img = document.createElement('img');
                            img.src = leagueData.logo;
                            img.alt = leagueData.name;
                            img.style.width = '28px';
                            img.style.height = '28px';
                            img.style.objectFit = 'contain';
                            left.appendChild(img);
                        }

                        const name = document.createElement('span');
                        name.textContent = `${leagueData.name} (${leagueId})`;
                        left.appendChild(name);

                        // 🔸 Lado direito: tipo
                        const right = document.createElement('div');
                        if (leagueData.type) {
                            const b = document.createElement('span');
                            b.className = 'badge bg-light text-dark';
                            b.textContent = leagueData.type;
                            right.appendChild(b);
                        }

                        item.append(left, right);

                        // 🔹 Ao clicar: preenche inputs hidden corretamente
                        item.addEventListener('click', () => {
                            console.log(`Liga selecionada: ${leagueData.name} (${leagueId})`);
                            console.log(`País da API: ${leagueCountry} | País do acordeão: ${country}`);

                            document.getElementById('leagueInput').value = leagueId;
                            document.getElementById('leagueCountryInput').value = leagueCountry;
                            document.getElementById('searchInput')?.focus();
                        });

                        group.appendChild(item);
                    });

                    list.innerHTML = '';
                    list.appendChild(group);
                })
                .catch(() => {
                    list.innerHTML = '<div class="text-danger small">Erro ao carregar ligas.</div>';
                });
        });
    });
})();
</script>
@endsection
