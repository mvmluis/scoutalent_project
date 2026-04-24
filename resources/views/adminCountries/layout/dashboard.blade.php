@extends('adminCountries.layout.app')

@section('main-content')
    <div class="container-fluid countries-container">

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm text-white" role="alert" id="flash-success">
                ✅ {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm text-white" role="alert" id="flash-error">
                ⚠️ {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="max-width:1000px;">
            <h4 class="mb-2 mb-sm-0 text-danger">🌍 Países</h4>

            {{-- Sincronização --}}
            <form id="syncForm" action="{{ route('admin.profile.country.sync') }}" method="POST" class="m-0 p-0">
                @csrf
                <button type="submit" id="btnSyncCountries" class="btn btn-sm btn-danger">
                    🔄 Sincronizar API
                </button>
            </form>
        </div>

        {{-- Lista --}}
        <div class="card shadow-sm" style="max-width:1000px;">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:50px;">Bandeira</th>
                        <th>Nome</th>
                        <th style="width:80px;">Código</th>
                        <th style="width:150px;">Continente</th>
                        <th style="width:120px;" class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($countries as $c)
                        <tr>
                            {{-- Bandeira --}}
                            <td>
                                @if($flag = data_get($c, 'flag'))
                                    <img src="{{ $flag }}" alt="flag" width="28" class="rounded border">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Dados --}}
                            <td>{{ data_get($c, 'name', '—') }}</td>
                            <td>{{ data_get($c, 'code', '—') }}</td>
                            <td>{{ data_get($c, 'continent', '—') }}</td>

                            {{-- Ações --}}
                            <td class="text-end d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.profile.country.edit', data_get($c, 'id')) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form action="{{ route('admin.profile.country.destroy', data_get($c, 'id')) }}"
                                      method="POST"
                                      onsubmit="return confirm('Apagar este país?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Apagar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Nenhum país encontrado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Overlay de sincronização --}}
    <div id="simpleSyncOverlay" aria-hidden="true" role="status" style="display:none;">
        <div class="d-flex align-items-center justify-content-center" style="height:100%;">
            <div style="background:#fff;border-radius:8px;padding:18px 22px;box-shadow:0 6px 30px rgba(0,0,0,0.08);display:inline-flex;gap:12px;align-items:center;">
                <div style="width:28px;height:28px;border:3px solid rgba(0,0,0,0.08);border-top-color:#dc3545;border-radius:50%;animation:spin .9s linear infinite;"></div>
                <div style="text-align:left;">
                    <div style="font-weight:700;">Sincronização em curso…</div>
                    <div style="color:#6c757d;font-size:13px;margin-top:6px;">Isto pode demorar alguns minutos. Por favor não feche a página.</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }

        #simpleSyncOverlay {
            position: fixed; inset: 0; display: none;
            align-items: center; justify-content: center;
            background: rgba(255,255,255,0.85);
            z-index: 999999;
        }

        /* --- Ajustes de layout --- */
        .countries-container {
            margin-left: 280px;
            padding-top: 40px;
            transition: margin-left .3s ease;
        }

        /* Em mobile o conteúdo ocupa toda a largura */
        @media (max-width: 1199px) {
            .countries-container {
                margin-left: 0 !important;
                padding-top: 90px !important;
            }
        }

        /* Tabela responsiva */
        @media (max-width: 768px) {
            .countries-container table {
                font-size: 13px;
            }
            .countries-container th,
            .countries-container td {
                white-space: nowrap;
            }
            .countries-container .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>

    <script>
        (function () {
            const syncForm = document.getElementById('syncForm');
            const btnSync = document.getElementById('btnSyncCountries');
            const simpleSyncOverlay = document.getElementById('simpleSyncOverlay');

            const showOverlay = () => {
                if (!simpleSyncOverlay) return;
                document.body.appendChild(simpleSyncOverlay);
                simpleSyncOverlay.style.display = 'flex';
                simpleSyncOverlay.setAttribute('aria-hidden', 'false');
            };

            btnSync?.addEventListener('click', () => {
                setTimeout(() => {
                    showOverlay();
                    Array.from(syncForm.querySelectorAll('button, input[type="submit"]')).forEach(b => b.disabled = true);
                }, 10);
            });

            syncForm?.addEventListener('submit', () => {
                showOverlay();
                setTimeout(() => {
                    Array.from(syncForm.querySelectorAll('button, input[type="submit"]')).forEach(b => b.disabled = true);
                }, 10);
            });

            ['flash-success','flash-error'].forEach(id => {
                const el = document.getElementById(id);
                if (el) setTimeout(() => el.style.display='none', 4000);
            });
        })();
    </script>
@endsection
