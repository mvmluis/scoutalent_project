@extends('ligas.layout.app')

@section('title', 'Ligas / Competitions')

@section('main-content')
    @php
        use Illuminate\Support\Facades\Route as R;

        $countries = $countries ?? [];
        $leagues = $leagues ?? [];
        $selectedCountry = old('country', $country ?? request()->query('country',''));
        $selectedSeason  = old('season', $season ?? request()->query('season', now()->year));
        $seasons = range(now()->year, now()->year - 5);

        // 🔹 Rotas atualizadas
        $fetchUrl = R::has('admin.players.leagues_by_country') ? route('admin.players.leagues_by_country') : '#';
       $syncUrl  = R::has('admin.competition.leagues.sync') ? route('admin.competition.leagues.sync') : '#';
    @endphp

    <style>
        .country-flag {
            width: 20px;
            height: auto;
            margin-right: 8px;
            vertical-align: middle;
        }

        .country-item { cursor: pointer; }
        .country-item.active {
            background: rgba(13,110,253,0.06);
            border-left: 3px solid #0d6efd;
        }

        .logo-competition {
            width: 40px; height: 40px; object-fit: contain;
            border-radius: 6px; background: #fff; padding: 4px;
        }

        .badge-coverage { margin-left:6px; font-size:.75rem; border-radius:6px; padding:4px 6px; }
        .list-empty { padding:2rem; text-align:center; color:#6c757d; }

        .alert.text-white { color:#fff !important; }
        .btn-close-white { filter: invert(1) brightness(2); }
    </style>

    <div class="container py-4">

        {{-- FLASH MESSAGES --}}
        @foreach (['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'] as $type => $class)
            @if(session($type))
                <div id="flash-{{ $type }}" class="alert alert-{{ $class }} alert-dismissible fade show text-white" role="alert">
                    <strong>{{ ucfirst($type) }}</strong> — {!! nl2br(e(session($type))) !!}
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            @endif
        @endforeach

        <div class="row g-3 mb-3">
            {{-- LEFT: países --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">🌍 Países</div>
                        <select id="seasonFilter" class="form-select form-select-sm" style="width:110px;">
                            <option value="">Todas</option>
                            @foreach($seasons as $s)
                                <option value="{{ $s }}" {{ (int)$s === (int)$selectedSeason ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body" style="max-height:56vh; overflow:auto;">
                        <div class="mb-2 text-muted small">Clica num país para listar competições disponíveis.</div>
                        <div class="list-group" id="countriesList">
                            @foreach($countries as $c)
                                @php
                                    $name = data_get($c,'name',$c);
                                    $code = data_get($c,'code','');
                                @endphp
                                <button type="button"
                                        class="list-group-item list-group-item-action country-item {{ $selectedCountry === $name ? 'active' : '' }}"
                                        data-country="{{ $name }}">
                                    @if($code)
                                        <img src="https://media.api-sports.io/flags/{{ strtolower($code) }}.svg" alt="{{ $code }}" class="country-flag">
                                    @endif
                                    <span>{{ $name }}</span>
                                </button>
                            @endforeach
                            @if(count($countries)===0)
                                <div class="text-muted p-2">Nenhum país disponível.</div>
                            @endif
                        </div>
                        <div class="mt-3 d-grid gap-2">
                            <button id="btnClearCountries" class="btn btn-sm btn-outline-secondary">Limpar seleção</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: ligas --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>🏆 Competições</strong>
                            <div class="text-muted small">Listagem de ligas / copas</div>
                        </div>
                        <button id="btnSyncAll" class="btn btn-danger btn-sm">🔁 Sync todas</button>
                    </div>

                    <div class="card-body">
                        <div id="leaguesStatus" class="mb-2"></div>
                        <div id="leaguesList" class="row g-3">
                            @if(!empty($leagues) && count($leagues) > 0)
                                @foreach($leagues as $l)
                                    @php
                                        $lid = data_get($l,'id', data_get($l,'league.id'));
                                        $name = data_get($l,'name', data_get($l,'league.name', '—'));
                                        $logo = data_get($l,'logo', data_get($l,'league.logo', "https://media.api-sports.io/football/leagues/{$lid}.png"));
                                        $coverage = data_get($l,'coverage', data_get($l,'league.coverage', []));
                                        $lseasons = data_get($l,'seasons', []);
                                    @endphp
                                    <div class="col-12 col-md-6">
                                        <div class="card h-100 p-2" data-league-id="{{ $lid }}">
                                            <div class="d-flex gap-3 align-items-center">
                                                <img src="{{ $logo }}" class="logo-competition" alt="{{ $name }}"
                                                     onerror="this.src='https://via.placeholder.com/40x40?text=?'">
                                                <div class="flex-fill">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <strong>{{ $name }}</strong>
                                                            <div class="small text-muted">
                                                                {{ count($lseasons) }} temporadas
                                                                @if(count($lseasons))
                                                                    — {{ collect($lseasons)->pluck('year')->implode(', ') }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-end small text-muted">ID: <strong>{{ $lid }}</strong></div>
                                                    </div>
                                                    <div class="mt-2 d-flex gap-2 align-items-center flex-wrap">
                                                        @foreach(['players','standings','fixtures','top_scorers','top_assists'] as $key)
                                                            @if(data_get($coverage, $key))
                                                                <span class="badge bg-success badge-coverage">{{ $key }}</span>
                                                            @else
                                                                <span class="badge bg-light text-muted badge-coverage">{{ $key }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    <div class="mt-2 d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline-primary btn-sync-league">Sync liga</button>
                                                        <button class="btn btn-sm btn-outline-secondary btn-copy-id" data-clipboard-text="{{ $lid }}">Copiar ID</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12"><div class="list-empty">Nenhuma liga carregada. Seleciona um país à esquerda.</div></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const fetchUrl = "{{ $fetchUrl }}";
            const syncUrl = "{{ $syncUrl }}";
            const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';

            const countriesList=document.getElementById('countriesList');
            const seasonFilter=document.getElementById('seasonFilter');
            const leaguesList=document.getElementById('leaguesList');
            const leaguesStatus=document.getElementById('leaguesStatus');
            const btnClear=document.getElementById('btnClearCountries');
            const btnSyncAll=document.getElementById('btnSyncAll');

            function setStatus(msg,type='info'){
                leaguesStatus.innerHTML=`<div class="alert alert-${type} text-white py-2 mb-2">${msg}</div>`;
            }

            async function fetchLeagues(country,season){
                if(!country) return setStatus('Selecciona um país.','warning');
                setStatus('A carregar ligas…','info');
                const params=new URLSearchParams({country});
                if(season) params.set('season',season);
                try{
                    const res=await fetch(fetchUrl+'?'+params.toString(),{headers:{'X-Requested-With':'XMLHttpRequest'}});
                    const json=await res.json();
                    const list=json.leagues||json.response||json.data||[];
                    renderLeagues(list);
                    setStatus(`Foram carregadas ${list.length} ligas.`,'success');
                }catch(e){ setStatus('Erro: '+e.message,'danger'); }
            }

            function renderLeagues(list){
                leaguesList.innerHTML='';
                if(!list.length){
                    leaguesList.innerHTML='<div class="col-12"><div class="list-empty">Nenhuma liga disponível.</div></div>';
                    return;
                }
                list.forEach(l=>{
                    const id=l.id||l.league?.id;
                    const name=l.name||l.league?.name||'—';
                    const logo=l.logo||l.league?.logo||`https://media.api-sports.io/football/leagues/${id}.png`;
                    const coverage=l.coverage||l.league?.coverage||{};
                    const card=document.createElement('div');
                    card.className='col-12 col-md-6';
                    card.innerHTML=`
                        <div class="card h-100 p-2" data-league-id="${id}">
                            <div class="d-flex gap-3 align-items-center">
                                <img src="${logo}" class="logo-competition" alt="${name}">
                                <div class="flex-fill">
                                    <strong>${name}</strong>
                                    <div class="mt-2 d-flex gap-2 flex-wrap">
                                        ${['players','standings','fixtures','top_scorers','top_assists'].map(k=>`
                                            <span class="badge ${coverage[k]?'bg-success':'bg-light text-muted'} badge-coverage">${k}</span>
                                        `).join('')}
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary btn-sync-league">Sync liga</button>
                                        <button class="btn btn-sm btn-outline-secondary btn-copy-id">Copiar ID</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    leaguesList.appendChild(card);

                    card.querySelector('.btn-copy-id')?.addEventListener('click',()=>{
                        navigator.clipboard?.writeText(String(id));
                        setStatus('ID copiado.','success');
                    });
                    card.querySelector('.btn-sync-league')?.addEventListener('click',()=>syncSingle(id));
                });
            }

            async function syncSingle(id){
                if(!id) return setStatus('ID inválido.','warning');
                setStatus('A sincronizar…','info');
                try{
                    const body=new URLSearchParams();
                    body.set('_token',csrfToken);
                    body.set('league',id);
                    const res=await fetch(syncUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body});
                    const j=await res.json();
                    setStatus(j.message||'Sincronização concluída.','success');
                }catch(e){setStatus('Erro: '+e.message,'danger');}
            }

            async function syncAll(){
                setStatus('A sincronizar todas as ligas…','info');
                try{
                    const country=document.querySelector('.country-item.active')?.dataset.country||'';
                    const season=seasonFilter.value;
                    const body=new URLSearchParams({_token:csrfToken,country,season});
                    const res=await fetch(syncUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body});
                    const j=await res.json();
                    setStatus(j.message||'Sincronização completa.','success');
                }catch(e){setStatus('Erro: '+e.message,'danger');}
            }

            countriesList?.addEventListener('click',ev=>{
                const btn=ev.target.closest('.country-item');
                if(!btn)return;
                document.querySelectorAll('.country-item').forEach(b=>b.classList.remove('active'));
                btn.classList.add('active');
                fetchLeagues(btn.dataset.country,seasonFilter.value);
            });

            seasonFilter?.addEventListener('change',()=>{
                const active=document.querySelector('.country-item.active');
                if(active) fetchLeagues(active.dataset.country,seasonFilter.value);
            });

            btnClear?.addEventListener('click',()=>{
                document.querySelectorAll('.country-item').forEach(b=>b.classList.remove('active'));
                leaguesList.innerHTML='<div class="col-12"><div class="list-empty">Nenhuma liga carregada.</div></div>';
                leaguesStatus.innerHTML='';
            });

            btnSyncAll?.addEventListener('click',syncAll);

            const initial=document.querySelector('.country-item.active');
            if(initial) setTimeout(()=>fetchLeagues(initial.dataset.country,seasonFilter.value),150);
        });
    </script>
@endsection
