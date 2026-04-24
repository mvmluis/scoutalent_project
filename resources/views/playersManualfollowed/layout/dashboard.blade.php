@extends('playersManualfollowed.layout.app')

@section('main-content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold text-danger mb-0">
                <i class="bi bi-heart-fill me-2"></i>Jogadores Seguidos
            </h2>
        </div>

        <div class="card shadow-lg border-0">
            <div class="table-responsive">
                <table class="table text-center align-middle mb-0">
                    <thead class="custom-header">
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>Idade</th>
                        <th>Equipa</th>
                        <th>Posição</th>
                        <th>Rating</th>
                        <th>Golos</th>
                        <th>Relatórios</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($players as $p)
                        <tr>
                            <td>
                                <img src="{{ $p->photo ?? '/images/default-player.png' }}" width="48" height="48"
                                     class="rounded-circle shadow-sm" style="object-fit:cover;">
                            </td>

                            <td class="fw-semibold text-start" style="min-width:200px;">{{ $p->name }}</td>

                            <td>{{ $p->age ?? '—' }}</td>

                            <td>{{ $p->team_name ?? '—' }}</td>

                            <td>{{ $p->position ? \Illuminate\Support\Str::title($p->position) : '—' }}</td>

                            <td>
                                @if(isset($p->rating))
                                    @php
                                        $rating = floatval($p->rating);
                                        $badge = $rating >= 7 ? 'bg-success' : ($rating >= 6 ? 'bg-warning text-dark' : 'bg-danger');
                                    @endphp
                                    <span class="badge px-3 py-2 fw-bold {{ $badge }}">{{ number_format($rating, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>{{ $p->goals ?? 0 }}</td>

                            {{-- coluna: reports_count --}}
                            <td>
                                @if(isset($p->reports_count) && $p->reports_count > 0)
                                    {{-- badge -> botão para alinhar com os outros --}}
                                    <a href="{{ route('players.show', $p->id) }}"
                                       class="btn btn-sm btn-danger px-3 d-inline-flex align-items-center"
                                       title="Ver relatórios">
                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        {{ $p->reports_count }} relatório{{ $p->reports_count > 1 ? 's' : '' }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    {{-- Ver perfil / criar relatório --}}
                                    @if(isset($p->reports_count) && $p->reports_count > 0)
                                        <a href="{{ route('reports.mine', ['player_id' => $p->id]) }}"
                                           class="btn btn-sm btn-outline-dark px-3" title="Ver relatórios">
                                            <i class="bi bi-folder2-open me-1"></i> Ver Relatórios
                                        </a>
                                    @else
                                        <a href="{{ route('players.show', $p->id) }}"
                                           class="btn btn-sm btn-outline-dark px-3" title="Fazer relatório">
                                            <i class="bi bi-file-earmark-text me-1"></i> Fazer Relatório
                                        </a>
                                    @endif

                                    {{-- Deixar de Seguir --}}
                                    <form method="POST" action="{{ route('players.follow', $p->id) }}" class="unfollow-form m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger px-3 unfollow-btn">
                                            <i class="bi bi-x-circle me-1"></i> Deixar de seguir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted py-4">Ainda não segues nenhum jogador.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">{{ $players->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    <style>
        .custom-header {
            background: linear-gradient(135deg,#1b2b52,#0a1733);
            color:#fff;
            text-transform:uppercase;
        }
        tr.fade-out { opacity: 0; transition: opacity .4s ease; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Unfollow com animação
            document.querySelectorAll('.unfollow-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const btn = form.querySelector('.unfollow-btn');
                    const row = form.closest('tr');
                    const url = form.action;
                    const token = form.querySelector('input[name="_token"]').value;

                    btn.disabled = true;
                    btn.innerHTML = '⏳ A remover...';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'unfollowed') {
                                row.classList.add('fade-out');
                                setTimeout(() => row.remove(), 400);
                            } else {
                                alert('Não foi possível desmarcar este jogador.');
                            }
                        })
                        .catch(() => alert('Erro de comunicação com o servidor.'))
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Deixar de seguir';
                        });
                });
            });
        });
    </script>
@endsection
