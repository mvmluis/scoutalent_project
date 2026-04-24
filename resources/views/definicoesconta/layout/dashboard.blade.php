{{-- resources/views/definicoesconta.blade.php --}}
@extends('definicoesconta.layout.app')

@section('main-content')
    @php
        use Illuminate\Support\Facades\Storage;
        $user = $user ?? auth()->user();
    @endphp

    <style>
        /* ===== Escopo desta página ===== */
        .account-settings .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #e63333;
            padding: 8px 0 14px 0;
            margin-bottom: 22px;
        }

        .account-settings .pill {
            border: 2px solid #e63333;
            color: #e63333;
            border-radius: 999px;
            padding: 6px 18px;
            font-weight: 600;
            background: #fff;
        }

        .account-settings .section-title {
            color: #e63333;
            font-weight: 700;
        }

        /* Avatar + câmara (escopado) */
        .account-settings .avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .account-settings .avatar-wrap img {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #e6333322;
        }

        .account-settings .avatar-camera {
            position: absolute;
            left: -10px;
            top: 6px;
            background: #e63333;
            color: #fff;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .08);
            border: 0;
        }

        /* Form */
        .account-settings .label-col {
            color: #6c757d;
            font-weight: 600;
            min-width: 210px;
        }

        .account-settings .st-input {
            border: 2px solid #e63333;
            border-radius: 10px;
            height: 44px;
        }

        .account-settings .st-input:focus {
            border-color: #c82020;
            box-shadow: 0 0 0 .15rem rgba(230, 51, 51, .15);
        }

        /* country select */
        .account-settings .country-wrap { position: relative; }
        .account-settings .country-flag {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #fff;
            border: 2px solid #e63333;
            border-radius: 8px;
            padding: 4px 8px;
            display: flex;
            gap: 6px;
            align-items: center;
            font-weight: 700;
        }
        .account-settings .country-select { padding-left: 120px; }

        .account-settings .btn-save {
            background: #e63333;
            border: 2px solid #e63333;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: .6rem 2rem;
        }

        .account-settings .btn-save:hover {
            background: #bf1f1f;
            border-color: #bf1f1f;
            color: #fff;
        }

        .account-settings .card-soft {
            border-radius: 16px;
            border: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }

        .text-error-small {
            color: #b02a37;
            font-size: .875rem;
            margin-top: .35rem;
        }

        /* Password toggle */
        .input-group .btn-toggle {
            border-radius: 8px;
            border: 2px solid #e63333;
            color: #e63333;
            background: #fff;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 .75rem;
        }
        .input-group .btn-toggle:focus { box-shadow: 0 0 0 .15rem rgba(230,51,51,.12); }

        @media (max-width: 768px) {
            .account-settings .label-col { min-width: 140px; }
            .account-settings .country-select { padding-left: 110px; }
        }
    </style>

    @php
        $avatarUrl = $user->avatar ? asset('storage/'.$user->avatar)
                                   : asset('assets/img/luis.jpg');
    @endphp

    <div class="account-settings container py-4">
        {{-- Topbar --}}
        <div class="topbar">
            <span class="pill">Definições de Conta</span>
        </div>

        {{-- Feedback --}}
        @if(session('success'))
            <div class="alert alert-success text-white">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card card-soft">
            <div class="card-body px-4 px-md-5 py-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h2 class="section-title mb-0">ADMIN</h2>
                </div>

                <form action="{{ route('account.settings.store') }}" method="POST" enctype="multipart/form-data" id="accountSettingsForm">
                    @csrf

                    {{-- Avatar --}}
                    <div class="mb-4 d-flex align-items-center gap-4">
                        <div class="avatar-wrap">
                            <img id="avatarPreview" src="{{ $avatarUrl }}" alt="Avatar de {{ $user->name }}">
                            <button type="button" class="avatar-camera" id="btnPickAvatar" aria-label="Alterar foto">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>

                        <div class="flex-grow-1">
                            <label for="avatarInput" class="form-label mb-1">Foto de Perfil</label>
                            <input type="file" name="avatar" id="avatarInput" class="form-control form-control-sm"
                                   accept=".jpg,.jpeg,.png,.webp">
                            <small class="text-muted d-block">JPG/PNG/WebP até 2MB.</small>
                            <div id="avatarError" class="text-error-small" style="display:none;"></div>
                        </div>
                    </div>

                    {{-- Nome --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Nome:</div>
                        <div class="col-12 col-md">
                            <input type="text" name="name" class="form-control st-input" value="{{ old('name', $user->name) }}" required>
                        </div>
                    </div>

                    {{-- Nome Técnico (bloqueado) --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Nome Técnico:</div>
                        <div class="col-12 col-md">
                            <input type="text"
                                   name="nome_tecnico"
                                   class="form-control st-input bg-light text-muted"
                                   value="{{ $user->nome_tecnico }}"
                                   readonly
                                   disabled>
                            <div class="form-text small text-muted">
                                Nome do técnico responsável / contacto.
                            </div>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Email:</div>
                        <div class="col-12 col-md">
                            <input type="email" name="email" class="form-control st-input" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>

                    {{-- Data de Nascimento --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Data de Nascimento:</div>
                        <div class="col-12 col-md">
                            <input type="date" name="birthdate" class="form-control st-input" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d') ?? '') }}">
                        </div>
                    </div>

                    {{-- País --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">País:</div>
                        <div class="col-12 col-md country-wrap">
                            <div class="country-flag"><span>🇵🇹</span> <span>Portugal</span></div>
                            <select name="country" class="form-select st-input country-select">
                                <option value="">—</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" @selected(old('country', $user->country) === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- NIF --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">NIF:</div>
                        <div class="col-12 col-md">
                            <input type="text" name="nif" class="form-control st-input" value="{{ old('nif', $user->nif) }}" maxlength="16" pattern="\d*">
                            <div class="form-text small text-muted">Opcional — normalmente 9 dígitos para NIF PT.</div>
                        </div>
                    </div>

                    {{-- Morada --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Morada:</div>
                        <div class="col-12 col-md">
                            <input type="text" name="morada" class="form-control st-input" value="{{ old('morada', $user->morada) }}" maxlength="255">
                        </div>
                    </div>

                    {{-- Alterar Password (com toggle) --}}
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-12 col-md-3 label-col">Alterar Password:</div>
                        <div class="col-12 col-md">
                            <div class="input-group">
                                <input id="passwordInput" type="password" name="password" class="form-control st-input"
                                       placeholder="Mín. 8 caracteres" aria-describedby="togglePasswordBtn">
                                <button type="button" id="togglePasswordBtn" class="btn btn-toggle" title="Mostrar/Esconder password" aria-pressed="false" aria-label="Mostrar password">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden">Mostrar password</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Repetir Password (com toggle) --}}
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-12 col-md-3 label-col">Repetir Password:</div>
                        <div class="col-12 col-md">
                            <div class="input-group">
                                <input id="passwordConfirmInput" type="password" name="password_confirmation" class="form-control st-input" aria-describedby="togglePasswordConfirmBtn">
                                <button type="button" id="togglePasswordConfirmBtn" class="btn btn-toggle" title="Mostrar/Esconder confirmação" aria-pressed="false" aria-label="Mostrar confirmação">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden">Mostrar confirmação</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button class="btn btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <img src="{{ asset('imagens/logo.png') }}" alt="SCOUTTALENT" style="height:26px;">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btnPickAvatar');
            const input = document.getElementById('avatarInput');
            const preview = document.getElementById('avatarPreview');
            const err = document.getElementById('avatarError');
            const MAX_SIZE = 2 * 1024 * 1024; // 2MB
            const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

            if (btn && input) btn.addEventListener('click', () => input.click());

            if (input && preview) {
                input.addEventListener('change', (e) => {
                    err.style.display = 'none';
                    err.textContent = '';

                    const file = input.files && input.files[0];
                    if (!file) return;

                    // Valida tipo
                    if (!ALLOWED.includes(file.type)) {
                        err.style.display = 'block';
                        err.textContent = 'Formato não suportado. Use JPG, PNG ou WebP.';
                        input.value = ''; // limpa seleção inválida
                        return;
                    }

                    // Valida tamanho
                    if (file.size > MAX_SIZE) {
                        err.style.display = 'block';
                        err.textContent = 'Ficheiro demasiado grande. Máx. 2MB.';
                        input.value = '';
                        return;
                    }

                    // Lê ficheiro e actualiza preview + header
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        preview.src = ev.target.result;

                        // actualiza também o header (se função global existir)
                        if (window.updateHeaderAvatar) {
                            // passa dataURL para actualização imediata do header
                            window.updateHeaderAvatar(ev.target.result);
                        } else {
                            // fallback: actualiza directamente imgs com classe header-avatar
                            document.querySelectorAll('.header-avatar').forEach(img => img.src = ev.target.result);
                        }
                    };
                    reader.onerror = function () {
                        err.style.display = 'block';
                        err.textContent = 'Erro ao ler a imagem.';
                        input.value = '';
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Toggle para password principal
            const pwdInput = document.getElementById('passwordInput');
            const togglePwdBtn = document.getElementById('togglePasswordBtn');

            if (togglePwdBtn && pwdInput) {
                togglePwdBtn.addEventListener('click', () => {
                    const isPassword = pwdInput.type === 'password';
                    pwdInput.type = isPassword ? 'text' : 'password';
                    togglePwdBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    togglePwdBtn.setAttribute('aria-label', isPassword ? 'Esconder password' : 'Mostrar password');

                    const icon = togglePwdBtn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }

            // Toggle para password de confirmação
            const pwdConfirmInput = document.getElementById('passwordConfirmInput');
            const togglePwdConfirmBtn = document.getElementById('togglePasswordConfirmBtn');

            if (togglePwdConfirmBtn && pwdConfirmInput) {
                togglePwdConfirmBtn.addEventListener('click', () => {
                    const isPassword = pwdConfirmInput.type === 'password';
                    pwdConfirmInput.type = isPassword ? 'text' : 'password';
                    togglePwdConfirmBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    togglePwdConfirmBtn.setAttribute('aria-label', isPassword ? 'Esconder confirmação' : 'Mostrar confirmação');

                    const icon = togglePwdConfirmBtn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }
        });
    </script>
@endsection
