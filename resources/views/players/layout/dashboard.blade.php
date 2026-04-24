{{-- resources/views/players/layout/create.blade.php --}}
@extends('players.layout.app')

@section('main-content')
    @php
        $countries = $countries ?? [];
        $currentYear = now()->year;
        $years = range($currentYear, $currentYear - 5);
        $selectedSeason = old('season', $season ?? $currentYear);
        $ran = $ran ?? false;
    @endphp

    <style>
        .country-flag { width: 20px; height: auto; margin-right: 8px; vertical-align: middle; }
        .list-group-item:hover { background: rgba(13,110,253,0.05); }
        #loadingOverlay, #simpleSyncOverlay {
            position: fixed; inset: 0; display:none; align-items:center; justify-content:center;
            background: rgba(0,0,0,.4); z-index:9999999;
        }
        #loadingOverlay .status-box, #simpleSyncOverlay .status-box {
            background:#fff; padding:20px; border-radius:8px; text-align:center;
        }
        .badge-muted { background:#f1f3f5; color:#333; border-radius:6px; padding:3px 6px; font-size:.8rem; }
        .small-muted { font-size:.9rem; color:#6c757d; }
        .league-loading { font-size:.9rem; color:#6c757d; padding:8px 0; text-align:center; }
    </style>

    <div class="container py-4">

        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h5 mb-0">📋 Jogadores — Liga Completa</h1>
            <a href="{{ route('admin.players.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
        </div>

        {{-- Mensagens --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show text-white">
                ✅ {!! session('success') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show text-white">
                ⚠️ {!! session('error') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($ran && empty($players))
            <div class="alert alert-warning text-white">
                ⚠️ <strong>Sem jogadores disponíveis</strong> para a liga <strong>{{ $league ?? '—' }}</strong>
                (época <strong>{{ $selectedSeason }}</strong>).
            </div>
        @endif

        <div class="row g-3">

            {{-- Painel esquerdo: Países + Ligas --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">🌍 Países & Ligas</span>
                        <select id="filterSeasonBox" class="form-select form-select-sm" style="width:120px;">
                            @foreach($years as $s)
                                <option value="{{ $s }}" {{ (int)$s === (int)$selectedSeason ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="card-body">
                        <input id="countrySearch" class="form-control form-control-sm mb-2" placeholder="Procurar país..." type="search">
                        <div class="accordion" id="countriesAccordion" style="max-height:60vh; overflow:auto;">
                            @forelse($countries as $c)
                                @php
                                    $countryName = data_get($c,'name', $c);
                                    $countryCode = data_get($c,'code', '');
                                    $collapseId = 'country-' . \Illuminate\Support\Str::slug($countryName);
                                @endphp
                                <div class="accordion-item country-item" data-country-name="{{ strtolower($countryName) }}">
                                    <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                                        <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                            @if($countryCode)
                                                <img src="https://media.api-sports.io/flags/{{ strtolower($countryCode) }}.svg"
                                                     alt="{{ $countryCode }}" class="country-flag">
                                            @endif
                                            {{ $countryName }}
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="heading-{{ $collapseId }}" data-bs-parent="#countriesAccordion">
                                        <div class="accordion-body p-2">
                                            <div class="small text-muted mb-2">Clique numa liga para carregar todos os jogadores.</div>
                                            <div class="leagues-list" data-country="{{ $countryName }}">
                                                <div class="league-loading">Carregar ligas…</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">Nenhum país disponível.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Painel direito: formulário + lista de jogadores --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">

                        {{-- Info da liga seleccionada --}}
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <div><strong>Liga seleccionada</strong></div>
                            <div id="selectedLeagueInfo" class="small-muted">Nenhuma</div>
                        </div>

                        {{-- Formulário principal --}}
                        <form action="{{ route('admin.players.index') }}" method="GET" id="playersFormInline" class="row g-2 align-items-end">
                            @csrf
                            <input type="hidden" name="load" id="loadHidden" value="0">
                            <input type="hidden" name="league_name" id="leagueNameInput" value="">
                            <div class="col-md-6">
                                <label class="form-label">Liga (ID)</label>
                                <input type="text" name="league" id="leagueInput"
                                       class="form-control" placeholder="ex: 39 (Premier League)"
                                       value="{{ old('league', $league) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Época</label>
                                <select name="season" id="seasonInput" class="form-select">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ (int)$selectedSeason === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="btnLoad" class="btn btn-success w-100">📥 Mostrar</button>
                            </div>
                        </form>

                        <div class="mt-3 d-flex gap-2 align-items-center">
                            <form action="{{ route('admin.players.export.csv') }}" method="POST" id="csvForm">
                                @csrf
                                <input type="hidden" name="league" value="{{ $league ?? '' }}">
                                <input type="hidden" name="season" value="{{ $selectedSeason }}">
                                <button type="submit" class="btn btn-outline-success btn-sm">⬇️ CSV</button>
                            </form>
                            <form action="{{ route('admin.players.sync') }}" method="POST" id="syncForm">
                                @csrf
                                <input type="hidden" name="league" id="syncLeague">
                                <input type="hidden" name="season" id="syncSeason">
                                <button type="button" id="btnSync" class="btn btn-outline-danger btn-sm">🔄 Sync</button>
                            </form>
                            <div class="ms-auto text-muted small">
                                Total jogadores carregados: <strong>{{ count($players ?? []) }}</strong>
                            </div>
                        </div>

                        {{-- 🧩 Tabela de jogadores --}}
                        <div class="mt-3">
                            @if(!empty($players))
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle small">
                                        <thead class="table-light text-center">
                                        <tr>
                                            <th></th>
                                            <th>Nome</th>
                                            <th>Idade</th>
                                            <th>Nacionalidade</th>
                                            <th>Clube</th>
                                            <th>Posição</th>
                                            <th><i class="fas fa-ruler-vertical me-1 text-secondary"></i>Altura</th>
                                            <th><i class="fas fa-weight-hanging me-1 text-secondary"></i>Peso</th>
                                            <th><i class="fas fa-birthday-cake me-1 text-secondary"></i>Nascimento</th>
                                            <th><i class="fas fa-star me-1 text-warning"></i>Rating</th>
                                            <th><i class="fas fa-futbol me-1 text-secondary"></i>Jogos</th>
                                            <th><i class="fas fa-bullseye me-1 text-secondary"></i>Golos</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($players as $p)
                                            <tr>
                                                <td class="text-center">
                                                    <img src="{{ $p['photo'] ?? '' }}" width="40" height="40"
                                                         style="object-fit:cover;border-radius:6px"
                                                         onerror="this.src='https://via.placeholder.com/40?text=⚽';">
                                                </td>
                                                <td>{{ $p['name'] ?? '—' }}</td>
                                                <td>{{ $p['age'] ?? '—' }}</td>
                                                <td>{{ $p['nationality'] ?? '—' }}</td>
                                                <td>{{ $p['team_name'] ?? '—' }}</td>
                                                <td>{{ $p['position'] ?? '—' }}</td>
                                                <td class="text-center">{{ $p['height'] ?? '—' }}</td>
                                                <td class="text-center">{{ $p['weight'] ?? '—' }}</td>
                                                <td class="text-center">
                                                    @if(!empty($p['birth_date']))
                                                        {{ \Carbon\Carbon::parse($p['birth_date'])->format('d/m/Y') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if(!empty($p['rating']))
                                                        <span class="badge {{ $p['rating'] >= 7 ? 'bg-success' : ($p['rating'] >= 6 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                            {{ number_format($p['rating'], 2) }}
                                                        </span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $p['appearances'] ?? 0 }}</td>
                                                <td class="text-center fw-semibold">{{ $p['goals'] ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted small mt-2">Nenhum jogador carregado.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Overlays --}}
        <div id="loadingOverlay" aria-hidden="true" style="display:none">
            <div class="status-box text-center" role="status" aria-live="polite" style="min-width:260px">
                <div class="spinner-border text-primary mb-2" role="status" aria-hidden="true"></div>
                <div class="overlay-title fw-semibold">A carregar jogadores da liga…</div>
                <div id="overlaySub" class="small-muted" style="font-size:.9rem;margin-top:6px">Isto pode demorar alguns minutos — não feche a página.</div>
            </div>
        </div>
        <div id="simpleSyncOverlay" aria-hidden="true" style="display:none">
            <div class="status-box text-center" role="status" aria-live="polite" style="min-width:260px">
                <div class="spinner-border text-danger mb-2" role="status" aria-hidden="true"></div>
                <div class="overlay-title fw-semibold">A sincronizar…</div>
                <div class="small-muted" style="font-size:.9rem;margin-top:6px">A sincronização pode demorar. Mantém a página aberta.</div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // --- Elements
            const countriesAccordion = document.getElementById('countriesAccordion');
            const countrySearch = document.getElementById('countrySearch');
            const filterSeasonBox = document.getElementById('filterSeasonBox');
            const seasonInput = document.getElementById('seasonInput');
            const leagueInput = document.getElementById('leagueInput');
            const leagueNameInput = document.getElementById('leagueNameInput');
            const btnLoad = document.getElementById('btnLoad');
            const playersForm = document.getElementById('playersFormInline');
            const syncForm = document.getElementById('syncForm');
            const syncLeague = document.getElementById('syncLeague');
            const syncSeason = document.getElementById('syncSeason');
            const selectedLeagueInfo = document.getElementById('selectedLeagueInfo');

            // ensure overlay nodes are attached to body
            function ensureOverlay(id) {
                let el = document.getElementById(id);
                if (!el) {
                    el = document.createElement('div');
                    el.id = id;
                    el.setAttribute('aria-hidden', 'true');
                    el.style.display = 'none';
                    document.body.appendChild(el);
                } else if (el.parentNode !== document.body) {
                    document.body.appendChild(el);
                }
                return el;
            }
            const loadingOverlay = ensureOverlay('loadingOverlay');
            const simpleSyncOverlay = ensureOverlay('simpleSyncOverlay');
            const overlaySub = document.getElementById('overlaySub');

            // style overlays (defensive)
            [loadingOverlay, simpleSyncOverlay].forEach(o => {
                o.style.position = 'fixed';
                o.style.inset = '0';
                o.style.display = 'none';
                o.style.alignItems = 'center';
                o.style.justifyContent = 'center';
                o.style.background = 'rgba(0,0,0,.4)';
                o.style.zIndex = '9999999';
            });

            function forceShow(el, message = null) {
                if (!el) return;
                if (message && overlaySub) overlaySub.textContent = message;
                el.style.setProperty('display', 'flex', 'important');
                el.setAttribute('aria-hidden', 'false');
            }
            function forceHide(el) {
                if (!el) return;
                el.style.removeProperty('display');
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
            }
            function disableAllButtons() {
                Array.from(document.querySelectorAll('button, input[type="submit"], input[type="button"]')).forEach(b => b.disabled = true);
            }

            // --- Country search (client-side)
            countrySearch?.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('.country-item').forEach(el => {
                    const name = el.dataset.countryName || '';
                    el.style.display = (!q || name.includes(q)) ? '' : 'none';
                });
            });

            // --- Fetch leagues when accordion panel opens
            // Uses Bootstrap's collapse event 'show.bs.collapse'
            if (countriesAccordion) {
                countriesAccordion.addEventListener('show.bs.collapse', function (ev) {
                    const collapseEl = ev.target;
                    const leaguesContainer = collapseEl.querySelector('.leagues-list');
                    if (!leaguesContainer) return;

                    const selectedFilterSeason = filterSeasonBox?.value || '';

                    // skip if already loaded for this season
                    if (leaguesContainer.dataset.loaded === '1' && leaguesContainer.dataset.season === selectedFilterSeason) return;

                    const country = leaguesContainer.dataset.country;
                    leaguesContainer.innerHTML = '<div class="league-loading">A carregar ligas…</div>';

                    const params = new URLSearchParams();
                    params.set('country', country);
                    if (selectedFilterSeason) params.set('season', selectedFilterSeason);

                    fetch("{{ route('admin.players.leagues_by_country') }}?" + params.toString(), { headers: {'X-Requested-With':'XMLHttpRequest'} })
                        .then(response => {
                            if (!response.ok) throw new Error('Status ' + response.status);
                            return response.json();
                        })
                        .then(json => {
                            // accept both shapes: { leagues: [...] } or { response: [...] }
                            const leagues = json.leagues ?? json.response ?? [];
                            if (!Array.isArray(leagues) || leagues.length === 0) {
                                leaguesContainer.innerHTML = '<div class="text-muted small">Sem ligas para este país/época.</div>';
                                leaguesContainer.dataset.loaded = '1';
                                leaguesContainer.dataset.season = selectedFilterSeason;
                                return;
                            }

                            const group = document.createElement('div');
                            group.className = 'list-group';

                            leagues.forEach(l => {
                                const leagueBtn = document.createElement('button');
                                leagueBtn.type = 'button';
                                leagueBtn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                                const left = document.createElement('div');
                                left.className = 'd-flex align-items-center';
                                if (l.logo) {
                                    const img = document.createElement('img');
                                    img.src = l.logo;
                                    img.alt = l.name;
                                    img.style.width = '28px';
                                    img.style.height = '28px';
                                    img.style.objectFit = 'contain';
                                    img.style.marginRight = '8px';
                                    left.appendChild(img);
                                }
                                const nameSpan = document.createElement('span');
                                const seasonText = (l.seasons && l.seasons.length) ? (' — ' + (l.seasons[0].year ?? '')) : '';
                                nameSpan.textContent = (l.name || '—') + seasonText;
                                left.appendChild(nameSpan);
                                leagueBtn.appendChild(left);

                                // determine availability for selected season
                                let available = true;
                                const selSeason = selectedFilterSeason || seasonInput?.value || '';
                                if (selSeason) {
                                    const seasons = l.seasons || [];
                                    const hasSeason = seasons.some(s => String(s.year) === String(selSeason));
                                    const coveragePlayers = (l.coverage && l.coverage.players) ? true : false;
                                    if (seasons.length > 0 && !hasSeason && !coveragePlayers) available = false;
                                    if (!coveragePlayers && seasons.length === 0) available = false;
                                }

                                const right = document.createElement('div');
                                right.className = 'd-flex gap-2 align-items-center';
                                if (l.type) {
                                    const t = document.createElement('span');
                                    t.className = 'badge badge-muted';
                                    t.textContent = l.type;
                                    right.appendChild(t);
                                }
                                if (!available) {
                                    const b = document.createElement('span');
                                    b.className = 'badge bg-danger text-white';
                                    b.textContent = 'Sem dados nesta época';
                                    right.appendChild(b);
                                    leagueBtn.classList.add('disabled');
                                    leagueBtn.dataset.available = '0';
                                } else {
                                    leagueBtn.dataset.available = '1';
                                }

                                leagueBtn.dataset.leagueId = l.id ?? '';
                                leagueBtn.dataset.leagueName = l.name ?? '';
                                leagueBtn.dataset.leagueCountry = leaguesContainer.dataset.country ?? '';

                                leagueBtn.addEventListener('click', function () {
                                    if (this.dataset.available === '0') {
                                        const alertBox = document.createElement('div');
                                        alertBox.className = 'alert alert-warning py-1 px-2 mt-2';
                                        alertBox.textContent = 'Não existem dados para esta época nesta competição.';
                                        leaguesContainer.prepend(alertBox);
                                        setTimeout(() => alertBox.remove(), 3000);
                                        return;
                                    }

                                    const selectedLeagueId = this.dataset.leagueId || '';
                                    const selectedLeagueName = this.dataset.leagueName || '';

                                    if (leagueInput) leagueInput.value = selectedLeagueId;
                                    if (leagueNameInput) leagueNameInput.value = selectedLeagueName;

                                    if (selectedLeagueInfo) {
                                        selectedLeagueInfo.textContent = selectedLeagueName + ' (' + selectedLeagueId + ')';
                                    }
                                });

                                group.appendChild(leagueBtn);
                            });

                            leaguesContainer.innerHTML = '';
                            leaguesContainer.appendChild(group);
                            leaguesContainer.dataset.loaded = '1';
                            leaguesContainer.dataset.season = selectedFilterSeason;
                        })
                        .catch(err => {
                            console.error('Erro fetch leagues:', err);
                            leaguesContainer.innerHTML = '<div class="text-danger small">Erro ao carregar ligas.</div>';
                        });
                });
            }

            // --- Season change: force reload of leagues
            filterSeasonBox?.addEventListener('change', function () {
                document.querySelectorAll('.leagues-list').forEach(el => {
                    el.dataset.loaded = '0';
                    el.dataset.season = '';
                    el.innerHTML = '<div class="league-loading">Carregar ligas…</div>';
                });
                if (selectedLeagueInfo) selectedLeagueInfo.textContent = 'Nenhuma';
            });

            // --- Load button -> submit with overlay
            btnLoad?.addEventListener('click', function (ev) {
                ev.preventDefault();
                const leagueVal = leagueInput?.value?.trim();
                const seasonVal = seasonInput?.value?.trim();
                if (!leagueVal || !seasonVal) {
                    if (!leagueVal) {
                        leagueInput.classList.add('is-invalid');
                        setTimeout(() => leagueInput.classList.remove('is-invalid'), 1600);
                    }
                    return;
                }

                // show overlay and submit
                forceShow(loadingOverlay, 'A carregar jogadores da liga… (pode demorar vários minutos)');
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        disableAllButtons();
                        document.getElementById('loadHidden').value = '1';
                        playersForm.submit();
                    }, 100);
                });
            });

            // --- Sync button
            document.getElementById('btnSync')?.addEventListener('click', function (ev) {
                ev.preventDefault();
                if (syncLeague) syncLeague.value = leagueInput?.value || '';
                if (syncSeason) syncSeason.value = seasonInput?.value || '';

                if (!syncLeague.value || !syncSeason.value) {
                    alert('Indica Liga e Época antes de sincronizar.');
                    return;
                }

                forceShow(simpleSyncOverlay);
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        disableAllButtons();
                        syncForm.submit();
                    }, 100);
                });
            });

            // safety: hide overlays on pagehide to avoid stuck overlays
            window.addEventListener('pagehide', function () {
                forceHide(loadingOverlay);
                forceHide(simpleSyncOverlay);
            });
        })();
    </script>
@endsection
