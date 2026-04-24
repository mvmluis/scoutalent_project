@extends('coachsManual.layout.app')

@section('main-content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <div class="container-fluid my-5">

        {{-- CABEÇALHO --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold text-danger mb-0">
                <i class="bi bi-person-badge-fill me-2"></i>Lista de Treinadores
            </h2>

            <div class="d-flex gap-2">
                <a href="{{ route('manual.coachs.index') }}"
                    class="btn btn-outline-secondary shadow-sm fw-semibold px-4 py-2">
                    <i class="bi bi-x-circle me-1"></i> Limpar
                </a>

                <a href="{{ route('manual.players.index') }}"
                    class="btn btn-outline-danger shadow-sm fw-semibold px-4 py-2">
                    <i class="bi bi-people-fill me-1"></i> Jogadores
                </a>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card shadow-sm border-0 mb-5 filter-card">
            <div class="card-header bg-gradient text-white py-3 rounded-top">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-funnel-fill me-2"></i>Filtrar Treinadores
                </h5>
            </div>

            <div class="card-body bg-white rounded-bottom p-4">
                <form id="filterForm" class="row gy-3 gx-3 align-items-center">

                    {{-- Nome --}}
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label fw-semibold text-dark">Nome</label>
                        <input type="text" name="name" class="form-control form-control-lg filter-input"
                            placeholder="Ex: José Mourinho">
                    </div>

                    {{-- Ano --}}
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label fw-semibold text-dark">Ano</label>
                        <select name="year" id="yearSelect" class="form-select form-select-lg filter-input">
                            <option value="">Todos</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- País --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">País da Liga</label>
                            <select id="countrySelect" name="country_id" class="form-select form-select-lg filter-input">
                                <option value="">Todos</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" data-flag="{{ $country->flag }}">
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

                    {{-- Liga (AJAX) --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">Liga</label>
                            <select id="leagueSelect" name="league_id" class="form-select form-select-lg filter-input">
                                <option value="">Seleciona um país (ou deixa em Todos)</option>
                            </select>
                        </div>
                        <div class="ms-2 mt-3">
                            <img id="leaguePreview" src="" alt="logo"
                                style="width:34px;height:34px;object-fit:contain;border-radius:4px;display:none;border:1px solid #e6e6e6;background:#fff;padding:3px;">
                        </div>
                    </div>

                    {{-- Equipa (AJAX) --}}
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex align-items-start">
                        <div style="flex:1">
                            <label class="form-label fw-semibold text-dark">Equipa</label>
                            <select id="teamSelect" name="team" class="form-select form-select-lg filter-input">
                                <option value="">Seleciona uma liga</option>
                            </select>
                        </div>
                        <div class="ms-2 mt-3">
                            <img id="teamPreview" src="" alt="team"
                                style="width:34px;height:34px;object-fit:contain;border-radius:4px;display:none;border:1px solid #e6e6e6;background:#fff;padding:3px;">
                        </div>
                    </div>

                    {{-- Idade min/max --}}
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <label class="form-label fw-semibold text-dark text-center d-block">Idade Mín.</label>
                        <input type="number" name="age_min" class="form-control form-control-lg filter-input text-center">
                    </div>
                    <div class="col-lg-1 col-md-2 col-sm-3">
                        <label class="form-label fw-semibold text-dark text-center d-block">Idade Máx.</label>
                        <input type="number" name="age_max" class="form-control form-control-lg filter-input text-center">
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
                <table class="table align-middle text-center mb-0" style="min-width:1300px;">
                    <thead class="custom-header">
                        <tr>
                            <th>FOTO</th>
                            <th class="sortable" data-sort="name" style="min-width:260px;">NOME <i
                                    class="bi bi-arrow-down-up text-muted ms-1"></i></th>
                            <th>NACIONALIDADE</th>
                            <th class="sortable" data-sort="age">IDADE <i class="bi bi-arrow-down-up text-muted ms-1"></i>
                            </th>
                            <th style="min-width:200px;">EQUIPA</th>
                            <th style="min-width:240px;">LIGA</th>
                            <th style="min-width:170px;">PAÍS (LIGA)</th>
                            <th class="sortable" data-sort="created_at">INSERIDO <i
                                    class="bi bi-arrow-down-up text-muted ms-1"></i></th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody id="coachsTbody">
                        <tr>
                            <td colspan="9" class="text-muted fst-italic py-4">A carregar…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" id="pageInfo">—</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary shadow-sm fw-semibold px-4 py-2" id="prevBtn" disabled>
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>
                    <button class="btn btn-outline-primary shadow-sm fw-semibold px-4 py-2" id="nextBtn" disabled>
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
            background: linear-gradient(135deg, #0d6efd, #1f5bd7);
            border: none;
        }

        .filter-input {
            background: #fff !important;
            border: 1px solid #e1e3e6 !important;
            border-radius: 10px;
            height: 44px;
            font-size: 0.95rem;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .04);
            transition: all .15s ease;
        }

        .filter-input:focus {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 .12rem rgba(13, 110, 253, .10);
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
            font-size: .85rem;
            white-space: nowrap;
            cursor: pointer;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('filterForm');
            const tbody = document.getElementById('coachsTbody');

            const loadingBadge = document.getElementById('loadingBadge');
            const errorBox = document.getElementById('errorBox');

            const countrySelect = document.getElementById('countrySelect');
            const leagueSelect = document.getElementById('leagueSelect');
            const teamSelect = document.getElementById('teamSelect');
            const yearSelect = document.getElementById('yearSelect');

            const countryPreview = document.getElementById('countryPreview');
            const leaguePreview = document.getElementById('leaguePreview');
            const teamPreview = document.getElementById('teamPreview');

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');

            const DATA_URL = @json(route('manual.coachs.data'));
            const LEAGUES_URL = @json(route('manual.coachs.leagues'));
            const TEAMS_URL = @json(route('manual.coachs.teams'));

            let currentSort = 'name';
            let currentDir = 'asc';
            let currentPage = 1;

            // ✅ guarda SEMPRE a query usada no último load
            let lastQs = '';

            let lastMeta = {
                current_page: 1,
                total_pages: 1,
                total: 0,
                next: null,
                prev: null
            };

            function setLoading(on) {
                loadingBadge?.classList.toggle('d-none', !on);
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

            function buildParams(extra = {}) {
                const fd = new FormData(form);
                const params = new URLSearchParams();

                for (const [k, v] of fd.entries()) {
                    const val = (v ?? '').toString().trim();
                    if (val !== '') params.set(k, val);
                }

                params.set('sort', currentSort);
                params.set('direction', currentDir);

                Object.entries(extra).forEach(([k, v]) => {
                    if (v === null || v === undefined || v === '') params.delete(k);
                    else params.set(k, v);
                });

                return params;
            }

            // ✅ Mantém a URL sincronizada (opcional, mas útil)
            function syncUrlFromParams(params) {
                const url = new URL(window.location.href);
                url.search = params.toString() ? `?${params.toString()}` : '';
                window.history.replaceState({}, '', url.toString());
            }

            async function safeJson(res) {
                const ct = (res.headers.get('content-type') || '').toLowerCase();
                if (!ct.includes('application/json')) {
                    const txt = await res.text();
                    throw new Error('Resposta não é JSON (provável HTML/redirect): ' + txt.slice(0, 160));
                }
                return res.json();
            }

            // ✅ recebe qs explicitamente (não depende de window.location.search)
            function renderRows(rows, qs) {
                if (!rows || rows.length === 0) {
                    tbody.innerHTML =
                        `<tr><td colspan="9" class="text-muted fst-italic py-4">Nenhum treinador encontrado.</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(c => {
                    const photo = c.photo ?? '/images/default-player.png';

                    const teamLogo = c.team_logo ? `
                <img src="${c.team_logo}" style="width:28px;height:28px;object-fit:contain;margin-right:8px;vertical-align:middle;border-radius:4px;border:1px solid #eee;padding:2px;background:#fff;">
            ` : '';

                    const leagueLogo = c.league_logo ? `
                <img src="${c.league_logo}" style="width:24px;height:24px;object-fit:contain;margin-right:8px;vertical-align:middle;">
            ` : '';

                    const flag = c.league_country_flag ? `
                <img src="${c.league_country_flag}" style="width:26px;height:18px;object-fit:cover;margin-right:8px;vertical-align:middle;border-radius:3px;border:1px solid #eee;">
            ` : '';

                    const created = c.created_at ? String(c.created_at).slice(0, 10) : '—';

                    return `
                <tr class="align-middle">
                    <td>
                        <img src="${photo}" class="rounded-circle shadow-sm border border-light"
                             width="48" height="48" style="object-fit: cover;">
                    </td>

                    <td class="fw-semibold text-dark text-start">
                        ${c.name ?? '—'}
                        <div class="text-muted" style="font-size:12px;">
                            API ID: ${c.external_id ?? '—'} · Nasc.: ${c.birth_date ?? '—'}
                        </div>
                    </td>

                    <td>${c.nationality ?? '—'}</td>
                    <td>${c.age ?? '—'}</td>

                    <td class="text-start">
                        ${teamLogo}<span>${c.team_name ?? '—'}</span>
                    </td>

                    <td class="text-start">
                        ${leagueLogo}<span>${c.league_name ?? '—'}</span>
                    </td>

                    <td class="text-start">
                        ${flag}<span>${c.league_country ?? '—'}</span>
                    </td>

                    <td class="text-muted">${created}</td>

                    <td>
                        <!-- ✅ AGORA VAI SEMPRE COM OS FILTROS -->
                        <a href="/manual/coachs/${c.id}${qs}" class="btn btn-sm btn-outline-dark px-3">
                            <i class="bi bi-eye me-1"></i> Ver
                        </a>
                    </td>
                </tr>
            `;
                }).join('');
            }

            async function loadCoachs(page = 1) {
                setError(null);
                setLoading(true);

                tbody.innerHTML =
                `<tr><td colspan="9" class="text-muted fst-italic py-4">A carregar…</td></tr>`;

                try {
                    currentPage = Math.max(1, parseInt(page || 1, 10));
                    const params = buildParams({
                        page: currentPage
                    });

                    // ✅ fixa a query usada neste load
                    lastQs = params.toString() ? `?${params.toString()}` : '';

                    // (opcional) manter URL igual ao estado actual
                    syncUrlFromParams(params);

                    const url = `${DATA_URL}${lastQs}`;

                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        const txt = await res.text();
                        throw new Error(`HTTP ${res.status} - ${txt.slice(0,200)}`);
                    }

                    const json = await safeJson(res);

                    renderRows(json.data || [], lastQs);

                    lastMeta = json.meta || lastMeta;
                    prevBtn.disabled = !lastMeta.prev;
                    nextBtn.disabled = !lastMeta.next;

                    pageInfo.textContent =
                        `Página ${lastMeta.current_page ?? currentPage} de ${lastMeta.total_pages ?? 1} · Total: ${lastMeta.total ?? 0}`;

                } catch (err) {
                    console.error(err);
                    setError(err.message);
                    tbody.innerHTML =
                    `<tr><td colspan="9" class="text-danger py-4">Erro ao carregar.</td></tr>`;
                } finally {
                    setLoading(false);
                }
            }

            async function loadLeagues() {
                const countryId = countrySelect.value || '';

                leagueSelect.innerHTML = `<option value="">A carregar ligas…</option>`;
                teamSelect.innerHTML = `<option value="">Seleciona uma liga</option>`;
                leaguePreview.style.display = 'none';
                teamPreview.style.display = 'none';

                const url = new URL(LEAGUES_URL);
                if (countryId) url.searchParams.set('country_id', countryId);

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const leagues = await safeJson(res);

                leagueSelect.innerHTML = `<option value="">Todas</option>` + (leagues || []).map(l => `
            <option value="${l.external_id}" data-logo="${l.logo ?? ''}">
                ${(l.country ? l.country + ' — ' : '') + (l.name ?? '—')}
            </option>
        `).join('');
            }

            async function loadTeams() {
                const leagueId = leagueSelect.value || '';
                if (!leagueId) {
                    teamSelect.innerHTML = `<option value="">Seleciona uma liga</option>`;
                    return;
                }

                teamSelect.innerHTML = `<option value="">A carregar equipas…</option>`;
                teamPreview.style.display = 'none';

                const url = new URL(TEAMS_URL);
                url.searchParams.set('league_id', leagueId);

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const teams = await safeJson(res);

                teamSelect.innerHTML = `<option value="">Todas</option>` + (teams || []).map(t => `
            <option value="${t.name}" data-logo="${t.logo ?? ''}">${t.name}</option>
        `).join('');
            }

            // Debounce filtros
            let tmr;
            form.querySelectorAll('.filter-input').forEach(el => {
                const type = el.tagName.toLowerCase();
                const evt = (type === 'input') ? 'input' : 'change';

                el.addEventListener(evt, () => {
                    clearTimeout(tmr);
                    tmr = setTimeout(() => loadCoachs(1), (evt === 'input' ? 450 : 0));
                });
            });

            // Dropdowns dependentes + previews
            countrySelect.addEventListener('change', async () => {
                setPreview(countrySelect, countryPreview, 'flag');
                leagueSelect.value = '';
                teamSelect.value = '';
                await loadLeagues();
                await loadCoachs(1);
            });

            leagueSelect.addEventListener('change', async () => {
                setPreview(leagueSelect, leaguePreview, 'logo');
                teamSelect.value = '';
                await loadTeams();
                await loadCoachs(1);
            });

            teamSelect.addEventListener('change', () => {
                setPreview(teamSelect, teamPreview, 'logo');
                loadCoachs(1);
            });

            yearSelect.addEventListener('change', () => loadCoachs(1));

            // Sorting
            document.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const col = th.dataset.sort;
                    if (!col) return;

                    if (currentSort === col) currentDir = (currentDir === 'asc' ? 'desc' : 'asc');
                    else {
                        currentSort = col;
                        currentDir = 'asc';
                    }

                    loadCoachs(1);
                });
            });

            // Paginação por page
            nextBtn.addEventListener('click', () => {
                if (!lastMeta.next) return;
                loadCoachs((lastMeta.current_page || currentPage) + 1);
            });

            prevBtn.addEventListener('click', () => {
                if (!lastMeta.prev) return;
                loadCoachs(Math.max(1, (lastMeta.current_page || currentPage) - 1));
            });

            // init
            setPreview(countrySelect, countryPreview, 'flag');
            loadLeagues().then(() => loadCoachs(1)).catch(err => {
                console.error(err);
                tbody.innerHTML =
                    `<tr><td colspan="9" class="text-danger py-4">Erro: ${err.message}</td></tr>`;
            });
        });
    </script>
@endsection
