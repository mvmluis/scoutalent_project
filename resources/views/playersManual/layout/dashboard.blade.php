{{-- ✅ View (playersManual/layout/dashboard.blade.php) --}}
@extends('playersManual.layout.app')

@section('main-content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <div class="container-fluid my-5">

        {{-- CABEÇALHO --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold text-danger mb-0">
                <i class="bi bi-people-fill me-2"></i>Lista de Jogadores
            </h2>

            <div class="d-flex gap-2">
                <a href="{{ route('manual.players.index') }}"
                   class="btn btn-outline-secondary shadow-sm fw-semibold px-4 py-2">
                    <i class="bi bi-x-circle me-1"></i> Limpar
                </a>

                {{-- ✅ id para o JS conseguir reescrever o href após mudares filtros via AJAX --}}
                <a id="btnFollowed"
                   href="{{ route('players.followed', request()->query()) }}"
                   class="btn btn-outline-danger shadow-sm fw-semibold px-4 py-2">
                    <i class="bi bi-heart me-1"></i> Seguidos
                </a>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card shadow-sm border-0 mb-5 filter-card">
            <div class="card-header bg-gradient text-white py-3 rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-funnel-fill me-2"></i>Filtrar Jogadores
                </h5>
            </div>

            <div class="card-body bg-white rounded-bottom p-4">
                <form id="filterForm" method="GET" class="row gy-3 gx-3 align-items-center">

                    {{-- Nome --}}
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label fw-semibold text-dark">Nome</label>
                        <input type="text" name="name" value="{{ request('name') }}"
                               class="form-control form-control-lg filter-input" placeholder="Ex: João Silva">
                    </div>

                    {{-- Ano --}}
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label fw-semibold text-dark">Ano</label>
                        <select id="yearSelect" name="year" class="form-select form-select-lg filter-input">
                            <option value="">Todos</option>
                            @foreach ($years ?? [] as $y)
                                <option value="{{ $y }}"
                                        {{ (string) request('year') === (string) $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- País --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">País da Liga / Equipa</label>
                            <select id="countrySelect" name="country_id" class="form-select form-select-lg filter-input">
                                <option value="">Todos</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" data-flag="{{ $country->flag }}"
                                            {{ (string) request('country_id') === (string) $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ms-2 mt-3">
                            <img id="countryPreview" src="" alt="flag"
                                 style="width:34px;height:22px;object-fit:cover;border-radius:3px;display:none;border:1px solid #e6e6e6;">
                        </div>
                    </div>

                    {{-- Liga --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">Liga</label>
                            <select id="leagueSelect" name="league_id" class="form-select form-select-lg filter-input">
                                <option value="">Todas</option>
                                @foreach ($leagues as $league)
                                    <option value="{{ $league->id }}" data-logo="{{ $league->logo }}"
                                            data-country="{{ $league->country }}"
                                            {{ (string) request('league_id') === (string) $league->id ? 'selected' : '' }}>
                                        {{ ($league->country ? $league->country . ' — ' : '') . $league->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ms-2 mt-3">
                            <img id="leaguePreview" src="" alt="logo"
                                 style="width:34px;height:34px;object-fit:contain;border-radius:4px;display:none;
                                    border:1px solid #e6e6e6;background:#fff;padding:3px;">
                        </div>
                    </div>

                    {{-- Equipa --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">Equipa</label>
                            <select id="teamSelect" name="team_id" class="form-select form-select-lg filter-input">
                                <option value="">Todas</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" data-logo="{{ $team->logo }}"
                                            {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ms-2 mt-3">
                            <img id="teamPreview" src="" alt="team"
                                 style="width:34px;height:34px;object-fit:contain;border-radius:4px;display:none;
                                    border:1px solid #e6e6e6;background:#fff;padding:3px;">
                        </div>
                    </div>

                    {{-- Posição --}}
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label fw-semibold text-dark">Posição</label>
                        <select name="position" class="form-select form-select-lg filter-input">
                            <option value="">Todas</option>
                            @foreach ($positions as $pos)
                                <option value="{{ $pos }}" {{ request('position') === $pos ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Str::title($pos) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Idades --}}
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <label class="form-label fw-semibold text-dark text-center d-block">Idade Mín.</label>
                        <input type="number" name="age_min" value="{{ request('age_min') }}"
                               class="form-control form-control-lg filter-input text-center">
                    </div>

                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <label class="form-label fw-semibold text-dark text-center d-block">Idade Máx.</label>
                        <input type="number" name="age_max" value="{{ request('age_max') }}"
                               class="form-control form-control-lg filter-input text-center">
                    </div>

                </form>

                <div class="mt-3 d-flex align-items-center gap-3">
                    <div id="loadingBadge" class="badge bg-secondary d-none">
                        <i class="bi bi-arrow-repeat me-1"></i> A carregar…
                    </div>
                    <div id="errorBox" class="text-danger small d-none"></div>
                </div>
            </div>
        </div>

        {{-- LISTA --}}
        <div class="card shadow-lg border-0">
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="table align-middle text-center mb-0" id="playersTable" style="min-width:1200px;">
                    <thead class="custom-header">
                        <tr>
                            <th>FOTO</th>

                            @php
                                $currentSort = request('sort', 'name');
                                $currentDir = request('direction', 'asc');
                                $makeSortUrl = function ($col) use ($currentSort, $currentDir) {
                                    $nextDir = $currentSort === $col && $currentDir === 'asc' ? 'desc' : 'asc';
                                    return request()->fullUrlWithQuery([
                                        'sort' => $col,
                                        'direction' => $nextDir,
                                        'cursor' => null,
                                    ]);
                                };
                            @endphp

                            <th class="sortable" data-col="name">
                                <a href="{{ $makeSortUrl('name') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    NOME <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="age">
                                <a href="{{ $makeSortUrl('age') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    IDADE <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="team_name" style="min-width:180px;">
                                <a href="{{ $makeSortUrl('team_name') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    EQUIPA <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="position">
                                <a href="{{ $makeSortUrl('position') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    POSIÇÃO <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="height">
                                <a href="{{ $makeSortUrl('height') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    ALTURA <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="weight">
                                <a href="{{ $makeSortUrl('weight') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    PESO <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="rating">
                                <a href="{{ $makeSortUrl('rating') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    RATING <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="appearances">
                                <a href="{{ $makeSortUrl('appearances') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    JOGOS <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th class="sortable" data-col="goals">
                                <a href="{{ $makeSortUrl('goals') }}"
                                   class="sort-link text-decoration-none text-white fw-semibold">
                                    GOLOS <i class="bi bi-arrow-down-up sort-icon ms-1" style="font-size:0.95rem"></i>
                                </a>
                            </th>

                            <th>AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody id="playersTbody">
                        <tr>
                            <td colspan="11" class="text-muted fst-italic py-4">A carregar…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="text-muted small" id="pageInfo">—</div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary shadow-sm fw-semibold px-4 py-2" id="btnPrev" disabled>
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>
                    <button class="btn btn-outline-primary shadow-sm fw-semibold px-4 py-2" id="btnNext" disabled>
                        Seguinte <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <style>
        body {
            background-color: #f6f7fb;
            font-family: 'Poppins', sans-serif;
        }

        .card-footer {
            background: #f8f9fb !important;
            border-top: 1px solid #dee2e6;
            padding: 1.2rem 1.25rem;
            border-radius: 0 0 14px 14px;
        }

        .filter-card {
            border-radius: 14px;
            overflow: hidden;
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, #b31217, #e52d27);
            border: none;
        }

        .filter-card .card-body {
            padding: 1.6rem 1.75rem;
        }

        .filter-input {
            background: #fff !important;
            border: 1px solid #e1e3e6 !important;
            border-radius: 10px;
            height: 44px;
            font-size: 0.95rem;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
            transition: all .15s ease;
        }

        .filter-input:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.12rem rgba(220, 53, 69, .10);
        }

        .custom-header {
            background: linear-gradient(135deg, #1b2b52, #0a1733);
            color: #fff;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            z-index: 1010;
        }

        .custom-header th {
            font-weight: 600;
            padding: 12px 16px;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        th.sortable {
            cursor: pointer;
        }

        th.sortable a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            height: 100%;
            color: #fff;
        }

        th.sortable a:hover {
            text-decoration: underline;
            color: #fff;
        }

        @media (max-width: 992px) {
            .filter-card .card-body {
                padding: 1rem;
            }

            .custom-header th {
                font-size: 0.78rem;
                padding: 10px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const form = document.getElementById('filterForm');
            const tbody = document.getElementById('playersTbody');

            const loadingBadge = document.getElementById('loadingBadge');
            const errorBox = document.getElementById('errorBox');

            const btnPrev = document.getElementById('btnPrev');
            const btnNext = document.getElementById('btnNext');
            const pageInfo = document.getElementById('pageInfo');

            const countrySelect = document.getElementById('countrySelect');
            const leagueSelect = document.getElementById('leagueSelect');
            const teamSelect = document.getElementById('teamSelect');

            const countryPreview = document.getElementById('countryPreview');
            const leaguePreview = document.getElementById('leaguePreview');
            const teamPreview = document.getElementById('teamPreview');

            // ✅ base URL para o botão "Seguidos" (evita hardcode e problemas de escaping)
            const followedBaseUrl = @json(route('players.followed'));

            let nextCursor = null;
            let prevCursor = null;

            let currentPage = 1;
            let totalPlayers = 0;
            let perPage = 15;

            function setLoading(on) {
                if (!loadingBadge) return;
                loadingBadge.classList.toggle('d-none', !on);
            }

            function setError(msg) {
                if (!errorBox) return;
                if (!msg) {
                    errorBox.classList.add('d-none');
                    errorBox.textContent = '';
                    return;
                }
                errorBox.textContent = msg;
                errorBox.classList.remove('d-none');
            }

            function setPreview(selectEl, previewEl, dataAttr) {
                if (!selectEl || !previewEl) return;
                const opt = selectEl.options[selectEl.selectedIndex];
                const url = opt ? opt.getAttribute('data-' + dataAttr) : null;
                if (url) {
                    previewEl.src = url;
                    previewEl.style.display = 'inline-block';
                } else {
                    previewEl.style.display = 'none';
                }
            }

            function populateTeamsSelect(teams) {
                if (!teamSelect) return;

                const currentSelected = teamSelect.value;
                teamSelect.innerHTML = `<option value="">Todas</option>`;

                (teams || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = String(t.id);
                    opt.textContent = t.name;
                    if (t.logo) opt.setAttribute('data-logo', t.logo);
                    if (String(t.id) === String(currentSelected)) opt.selected = true;
                    teamSelect.appendChild(opt);
                });

                setPreview(teamSelect, teamPreview, 'logo');
            }

            setPreview(countrySelect, countryPreview, 'flag');
            setPreview(leagueSelect, leaguePreview, 'logo');
            setPreview(teamSelect, teamPreview, 'logo');

            // ✅ Constrói params a partir do form + sort/direction do URL + extras
            function buildParams(extra = {}) {
                const fd = new FormData(form);
                const params = new URLSearchParams();

                for (const [k, v] of fd.entries()) {
                    const val = (v ?? '').toString().trim();
                    if (val !== '') params.set(k, val);
                }

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('sort')) params.set('sort', urlParams.get('sort'));
                if (urlParams.get('direction')) params.set('direction', urlParams.get('direction'));

                Object.entries(extra).forEach(([k, v]) => {
                    if (v === null || v === undefined || v === '') params.delete(k);
                    else params.set(k, v);
                });

                return params;
            }

            // ✅ Mantém a URL do browser sincronizada com os filtros (e atualiza o href do "Seguidos")
            function syncUrlFromParams(params) {
                const url = new URL(window.location.href);

                url.search = params.toString() ? `?${params.toString()}` : '';
                window.history.replaceState({}, '', url.toString());

                // ✅ Atualiza o botão "Seguidos" com os filtros actuais
                const btnFollowed = document.getElementById('btnFollowed');
                if (btnFollowed) {
                    btnFollowed.href = params.toString()
                        ? `${followedBaseUrl}?${params.toString()}`
                        : followedBaseUrl;
                }
            }

            function badgeHtml(rating) {
                if (rating === null || rating === undefined || rating === '')
                    return `<span class="text-muted">—</span>`;
                const r = Number(rating);
                const cls = r >= 7 ? 'bg-success' : (r >= 6 ? 'bg-warning text-dark' : 'bg-danger');
                return `<span class="badge px-3 py-2 fw-bold ${cls}">${r.toFixed(2)}</span>`;
            }

            function titleCase(s) {
                if (!s) return '—';
                return s.charAt(0).toUpperCase() + s.slice(1);
            }

            function rowHtml(p, followedIds) {
                const isFollowed = (followedIds || []).includes(p.id);

                const teamLabel = p.resolved_team ?? p.team_name ?? '—';
                const teamLogo = p.team_logo ?
                    `<img src="${p.team_logo}" alt="${teamLabel}"
                style="width:28px;height:28px;object-fit:contain;margin-right:8px;vertical-align:middle;border-radius:4px;border:1px solid #eee;padding:2px;background:#fff;">` :
                    '';

                const followBtn = isFollowed ?
                    `<button class="btn btn-sm btn-danger px-3 follow-btn" data-player-id="${p.id}" aria-pressed="true" title="Deixar de seguir">
                <i class="bi bi-heart-fill me-1"></i> A seguir
            </button>` :
                    `<button class="btn btn-sm btn-outline-danger px-3 follow-btn" data-player-id="${p.id}" aria-pressed="false" title="Seguir">
                <i class="bi bi-heart me-1"></i> Seguir
            </button>`;

                const qs = window.location.search || '';

                return `
            <tr class="align-middle">
                <td>
                    <img src="${p.photo ?? '/images/default-player.png'}"
                        class="rounded-circle shadow-sm border border-light"
                        width="48" height="48" style="object-fit: cover;">
                </td>

                <td class="fw-semibold text-dark text-start" style="min-width:200px;">
                    ${p.name ?? '—'}
                </td>

                <td>${p.age ?? '—'}</td>

                <td style="min-width:180px;" class="text-start">
                    ${teamLogo}<span>${teamLabel}</span>
                </td>

                <td>${p.position ? titleCase(p.position) : '—'}</td>

                <td>${p.height ?? '—'}</td>
                <td>${p.weight ?? '—'}</td>

                <td class="text-center">${badgeHtml(p.rating)}</td>

                <td>${p.appearances ?? 0}</td>
                <td>${p.goals ?? 0}</td>

                <td>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="/manual/players/${p.id}${qs}" class="btn btn-sm btn-outline-dark px-3">
                            <i class="bi bi-eye me-1"></i> Ver
                        </a>
                        ${followBtn}
                    </div>
                </td>
            </tr>
        `;
            }

            function updatePageInfo(rowsLen) {
                if (!totalPlayers) {
                    pageInfo.textContent = `Página ${currentPage} · —`;
                    return;
                }
                const start = totalPlayers === 0 ? 0 : (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, totalPlayers);

                pageInfo.textContent =
                    `Página ${currentPage} · jogadores ${start}–${end} de ${totalPlayers} (nesta página: ${rowsLen})`;
            }

            function updateSortIcons() {
                const urlParams = new URLSearchParams(window.location.search);
                const activeSort = urlParams.get('sort') || 'name';
                const activeDir = (urlParams.get('direction') || 'asc').toLowerCase();

                document.querySelectorAll('th.sortable').forEach(th => {
                    const col = th.getAttribute('data-col');
                    const icon = th.querySelector('.sort-icon');
                    if (!icon) return;

                    if (col !== activeSort) {
                        icon.className = 'bi bi-arrow-down-up sort-icon ms-1';
                        icon.style.opacity = '0.6';
                    } else {
                        icon.style.opacity = '1';
                        icon.className = activeDir === 'asc' ?
                            'bi bi-arrow-up sort-icon ms-1' :
                            'bi bi-arrow-down sort-icon ms-1';
                    }
                });
            }

            async function load(cursor = null, resetPage = false) {
                setError(null);
                setLoading(true);

                if (resetPage) currentPage = 1;

                tbody.innerHTML =
                    `<tr><td colspan="11" class="text-muted fst-italic py-4">A carregar…</td></tr>`;

                try {
                    const params = buildParams({ cursor });

                    // ✅ Sincroniza URL + atualiza href do "Seguidos"
                    syncUrlFromParams(params);

                    const url = `{{ route('manual.players.data') }}?${params.toString()}`;

                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('Erro ao carregar dados (' + res.status + ')');

                    const json = await res.json();

                    if (json.teams) populateTeamsSelect(json.teams);

                    const rows = json.data || [];
                    const followedIds = json.followedIds || [];

                    totalPlayers = Number(json.total ?? totalPlayers ?? 0);
                    perPage = Number(json.perPage ?? perPage ?? 15);

                    nextCursor = json.nextCursor || null;
                    prevCursor = json.prevCursor || null;

                    btnPrev.disabled = !prevCursor || currentPage <= 1;
                    btnNext.disabled = !nextCursor;

                    updatePageInfo(rows.length);

                    if (!rows.length) {
                        tbody.innerHTML =
                            `<tr><td colspan="11" class="text-muted fst-italic py-4">Nenhum jogador encontrado.</td></tr>`;
                    } else {
                        tbody.innerHTML = rows.map(p => rowHtml(p, followedIds)).join('');
                        attachFollowHandlers();
                    }

                } catch (e) {
                    console.error(e);
                    setError('Não foi possível carregar os jogadores. Vê a consola/laravel.log.');
                    tbody.innerHTML =
                        `<tr><td colspan="11" class="text-danger py-4">Erro ao carregar.</td></tr>`;
                } finally {
                    setLoading(false);
                    updateSortIcons();
                }
            }

            function attachFollowHandlers() {
                document.querySelectorAll('.follow-btn').forEach(btn => {
                    if (btn.dataset.bound === '1') return;
                    btn.dataset.bound = '1';

                    btn.addEventListener('click', async e => {
                        e.preventDefault();
                        const playerId = btn.dataset.playerId;

                        try {
                            const res = await fetch(`/players/${playerId}/follow`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({})
                            });

                            if (!res.ok) throw new Error('Erro na resposta da rede');
                            const data = await res.json();

                            if (data.status === 'followed') {
                                btn.classList.remove('btn-outline-danger');
                                btn.classList.add('btn-danger');
                                btn.innerHTML = '<i class="bi bi-heart-fill me-1"></i> A seguir';
                                btn.setAttribute('aria-pressed', 'true');
                                btn.setAttribute('title', 'Deixar de seguir');
                            } else if (data.status === 'unfollowed') {
                                btn.classList.remove('btn-danger');
                                btn.classList.add('btn-outline-danger');
                                btn.innerHTML = '<i class="bi bi-heart me-1"></i> Seguir';
                                btn.setAttribute('aria-pressed', 'false');
                                btn.setAttribute('title', 'Seguir');
                            } else if (data.error) {
                                alert(data.error);
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Ocorreu um erro. Tenta novamente.');
                        }
                    });
                });
            }

            btnNext.addEventListener('click', () => {
                if (!nextCursor) return;
                currentPage++;
                load(nextCursor, false);
            });

            btnPrev.addEventListener('click', () => {
                if (!prevCursor || currentPage <= 1) return;
                currentPage = Math.max(1, currentPage - 1);
                load(prevCursor, false);
            });

            // Preview flags/logos
            if (countrySelect) countrySelect.addEventListener('change', () => {
                setPreview(countrySelect, countryPreview, 'flag');
                load(null, true);
            });

            if (leagueSelect) leagueSelect.addEventListener('change', () => {
                setPreview(leagueSelect, leaguePreview, 'logo');

                if (teamSelect) {
                    teamSelect.value = '';
                    teamSelect.innerHTML = `<option value="">Todas</option>`;
                }
                if (teamPreview) teamPreview.style.display = 'none';

                load(null, true);
            });

            if (teamSelect) teamSelect.addEventListener('change', () => {
                setPreview(teamSelect, teamPreview, 'logo');
                load(null, true);
            });

            // Auto submit (reset página)
            let timeout;
            form.querySelectorAll('.filter-input').forEach(input => {
                if (input.tagName.toLowerCase() === 'input') {
                    input.addEventListener('input', () => {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => load(null, true), 500);
                    });
                } else {
                    input.addEventListener('change', () => load(null, true));
                }
            });

            // SORT: toggle robusto pelo estado do URL
            const thead = document.querySelector('#playersTable thead');
            thead.addEventListener('click', (e) => {
                const link = e.target.closest('a.sort-link');
                if (!link) return;

                e.preventDefault();

                const th = link.closest('th.sortable');
                const col = th ? th.getAttribute('data-col') : 'name';

                const cur = new URL(window.location.href);
                const curSort = (cur.searchParams.get('sort') || 'name');
                const curDir = (cur.searchParams.get('direction') || 'asc').toLowerCase();

                const nextDir = (curSort === col) ?
                    (curDir === 'asc' ? 'desc' : 'asc') :
                    'asc';

                cur.searchParams.set('sort', col);
                cur.searchParams.set('direction', nextDir);
                cur.searchParams.delete('cursor');

                window.history.pushState({}, '', cur.toString());

                updateSortIcons();
                load(null, true);
            });

            // Primeira carga
            updateSortIcons();
            load(null, true);
        });
    </script>
@endsection
