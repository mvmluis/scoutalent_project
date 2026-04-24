@php
    $user = Auth::user();
@endphp

<style>
    :root {
        --sidebar-w: 260px;
        --sidebar-left: 18px;
        --brand-red: #d60c0c;
    }

    /* === SIDEBAR BASE === */
    #sidenav-main {
        width: var(--sidebar-w);
        background-color: var(--brand-red) !important;
        color: #fff;
        border-radius: 18px;
        position: fixed;
        left: var(--sidebar-left);
        top: 50%;
        transform: translateY(-50%) translateX(-120%);
        max-height: calc(100vh - 80px);
        overflow-y: auto;
        z-index: 1400;
        padding: 8px 14px 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        transition: transform 0.35s ease, opacity 0.25s ease;
        opacity: 1;
        visibility: visible;
        backface-visibility: hidden;
        will-change: transform;
    }

    #sidenav-main.active {
        transform: translateY(-50%) translateX(0);
        background-color: var(--brand-red) !important;
        color: #fff;
    }

    /* === OVERLAY === */
    #sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1390;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    #sidebar-overlay.show {
        display: block;
        opacity: 1;
    }

    /* === LOGO === */
    #sidenav-main .logo-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 8px;
    }

    #sidenav-main .logo-wrap img {
        max-width: 76px;
        height: auto;
    }

    /* === LISTA === */
    #sidenav-main ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .nav-link,
    .btn-linklike {
        color: #fff !important;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .55rem .65rem;
        border-radius: 8px;
        gap: .6rem;
        transition: background 0.25s ease, color 0.25s ease, text-shadow 0.2s ease;
    }

    /* hover padrão (inclusive botão Voltar) */
    .nav-link:hover,
    .btn-linklike:hover {
        background: rgba(255, 255, 255, .18);
        color: #fff !important;
        text-shadow: 0 0 6px rgba(255, 255, 255, 0.6);
    }

    .nav-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex: 1;
    }

    .nav-left i {
        width: 22px;
        text-align: center;
    }

    /* === BOTÕES COM SUBMENU === */
    .nav-toggle {
        width: 100%;
        background: transparent;
        border: none;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .55rem .65rem;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease, text-shadow 0.2s ease;
        font-family: inherit;
        font-size: 0.95rem;
    }

    .nav-toggle:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        text-shadow: 0 0 6px rgba(255, 255, 255, 0.6);
    }

    .caret {
        font-size: 0.8rem;
        transform: rotate(-90deg);
        transition: transform 0.2s ease;
        opacity: 0.8;
    }

    .nav-toggle.active .caret {
        transform: rotate(0deg);
    }

    /* === SUBITENS === */
    .subitems {
        display: none;
        flex-direction: column;
        margin-top: 4px;
        margin-left: 26px;
        gap: 4px;
        animation: fadeIn 0.25s ease;
    }

    .subitems a {
        color: #fff;
        font-size: 0.9rem;
        padding: 4px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        opacity: 0.9;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .subitems a:hover {
        opacity: 1;
        transform: translateX(4px);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* === LOGOUT === */
    .btn-logout {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .6rem;
        border-radius: 8px;
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .1);
        text-decoration: none;
        transition: background .12s ease, color .12s ease, transform .06s ease;
    }

    .btn-logout:hover {
        background: #fff;
        color: #d60c0c;
        transform: translateY(-1px);
    }

    /* === RESPONSIVO === */
    @media (max-width: 1199px) {
        #sidenav-main {
            width: 80%;
            left: 0;
            top: 0;
            border-radius: 0;
            transform: translateX(-100%);
            max-height: none;
        }

        #sidenav-main.active {
            transform: translateX(0);
        }
    }
</style>

<!-- Overlay -->
<div id="sidebar-overlay"></div>

<!-- Sidebar -->
<aside id="sidenav-main" aria-label="Menu lateral principal">
    <div class="logo-wrap">
        <a href="{{ url('/') }}" title="Dashboard">
            <img src="{{ asset('imagens/logo.png') }}" alt="Logo">
        </a>
    </div>

    <hr style="border-color:rgba(255,255,255,.08); margin:6px 0;">

    <nav>
        <ul>
            <li>
                <a href="{{ route('manual.players.index', request()->query()) }}" class="btn-linklike">
                    <div class="nav-left"><i class="fas fa-arrow-left"></i>Voltar</div>
                </a>

            </li>

            <li>
                <a href="{{ route('account.settings.index') }}" class="nav-link">
                    <div class="nav-left"><i class="fas fa-cog"></i>Definições</div>
                </a>
            </li>

            @if (false)
                <li>
                    <span class="nav-link" style="cursor: not-allowed; opacity: 0.6;">
                        <div class="nav-left">
                            <i class="fas fa-search"></i> Motor de Busca
                        </div>
                    </span>
                </li>
            @endif
            <li>
                <button class="nav-toggle" type="button" data-target="submenu-relatorio">
                    <div class="nav-left">
                        <i class="fas fa-file-alt"></i>
                        <span>Relatórios</span>
                    </div>
                    <span class="caret">&lsaquo;</span>
                </button>
                <div id="submenu-relatorio" class="subitems">
                    <a href="{{ route('manual.players.index') }}">
                        <i class="fas fa-user-friends"></i> Jogadores
                    </a>
                    <a href="{{ route('players.followed') }}"><i class="fas fa-heart"></i> Jogadores Seguidos</a>
                    <a href="{{ route('reports.mine') }}">
                        <i class="fas fa-user-check"></i> Meus Relatórios
                    </a>
                </div>
            </li>

            <li>
                <button class="nav-toggle" type="button" data-target="submenu-formacao">
                    <div class="nav-left">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Formação</span>
                    </div>
                    <span class="caret">&lsaquo;</span>
                </button>
                <div id="submenu-formacao" class="subitems">
                    <a href="#"><i class="fas fa-list"></i> Lista de Formações</a>
                    @if ($user && $user->role === 'admin')
                        <a href="#"><i class="fas fa-plus-circle"></i> Nova Formação</a>
                    @endif
                    <a href="#"><i class="fas fa-certificate"></i> Certificados</a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="bottom mt-4">
        <hr style="border-color:rgba(255,255,255,.1); margin:8px 0;">
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="btn-logout">
            <i class="fas fa-power-off"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
    </div>
</aside>

<script>
    function initSidebar() {
        const sidebar = document.getElementById('sidenav-main');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const overlay = document.getElementById('sidebar-overlay');

        if (!sidebar || !toggleBtn) return;

        const toggleSidebar = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('show');
        };

        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        document.querySelectorAll('.nav-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = document.getElementById(btn.dataset.target);
                const isVisible = target.style.display === 'flex';
                document.querySelectorAll('.subitems').forEach(s => s.style.display = 'none');
                document.querySelectorAll('.nav-toggle').forEach(b => b.classList.remove('active'));
                if (!isVisible) {
                    target.style.display = 'flex';
                    btn.classList.add('active');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initSidebar);
    document.addEventListener('livewire:navigated', initSidebar);
    document.addEventListener('turbo:load', initSidebar);
</script>
