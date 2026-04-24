@extends('viewprincipal.layout.app')

@section('main-content')
    @php
        use Illuminate\Support\Facades\Route as RouteFacade;

        $currentYear = now()->year;
        $years = range($currentYear, $currentYear - 5);
        $selectedSeason = old('season', $season ?? $currentYear);
        $lastSync = session('last_sync') ?? '—';

        $route_or_hash = function(string $name, array $params = []) {
            try {
                if (RouteFacade::has($name)) return route($name, $params);
            } catch (\Throwable $e) { /* ignora */ }
            return '#';
        };

        $url_leagues_by_country = $route_or_hash('admin.leagues.byCountry') ?: $route_or_hash('leagues.byCountry');
        $url_leagues_standings  = $route_or_hash('admin.leagues.standings') ?: $route_or_hash('leagues.standings');
        $url_leagues_rounds     = $route_or_hash('admin.leagues.rounds') ?: $route_or_hash('leagues.rounds');
        $url_leagues_fixtures   = $route_or_hash('admin.leagues.fixtures') ?: $route_or_hash('leagues.fixtures');
        $url_update_last_sync   = $route_or_hash('update.last.sync') ?: url('/update-last-sync');
    @endphp

    <style>
        /* ---------- Ajustes de Layout Responsivo ---------- */
        body {
            background: #f7f8fa;
            overflow-x: hidden;
        }

        .container-fluid {
            max-width: 100%;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .country-flag {
            width: 20px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .leagues-list .list-group-item {
            cursor: pointer;
        }

        .leagues-list .list-group-item:hover {
            background: rgba(13, 110, 253, 0.04);
        }

        .small-muted {
            font-size: .9rem;
            color: #6c757d;
        }

        .card.auto-height {
            min-height: 65vh;
        }

        .card.auto-height .card-body {
            max-height: 60vh;
            overflow: auto;
        }

        #loadingOverlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.35);
            z-index: 9999;
        }

        #loadingOverlay .status-box {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.08);
            min-width: 260px;
            text-align: center;
        }

        #fixturesTable td, #standingsTable td {
            vertical-align: middle;
            font-size: 14px;
        }

        /* ---------- Responsividade ---------- */
        @media (max-width: 992px) {
            .search-bar {
                width: 100% !important;
                margin-top: .5rem;
            }

            .navbar, .sidebar {
                position: relative !important;
                width: 100%;
                z-index: 999;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }

        @media (max-width: 576px) {
            h1.h5 {
                font-size: 1rem;
            }

            .card-body h3 {
                font-size: 1.2rem;
            }
        }
    </style>

    <div class="container-fluid py-4">
        {{-- Cabeçalho --}}
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
            <h1 class="h5 mb-0">📊 Dashboard — ScoutTalent</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
        </div>

        {{-- KPIs --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Total Países</h6>
                        <h3 class="fw-bold">{{ count($countries) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Total de Ligas</h6>
                        <h3 class="fw-bold" id="totalLeaguesCount">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Top Equipa (pontos)</h6>
                        <h3 class="fw-bold text-primary" id="topTeamName">—</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Última Sincronização</h6>
                        <h3 class="fw-bold text-success" id="lastSync">{{ $lastSync }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Sidebar Países & Ligas --}}
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm auto-height h-100">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="fw-semibold mb-2 mb-lg-0">Países & Ligas</div>
                        <select id="filterSeasonBox" class="form-select form-select-sm" style="width:120px;">
                            @foreach($years as $s)
                                <option value="{{ $s }}" {{ (int)$s === (int)$selectedSeason ? 'selected' : '' }}>
                                    {{ $s }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body">
                        <input id="countrySearch" class="form-control form-control-sm mb-3"
                               placeholder="Procurar país..." type="search">
                        <div class="accordion" id="countriesAccordion" style="max-height:56vh; overflow:auto;">
                            @forelse($countries as $c)
                                @php
                                    $countryName = data_get($c,'name',$c);
                                    $countryCode = data_get($c,'code','');
                                    $collapseId = 'country-' . \Illuminate\Support\Str::slug($countryName);
                                @endphp
                                <div class="accordion-item country-item"
                                     data-country-name="{{ strtolower($countryName) }}">
                                    <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}"
                                                data-country="{{ $countryName }}">
                                            @if($countryCode)
                                                <img
                                                    src="https://media.api-sports.io/flags/{{ strtolower($countryCode) }}.svg"
                                                    alt="{{ $countryCode }}" class="country-flag">
                                            @endif
                                            <span>{{ $countryName }}</span>
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="heading-{{ $collapseId }}"
                                         data-bs-parent="#countriesAccordion">
                                        <div class="accordion-body p-2">
                                            <div class="small-muted mb-2">Clique numa liga:</div>
                                            <div class="leagues-list" data-country="{{ $countryName }}">
                                                <div class="text-muted small">Clique para carregar ligas…</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">Nenhum país disponível.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Painel principal --}}
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" id="classificationTitle">🏆 Classificação ({{ $selectedSeason }}
                            )</h5>
                        <div id="standingsTableWrapper" class="table-responsive">
                            <table class="table table-hover mb-0 align-middle small d-none" id="standingsTable">
                                <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Equipa</th>
                                    <th>Pontos</th>
                                    <th>J</th>
                                    <th>V</th>
                                    <th>E</th>
                                    <th>D</th>
                                    <th>GM</th>
                                    <th>GS</th>
                                    <th>DG</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="text-muted small">Carregue numa liga para ver a classificação…</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">📅 Jornadas</h5>
                        <select id="roundSelect" class="form-select form-select-sm mb-3">
                            <option value="">-- Seleciona a jornada --</option>
                        </select>
                        <div id="fixturesWrapper" class="table-responsive">
                            <table class="table table-hover align-middle d-none" id="fixturesTable">
                                <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th class="text-end">Casa</th>
                                    <th class="text-center">Resultado</th>
                                    <th>Fora</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="text-muted small">Escolha uma liga e jornada para ver os jogos…</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">📈 Vitórias por equipa</h5>
                        <canvas id="winsChart" height="120"></canvas>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">📊 Evolução de pontos por jornada</h5>
                        <canvas id="evolutionChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay">
        <div class="status-box">
            <div class="spinner-border text-primary"></div>
            <div class="overlay-title">A carregar…</div>
            <div class="overlay-sub small-muted">Aguarde um momento.</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const URLS = {
            leaguesByCountry: @json($url_leagues_by_country),
            leaguesStandings: @json($url_leagues_standings),
            leaguesRounds: @json($url_leagues_rounds),
            leaguesFixtures: @json($url_leagues_fixtures),
            updateLastSync: @json($url_update_last_sync)
        };
    </script>

    {{-- Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const seasonBox = document.getElementById('filterSeasonBox');
            const countriesAccordion = document.getElementById('countriesAccordion');
            const standingsTable = document.getElementById('standingsTable');
            const standingsBody = standingsTable.querySelector('tbody');
            const wrapper = document.getElementById('standingsTableWrapper');
            const fixturesTable = document.getElementById('fixturesTable');
            const fixturesBody = fixturesTable.querySelector('tbody');
            const roundSelect = document.getElementById('roundSelect');
            const overlay = document.getElementById('loadingOverlay');
            const totalLeaguesCard = document.getElementById('totalLeaguesCount');
            const topTeamName = document.getElementById('topTeamName');
            const classificationTitle = document.getElementById('classificationTitle');
            const lastSync = document.getElementById('lastSync');

            let selectedLeagueId = null;
            let selectedSeason = seasonBox.value;
            let winsChart = null;
            let evolutionChart = null;

            // filtro países
            document.getElementById('countrySearch')?.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('.country-item').forEach(el => {
                    const name = el.dataset.countryName || '';
                    el.style.display = (!q || name.includes(q)) ? '' : 'none';
                });
            });

            // carregar ligas ao expandir accordion (usa URL segura)
            countriesAccordion?.addEventListener('show.bs.collapse', function (ev) {
                const collapseEl = ev.target;
                const leaguesContainer = collapseEl.querySelector('.leagues-list');
                if (!leaguesContainer) return;
                const season = seasonBox.value;

                if (leaguesContainer.dataset.loaded === '1' && leaguesContainer.dataset.season === season) return;

                leaguesContainer.innerHTML = '<div class="text-center small text-muted py-2">A carregar ligas…</div>';

                if (!URLS.leaguesByCountry || URLS.leaguesByCountry === '#') {
                    leaguesContainer.innerHTML = '<div class="text-danger small">Rota de ligas não configurada.</div>';
                    return;
                }

                const country = encodeURIComponent(leaguesContainer.dataset.country || '');
                fetch(URLS.leaguesByCountry + '?country=' + country + '&season=' + encodeURIComponent(season))
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(json => {
                        if (!json.leagues || !json.leagues.length) {
                            leaguesContainer.innerHTML = '<div class="text-muted small">Sem ligas disponíveis.</div>';
                            return;
                        }
                        const group = document.createElement('div');
                        group.className = 'list-group';
                        json.leagues.forEach(l => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action d-flex align-items-center';
                            btn.dataset.leagueId = l.id;
                            btn.dataset.leagueName = l.name;
                            btn.dataset.leagueCountry = leaguesContainer.dataset.country;
                            if (l.logo) {
                                const img = document.createElement('img');
                                img.src = l.logo;
                                img.alt = l.name;
                                img.style.width = '24px';
                                img.style.height = '24px';
                                img.style.objectFit = 'contain';
                                img.style.marginRight = '8px';
                                btn.appendChild(img);
                            }
                            btn.appendChild(document.createTextNode(l.name));
                            group.appendChild(btn);
                        });
                        leaguesContainer.innerHTML = '';
                        leaguesContainer.appendChild(group);
                        leaguesContainer.dataset.loaded = '1';
                        leaguesContainer.dataset.season = season;

                        totalLeaguesCard.textContent = json.leagues.length;
                    })
                    .catch(err => {
                        leaguesContainer.innerHTML = '<div class="text-danger small">Erro ao carregar ligas.</div>';
                        console.error(err);
                    });
            });

            // clicar liga
            document.addEventListener('click', function (ev) {
                const btn = ev.target.closest('.list-group-item');
                if (!btn || !btn.dataset.leagueId) return;
                selectedLeagueId = btn.dataset.leagueId;
                selectedSeason = seasonBox.value;

                overlay.style.display = 'flex';
                classificationTitle.textContent = `🏆 Classificação (${selectedSeason})`;

                if (!URLS.leaguesStandings || URLS.leaguesStandings === '#') {
                    console.warn('rota de standings não definida');
                    overlay.style.display = 'none';
                    return;
                }

                fetch(URLS.leaguesStandings + '?league=' + encodeURIComponent(selectedLeagueId) + '&season=' + encodeURIComponent(selectedSeason))
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(json => {
                        standingsBody.innerHTML = '';
                        if (!json.standings || !json.standings.length) {
                            wrapper.querySelector('.text-muted').textContent = 'Sem classificação disponível.';
                            standingsTable.classList.add('d-none');
                            topTeamName.textContent = '—';
                        } else {
                            const labels = [];
                            const wins = [];
                            let topTeam = null;

                            json.standings.forEach((t, idx) => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>${idx + 1}</td>
                                    <td><img src="${t.team_logo}" style="width:20px;height:20px;object-fit:contain;margin-right:6px"> ${t.team_name}</td>
                                    <td>${t.points}</td>
                                    <td>${t.played}</td>
                                    <td>${t.win}</td>
                                    <td>${t.draw}</td>
                                    <td>${t.lose}</td>
                                    <td>${t.gf}</td>
                                    <td>${t.ga}</td>
                                    <td>${t.gd}</td>`;
                                standingsBody.appendChild(tr);

                                labels.push(t.team_name);
                                wins.push(t.win);
                                if (idx === 0) topTeam = t.team_name;
                            });

                            standingsTable.classList.remove('d-none');
                            wrapper.querySelector('.text-muted').textContent = '';
                            topTeamName.textContent = topTeam ?? '—';

                            if (winsChart) winsChart.destroy();
                            winsChart = new Chart(document.getElementById('winsChart'), {
                                type: 'bar',
                                data: {labels: labels, datasets: [{label: 'Vitórias', data: wins}]},
                                options: {
                                    responsive: true,
                                    plugins: {legend: {display: false}},
                                    scales: {y: {beginAtZero: true}}
                                }
                            });

                            if (evolutionChart) evolutionChart.destroy();
                            evolutionChart = new Chart(document.getElementById('evolutionChart'), {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{label: 'Pontos acumulados', data: wins, tension: .3, fill: true}]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {legend: {display: false}},
                                    scales: {y: {beginAtZero: true}}
                                }
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        wrapper.querySelector('.text-muted').textContent = 'Erro ao obter classificação.';
                    })
                    .finally(() => {
                        // atualizar sessão com última sincronização (se rota existir)
                        if (URLS.updateLastSync && URLS.updateLastSync !== '#') {
                            fetch(URLS.updateLastSync).catch(() => {
                            });
                        }
                        const today = new Date();
                        lastSync.textContent = today.toLocaleDateString('pt-PT');
                    });

                // carregar jornadas
                if (!URLS.leaguesRounds || URLS.leaguesRounds === '#') {
                    overlay.style.display = 'none';
                    return;
                }
                fetch(URLS.leaguesRounds + '?league=' + encodeURIComponent(selectedLeagueId) + '&season=' + encodeURIComponent(selectedSeason))
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(json => {
                        roundSelect.innerHTML = '<option value="">-- Seleciona a jornada --</option>';
                        if (json.rounds) {
                            json.rounds.forEach(r => {
                                const opt = document.createElement('option');
                                opt.value = r;
                                opt.textContent = r;
                                roundSelect.appendChild(opt);
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    })
                    .finally(() => {
                        overlay.style.display = 'none';
                    });
            });

            // fixtures
            roundSelect.addEventListener('change', function () {
                if (!selectedLeagueId || !this.value) return;
                overlay.style.display = 'flex';

                if (!URLS.leaguesFixtures || URLS.leaguesFixtures === '#') {
                    overlay.style.display = 'none';
                    return;
                }

                fetch(URLS.leaguesFixtures + '?league=' + encodeURIComponent(selectedLeagueId) + '&season=' + encodeURIComponent(selectedSeason) + '&round=' + encodeURIComponent(this.value))
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(json => {
                        fixturesBody.innerHTML = '';
                        if (!json.fixtures || !json.fixtures.length) {
                            fixturesTable.classList.add('d-none');
                            document.querySelector('#fixturesWrapper .text-muted').textContent = 'Sem jogos nesta jornada.';
                        } else {
                            json.fixtures.forEach(f => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>${f.date}</td>
                                    <td class="text-end"><img src="${f.home_logo}" style="width:22px;height:22px;object-fit:contain;margin-right:6px"> ${f.home}</td>
                                    <td class="text-center fw-bold">${f.goals_home ?? ''} - ${f.goals_away ?? ''}</td>
                                    <td><img src="${f.away_logo}" style="width:22px;height:22px;object-fit:contain;margin-right:6px"> ${f.away}</td>`;
                                fixturesBody.appendChild(tr);
                            });
                            fixturesTable.classList.remove('d-none');
                            document.querySelector('#fixturesWrapper .text-muted').textContent = '';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        document.querySelector('#fixturesWrapper .text-muted').textContent = 'Erro ao carregar jogos.';
                    })
                    .finally(() => {
                        overlay.style.display = 'none';
                    });
            });
        });
    </script>
@endsection
