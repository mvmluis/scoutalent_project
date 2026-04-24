@extends('estatisticas_db.layout.app')

@section('page')
@php
    $countries = $countries ?? collect(); // vem da BD (countries.name)
    $seasons   = $seasons ?? collect();

    $country  = $country ?? null;
    $leagueId = $leagueId ?? null; // leagues.external_id
    $teamId   = $teamId ?? null;   // teams.external_id
    $season   = $season ?? now()->year;

    $leagues = $leagues ?? [];      // carregado via AJAX (BD)
    $teams   = $teams ?? collect(); // carregado via AJAX (BD)
@endphp

<style>
    .page-wrap{ margin-left:0 !important; padding: 18px; position: relative; z-index: 1; }
    @media (max-width:1199px){ .page-wrap{ margin-left:0 !important; padding: 12px; } }

    .card-soft{ border-radius: 18px; overflow: hidden; border: 1px solid rgba(0,0,0,.06); }
    .card-head{ background: #fff; border-bottom: 1px solid rgba(0,0,0,.06); padding: 14px 16px; }
    .card-body-pad{ padding: 14px 16px; }

    .muted{ color:#6c757d; }
    .pill{ background:#f1f3f5; color:#333; border-radius: 999px; padding: .25rem .65rem; font-size: .85rem; border: 1px solid rgba(0,0,0,.05); }

    .kpi{ border: 1px solid rgba(0,0,0,.06); border-radius: 16px; padding: 12px 14px; background: #fff; }
    .kpi .v{ font-weight: 900; font-size: 1.1rem; line-height: 1.1; }
    .kpi .l{ font-size: .85rem; color:#6c757d; }

    .form-label{ font-size: .85rem; color:#6c757d; margin-bottom: .35rem; }
    .hint{ font-size: .82rem; color:#6c757d; }

    .input-icon{ position: relative; }
    .input-icon i{ position: absolute; left: 10px; top: 50%; transform: translateY(-50%); opacity: .75; pointer-events: none; font-size: .95rem; }
    .input-icon select{ padding-left: 34px !important; }

    .logo-xxs{ width: 22px; height: 22px; object-fit: contain; border-radius: 6px; background: #fff; }
    .table td, .table th{ vertical-align: middle !important; }
    .table thead th{ font-size: .85rem; letter-spacing: .2px; }

    .team-title{ font-weight: 800; }
    .subline{ font-size: .85rem; color:#6c757d; }

    .truncate{
        display:block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .table-fixed{ table-layout: fixed; width: 100%; }
    .table-fixed th, .table-fixed td{ overflow: hidden; }

    .col-team{ width: 300px; }
    .col-league{ width: 300px; }
    .col-season{ width: 80px; }
    .col-form{ width: 190px; }
    .col-num{ width: 90px; }

    .form-badges{
        display:flex;
        gap:6px;
        align-items:center;
        flex-wrap:nowrap;
        overflow-x:auto;
        scrollbar-width: thin;
        padding-bottom: 2px;
    }
    .form-badge{
        display:inline-flex; align-items:center; justify-content:center;
        min-width: 26px; height: 22px; padding: 0 8px;
        border-radius: 999px; font-weight: 800; font-size: .78rem;
        border: 1px solid rgba(0,0,0,.08);
        background: #f1f3f5; color:#333;
        flex: 0 0 auto;
    }
    .form-badge.w{ background:#e7f7ee; color:#1e7e34; border-color: rgba(30,126,52,.15); }
    .form-badge.d{ background:#eef2ff; color:#2f3a8f; border-color: rgba(47,58,143,.15); }
    .form-badge.l{ background:#ffecec; color:#c82333; border-color: rgba(200,35,51,.15); }
    .form-badge.u{ background:#f1f3f5; color:#6c757d; }

    .mini-legend{
        font-size:.8rem; color:#6c757d;
        display:flex; gap:10px; align-items:center; flex-wrap:wrap;
    }
    .mini-legend .dot{ width:10px; height:10px; border-radius:999px; display:inline-block; margin-right:6px; }
    .dot.w{ background:#1e7e34; }
    .dot.d{ background:#2f3a8f; }
    .dot.l{ background:#c82333; }

    .num-chip{
        display:inline-flex; align-items:center; justify-content:center;
        min-width: 56px; height: 24px; padding: 0 10px;
        border-radius: 999px; font-weight: 800; font-size: .8rem;
        border: 1px solid rgba(0,0,0,.08);
        background:#f8f9fa; color:#333;
        white-space: nowrap;
    }
    .num-chip.low{ background:#fff3cd; color:#8a6d3b; border-color: rgba(138,109,59,.15); }
    .num-chip.mid{ background:#e2f0ff; color:#0b5ed7; border-color: rgba(11,94,215,.15); }
    .num-chip.high{ background:#e7f7ee; color:#1e7e34; border-color: rgba(30,126,52,.15); }
</style>

<div class="page-wrap">

    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="h5 mb-0"><i class="fas fa-chart-line me-2"></i>Estatísticas (BD)</div>
            <div class="muted small">Filtros (País/Liga/Equipa) vêm da BD. Estatísticas vêm da BD.</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('estatisticas.bd') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-eraser me-1"></i> Limpar
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Filtros --}}
        <div class="col-lg-3">
            <div class="card card-soft shadow-sm">
                <div class="card-head d-flex justify-content-between align-items-center">
                    <div class="fw-semibold"><i class="fas fa-filter me-2"></i>Filtros</div>
                    <span class="pill">BD</span>
                </div>

                <div class="card-body-pad">
                    <form method="GET" action="{{ route('estatisticas.bd') }}" id="filtersForm" onsubmit="return false;">
                        <div class="mb-2">
                            <label class="form-label"><i class="far fa-calendar-alt me-1"></i>Época</label>
                            <div class="input-icon">
                                <i class="far fa-calendar"></i>
                                <select name="season" id="seasonBox" class="form-select form-select-sm">
                                    @foreach($seasons as $y)
                                        <option value="{{ $y }}" {{ (int)$y === (int)$season ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                    @if($seasons->isEmpty())
                                        <option value="{{ $season }}" selected>{{ $season }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><i class="fas fa-globe-europe me-1"></i>País</label>
                            <div class="input-icon">
                                <i class="fas fa-flag"></i>
                                <select name="country" id="countryBox" class="form-select form-select-sm">
                                    <option value="">— Todos —</option>
                                    @foreach($countries as $c)
                                        <option value="{{ $c }}" {{ (string)$country === (string)$c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hint mt-1" id="countryHint">Seleciona um país.</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><i class="fas fa-trophy me-1"></i>Liga</label>
                            <div class="input-icon">
                                <i class="fas fa-trophy"></i>
                                <select name="league" id="leagueBox" class="form-select form-select-sm" {{ $country ? '' : 'disabled' }}>
                                    <option value="">— Todas —</option>
                                    @foreach($leagues as $l)
                                        <option value="{{ $l['id'] }}" {{ (string)$leagueId === (string)$l['id'] ? 'selected' : '' }}>
                                            {{ $l['name'] ?? ('Liga #' . $l['id']) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hint mt-1" id="leagueHint">{{ $country ? 'Seleciona uma liga.' : 'Seleciona um país primeiro.' }}</div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label"><i class="fas fa-shield-alt me-1"></i>Equipa</label>
                            <div class="input-icon">
                                <i class="fas fa-shield-alt"></i>
                                <select name="team" id="teamBox" class="form-select form-select-sm" {{ $leagueId ? '' : 'disabled' }}>
                                    <option value="">— Todas —</option>
                                    @foreach($teams as $t)
                                        <option value="{{ $t->id }}" {{ (string)$teamId === (string)$t->id ? 'selected' : '' }}>
                                            {{ $t->name ?? ('Equipa #' . $t->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hint mt-1" id="teamHint">{{ $leagueId ? 'Seleciona uma equipa.' : 'Seleciona uma liga primeiro.' }}</div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-12">
                    <div class="kpi">
                        <div class="l">Registos (página)</div>
                        <div class="v">{{ $stats->count() }}</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="kpi">
                        <div class="l">Total (com filtros)</div>
                        <div class="v">—</div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="pill">Época: <strong>{{ $season }}</strong></span>
                @if($country)  <span class="pill">País: <strong>{{ $country }}</strong></span> @endif
                @if($leagueId) <span class="pill">Liga: <strong>{{ $leagueId }}</strong></span> @endif
                @if($teamId)   <span class="pill">Equipa: <strong>{{ $teamId }}</strong></span> @endif
            </div>
        </div>

        {{-- Resultados --}}
        <div class="col-lg-9">
            <div class="card card-soft shadow-sm">
                <div class="card-head d-flex justify-content-between align-items-center">
                    <div class="fw-semibold"><i class="fas fa-list-ul me-2"></i>Resultados (BD)</div>
                    <div class="muted small">
                        Página {{ method_exists($stats,'currentPage') ? $stats->currentPage() : 1 }}
                    </div>
                </div>

                <div class="card-body-pad">
                    @if($stats->count() === 0)
                        <div class="alert mb-0 text-white" style="background:#ff9800; border:0;">
                            Não há registos na BD para estes filtros.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-fixed">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap col-team">Equipa</th>
                                        <th class="text-nowrap col-league">Liga</th>
                                        <th class="text-nowrap col-season">Época</th>

                                        <th class="text-nowrap col-form">
                                            Forma
                                            <span class="ms-1 muted"
                                                  title="W = Vitória · D = Empate · L = Derrota"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-placement="top">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </th>

                                        <th class="text-nowrap col-num">
                                            GF
                                            <span class="ms-1 muted"
                                                  title="GF = Goals For (média de golos marcados por jogo)"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-placement="top">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </th>

                                        <th class="text-nowrap col-num">
                                            GA
                                            <span class="ms-1 muted"
                                                  title="GA = Goals Against (média de golos sofridos por jogo)"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-placement="top">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </th>

                                        <th class="text-nowrap col-num">
                                            Jog
                                            <span class="ms-1 muted"
                                                  title="Jog = Fixtures Played (n.º de jogos considerados na estatística)"
                                                  data-bs-toggle="tooltip"
                                                  data-bs-placement="top">
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($stats as $s)
                                        @php
                                            $teamFull   = $s->team_name ?? ('Equipa #' . ($s->team_id_external ?? $s->team_id ?? '—'));
                                            $leagueFull = $s->league_name ?? ('Liga #' . ($s->league_id_external ?? $s->league_id ?? '—'));
                                            $teamKey    = $s->team_id_external ?? $s->team_id ?? '—';
                                            $leagueKey  = $s->league_id_external ?? $s->league_id ?? null;
                                        @endphp

                                        <tr>
                                            <td class="col-team">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if(!empty($s->team_logo))
                                                        <img src="{{ $s->team_logo }}" class="logo-xxs" onerror="this.style.display='none'">
                                                    @endif
                                                    <div style="min-width:0; width:100%;">
                                                        <div class="team-title truncate"
                                                             title="{{ $teamFull }}"
                                                             data-bs-toggle="tooltip"
                                                             data-bs-placement="top">
                                                            {{ $teamFull }}
                                                        </div>
                                                        <div class="subline truncate">ID: {{ $teamKey }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="col-league">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if(!empty($s->league_logo))
                                                        <img src="{{ $s->league_logo }}" class="logo-xxs" onerror="this.style.display='none'">
                                                    @endif
                                                    <div style="min-width:0; width:100%;">
                                                        <div class="team-title truncate"
                                                             title="{{ $leagueFull }}"
                                                             data-bs-toggle="tooltip"
                                                             data-bs-placement="top">
                                                            {{ $leagueFull }}
                                                        </div>
                                                        <div class="subline truncate">
                                                            {{ $s->league_country ?? '' }}@if($leagueKey) · ID: {{ $leagueKey }}@endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-nowrap col-season">{{ $s->season ?? '—' }}</td>

                                            <td class="col-form">
                                                @php
                                                    $form  = strtoupper(trim((string)($s->form ?? '')));
                                                    $chars = $form !== '' ? preg_split('//u', $form, -1, PREG_SPLIT_NO_EMPTY) : [];
                                                    $map = [
                                                        'W' => ['cls' => 'w', 'title' => 'W = Vitória'],
                                                        'D' => ['cls' => 'd', 'title' => 'D = Empate'],
                                                        'L' => ['cls' => 'l', 'title' => 'L = Derrota'],
                                                    ];
                                                @endphp

                                                @if(empty($chars))
                                                    <span class="muted">—</span>
                                                @else
                                                    <div class="form-badges"
                                                         title="Sequência (mais recente → mais antigo)"
                                                         data-bs-toggle="tooltip"
                                                         data-bs-placement="top">
                                                        @foreach($chars as $ch)
                                                            @php $cfg = $map[$ch] ?? ['cls' => 'u', 'title' => $ch.' = Desconhecido']; @endphp
                                                            <span class="form-badge {{ $cfg['cls'] }}">{{ $ch }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="text-nowrap col-num">
                                                @php
                                                    $gf = is_numeric($s->goals_for_avg ?? null) ? (float)$s->goals_for_avg : null;
                                                    $gfClass = $gf === null ? '' : ($gf < 1.0 ? 'low' : ($gf < 2.0 ? 'mid' : 'high'));
                                                @endphp
                                                @if($gf === null)
                                                    <span class="muted">—</span>
                                                @else
                                                    <span class="num-chip {{ $gfClass }}">{{ number_format($gf, 2, ',', '.') }}</span>
                                                @endif
                                            </td>

                                            <td class="text-nowrap col-num">
                                                @php
                                                    $ga = is_numeric($s->goals_against_avg ?? null) ? (float)$s->goals_against_avg : null;
                                                    $gaClass = $ga === null ? '' : ($ga < 1.0 ? 'high' : ($ga < 2.0 ? 'mid' : 'low'));
                                                @endphp
                                                @if($ga === null)
                                                    <span class="muted">—</span>
                                                @else
                                                    <span class="num-chip {{ $gaClass }}">{{ number_format($ga, 2, ',', '.') }}</span>
                                                @endif
                                            </td>

                                            <td class="text-nowrap col-num">
                                                @php
                                                    $j = is_numeric($s->fixtures_played ?? null) ? (int)$s->fixtures_played : null;
                                                    $jClass = $j === null ? '' : ($j < 10 ? 'low' : ($j < 25 ? 'mid' : 'high'));
                                                @endphp
                                                @if($j === null)
                                                    <span class="muted">—</span>
                                                @else
                                                    <span class="num-chip {{ $jClass }}">{{ $j }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mini-legend mt-2">
                                <span><span class="dot w"></span>W = Vitória</span>
                                <span><span class="dot d"></span>D = Empate</span>
                                <span><span class="dot l"></span>L = Derrota</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            {{ $stats->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = @json(route('estatisticas.bd'));

    const seasonBox  = document.getElementById('seasonBox');
    const countryBox = document.getElementById('countryBox');
    const leagueBox  = document.getElementById('leagueBox');
    const teamBox    = document.getElementById('teamBox');

    const leagueHint = document.getElementById('leagueHint');
    const teamHint   = document.getElementById('teamHint');

    let isHydrating = false;

    function resetSelect(sel, placeholder) {
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
    }

    function go(params) {
        const url = new URL(baseUrl, window.location.origin);
        Object.entries(params).forEach(([k,v]) => {
            if (v === null || v === undefined || v === '') url.searchParams.delete(k);
            else url.searchParams.set(k, String(v));
        });
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    async function loadLeagues(selectedLeagueId = null) {
        const country = countryBox.value;
        const season  = seasonBox.value;

        resetSelect(leagueBox, '— Todas —');
        resetSelect(teamBox, '— Todas —');

        teamBox.disabled = true;
        teamHint.textContent = 'Seleciona uma liga primeiro.';

        if (!country) {
            leagueBox.disabled = true;
            leagueHint.textContent = 'Seleciona um país primeiro.';
            return;
        }

        leagueBox.disabled = false;
        leagueHint.textContent = 'A carregar ligas…';

        const url = @json(route('estatisticas.bd.leagues'))
            + '?country=' + encodeURIComponent(country)
            + '&season=' + encodeURIComponent(season);

        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const json = await res.json();

        const leagues = json.leagues || [];
        leagues.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = (l.name || ('Liga #' + l.id));
            leagueBox.appendChild(opt);
        });

        if (selectedLeagueId) leagueBox.value = String(selectedLeagueId);
        leagueHint.textContent = leagues.length ? 'Seleciona uma liga.' : 'Sem ligas para este país/época.';
    }

    // ✅ FIX: enviar também country para o backend filtrar equipas
    async function loadTeams(selectedTeamId = null) {
        const league  = leagueBox.value;
        const season  = seasonBox.value;
        const country = countryBox.value; // <- importante

        resetSelect(teamBox, '— Todas —');

        if (!league) {
            teamBox.disabled = true;
            teamHint.textContent = 'Seleciona uma liga primeiro.';
            return;
        }

        teamBox.disabled = false;
        teamHint.textContent = 'A carregar equipas…';

        const url = @json(route('estatisticas.bd.teams'))
            + '?league=' + encodeURIComponent(league)
            + '&season=' + encodeURIComponent(season)
            + '&country=' + encodeURIComponent(country); // <- FIX

        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const json = await res.json();

        const teams = json.teams || [];
        teams.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = (t.name || ('Equipa #' + t.id));
            teamBox.appendChild(opt);
        });

        if (selectedTeamId) teamBox.value = String(selectedTeamId);
        teamHint.textContent = teams.length ? 'Seleciona uma equipa.' : 'Sem equipas para esta liga/época.';
    }

    (async function hydrate() {
        const initialCountry = @json($country);
        const initialLeague  = @json($leagueId);
        const initialTeam    = @json($teamId);

        isHydrating = true;

        if (initialCountry) {
            countryBox.value = String(initialCountry);
            await loadLeagues(initialLeague);

            if (initialLeague) {
                await loadTeams(initialTeam);
            }
        } else {
            leagueBox.disabled = true;
            teamBox.disabled = true;
        }

        isHydrating = false;

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    })();

    seasonBox.addEventListener('change', async () => {
        if (isHydrating) return;

        const season  = seasonBox.value;
        const country = countryBox.value;

        if (country) {
            isHydrating = true;
            await loadLeagues(null);
            isHydrating = false;
        }

        go({ season, country, league: '', team: '' });
    });

    countryBox.addEventListener('change', async () => {
        if (isHydrating) return;

        const season  = seasonBox.value;
        const country = countryBox.value;

        isHydrating = true;
        await loadLeagues(null);
        isHydrating = false;

        go({ season, country, league: '', team: '' });
    });

    leagueBox.addEventListener('change', async () => {
        if (isHydrating) return;

        const season  = seasonBox.value;
        const country = countryBox.value;
        const league  = leagueBox.value;

        isHydrating = true;
        await loadTeams(null);
        isHydrating = false;

        go({ season, country, league, team: '' });
    });

    teamBox.addEventListener('change', () => {
        if (isHydrating) return;

        const season  = seasonBox.value;
        const country = countryBox.value;
        const league  = leagueBox.value;
        const team    = teamBox.value;

        go({ season, country, league, team });
    });
});
</script>

@endsection

@section('content')
    @yield('page')
@endsection

@section('main-content')
    @yield('page')
@endsection
