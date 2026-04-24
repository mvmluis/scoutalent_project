@php
    $user = auth()->user();
    $defaultAvatar = asset('assets/img/luis.jpg');
    $userName = $user->name ?? 'Utilizador';
    $adminLabel = 'ADMIN';
    $avatarUrl = $defaultAvatar;
    if ($user && !empty($user->avatar)) {
        $ts = $user->updated_at?->timestamp ?? time();
        $avatarUrl = asset('storage/'.$user->avatar) . '?v=' . $ts;
    }
@endphp

<nav class="navbar navbar-main navbar-expand-lg shadow-none border-radius-xl"
     id="navbarBlur" aria-label="Main navbar">
    <div class="container-fluid d-flex align-items-center justify-content-between flex-nowrap py-2">

        <div class="header-left d-flex align-items-center flex-shrink-0">
            <button id="hamburger-btn" class="btn btn-link text-danger me-3 d-xl-none" style="font-size:24px;">
                <i class="fas fa-bars"></i>
            </button>
            <div class="avatar-wrap d-flex align-items-center">
                <img src="{{ $avatarUrl }}" alt="{{ $userName }}" class="header-avatar rounded-circle">
                <div class="ms-2 d-none d-sm-block header-user">
                    <div class="admin-label">{{ $adminLabel }}</div>
                    <div class="user-name">{{ $userName }}</div>
                </div>
            </div>
        </div>

        <div class="flex-fill mx-3 d-none d-xl-block">
            <div class="header-line"></div>
        </div>

        <div class="header-search flex-shrink-0">
            <form class="d-flex align-items-center" action="#" method="GET">
                <div class="input-group search-pill">
                    <input type="search" name="q" class="form-control search-input"
                           placeholder="Pesquisa Rápida" aria-label="Pesquisa Rápida">
                    <button class="btn search-btn" type="submit" aria-label="Pesquisar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</nav>

<style>
    :root{
        --brand-red: #d60c0c;
        --header-height: 84px;
    }

    nav.navbar {
        height: var(--header-height);
        background: transparent;
        padding: 0 16px;
        z-index: 50;
    }

    .header-avatar {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .header-user .admin-label {
        color: var(--brand-red);
        font-weight:700;
        font-size:13px;
    }

    .header-user .user-name {
        color: #666;
        font-size:12px;
        margin-top:2px;
    }

    .header-line {
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, rgba(214,12,12,1) 0%, rgba(214,12,12,0.95) 100%);
        border-radius: 2px;
    }

    .search-pill {
        display: flex;
        align-items: center;
        border: 2px solid var(--brand-red);
        border-radius: 34px;
        background: #fff;
    }

    .search-input {
        border: 0;
        padding: 10px 18px;
        min-width: 220px;
        max-width: 420px;
        width: 36vw;
        outline: none;
        font-size: 14px;
    }

    .search-btn {
        border: 0;
        background: transparent;
        color: var(--brand-red);
        padding: 8px 14px;
        font-size: 16px;
    }

    @media (max-width: 1199px) {
        nav.navbar {
            height: auto;
            background: #fff;
            padding: 10px 12px;
        }
        .header-avatar { width: 46px; height: 46px; }
        .header-line { display: none; }
        .search-input { width: 48vw; min-width: 140px; }
    }
</style>
