@extends('adminCountries.layout.app')

@section('main-content')
    <div class="container-fluid" style="padding-top:40px; margin-left:300px;">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 text-danger">✏️ Editar País</h4>
                <div class="small text-muted">Edita os dados do país e guarda as alterações.</div>
            </div>

            {{-- Botão "Voltar" --}}
            <div>
                @php
                    $backRoute = \Illuminate\Support\Facades\Route::has('admin.profile.country.index')
                        ? route('admin.profile.country.index')
                        : null;
                @endphp

                @if($backRoute)
                    <a href="{{ $backRoute }}" class="btn btn-outline-secondary">← Voltar à lista</a>
                @else
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="(document.referrer ? window.location.href = document.referrer : window.history.back())">
                        ← Voltar
                    </button>
                @endif
            </div>
        </div>

        {{-- Mensagens flash --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show text-white" role="alert">
                ✅ {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show text-white" role="alert">
                ⚠️ {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        {{-- Se não existe país --}}
        @if(empty($country))
            <div class="alert alert-warning">
                ⚠️ País não encontrado. Se achares que a API não foi sincronizada, tenta sincronizar.
            </div>
            <form action="{{ route('admin.profile.country.sync') }}" method="POST" class="mb-4">
                @csrf
                <button class="btn btn-danger btn-sm">🔄 Sincronizar Países (API)</button>
            </form>
        @else
            {{-- Card principal --}}
            <div class="card shadow-sm" style="max-width:1100px;">
                <div class="card-body">
                    <form action="{{ route('admin.profile.country.update', data_get($country, 'id')) }}" method="POST" id="country-update-form">
                        @csrf
                        @method('PUT')

                        <div class="row gy-3">
                            {{-- Bandeira --}}
                            <div class="col-12 col-md-3 text-center">
                                <div style="max-width:160px;margin:0 auto;">
                                    <img id="flagPreview"
                                         src="{{ old('flag', data_get($country, 'flag', '')) ?: 'https://via.placeholder.com/160x160?text=Flag' }}"
                                         alt="Bandeira"
                                         style="width:100%;height:100%;object-fit:cover;border-radius:8px;border:1px solid #e9ecef;">
                                </div>
                                <div class="small text-muted mt-2">Pré-visualização da bandeira</div>

                                <div class="mt-3 d-grid">
                                    <a href="{{ route('admin.profile.country.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                                </div>
                            </div>

                            {{-- Campos --}}
                            <div class="col-12 col-md-9">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold" for="name">Nome <span class="text-danger">*</span></label>
                                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                               value="{{ old('name', data_get($country,'name','')) }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <label class="form-label fw-semibold" for="code">Código</label>
                                        <input id="code" name="code" type="text" class="form-control @error('code') is-invalid @enderror"
                                               value="{{ old('code', data_get($country,'code','')) }}" maxlength="8" placeholder="ex: PT / GB-ENG">
                                        <div class="form-text small">Alpha code (2–6 caracteres)</div>
                                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <label class="form-label fw-semibold" for="continent">Continente</label>
                                        <input id="continent" name="continent" type="text" class="form-control @error('continent') is-invalid @enderror"
                                               value="{{ old('continent', data_get($country,'continent','')) }}" placeholder="Europa, África...">
                                        @error('continent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold" for="flag">URL da bandeira</label>
                                        <input id="flag" name="flag" type="url" class="form-control @error('flag') is-invalid @enderror"
                                               value="{{ old('flag', data_get($country,'flag','')) }}"
                                               placeholder="https://media.api-sports.io/flags/PT.svg">
                                        <div class="form-text small">Cola a URL da bandeira (SVG/PNG). Atualiza automaticamente.</div>
                                        @error('flag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    {{-- Meta JSON --}}
                                    @if(!empty(data_get($country,'meta')))
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Dados da API (meta)</label>
                                            <pre class="border p-2" style="max-height:160px;overflow:auto;background:#f8f9fa;">
{{ json_encode(data_get($country,'meta'), JSON_PRETTY_PRINT) }}</pre>
                                            <div class="form-text small text-muted">Apenas para referência — não editável.</div>
                                        </div>
                                    @endif

                                    {{-- Ações --}}
                                    <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                                        <button type="submit" class="btn btn-danger">💾 Guardar alterações</button>
                                        <button type="submit" form="country-delete-form" class="btn btn-outline-danger"
                                                onclick="return confirm('Tem a certeza que deseja apagar este país? Esta ação é irreversível.')">
                                            🗑 Apagar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- DELETE --}}
                    <form id="country-delete-form"
                          action="{{ route('admin.profile.country.destroy', data_get($country,'id')) }}"
                          method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const flagInput = document.getElementById('flag');
            const preview = document.getElementById('flagPreview');
            if (!flagInput || !preview) return;

            function updatePreview() {
                const v = flagInput.value?.trim() || '';
                preview.src = v || 'https://via.placeholder.com/160x160?text=Flag';
            }

            flagInput.addEventListener('input', updatePreview);
            flagInput.addEventListener('change', updatePreview);
            preview.addEventListener('error', () => preview.src = 'https://via.placeholder.com/160x160?text=Flag');
        });
    </script>
@endsection
