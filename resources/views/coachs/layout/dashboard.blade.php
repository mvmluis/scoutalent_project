@extends('coachs.layout.app')

@section('main-content')
@php
    use Illuminate\Support\Facades\Route as R;

    $countries = $countries ?? [];
    $coachs = $coachs ?? [];
    $apiError = $apiError ?? null;

    $coachsIndexRoute = R::has('admin.coachs.index')
        ? route('admin.coachs.index')
        : (R::has('manual.coachs.index') ? route('manual.coachs.index') : '#');

    // ✅ rota de sincronização (POST)
    $coachsSyncRoute = R::has('admin.coachs.sync')
        ? route('admin.coachs.sync')
        : (R::has('manual.coachs.sync') ? route('manual.coachs.sync') : '#');

    $leaguesByCountryRoute = R::has('admin.players.leagues_by_country')
        ? route('admin.players.leagues_by_country')
        : (R::has('players.leagues_by_country') ? route('players.leagues_by_country') : '#');

    $season = (int) request('season', now()->year);
@endphp

<style>
    /* --- visual base --- */
    .country-flag { width:20px; height:auto; margin-right:8px; vertical-align:middle; }
    .leagues-list .list-group-item { cursor:pointer; }
    .leagues-list .list-group-item:hover { background: rgba(13, 110, 253, 0.04); }
    .coach-photo{ width:48px; height:48px; object-fit:cover; border-radius:10px; border:1px solid rgba(0,0,0,.06); }

    .custom-header{
        background: linear-gradient(135deg,#1b2b52,#0a1733);
        color:#fff; text-transform:uppercase; position:sticky; top:0; z-index:1010;
    }
    .custom-header th{ font-weight:600; padding:12px 16px; font-size:.82rem; white-space:nowrap; letter-spacing:.04em; }

    /* --- cards --- */
    .card { border:1px solid rgba(0,0,0,.06); border-radius:16px; }
    .card-header { background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:16px; border-top-right-radius:16px; }
    .shadow-soft { box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06); }

    /* --- filtros (layout) --- */
    .filters-title {
        font-weight:700;
        letter-spacing:.02em;
        display:flex; align-items:center; gap:.5rem;
    }
    .filters-hint { font-size:.86rem; color:#6c757d; }
    .filters-grid { margin-top:.75rem; }
    .filters-grid .form-label { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; margin-bottom:.35rem; }
    .filters-grid .form-control, .filters-grid .form-select { border-radius:12px; }
    .btn-pill { border-radius:999px; padding:.55rem 1rem; font-weight:600; }
    .btn-ghost { border-radius:999px; padding:.55rem 1rem; font-weight:600; background:#fff; }
    .btn-ghost:hover { background:#f7f7f8; }

    /* --- sync panel --- */
    .sync-panel {
        margin-top:1rem;
        border:1px solid rgba(0,0,0,.06);
        border-radius:16px;
        padding:14px 14px;
        background: #fff;
    }
    .sync-row { display:flex; gap:12px; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; }
    .sync-left { font-size:.9rem; color:#6c757d; }
    .sync-actions { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
    .sync-actions .form-label { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; margin-bottom:.35rem; }
    .sync-actions .form-select { border-radius:12px; min-width:160px; }

    /* --- ação sync mais “moderna” --- */
    .btn-sync {
        background: linear-gradient(135deg, #ff2d55, #ff375f);
        border:0;
        color:#fff;
        box-shadow: 0 10px 20px rgba(255, 45, 85, 0.18);
    }
    .btn-sync:hover { filter: brightness(0.98); }

    /* --- top toolbar --- */
    .page-top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .badge-soft {
        background:#f4f6ff; color:#1b2b52;
        border:1px solid rgba(27,43,82,.12);
        padding:.35rem .7rem; border-radius:999px; font-weight:700; font-size:.8rem;
    }
    .total-badge {
        background:#ff2d55; color:#fff; border-radius:10px;
        padding:.2rem .55rem; font-weight:800; font-size:.8rem;
    }
</style>

<div class="container py-4">

    <div class="page-top mb-3">
        <div class="d-flex align-items-center gap-2">
            <h1 class="h5 mb-0">🏃‍♂️ Treinadores (API)</h1>
            <span class="badge-soft">SEASON: {{ (int)request('season', now()->year) }}</span>
        </div>

        <div class="text-muted small d-flex align-items-center gap-2">
            <span>Total:</span>
            <span class="total-badge">{{ is_countable($coachs) ? count($coachs) : 0 }}</span>
        </div>
    </div>

    @if ($apiError)
        <div class="alert alert-danger">
            {{ $apiError }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show text-white" role="alert">
            ✅ {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show text-white" role="alert">
            ⚠️ {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">

        {{-- Países & Ligas --}}
        <div class="col-lg-4">
            <div class="card shadow-soft">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">🌍 Países & Ligas</div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="small text-muted">Season</div>
                        <select id="filterSeasonBox" class="form-select form-select-sm" style="width:120px;">
                            @php
                                $currentYear = now()->year;
                                $seasonYears = range($currentYear, $currentYear - 6);
                            @endphp
                            @foreach ($seasonYears as $s)
                                <option value="{{ $s }}" {{ (int)$s === (int)$season ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex gap-2 mb-2 align-items-center">
                        <input id="countrySearch" class="form-control form-control-sm" placeholder="Procurar país..." type="search">
                        <button id="collapseAllBtn" type="button" class="btn btn-sm btn-outline-secondary btn-pill" title="Fechar todos">
                            ✖
                        </button>
                    </div>

                    <div class="accordion" id="countriesAccordion" style="max-height:56vh; overflow:auto;">
                        @forelse($countries as $c)
                            @php
                                $name = data_get($c, 'name', $c);
                                $code = data_get($c, 'code', '');
                                $collapseId = 'country-' . \Illuminate\Support\Str::slug($name);
                            @endphp

                            <div class="accordion-item country-item" data-country-name="{{ strtolower($name) }}">
                                <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                                    <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $collapseId }}"
                                            aria-expanded="false"
                                            aria-controls="{{ $collapseId }}">
                                        @if ($code)
                                            <img src="https://media.api-sports.io/flags/{{ strtolower($code) }}.svg" alt="{{ $code }}" class="country-flag">
                                        @endif
                                        {{ $name }}
                                    </button>
                                </h2>

                                <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                     aria-labelledby="heading-{{ $collapseId }}"
                                     data-bs-parent="#countriesAccordion">
                                    <div class="accordion-body p-2">
                                        <div class="small text-muted mb-2">Clique numa liga para preencher <code>league_id</code>.</div>
                                        <div class="leagues-list" data-country="{{ $name }}">
                                            <div class="text-muted small">Clique para carregar ligas deste país…</div>
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

        {{-- Filtros + Sync + Tabela --}}
        <div class="col-lg-8">
            <div class="card shadow-soft">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div class="filters-title">Filtros (API)</div>
                        <div class="filters-hint">Preenche e clica em <strong>Aplicar</strong>. Para sincronizar a BD usa o botão abaixo.</div>
                    </div>

                    {{-- GET filtros (para API) --}}
                    <form id="filtersForm" action="{{ $coachsIndexRoute }}" method="GET" class="filters-grid">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">Season</label>
                                <input type="number" id="seasonInput" name="season" class="form-control"
                                       value="{{ request('season', now()->year) }}" placeholder="ex: 2025">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">league_id</label>
                                <input type="text" name="league_id" id="leagueIdInput" class="form-control"
                                       value="{{ request('league_id') }}" placeholder="ex: 94">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">team (id)</label>
                                <input type="text" name="team" class="form-control"
                                       value="{{ request('team') }}" placeholder="ex: 234">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">search</label>
                                <input type="text" name="search" class="form-control"
                                       value="{{ request('search') }}" placeholder="nome...">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">id (coach)</label>
                                <input type="text" name="id" class="form-control"
                                       value="{{ request('id') }}" placeholder="ex: 2214">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">country</label>
                                <input type="text" name="country" class="form-control"
                                       value="{{ request('country') }}" placeholder="ex: Portugal">
                            </div>

                            <div class="col-md-6 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-success btn-pill">
                                    <i class="bi bi-check2-circle me-1"></i> Aplicar
                                </button>
                                <a href="{{ $coachsIndexRoute }}" class="btn btn-ghost btn-outline-secondary btn-pill">
                                    <i class="bi bi-x-circle me-1"></i> Limpar
                                </a>
                            </div>

                        </div>
                    </form>

                    {{-- Sync Panel --}}
                    <div class="sync-panel">
                        <div class="sync-row">
                            <div class="sync-left">
                                Sincronização grava na tabela <code>coachs</code> (<code>updateOrCreate</code>) com os filtros actuais.
                            </div>

                            <form id="syncForm" action="{{ $coachsSyncRoute }}" method="POST"
                                  class="sync-actions"
                                  onsubmit="return confirm('Confirmar sincronização para a BD com os filtros atuais?');">
                                @csrf

                                {{-- hidden fields para o controller syncCoachs --}}
                                <input type="hidden" id="syncSeasonHidden" name="season" value="{{ request('season', now()->year) }}">
                                <input type="hidden" id="syncSearchHidden" name="search" value="{{ request('search') }}">
                                <input type="hidden" name="coach" value="{{ request('coach') }}">
                                <input type="hidden" id="syncTeamHidden" name="team" value="{{ request('team') }}">
                                <input type="hidden" id="syncLeagueHidden" name="league" value="{{ request('league_id') }}">

                                <div>
                                    <label class="form-label">Modo</label>
                                    <select class="form-select form-select-sm" id="syncMode" style="width:160px;">
                                        <option value="league">League</option>
                                        <option value="team">Team</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-info btn-pill">
                                    <i class="bi bi-arrow-repeat me-1"></i> SINCRONIZAR BD
                                </button>
                            </form>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Resultados</div>
                        <div class="text-muted small">A lista abaixo vem da API (a sincronização é separada).</div>
                    </div>

                    {{-- Tabela --}}
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="custom-header">
                            <tr>
                                <th style="width:60px;"></th>
                                <th>Nome</th>
                                <th>Idade</th>
                                <th>Nacionalidade</th>
                                <th>Nascimento</th>
                                <th>Clube</th>
                                <th style="width:90px;">ID</th>
                            </tr>
                            </thead>

                            <tbody>
                            @forelse($coachs as $c)
                                @php
                                    $id = data_get($c, 'id');
                                    $photo = data_get($c, 'photo') ?: ($id ? "https://media.api-sports.io/football/coachs/{$id}.png" : null);
                                    $birth = data_get($c, 'birth.date') ?: data_get($c, 'birth_date');
                                    $teamName = data_get($c, 'team.name') ?: data_get($c, 'team_name') ?: '—';
                                @endphp

                                <tr>
                                    <td>
                                        @if($photo)
                                            <img src="{{ $photo }}" class="coach-photo"
                                                 onerror="this.src='https://via.placeholder.com/48?text=%F0%9F%91%A4'">
                                        @endif
                                    </td>
                                    <td><strong>{{ data_get($c, 'name', '—') }}</strong></td>
                                    <td>{{ data_get($c, 'age', '—') }}</td>
                                    <td>{{ data_get($c, 'nationality', '—') }}</td>
                                    <td>{{ $birth ?: '—' }}</td>
                                    <td>{{ $teamName }}</td>
                                    <td>{{ $id ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted fst-italic py-4">
                                        Nenhum treinador encontrado (API).
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    const leaguesByCountryUrl = @json($leaguesByCountryRoute);

    function normalize(str) {
        return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    // === Season sync (BUGFIX) ===
    const seasonBox = document.getElementById('filterSeasonBox');
    const seasonInput = document.getElementById('seasonInput');
    const syncSeasonHidden = document.getElementById('syncSeasonHidden');

    function syncSeasonEverywhere(pushToUrl = true) {
        const s = seasonBox?.value || '';
        if (!s) return;

        // GET form
        if (seasonInput) seasonInput.value = s;

        // POST sync hidden
        if (syncSeasonHidden) syncSeasonHidden.value = s;

        // manter URL consistente (para o form e para “Aplicar”)
        if (pushToUrl) {
            const u = new URL(window.location.href);
            u.searchParams.set('season', s);
            window.history.replaceState({}, '', u.toString());
        }
    }

    seasonBox?.addEventListener('change', () => syncSeasonEverywhere(true));
    // inicial
    syncSeasonEverywhere(false);

    // === Países search + collapse all ===
    document.getElementById('countrySearch')?.addEventListener('input', function () {
        const q = normalize(this.value.trim());
        document.querySelectorAll('.country-item').forEach(el => {
            const name = normalize(el.dataset.countryName || '');
            el.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    });

    document.getElementById('collapseAllBtn')?.addEventListener('click', () => {
        document.querySelectorAll('.accordion-collapse.show').forEach(c => {
            try { bootstrap.Collapse.getInstance(c)?.hide(); } catch (e) {}
        });
    });

    // === Carregar ligas por país ===
    document.getElementById('countriesAccordion')?.addEventListener('show.bs.collapse', ev => {
        const el = ev.target;
        const list = el.querySelector('.leagues-list');
        if (!list) return;

        if (!leaguesByCountryUrl || leaguesByCountryUrl === '#') {
            list.innerHTML = '<div class="text-danger small">Rota leagues_by_country em falta.</div>';
            return;
        }

        const country = list.dataset.country;
        const season = document.getElementById('filterSeasonBox')?.value || '';

        // garantir coerência (BUGFIX) sempre que abres o país
        syncSeasonEverywhere(false);

        list.innerHTML = '<div class="text-center small text-muted py-2">A carregar ligas…</div>';

        fetch(`${leaguesByCountryUrl}?country=${encodeURIComponent(country)}&season=${season}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(json => {
            if (!json?.leagues?.length) {
                list.innerHTML = '<div class="text-muted small">Sem ligas para este país.</div>';
                return;
            }

            const group = document.createElement('div');
            group.className = 'list-group';

            json.leagues.forEach(l => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                const left = document.createElement('div');
                left.className = 'd-flex align-items-center';

                if (l.logo) {
                    const img = document.createElement('img');
                    img.src = l.logo;
                    img.alt = l.name;
                    img.style.width = '26px';
                    img.style.height = '26px';
                    img.style.objectFit = 'contain';
                    img.style.marginRight = '10px';
                    left.appendChild(img);
                }

                const span = document.createElement('span');
                span.textContent = `${l.name} (${l.id})`;
                left.appendChild(span);

                item.appendChild(left);

                item.addEventListener('click', () => {
                    // ao escolher liga, garante que season do form está igual ao da esquerda (BUGFIX)
                    syncSeasonEverywhere(true);

                    const leagueIdInput = document.getElementById('leagueIdInput');
                    if (leagueIdInput) leagueIdInput.value = String(l.id);

                    // manter URL consistente
                    const u = new URL(window.location.href);
                    u.searchParams.set('league_id', String(l.id));
                    window.history.replaceState({}, '', u.toString());
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

    // === Sync Mode (team vs league) ===
    document.getElementById('syncMode')?.addEventListener('change', function () {
        const form = document.getElementById('syncForm');
        if (!form) return;

        const mode = this.value;
        const leagueHidden = document.getElementById('syncLeagueHidden');
        const teamHidden = document.getElementById('syncTeamHidden');

        // refresca sempre valores actuais do URL antes de submeter (evita stale)
        const params = new URLSearchParams(window.location.search);
        if (syncSeasonHidden) syncSeasonHidden.value = params.get('season') || (seasonInput?.value || '');
        const searchHidden = document.getElementById('syncSearchHidden');
        if (searchHidden) searchHidden.value = params.get('search') || '';
        if (teamHidden) teamHidden.value = params.get('team') || '';
        if (leagueHidden) leagueHidden.value = params.get('league_id') || '';

        if (mode === 'team') {
            if (leagueHidden) leagueHidden.value = '';
        } else {
            // league: mantém league_id
        }
    });

})();
</script>
@endsection
