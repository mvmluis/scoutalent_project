@extends('search.layout.app')

@section('main-content')
    <div class="main-content">
        <!-- Caixa de Hierarquia -->
        <div class="hierarquia-box">
            <div class="hierarquia-title">Hierarquia da Base de Dados</div>

            <div class="hierarquia-filtros">
                <!-- Países -->
                <div class="form-group">
                    <label>País</label>
                    <input type="text" id="country-search" class="search-input" placeholder="Procurar país...">
                    <ul id="country-list" class="custom-list">
                        @foreach($countries as $country)
                            <li data-id="{{ $country->id }}">
                                <img src="{{ $country->flag }}" alt="flag" class="icon">
                                <span class="name">{{ $country->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Ligas -->
                <div class="form-group">
                    <label>Liga</label>
                    <ul id="league-list" class="custom-list disabled">
                        <li class="placeholder">-- Escolher Liga --</li>
                    </ul>
                </div>

                <!-- Clubes -->
                <div class="form-group">
                    <label>Clube</label>
                    <ul id="team-list" class="custom-list disabled">
                        <li class="placeholder">-- Escolher Clube --</li>
                    </ul>
                </div>

                <!-- Jogadores -->
                <div class="form-group" id="players-group" style="display:none;">
                    <div class="d-flex align-items-center justify-content-between">
                        <label>Jogadores</label>
                        <label class="filter-checkbox">
                            <input type="checkbox" id="filterReportsPlayers"> Mostrar apenas com relatórios
                        </label>
                    </div>
                    <div class="legend">🧾 Com relatórios &nbsp;&nbsp; 🕓 Sem relatórios</div>
                    <ul id="player-list" class="custom-list disabled">
                        <li class="placeholder">-- Escolher Jogador --</li>
                    </ul>
                </div>

                <!-- Treinadores -->
                <div class="form-group" id="coaches-group" style="display:none;">
                    <label>Treinadores</label>
                    <ul id="coach-list" class="custom-list disabled">
                        <li class="placeholder">-- Escolher Treinador --</li>
                    </ul>
                </div>
            </div>

            <!-- Área de aviso -->
            <div id="notice" role="status" aria-live="polite" style="padding:8px 16px; text-align:center;"></div>

            <!-- Botão limpar -->
            <div class="actions">
                <button class="clear-btn" id="clear-btn">Limpar Seleções</button>
            </div>
        </div>
    </div>

    <!-- Modal de escolha -->
    <div id="chooseModal" class="modal-backdrop" style="display:none;">
        <div class="modal-box">
            <h5 class="fw-bold mb-3">O que deseja ver?</h5>
            <p>Deseja listar os <strong>Jogadores</strong> ou os <strong>Treinadores</strong> deste clube?</p>
            <div class="d-flex justify-content-center mt-3">
                <button id="btnPlayers" class="btn btn-danger">Jogadores</button>
                <button id="btnCoaches" class="btn btn-outline-danger">Treinadores</button>
                <button id="btnCancelModal" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <style>
        .main-content {
            padding: 25px;
            background: #f4f6f8;
        }

        .hierarquia-box {
            border: 1px solid #c00;
            border-radius: 6px;
            background: #fff;
            max-width: 950px;
            margin: 0 auto;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .hierarquia-title {
            background: #c00;
            color: #fff;
            padding: 12px 16px;
            font-weight: bold;
            font-size: 16px;
            border-radius: 6px 6px 0 0;
        }

        .hierarquia-filtros {
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 20px 40px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .legend {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }

        .filter-checkbox {
            font-size: 13px;
            color: #444;
            user-select: none;
            cursor: pointer;
        }

        .filter-checkbox input {
            margin-right: 4px;
            vertical-align: middle;
        }

        .search-input {
            margin-bottom: 6px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .custom-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            max-height: 240px;
            overflow-y: auto;
            background: #fafafa;
            scroll-behavior: smooth;
        }

        .custom-list li {
            padding: 10px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }

        .custom-list li:last-child {
            border-bottom: none;
        }

        .custom-list li:hover {
            background: #f2f2f2;
        }

        .custom-list.disabled {
            opacity: .6;
            pointer-events: none;
        }

        .custom-list li.placeholder {
            color: #999;
            cursor: default;
            text-align: center;
        }

        .icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #ddd;
        }

        .actions {
            padding: 15px;
            text-align: center;
        }

        .clear-btn {
            background: #c00;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all .2s ease;
        }

        .clear-btn:hover {
            background: #a00;
            transform: scale(1.03);
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 10px;
        }

        .modal-box {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-box h5 { margin-bottom: 10px; }
        .modal-box p { margin-bottom: 15px; }

        .modal-box .d-flex {
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .modal-box button {
            flex: 1 1 auto;
            min-width: 120px;
            max-width: 150px;
            font-weight: bold;
        }

        /* 🔹 Melhorias Mobile */
        @media (max-width: 768px) {
            .hierarquia-filtros {
                grid-template-columns: 1fr;
                grid-gap: 18px;
                padding: 18px;
            }

            .hierarquia-box {
                border-radius: 10px;
            }

            .hierarquia-title {
                font-size: 15px;
                text-align: center;
            }

            .form-group label {
                font-size: 14px;
            }

            .custom-list {
                max-height: 180px;
                font-size: 13px;
            }

            .search-input {
                font-size: 13px;
                padding: 7px 10px;
            }

            .legend, .filter-checkbox {
                font-size: 12px;
            }

            .clear-btn {
                width: 100%;
                padding: 12px;
            }

            .custom-list li {
                padding: 8px 10px;
            }
        }

        /* 🔸 Scrollbar estilizado */
        .custom-list::-webkit-scrollbar {
            width: 6px;
        }

        .custom-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-list::-webkit-scrollbar-thumb {
            background: #c00;
            border-radius: 4px;
        }

        .custom-list::-webkit-scrollbar-thumb:hover {
            background: #900;
        }
    </style>

    <script>
        function normalizeText(s){return s?s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim().toLowerCase():"";}
        function filterCountries(searchTerm){const q=normalizeText(searchTerm);document.querySelectorAll('#country-list li').forEach(item=>{const nameEl=item.querySelector('.name');if(!nameEl)return;const nameNorm=normalizeText(nameEl.textContent);item.style.display=nameNorm.includes(q)?'':'none';});}
        function showNotice(msg,timeout=4000){const n=document.getElementById('notice');n.textContent=msg;if(timeout>0)setTimeout(()=>{n.textContent='';},timeout);}
        function setInitialList(elId,txt){const el=document.getElementById(elId);el.innerHTML=`<li class="placeholder">${txt}</li>`;el.classList.add('disabled');}
        function clearSelections(){sessionStorage.clear();document.getElementById('country-search').value='';document.querySelectorAll('#country-list li').forEach(li=>li.style.display='');setInitialList('league-list','-- Escolher Liga --');setInitialList('team-list','-- Escolher Clube --');setInitialList('player-list','-- Escolher Jogador --');setInitialList('coach-list','-- Escolher Treinador --');document.getElementById('players-group').style.display='none';document.getElementById('coaches-group').style.display='none';showNotice('Seleções limpas.',2000);}
        function fetchJson(url){return fetch(url).then(r=>{if(!r.ok)throw new Error('Erro HTTP '+r.status);return r.json();});}
        function showModal(){document.getElementById('chooseModal').style.display='flex';}
        function hideModal(){document.getElementById('chooseModal').style.display='none';}
        function renderList(elId,items,placeholderIfEmpty){const el=document.getElementById(elId);if(!items||!items.length){el.innerHTML=`<li class="placeholder">${placeholderIfEmpty}</li>`;el.classList.add('disabled');return;}el.classList.remove('disabled');el.innerHTML='';items.forEach(it=>{const logo=(it.logo||it.flag)?`<img src="${it.logo||it.flag}" class="icon">`:'';el.innerHTML+=`<li data-id="${it.id}">${logo}<span class="name">${it.name}</span></li>`;});}

        document.addEventListener('DOMContentLoaded',()=>{
            const filterPlayers=document.getElementById('filterReportsPlayers');
            const renderFiltered=(listId,filter)=>{
                document.querySelectorAll(`#${listId} li[data-id]`).forEach(li=>{
                    const text=li.textContent;
                    li.style.display=(filter && !text.includes('🧾'))?'none':'flex';
                });
            };
            filterPlayers.addEventListener('change',()=>renderFiltered('player-list',filterPlayers.checked));

            // Pesquisar país
            document.getElementById('country-search').addEventListener('input',e=>filterCountries(e.target.value));
            document.getElementById('clear-btn').addEventListener('click',clearSelections);

            // País
            document.getElementById('country-list').addEventListener('click',ev=>{
                const li=ev.target.closest('li[data-id]');if(!li)return;
                const id=li.dataset.id;sessionStorage.setItem('country',id);
                setInitialList('league-list','-- A carregar ligas... --');
                fetchJson('/search/leagues/'+id)
                    .then(d=>renderList('league-list',d,'Sem ligas disponíveis.'))
                    .catch(()=>showNotice('Erro ao carregar ligas.',5000));
            });

            // Liga
            document.getElementById('league-list').addEventListener('click',ev=>{
                const li=ev.target.closest('li[data-id]');if(!li)return;
                const id=li.dataset.id;sessionStorage.setItem('league',id);
                setInitialList('team-list','-- A carregar clubes... --');
                fetchJson('/search/teams/'+id)
                    .then(d=>renderList('team-list',d,'Sem clubes disponíveis.'))
                    .catch(()=>showNotice('Erro ao carregar clubes.',5000));
            });

            // Clube
            document.getElementById('team-list').addEventListener('click',ev=>{
                const li=ev.target.closest('li[data-id]');if(!li)return;
                const id=li.dataset.id;sessionStorage.setItem('team',id);
                document.getElementById('btnPlayers').dataset.team=id;
                document.getElementById('btnCoaches').dataset.team=id;
                showModal();
            });

            // Jogadores
            document.getElementById('btnPlayers').addEventListener('click',function(){
                hideModal();sessionStorage.setItem('mode','players');
                document.getElementById('players-group').style.display='block';
                document.getElementById('coaches-group').style.display='none';
                const id=this.dataset.team;
                fetchJson('/search/players/'+id)
                    .then(d=>{
                        const mapped=d.map(p=>({id:p.id,name:`${p.name} ${p.has_reports?'🧾':'🕓'}`,logo:p.photo??'/images/default-player.png'}));
                        renderList('player-list',mapped,'Sem jogadores disponíveis.');
                        renderFiltered('player-list',filterPlayers.checked);
                    })
                    .catch(()=>showNotice('Erro ao carregar jogadores.',5000));
            });

            // Treinadores
            document.getElementById('btnCoaches').addEventListener('click',function(){
                hideModal();sessionStorage.setItem('mode','coaches');
                document.getElementById('coaches-group').style.display='block';
                document.getElementById('players-group').style.display='none';
                const id=this.dataset.team;
                fetchJson('/search/coaches/'+id)
                    .then(d=>{
                        const mapped=d.map(c=>({id:c.id,name:c.name,logo:c.photo??'/images/default-coach.png'}));
                        renderList('coach-list',mapped,'Sem treinadores disponíveis.');
                    })
                    .catch(()=>showNotice('Erro ao carregar treinadores.',5000));
            });

            document.getElementById('btnCancelModal').addEventListener('click',hideModal);

            document.getElementById('player-list').addEventListener('click',ev=>{
                const li=ev.target.closest('li[data-id]');if(!li)return;window.location.href='/players/'+li.dataset.id;
            });
            document.getElementById('coach-list').addEventListener('click',ev=>{
                const li=ev.target.closest('li[data-id]');if(!li)return;window.location.href='/coachs/'+li.dataset.id;
            });

            // 🔁 Restaurar seleções anteriores
            const savedCountry=sessionStorage.getItem('country');
            const savedLeague=sessionStorage.getItem('league');
            const savedTeam=sessionStorage.getItem('team');
            const savedMode=sessionStorage.getItem('mode');
            if(savedCountry){
                fetchJson('/search/leagues/'+savedCountry).then(lgs=>{
                    renderList('league-list',lgs,'Sem ligas disponíveis.');
                    if(savedLeague){
                        fetchJson('/search/teams/'+savedLeague).then(teams=>{
                            renderList('team-list',teams,'Sem clubes disponíveis.');
                            if(savedTeam){
                                if(savedMode==='players'){
                                    fetchJson('/search/players/'+savedTeam).then(ps=>{
                                        const mapped=ps.map(p=>({id:p.id,name:`${p.name} ${p.has_reports?'🧾':'🕓'}`,logo:p.photo??'/images/default-player.png'}));
                                        renderList('player-list',mapped,'Sem jogadores disponíveis.');
                                        document.getElementById('players-group').style.display='block';
                                    });
                                }else if(savedMode==='coaches'){
                                    fetchJson('/search/coaches/'+savedTeam).then(cs=>{
                                        const mapped=cs.map(c=>({id:c.id,name:c.name,logo:c.photo??'/images/default-coach.png'}));
                                        renderList('coach-list',mapped,'Sem treinadores disponíveis.');
                                        document.getElementById('coaches-group').style.display='block';
                                    });
                                }
                            }
                        });
                    }
                });
            }
        });
    </script>
@endsection
