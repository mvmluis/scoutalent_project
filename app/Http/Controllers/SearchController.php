<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\CoachProfile;
use App\Models\Players;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function index()
    {
        // Vai buscar todos os países
        $countries = DB::table('countries')->select('id', 'name', 'code', 'continent', 'flag')->get();

        return view('search.layout.dashboard', compact('countries'));
    }

    public function getLeagues($country_id)
    {
        // Como a tabela leagues tem apenas o campo "country" (nome),
        // precisamos buscar ligas pelo país (texto).
        $country = DB::table('countries')->where('id', $country_id)->first();

        $leagues = DB::table('leagues')
            ->where('country', $country->name)
            ->select('id', 'name', 'logo', 'type')
            ->get();

        return response()->json($leagues);
    }

    public function getTeams($league_id)
    {
        // 🔹 Buscar a liga pelo ID interno
        $league = DB::table('leagues')->where('id', $league_id)->first();
        if (!$league) {
            return response()->json([], 404);
        }

        // 🔹 Buscar equipas pelo external_id da liga (armazenado em teams.league_id)
        $teams = DB::table('teams')
            ->where('league_id', $league->external_id)
            ->select('id', 'name', 'logo', 'founded', 'venue')
            ->orderBy('name')
            ->get();

        return response()->json($teams);
    }


    public function getPlayers($team_id)
    {
        // 🔹 Verifica se a equipa existe
        $team = DB::table('teams')->where('id', $team_id)->first();
        if (!$team) {
            return response()->json([], 404);
        }

        // 🔹 Buscar todos os jogadores com o team_id local
        $players = DB::table('players')
            ->where('team_id', $team->external_id)
            ->select('id', 'name', 'photo', 'age', 'nationality')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                // Indica se o jogador tem relatórios
                $p->has_reports = DB::table('player_reports')
                    ->where('player_id', $p->id)
                    ->exists();

                // Foto padrão se não existir
                if (empty($p->photo)) {
                    $p->photo = '/images/default-player.png';
                }

                return $p;
            });

        return response()->json($players);
    }


    public function showPlayers($id)
    {
        $player = DB::table('players')->where('id', $id)->first();

        if (!$player) {
            return redirect()->back()->with('error', 'Jogador não encontrado.');
        }

        $meta = $player->meta ? json_decode($player->meta, true) : [];
        $player->photo = $meta['player']['photo']
            ?? $meta['photo']
            ?? '/images/default-player.png';

        $profile = DB::table('player_profiles')
            ->where('player_id', $player->id)
            ->first();

        // 🔹 Carregar relatórios sem colunas extras
        $reports = DB::table('player_reports')
            ->where('player_id', $player->id)
            ->orderBy('report_date', 'desc')
            ->get()
            ->map(function ($r) {
                // Decodifica indicadores
                if (isset($r->indicators) && $r->indicators !== null) {
                    if (is_string($r->indicators)) {
                        $decoded = json_decode($r->indicators, true);
                        $r->indicators = $decoded === null ? null : $decoded;
                    } elseif (is_object($r->indicators)) {
                        $r->indicators = (array)$r->indicators;
                    }
                } else {
                    $r->indicators = null;
                }

                // 🔹 Gerar labels manualmente (simular “4+”, “3–”, etc.)
                $r->scoutalent_rentabilidade_label = self::generateLabel($r->scoutalent_rentabilidade);
                $r->scoutalent_potencial_label = self::generateLabel($r->scoutalent_potencial);

                return $r;
            });

        return view('players.showplayer', [
            'player' => $player,
            'profile' => $profile,
            'reports' => $reports,
        ]);
    }

    /**
     * Função auxiliar para gerar o símbolo "+" ou "-" consoante o valor.
     * Exemplo: 4 -> "4+", -3 -> "3–", 0 -> "0"
     */
    private static function generateLabel($value)
    {
        if ($value === null) return '—';
        $abs = abs($value);
        if ($value > 0) return $abs . '+';
        if ($value < 0) return $abs . '–';
        return (string) $value;
    }

    public function getCoaches($team_id)
    {
        $team = DB::table('teams')->where('id', $team_id)->first();
        if (!$team) return response()->json([]);

        $coaches = DB::table('coachs')
            ->where('team_id', $team->id)
            ->select('id', 'name', 'photo', 'age', 'nationality')
            ->get()
            ->map(function ($c) {
                if (empty($c->photo)) {
                    $c->photo = '/images/default-coach.png';
                }
                return $c;
            });

        return response()->json($coaches);
    }

    public function showProfile($id)
    {
        $coach = Coach::findOrFail($id);
        $profile = DB::table('coach_profiles')->where('coach_id', $id)->first();
        return view('coachs.profile', compact('coach', 'profile'));
    }

    public function showCoachs($id)
    {
        // Buscar treinador + equipa associada
        $coach = DB::table('coachs')
            ->leftJoin('teams', 'coachs.team_id', '=', 'teams.id')
            ->select('coachs.*', 'teams.name as team_name', 'teams.logo as team_logo')
            ->where('coachs.id', $id)
            ->first();

        if (!$coach) {
            return redirect()->route('search.index')->with('error', 'Treinador não encontrado.');
        }

        // Buscar o perfil do treinador (ScouTalent)
        $profile = DB::table('coach_profiles')
            ->where('coach_id', $coach->id)
            ->first();

        // Decodificar meta JSON
        $meta = [];
        if (!empty($coach->meta)) {
            $meta = is_string($coach->meta) ? json_decode($coach->meta, true) : $coach->meta;
        }

        // Foto prioritária: campo → meta.photo → padrão
        $coach->photo = $coach->photo
            ?? data_get($meta, 'photo')
            ?? '/images/default-coach.png';

        // Nome da equipa: DB → meta.team.name
        $coach->team_name = $coach->team_name
            ?? data_get($meta, 'team.name');

        // Enviar tudo para a view
        return view('coachs.showcoachs', compact('coach', 'meta', 'profile'));
    }

    public function storeProfile(Request $request, Players $player)
    {
        // Validação dos dados básicos e estatísticos
        $validated = $request->validate([
            'market_value' => 'nullable|numeric',
            'contract_end' => 'nullable|date',
            'stat1_label' => 'nullable|string|max:100',
            'stat1_value' => 'nullable|string|max:100',
            'stat2_label' => 'nullable|string|max:100',
            'stat2_value' => 'nullable|string|max:100',
            'stat3_label' => 'nullable|string|max:100',
            'stat3_value' => 'nullable|string|max:100',
            'stat4_label' => 'nullable|string|max:100',
            'stat4_value' => 'nullable|string|max:100',
            'stat5_label' => 'nullable|string|max:100',
            'stat5_value' => 'nullable|string|max:100',
            'stat6_label' => 'nullable|string|max:100',
            'stat6_value' => 'nullable|string|max:100',
        ]);

        // Calcula médias com base nos relatórios existentes
        $reports = DB::table('player_reports')
            ->where('player_id', $player->id)
            ->whereNotNull('scoutalent_rentabilidade')
            ->whereNotNull('scoutalent_potencial')
            ->get()
            ->map(function ($r) {
                // decodifica indicators se necessário (defensivo)
                if (isset($r->indicators) && $r->indicators !== null) {
                    if (is_string($r->indicators)) {
                        $decoded = json_decode($r->indicators, true);
                        $r->indicators = $decoded === null ? null : $decoded;
                    } elseif (is_object($r->indicators)) {
                        $r->indicators = (array)$r->indicators;
                    }
                } else {
                    $r->indicators = null;
                }
                return $r;
            });

        $mediaRentabilidade = $reports->avg('scoutalent_rentabilidade') ?? 0;
        $mediaPotencial = $reports->avg('scoutalent_potencial') ?? 0;

        // Adiciona os valores calculados (garante numerics)
        $validated['scoutalent_rentabilidade'] = is_null($mediaRentabilidade) ? 0 : $mediaRentabilidade;
        $validated['scoutalent_potencial'] = is_null($mediaPotencial) ? 0 : $mediaPotencial;

        // Atualiza ou cria o perfil
        $player->profile()->updateOrCreate(
            ['player_id' => $player->id],
            $validated
        );

        return back()->with('success', 'Perfil ScouTalent atualizado com sucesso! Médias calculadas automaticamente.');
    }

    public function storeCoach(Request $request, $coachId)
    {
        // 🔍 Validação básica
        $validated = $request->validate([
            'contract_end' => 'nullable|date',
            'stat1_label' => 'nullable|string|max:255',
            'stat1_value' => 'nullable|string|max:255',
            'stat2_label' => 'nullable|string|max:255',
            'stat2_value' => 'nullable|string|max:255',
            'stat3_label' => 'nullable|string|max:255',
            'stat3_value' => 'nullable|string|max:255',
        ]);

        try {
            // 🧩 Guardar ou atualizar o perfil do treinador
            CoachProfile::updateOrCreate(
                ['coach_id' => $coachId],
                [
                    'contract_end' => $validated['contract_end'] ?? null,
                    'stat1_label' => $validated['stat1_label'] ?? null,
                    'stat1_value' => $validated['stat1_value'] ?? null,
                    'stat2_label' => $validated['stat2_label'] ?? null,
                    'stat2_value' => $validated['stat2_value'] ?? null,
                    'stat3_label' => $validated['stat3_label'] ?? null,
                    'stat3_value' => $validated['stat3_value'] ?? null,
                ]
            );

            return back()->with('success', '✅ Perfil do treinador atualizado com sucesso.');
        } catch (\Throwable $e) {
            \Log::error('Erro ao guardar perfil do treinador', [
                'coach_id' => $coachId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', '⚠️ Ocorreu um erro ao guardar o perfil do treinador.');
        }
    }

    public function storeReport(Request $request, $playerId)
    {
        $data = $request->validate([
            'report_date' => 'nullable|date',
            'observations' => 'nullable|string|max:250',
            'scoutalent_rentabilidade' => 'nullable|numeric|min:-5|max:5',
            'scoutalent_potencial' => 'nullable|numeric|min:-5|max:5',
            'role' => 'nullable|string|max:100',
            'indicators_json' => 'nullable|string',
        ]);

        // 🔹 Utilizador autenticado
        $user = Auth::user();
        $data['author'] = $user->nome_tecnico ?? $user->name;

        // 🔹 Prepara o insert
        $insert = [
            'player_id' => $playerId,
            'user_id' => $user->id, // ✅ grava o ID do utilizador autenticado
            'report_date' => $data['report_date'] ?? null,
            'observations' => $data['observations'] ?? null,
            'scoutalent_rentabilidade' => $data['scoutalent_rentabilidade'] ?? null,
            'scoutalent_potencial' => $data['scoutalent_potencial'] ?? null,
            'author' => $data['author'],
            'role' => $data['role'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 🔹 Guarda os indicadores (JSON)
        if (!empty($data['indicators_json'])) {
            $decoded = json_decode($data['indicators_json'], true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['success' => false, 'message' => 'JSON de indicadores inválido.'], 422);
            }
            $insert['indicators'] = json_encode($decoded);
        } else {
            $insert['indicators'] = null;
        }

        // 🔹 Inserir o relatório
        $id = DB::table('player_reports')->insertGetId($insert);

        // 🔹 Atualizar médias do jogador
        $this->recalculatePlayerProfileAverages($playerId);

        // 🔹 Buscar relatório completo
        $report = DB::table('player_reports')->where('id', $id)->first();

        if (!empty($report->indicators) && is_string($report->indicators)) {
            $report->indicators = json_decode($report->indicators, true) ?: null;
        } else {
            $report->indicators = null;
        }

        return response()->json([
            'success' => true,
            'message' => '✅ Relatório criado com sucesso!',
            'report' => $report
        ]);
    }

    public function updateReport(Request $request, Players $player, $reportId)
    {
        // 🔹 Validação
        $validated = $request->validate([
            'report_date' => 'nullable|date',
            'observations' => 'nullable|string|max:250',
            'scoutalent_rentabilidade' => 'nullable|numeric|min:-5|max:5',
            'scoutalent_potencial' => 'nullable|numeric|min:-5|max:5',
            'role' => 'nullable|string|max:100',
            'indicators_json' => 'nullable|string',
        ]);

        // 🔹 Buscar relatório existente
        $existing = DB::table('player_reports')->where('id', $reportId)->first();
        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }

        // 🔹 Utilizador autenticado
        $user = Auth::user();
        $authorName = $user->nome_tecnico ?? $user->name;

        // 🔹 Prepara campos a atualizar
        $update = [
            'updated_at' => now(),
            'author' => $authorName,
            'user_id' => $user->id, // ✅ garante que o ID do autor é atualizado também
        ];

        if ($request->has('report_date')) {
            $update['report_date'] = $request->input('report_date') ?: null;
        }
        if ($request->has('observations')) {
            $update['observations'] = $request->input('observations') ?: null;
        }
        if ($request->has('scoutalent_rentabilidade')) {
            $update['scoutalent_rentabilidade'] = $request->input('scoutalent_rentabilidade');
        }
        if ($request->has('scoutalent_potencial')) {
            $update['scoutalent_potencial'] = $request->input('scoutalent_potencial');
        }
        if ($request->has('role')) {
            $update['role'] = $request->input('role') ?: null;
        }

        // 🔹 Indicators JSON
        if ($request->has('indicators_json')) {
            $indJson = $request->input('indicators_json');
            if (!empty($indJson)) {
                $decoded = json_decode($indJson, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    \Log::warning('JSON inválido em indicators_json ao atualizar relatório', [
                        'report_id' => $reportId,
                        'payload' => $indJson,
                        'error' => json_last_error_msg(),
                    ]);
                    return response()->json(['success' => false, 'message' => 'JSON de indicadores inválido.'], 422);
                }
                $update['indicators'] = json_encode($decoded);
            } else {
                $update['indicators'] = null;
            }
        }

        // 🔹 Atualiza no BD se houver mais campos
        if (count($update) > 1) {
            DB::table('player_reports')->where('id', $reportId)->update($update);
        }

        // 🔹 Buscar novamente o relatório atualizado
        $report = DB::table('player_reports')->where('id', $reportId)->first();

        // 🔹 Decodificar indicators
        if (!empty($report->indicators) && is_string($report->indicators)) {
            $report->indicators = json_decode($report->indicators, true) ?: null;
        } else {
            $report->indicators = null;
        }

        // 🔹 Recalcular médias
        if ($report && isset($report->player_id)) {
            $this->recalculatePlayerProfileAverages($report->player_id);
        }

        return response()->json([
            'success' => true,
            'message' => '✏️ Relatório atualizado com sucesso!',
            'report' => $report
        ]);
    }



    public function editReport(Players $player, $reportId)
    {
        $report = DB::table('player_reports')->where('id', $reportId)->first();

        // Decodifica indicators do relatório em edição
        if (isset($report->indicators) && $report->indicators !== null) {
            if (is_string($report->indicators)) {
                $decoded = json_decode($report->indicators, true);
                $report->indicators = $decoded === null ? null : $decoded;
            } elseif (is_object($report->indicators)) {
                $report->indicators = (array)$report->indicators;
            }
        } else {
            $report->indicators = null;
        }

        // Passamos também o relatório em edição
        $reports = DB::table('player_reports')
            ->where('player_id', $player->id)
            ->orderBy('report_date', 'desc')
            ->get()
            ->map(function ($r) {
                if (isset($r->indicators) && $r->indicators !== null) {
                    if (is_string($r->indicators)) {
                        $decoded = json_decode($r->indicators, true);
                        $r->indicators = $decoded === null ? null : $decoded;
                    } elseif (is_object($r->indicators)) {
                        $r->indicators = (array)$r->indicators;
                    }
                } else {
                    $r->indicators = null;
                }
                return $r;
            });

        $profile = DB::table('player_profiles')
            ->where('player_id', $player->id)
            ->first();

        return view('players.showplayer', [
            'player' => $player,
            'profile' => $profile,
            'reports' => $reports,
            'editingReport' => $report, // relatório que está em edição
        ]);
    }

    public function destroyReport(Players $player, $reportId)
    {
        $row = DB::table('player_reports')->where('id', $reportId)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }

        DB::table('player_reports')->where('id', $reportId)->delete();

        // Recalcular médias do player
        $this->recalculatePlayerProfileAverages($row->player_id);

        return response()->json([
            'success' => true,
            'message' => '🗑️ Relatório eliminado com sucesso!'
        ]);
    }

    /**
     * Recalcula as médias rentabilidade/potencial do player e actualiza player_profiles.
     *
     * @param int $playerId
     * @return void
     */
    protected function recalculatePlayerProfileAverages(int $playerId): void
    {
        $reports = DB::table('player_reports')
            ->where('player_id', $playerId)
            ->whereNotNull('scoutalent_rentabilidade')
            ->whereNotNull('scoutalent_potencial')
            ->get();

        $mediaRent = $reports->avg('scoutalent_rentabilidade');
        $mediaPot = $reports->avg('scoutalent_potencial');

        // Normalizar para 0 se nulo
        $mediaRent = is_null($mediaRent) ? 0 : $mediaRent;
        $mediaPot = is_null($mediaPot) ? 0 : $mediaPot;

        // Atualiza ou insere o profile
        DB::table('player_profiles')->updateOrInsert(
            ['player_id' => $playerId],
            [
                'scoutalent_rentabilidade' => $mediaRent,
                'scoutalent_potencial' => $mediaPot,
                'updated_at' => now(),
            ]
        );
    }
}
