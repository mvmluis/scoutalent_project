@php
    use Illuminate\Support\Facades\Auth;

    $sidebarWidth = '260px';
    $sidebarLeft  = '18px';
    $user = Auth::user();
@endphp

<style>
    #sidenav-main {
        width: var(--sidebar-w, 260px);
        background-color: #d60c0c !important;
        color: #fff;
        border-radius: 18px;
        position: fixed;
        top: 50%;
        left: 20px;
        transform: translateY(-50%);
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        z-index: 999;
        padding: 8px 14px 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
        transition: transform .3s ease;
        will-change: transform;
    }

    /* 🍔 Mobile */
    @media (max-width: 1199px) {
        #sidenav-main {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 260px !important;
            height: 100vh !important;
            border-radius: 0 !important;
            padding-top: 30px;
            background-color: #d60c0c !important;
            transform: translateX(-100%);
            transition: transform .3s ease-out;
            will-change: transform;
        }

        #sidenav-main.active {
            transform: translateX(0);
        }

        #sidebar-overlay {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.45);
            z-index: 998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        #sidebar-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
    }

    .logo-wrap { display: flex; justify-content: center; align-items: center; margin-bottom: 10px; }
    .logo-wrap img { max-width: 76px; height: auto; display: block; }

    ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }

    .nav-link, .nav-toggle {
        color: #fff; text-decoration: none; display: flex; align-items: center;
        justify-content: space-between; padding: .5rem .5rem; border-radius: 8px;
        background: transparent; border: 0; cursor: pointer; transition: background .2s ease;
    }

    .nav-link:hover, .nav-toggle:hover { background: rgba(255,255,255,.08); }

    .nav-left { display: flex; align-items: center; gap: .75rem; flex: 1; }
    .nav-left i { width: 22px; text-align: center; }

    .caret { transition: transform .18s ease; color: rgba(255,255,255,.9); font-size: .9rem; min-width: 18px; text-align: center; }
    .rotated { transform: rotate(-90deg); }

    .subitems { overflow: hidden; max-height: 0; transition: max-height .28s ease; margin-left: 1rem;
        border-left: 2px solid rgba(255,255,255,.06); padding-left: .6rem; }
    .subitems a {
        display: block; color: rgba(255,255,255,.9); padding: .35rem .5rem; border-radius: 6px;
        text-decoration: none; font-size: .92rem;
    }
    .subitems a:hover { background: rgba(255,255,255,.08); }
    .subitems.show { max-height: 500px; }

    .btn-logout {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .45rem .6rem; border-radius: 8px; background: transparent;
        color: #fff; border: 1px solid rgba(255,255,255,.1); transition: all .15s ease;
    }
    .btn-logout:hover { background: #fff !important; color: #d60c0c !important; transform: translateY(-1px); }
</style>

<div id="sidebar-overlay"></div>

<aside id="sidenav-main" aria-label="Menu lateral principal">
    <div class="logo-wrap">
        <a href="{{ route('dashboard') }}"><img src="{{ asset('imagens/logo.png') }}" alt="Logo"></a>
    </div>

    <hr style="border-color:rgba(255,255,255,.08); margin:6px 0;">

    <nav>
        <ul>
            <li><a href="{{ route('dashboard') }}" class="nav-link"><div class="nav-left"><i class="fas fa-arrow-left"></i> Voltar</div></a></li>
            <li><a href="{{ route('account.settings.index') }}" class="nav-link"><div class="nav-left"><i class="fas fa-cog"></i> Definições de Conta</div></a></li>
            <li>
                <button class="nav-toggle" type="button" data-target="submenu-relatorio">
                    <div class="nav-left"><i class="fas fa-file-alt"></i> Relatórios</div>
                    <span class="caret">&lsaquo;</span>
                </button>
                <div id="submenu-relatorio" class="subitems">
                    <a href="{{ route('manual.players.index') }}"><i class="fas fa-user-friends"></i> Jogadores</a>
                    <a href="{{ route('reports.mine') }}"><i class="fas fa-user-check"></i> Meus Relatórios</a>
                </div>
            </li>

            <li>
                <button class="nav-toggle" type="button" data-target="submenu-formacao">
                    <div class="nav-left"><i class="fas fa-graduation-cap"></i> Formação</div>
                    <span class="caret">&lsaquo;</span>
                </button>
                <div id="submenu-formacao" class="subitems">
                    <a href="#" class="disabled-link"><i class="fas fa-list"></i> Lista de Formações</a>
                    <a href="#" class="disabled-link"><i class="fas fa-certificate"></i> Certificados</a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="bottom">
        <hr style="border-color:rgba(255,255,255,.08); margin:8px 0;">
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn-logout">
            <i class="fas fa-power-off"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
    </div>
</aside>

<script>
    (function() {
        function initSidebar() {
            const sidebar = document.getElementById('sidenav-main');
            const overlay = document.getElementById('sidebar-overlay');
            const hamburger = document.getElementById('hamburger-btn');

            if (!sidebar) return;

            document.querySelectorAll('.nav-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById(btn.dataset.target);
                    const expanded = btn.getAttribute('aria-expanded') === 'true';
                    target.classList.toggle('show', !expanded);
                    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    btn.querySelector('.caret')?.classList.toggle('rotated', !expanded);
                });
            });

            overlay?.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });

            if (hamburger) {
                hamburger.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    sidebar.style.backgroundColor = '#d60c0c';
                    void sidebar.offsetWidth;
                });
            } else {
                setTimeout(initSidebar, 300);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebar);
        } else {
            initSidebar();
        }
    })();
</script>
