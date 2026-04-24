<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\FootballApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Players;
use Illuminate\Support\Facades\Log;


class FootballController extends Controller
{
    public function index(Request $request)
    {
        $league = $request->input('league', '39'); // liga default
        $season = $request->input('season', (string)now()->year);
        $team = $request->input('team'); // opcional

        $countries = $this->fetchCountries();
        $seasons = $this->fetchSeasons();

        $players = [];
        $error = null;
        $ran = false;

        if ($request->isMethod('post') || $request->boolean('load')) {
            $ran = true;
            try {
                // Passa null quando não há team selecionado
                $players = $this->fetchAllPlayers($league, $season, $team ?? null);
            } catch (\Throwable $e) {
                \Log::error("❌ Erro em fetchAllPlayers: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $error = $e->getMessage();
                $players = [];
            }
        }

        return view('players.layout.dashboard', compact(
            'players', 'league', 'season', 'team', 'error', 'ran', 'countries', 'seasons'
        ));
    }


    public function exportCsv(Request $request)
    {
        $league = trim($request->input('league', ''));
        $season = trim($request->input('season', ''));
        $team = trim($request->input('team', ''));

        if ($league === '' || $season === '') {
            return back()->with('error', 'Indique liga e época para exportar.');
        }

        // usa a mesma função da listagem
        $players = $this->fetchAllPlayers($league, $season, $team);

        $filename = 'players_' . $league . '_' . $season . ($team ? '_team_' . $team : '') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        // ponto e vírgula para abrir bem no Excel PT
        $delim = ';';

        $callback = function () use ($players, $delim) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 para acentos no Excel
            fprintf($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Nome', 'Idade', 'Nacionalidade', 'Altura', 'Peso',
                'Nascimento', 'Equipa', 'Aparições', 'Minutos', 'Golos', 'Cartões (Y/R)'
            ], $delim);

            foreach ($players as $item) {
                $p = $item['player'] ?? [];
                $stats = $item['statistics'][0] ?? [];
                $games = $stats['games'] ?? [];
                $goals = $stats['goals'] ?? [];
                $cards = $stats['cards'] ?? [];

                fputcsv($out, [
                    $p['name'] ?? '—',
                    $p['age'] ?? null,
                    $p['nationality'] ?? '—',
                    $p['height'] ?? '—',
                    $p['weight'] ?? '—',
                    data_get($p, 'birth.date', '—'),
                    data_get($stats, 'team.name', '—'),
                    // a API-Football usa a chave "appearences" (com esse typo)
                    $games['appearences'] ?? ($games['lineups'] ?? null),
                    $games['minutes'] ?? null,
                    $goals['total'] ?? 0,
                    ($cards['yellow'] ?? 0) . '/' . ($cards['red'] ?? 0),
                ], $delim);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * Busca todos os jogadores de uma liga completa (incluindo os que não jogaram).
     *
     * @param string $league ID da liga (ex: 39)
     * @param string $season Ano da época (ex: 2024)
     * @return array Lista de jogadores com estatísticas (rating sempre float)
     */
    private function fetchAllPlayers(string $league, string $season, ?string $team = null): array
    {
        $apiKey = $this->apiKey;
        $baseUrl = $this->baseUrl;
        $players = [];
        $seenPlayerIds = [];

        \Log::info("📡 [FETCH PLAYERS - COMBINED] Início", compact('league', 'season', 'team'));

        $leagueName = DB::table('leagues')->where('id', (int)$league)->value('name') ?? "Liga {$league}";

        // 1) obter equipas
        $teamsResponse = Http::withHeaders(['x-apisports-key' => $apiKey])
            ->get("{$baseUrl}/teams", ['league' => $league, 'season' => $season]);

        if (!$teamsResponse->ok()) {
            \Log::error("❌ Erro ao obter equipas", ['status' => $teamsResponse->status()]);
            return [];
        }

        $teams = data_get($teamsResponse->json(), 'response', []);
        if (empty($teams)) {
            \Log::warning("⚠️ Nenhuma equipa encontrada na liga {$leagueName}");
            return [];
        }

        foreach ($teams as $teamData) {
            $teamObj = data_get($teamData, 'team');
            if (!$teamObj) continue;

            $teamId = (int)data_get($teamObj, 'id');
            $teamName = data_get($teamObj, 'name', '—');

            \Log::info("⚽ Iniciando equipa: {$teamName} (ID {$teamId})");

            // ---------- A) carregar SQUAD (plantel completo: posição, height, weight, birth) ----------
            $squadResponse = Http::withHeaders(['x-apisports-key' => $apiKey])
                ->get("{$baseUrl}/players/squads", ['team' => $teamId]);

            $squad = [];
            if ($squadResponse->ok()) {
                $squad = data_get($squadResponse->json(), 'response.0.players', []) ?: [];
            } else {
                \Log::warning("⚠️ Falha /players/squads para {$teamName}", ['status' => $squadResponse->status()]);
            }

            $addedFromSquad = 0;
            foreach ($squad as $pl) {
                $id = $pl['id'] ?? null;
                if (!$id || isset($seenPlayerIds[$id])) continue;
                $seenPlayerIds[$id] = true;

                $birthRaw = data_get($pl, 'birth.date') ?: data_get($pl, 'birth_date') ?: null;
                $birthDate = $birthRaw ? date('Y-m-d', strtotime($birthRaw)) : null;
                $heightRaw = trim($pl['height'] ?? '');
                $weightRaw = trim($pl['weight'] ?? '');

                $height = $heightRaw !== '' ? (preg_replace('/[^0-9]/', '', $heightRaw) . (strpos($heightRaw, 'cm') === false ? ' cm' : '')) : null;
                $weight = $weightRaw !== '' ? (preg_replace('/[^0-9]/', '', $weightRaw) . (strpos($weightRaw, 'kg') === false ? ' kg' : '')) : null;

                $players[$id] = [
                    'id' => (int)$id,
                    'name' => $pl['name'] ?? '—',
                    'age' => $pl['age'] ?? null,
                    'nationality' => $pl['nationality'] ?? 'Desconhecida',
                    'height' => $height,
                    'weight' => $weight,
                    'birth_date' => $birthDate,
                    'photo' => $pl['photo'] ?? null,
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'league_id' => (int)$league,
                    'league_name' => $leagueName,
                    // posição normalmente vem aqui no squad
                    'position' => $pl['position'] ?? ($pl['role'] ?? null) ?? '—',
                    'appearances' => 0,
                    'minutes' => 0,
                    'goals' => 0,
                    'yellow_cards' => 0,
                    'red_cards' => 0,
                    'rating' => 0.0,
                ];
                $addedFromSquad++;
            }
            \Log::info("   ✅ squad carregado: {$teamName} -> {$addedFromSquad} jogadores adicionados pelo squad");

            // ---------- B) carregar /players (estatísticas paginadas) e mesclar ----------
            $page = 1;
            $addedFromStats = 0;
            do {
                $statsResponse = Http::withHeaders(['x-apisports-key' => $apiKey])
                    ->get("{$baseUrl}/players", [
                        'league' => $league,
                        'season' => $season,
                        'team' => $teamId,
                        'page' => $page,
                    ]);

                if (!$statsResponse->ok()) {
                    \Log::warning("⚠️ Falha /players (stats) para {$teamName} page {$page}", ['status' => $statsResponse->status()]);
                    break;
                }

                $data = data_get($statsResponse->json(), 'response', []);
                if (empty($data)) break;

                foreach ($data as $item) {
                    $p = data_get($item, 'player', []);
                    $id = $p['id'] ?? null;
                    if (!$id) continue;

                    // Se ainda não estava no array (por algum motivo não apareceu no squad), adiciona info básica
                    if (!isset($players[$id])) {
                        $birthRaw = data_get($p, 'birth.date') ?: null;
                        $birthDate = $birthRaw ? date('Y-m-d', strtotime($birthRaw)) : null;

                        $players[$id] = [
                            'id' => (int)$id,
                            'name' => $p['name'] ?? '—',
                            'age' => $p['age'] ?? null,
                            'nationality' => $p['nationality'] ?? 'Desconhecida',
                            'height' => $p['height'] ?? null,
                            'weight' => $p['weight'] ?? null,
                            'birth_date' => $birthDate,
                            'photo' => $p['photo'] ?? null,
                            'team_id' => $teamId,
                            'team_name' => $teamName,
                            'league_id' => (int)$league,
                            'league_name' => $leagueName,
                            'position' => $p['position'] ?? '—',
                            'appearances' => 0,
                            'minutes' => 0,
                            'goals' => 0,
                            'yellow_cards' => 0,
                            'red_cards' => 0,
                            'rating' => 0.0,
                        ];
                        $addedFromStats++;
                    }

                    // Mesclar estatísticas (se existirem)
                    $statsList = data_get($item, 'statistics', []);
                    $apps = $mins = $goals = $yellow = $red = 0;
                    $ratingSum = 0.0; $ratingCount = 0;

                    foreach ($statsList as $st) {
                        $apps += (int)data_get($st, 'games.appearences', 0);
                        $mins += (int)data_get($st, 'games.minutes', 0);
                        $goals += (int)data_get($st, 'goals.total', 0);
                        $yellow += (int)data_get($st, 'cards.yellow', 0);
                        $red += (int)data_get($st, 'cards.red', 0);

                        if (!empty($st['games']['rating'])) {
                            $ratingSum += (float)$st['games']['rating'];
                            $ratingCount++;
                        }

                        // posição alternativa: alguns endpoints colocam a posição em statistics.games.position
                        if (empty($players[$id]['position']) || $players[$id]['position'] === '—') {
                            $posAlt = data_get($st, 'games.position') ?: data_get($p, 'position');
                            if ($posAlt) $players[$id]['position'] = $posAlt;
                        }
                    }

                    if ($ratingCount > 0) {
                        $players[$id]['rating'] = round($ratingSum / $ratingCount, 2);
                    }

                    $players[$id]['appearances'] = $apps;
                    $players[$id]['minutes'] = $mins;
                    $players[$id]['goals'] = $goals;
                    $players[$id]['yellow_cards'] = $yellow;
                    $players[$id]['red_cards'] = $red;
                }

                $page++;
            } while (!empty($data));

            \Log::info("   ✅ stats mescladas: {$teamName} -> +{$addedFromStats} jogadores adicionados via /players (stats)");
            \Log::info("   ℹ️ total unique players até agora: " . count($players));
        }

        \Log::info("🏁 Fim — total final", ['total' => count($players)]);
        // retorna array indexado numericamente
        return array_values($players);
    }



    public function teamsByLeague(Request $request)
    {
        $league = $request->query('league');
        $season = $request->query('season', now()->year);

        if (!$league) {
            return response()->json(['error' => 'league required'], 422);
        }

        try {
            // fetchTeamsFromApi aceita tanto id interno como external_id
            $teamsRaw = $this->fetchTeamsFromApi((int)$league, (int)$season);

            // Normaliza para enviar só o objecto team (id, name, logo, ...)
            $teams = array_map(function ($item) {
                return $item['team'] ?? $item;
            }, $teamsRaw);

            return response()->json(['teams' => $teams]);
        } catch (\Throwable $e) {
            Log::error('teamsByLeague error', [
                'message' => $e->getMessage(),
                'league' => $league,
                'season' => $season,
            ]);
            return response()->json(['error' => 'Erro ao obter clubes: ' . $e->getMessage()], 500);
        }
    }

    public function sync(Request $request)
    {
        $league = $request->input('league', '39');
        $season = $request->input('season', now()->year);
        $teamParam = $request->input('team');

        // Normaliza team param
        $teamIds = null;
        if (!is_null($teamParam) && $teamParam !== '') {
            if (is_array($teamParam)) {
                $teamArr = array_values(array_filter($teamParam, fn($t) => (string)$t !== ''));
            } elseif (is_string($teamParam) && strpos($teamParam, ',') !== false) {
                $teamArr = array_values(array_filter(array_map('trim', explode(',', $teamParam))));
            } else {
                $teamArr = [(string)$teamParam];
            }
            $teamIds = empty($teamArr) ? null : implode(',', $teamArr);
        }

        try {
            $players = $this->fetchAllPlayers($league, $season, $teamIds);

            if (empty($players)) {
                return back()->with('info', 'Nenhum jogador encontrado para sincronizar.');
            }

            $created = $updated = $skipped = 0;

            DB::transaction(function () use ($players, &$created, &$updated, &$skipped) {
                // external ids (inteiros)
                $externalIds = array_values(array_filter(array_map(fn($p) => isset($p['id']) ? (int)$p['id'] : null, $players)));
                if (empty($externalIds)) return;

                // jogadores existentes -> key by integer external_id
                $existing = Players::whereIn('external_id', $externalIds)->get()
                    ->keyBy(fn($p) => (int)$p->external_id);

                // mapear equipas (external_id -> local id)
                $teamExternalIds = array_unique(array_filter(array_map(fn($p) => $p['team_id'] ?? null, $players)));
                $teamsMap = [];
                if (!empty($teamExternalIds)) {
                    $teams = Team::whereIn('external_id', $teamExternalIds)
                        ->orWhereIn('id', $teamExternalIds)
                        ->get();
                    foreach ($teams as $t) {
                        if (!empty($t->external_id)) $teamsMap[(string)$t->external_id] = $t->id;
                        $teamsMap[(string)$t->id] = $t->id;
                    }
                }

                $rows = [];
                foreach ($players as $item) {
                    $externalId = $item['id'] ?? null;
                    if (empty($externalId)) {
                        $skipped++;
                        continue;
                    }

                    $externalId = (int)$externalId;

                    $birthDate = !empty($item['birth_date'])
                        ? date('Y-m-d', strtotime($item['birth_date']))
                        : null;

                    $rawTeam = $item['team_id'] ?? null;
                    $localTeamId = $rawTeam && isset($teamsMap[(string)$rawTeam])
                        ? $teamsMap[(string)$rawTeam]
                        : ($item['team_id'] ?? null);

                    $rows[] = [
                        'external_id' => $externalId,
                        'name' => $item['name'] ?? null,
                        'photo' => $item['photo'] ?? null,
                        'age' => isset($item['age']) ? (int)$item['age'] : null,
                        'nationality' => $item['nationality'] ?? null,
                        'height' => $item['height'] ?? null,           // <- altura
                        'weight' => $item['weight'] ?? null,           // <- peso
                        'birth_date' => $birthDate,                    // <- nascimento
                        'team_id' => $localTeamId,
                        'team_name' => $item['team_name'] ?? null,
                        'league_id' => $item['league_id'] ?? null,
                        'league_name' => $item['league_name'] ?? null,
                        'position' => $item['position'] ?? null,       // <- posição
                        'rating' => is_numeric($item['rating'] ?? null) ? (float)$item['rating'] : null,
                        'appearances' => (int)($item['appearances'] ?? 0),
                        'minutes' => (int)($item['minutes'] ?? 0),
                        'goals' => (int)($item['goals'] ?? 0),
                        'yellow_cards' => (int)($item['yellow_cards'] ?? 0),
                        'red_cards' => (int)($item['red_cards'] ?? 0),
                        'meta' => json_encode($item, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                }

                \Log::info('[SYNC] Rows prepared', ['total_rows' => count($rows), 'skipped' => $skipped, 'existing_found' => count($existing)]);

                // upsert em chunks
                $chunkSize = 200;
                $updateCols = [
                    'name','photo','age','nationality','height','weight',
                    'birth_date','team_id','team_name','league_id','league_name',
                    'position','rating','appearances','minutes','goals',
                    'yellow_cards','red_cards','meta','updated_at'
                ];

                foreach (array_chunk($rows, $chunkSize) as $chunk) {
                    Players::upsert($chunk, ['external_id'], $updateCols);
                }

                // contagem precisa created/updated
                foreach ($rows as $r) {
                    $ext = (int)$r['external_id'];
                    if (isset($existing[$ext])) {
                        $updated++;
                    } else {
                        $created++;
                    }
                }
            });

            $msg = "✅ Sync completa — criados: {$created}, atualizados: {$updated}, ignorados: {$skipped}.";
            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            \Log::error('❌ Erro na sincronização', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'league' => $league,
                'season' => $season,
                'team' => $teamParam,
            ]);
            return back()->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }


    private function apiBase(): string
    {
        return 'https://v3.football.api-sports.io';
    }

    private function apiHeaders(): array
    {
        return [
            'x-apisports-key' => config('services.football.key', env('FOOTBALL_API_KEY')),
        ];
    }

    private function allowedCountriesTerms(): array
    {
        return [
            'Netherlands', 'Germany', 'Denmark', 'Italy', 'Mexico', 'Switzerland', 'Romania', 'Cyprus', 'Morocco',
            'Azerbaijan', 'Armenia', 'Colombia', 'Czech Republic', 'Russia', 'England', 'Brazil',
            'United Arab Emirates', 'USA', 'United States', 'Poland', 'Scotland', 'Greece', 'Turkey',
            'Norway', 'Hungary', 'Bulgaria', 'Croatia', 'Spain', 'France', 'Belgium', 'Portugal'
        ];
    }

    /**
     * Busca countries do endpoint /countries e filtra pela whitelist.
     * Cache por 24h.
     */
    private function fetchCountries(): array
    {
        return Cache::remember('football:countries:whitelist', 60 * 60 * 24, function () {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/countries');
            if (!$res->ok()) {
                return [];
            }
            $all = $res->json()['response'] ?? [];

            $allowedTerms = array_map('strtolower', $this->allowedCountriesTerms());

            // Filtra por substring no name ou pelo code (se necessário)
            $filtered = array_filter($all, function ($c) use ($allowedTerms) {
                $name = strtolower(data_get($c, 'name', ''));
                $code = strtolower(data_get($c, 'code', ''));
                foreach ($allowedTerms as $term) {
                    if (str_contains($name, $term) || ($code && str_contains($code, $term))) {
                        return true;
                    }
                }
                return false;
            });

            // Normaliza a forma de devolver: name + code
            return array_values(array_map(function ($c) {
                return [
                    'name' => data_get($c, 'name'),
                    'code' => data_get($c, 'code'),
                ];
            }, $filtered));
        });
    }

    /**
     * Busca seasons suportadas pela API. Cache 24h.
     */
    private function fetchSeasons(): array
    {
        return Cache::remember('football:seasons', 60 * 60 * 24, function () {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/seasons');
            if (!$res->ok()) return [];
            // Normalmente response é um array de inteiros/anos
            return $res->json()['response'] ?? [];
        });
    }

    /**
     * Busca ligas pela API, filtrando por country (name) e season (opcional).
     * Cache por 6h por combinaçao country+season.
     */
    private function fetchLeagues(?string $country = null, ?string $season = null): array
    {
        $query = array_filter([
            'country' => $country ?: null,
            'season' => $season ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $cacheKey = 'football:leagues:' . md5(json_encode($query));
        return Cache::remember($cacheKey, 60 * 60 * 6, function () use ($query) {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/leagues', $query);
            if (!$res->ok()) {
                return [];
            }
            return $res->json()['response'] ?? [];
        });
    }

    /**
     * Rota AJAX: devolve ligas simplificadas para o frontend.
     */
   public function leaguesByCountry(Request $request)
{
    $country = $request->query('country');
    $season = $request->query('season');

    if (!$country) {
        return response()->json(['leagues' => []]);
    }

    try {
        $raw = $this->api->fetchLeagues($country, $season);
        $out = array_map(function ($l) use ($country) {
            $apiId = data_get($l, 'league.id');
            return [
                // frontend espera 'id' (mantém compatibilidade com o teu JS)
                'id'     => $apiId,
                'name'   => data_get($l, 'league.name'),
                'type'   => data_get($l, 'league.type'),
                'logo'   => data_get($l, 'league.logo'),
                'seasons'=> data_get($l, 'seasons'),
                // id interno se já existir na BD (pode ser útil)
                'db_id'  => DB::table('leagues')->where('external_id', $apiId)->value('id') ?: null,
            ];
        }, $raw);

        return response()->json(['leagues' => $out]);
    } catch (\Throwable $e) {
        Log::error('leaguesByCountry error: ' . $e->getMessage(), ['country' => $country, 'season' => $season]);
        return response()->json(['leagues' => []]);
    }
}

    // Se preferires manter a constante no service, torna-a `public const` lá
    // ou usa self::DEFAULT_LEAGUE_ID (definida aqui).
    protected $api;
    protected $baseUrl;
    protected $apiKey;

    public function __construct(FootballApiService $api)
    {
        $this->api = $api;

        // configurações (podes mover isto para o service se preferires)
        $this->baseUrl = 'https://v3.football.api-sports.io';
        $this->apiKey = config('services.football.key')
            ?? config('services.football.rapidapi_key')
            ?? env('FOOTBALL_API_KEY')
            ?? env('API_FOOTBALL_KEY')
            ?? env('X_RAPIDAPI_KEY');
    }

    /**
     * Mostra view com os resultados /teams (liga + época).
     * Querystring: ?league=39&season=2025&search=Sport
     */

   public function teamsIndex(Request $request)
{
    $league = $request->query('league');
    $league = $league !== null && $league !== '' ? (int)$league : null;

    $currentYear = (int) date('Y');
    $season = (int) $request->query('season', $currentYear);
    $searchFilter = $request->query('search');

    // CAPTURAR PAÍS PASSADO PELO FRONT-END (opcional)
    $leagueCountry = $request->query('league_country', null);
    if ($leagueCountry) {
        $leagueCountry = trim((string)$leagueCountry);
    }

    $apiError = null;
    $apiTeams = [];

    if ($league) {
        // Tentativa de mapear para o registo local das ligas (se existir)
      $leagueRecord = DB::table('leagues')
      ->where('external_id', $league)
      ->first();

        // Se não existe registo na BD -> avisar o utilizador (não tentar fallback inesperado)
        if (!$leagueRecord) {
            $apiError = "Liga com ID {$league} não encontrada na base de dados. Por favor resincronize as ligas (Admin -> Resync) antes de prosseguir.";
            Log::warning('teamsIndex: league not found in DB', ['league' => $league, 'league_country' => $leagueCountry]);
        } else {
            // Se o front-end passou um país, valida que o registo corresponde ao país seleccionado
            if ($leagueCountry) {
                $recordCountry = trim((string)($leagueRecord->country ?? ''));
                if ($recordCountry !== '' && strcasecmp($recordCountry, $leagueCountry) !== 0) {
                    $apiError = "A liga selecionada pertence a \"{$recordCountry}\" e não coincide com o país seleccionado \"{$leagueCountry}\". Verifica a seleção.";
                    Log::warning('teamsIndex: league-country mismatch', [
                        'league' => $league,
                        'record_country' => $recordCountry,
                        'selected_country' => $leagueCountry
                    ]);
                }
            }

            // se não houver erro até aqui, chamar a API
            if (!$apiError) {
                try {
                    $rawTeams = $this->fetchTeamsFromApi($leagueRecord->external_id, $season);
                    $normalized = array_map(fn($item) => $item['team'] ?? $item, $rawTeams);

                    if ($searchFilter) {
                        $apiTeams = array_values(array_filter($normalized, function ($t) use ($searchFilter) {
                            return !empty($t['name']) && (mb_stripos($t['name'], (string)$searchFilter) !== false);
                        }));
                    } else {
                        $apiTeams = array_values($normalized);
                    }
                } catch (\Throwable $e) {
                    Log::error('teamsIndex: fetchTeamsFromApi error: ' . $e->getMessage(), ['league' => $league, 'season' => $season]);
                    $apiError = 'Erro ao consultar API teams: ' . $e->getMessage();
                    $apiTeams = [];
                }
            }
        }
    }

    // ... resto do código mantém-se igual (fetchCountries, seasons, return view)
    try {
        $countries = $this->api->fetchCountries() ?? [];
    } catch (\Throwable $e) {
        Log::warning('teamsIndex: fetchCountries failed: ' . $e->getMessage());
        $countries = [];
    }

    try {
        $seasonsFromApi = $this->api->fetchSeasons() ?? [];
        $seasons = array_values(array_filter(array_map(function ($s) {
            if (is_array($s) && isset($s['season'])) return (int)$s['season'];
            if (is_scalar($s)) return (int)$s;
            return null;
        }, $seasonsFromApi)));
        if (empty($seasons)) {
            $seasons = range($currentYear, $currentYear - 5);
        }
    } catch (\Throwable $e) {
        Log::warning('teamsIndex: fetchSeasons failed: ' . $e->getMessage());
        $seasons = range($currentYear, $currentYear - 5);
    }

    $seasons = array_values(array_unique($seasons));
    rsort($seasons);

    return view('teams.layout.dashboard', [
        'apiTeams' => $apiTeams,
        'apiError' => $apiError,
        'league' => $league,
        'season' => $season,
        'countries' => $countries,
        'seasons' => $seasons,
    ]);
}


    /**
     * Faz fetch de todas as páginas do endpoint /teams com league+season.
     */
   private function fetchTeamsFromApi(?int $league = null, ?int $season = null): array
{
    $all = [];
    $page = 1;
    $total = 1;

    \Log::info('⚙️ [fetchTeamsFromApi] Início do processo', [
        'league' => $league,
        'season' => $season,
    ]);

    if (empty($league) && empty($season)) {
        \Log::error('❌ Nenhum parâmetro fornecido (league ou season).');
        throw new \RuntimeException('É necessário indicar league ou season para consultar a API.');
    }

    // 🔹 Liga na base de dados
    $leagueRecord = DB::table('leagues')
    ->where('external_id', $league)
    ->first();


    if (!$leagueRecord) {
        \Log::error("❌ Liga {$league} não encontrada na base de dados.");
        throw new \RuntimeException("Liga com ID {$league} não encontrada na base de dados.");
    }

    $apiLeagueId = $leagueRecord->external_id;
    $headers = ['x-apisports-key' => $this->apiKey];
    $query = ['league' => $apiLeagueId, 'season' => $season ?: now()->year];

    \Log::debug('🌍 Pedido inicial à API', [
        'url' => $this->baseUrl . '/teams',
        'query' => $query
    ]);

    $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/teams', $query);
    if (!$res->successful()) {
        \Log::error("❌ Erro HTTP {$res->status()}", [
            'body' => substr($res->body(), 0, 400)
        ]);
        throw new \RuntimeException("HTTP {$res->status()} - " . substr($res->body(), 0, 400));
    }

    $json = $res->json();
    $all = $json['response'] ?? [];

    $current = data_get($json, 'paging.current', 1);
    $total   = data_get($json, 'paging.total', 1);
    $page    = max(2, (int)$current + 1);

    \Log::info('📦 Página inicial recebida', [
        'current' => $current,
        'total_pages' => $total,
        'teams' => count($all)
    ]);

    // 🔁 Paginação
    while ($current < $total && $page <= 200) {
        $query['page'] = $page;

        \Log::debug("➡️ A pedir página {$page} de {$total}");

        $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/teams', $query);
        if (!$res->successful()) {
            \Log::warning("⚠️ Página {$page} falhou com HTTP {$res->status()}");
            break;
        }

        $json = $res->json();
        $items = $json['response'] ?? [];
        $all = array_merge($all, $items);

        $current = data_get($json, 'paging.current', $current);
        $total   = data_get($json, 'paging.total', $total);
        $page++;
    }

    $inserted = 0;
    $ignored  = 0;

    foreach ($all as $entry) {
        $team = $entry['team'] ?? null;
        if (!$team || empty($team['id'])) continue;

        $teamCountry = strtolower(trim($team['country'] ?? ''));
        $leagueCountry = strtolower(trim($leagueRecord->country ?? ''));

        if ($teamCountry !== $leagueCountry && $leagueCountry !== '') {
            $ignored++;
            \Log::warning("🚫 Ignorado: {$team['name']} ({$team['country']}) não pertence a {$leagueRecord->country}");
            continue;
        }

        $venue = $entry['venue'] ?? [];

        DB::table('teams')->updateOrInsert(
            ['external_id' => $team['id']],
            [
                'name'       => $team['name'] ?? null,
                'country'    => $team['country'] ?? null,
                'code'       => $team['code'] ?? null,
                'founded'    => $team['founded'] ?? null,
                'logo'       => $team['logo'] ?? null,
                'venue'      => $venue['name'] ?? null,
                'league_id'  => $apiLeagueId,
                'meta'       => json_encode($entry),
                'updated_at' => now(),
            ]
        );

        $inserted++;
    }

    \Log::info('✅ [fetchTeamsFromApi] Concluído', [
        'total_registos' => count($all),
        'inseridos'      => $inserted,
        'ignorados'      => $ignored,
        'league'         => $leagueRecord->name ?? 'Desconhecida',
        'league_country' => $leagueRecord->country ?? null,
        'season'         => $season,
    ]);

    return $all;
}


    /**
     * Sincroniza teams e grava na BD.
     */
    public function syncTeams(Request $request)
    {
        $data = $request->validate([
            'league' => 'required|integer', // Aceita ID interno OU external_id
            'season' => 'required|integer',
            'team_id' => 'nullable|integer',
        ]);

        $league = $data['league'];
        $season = $data['season'];
        $teamId = $data['team_id'] ?? null;

        try {
            \Log::info('syncTeams request recebido', ['league' => $league, 'season' => $season]);

            // 🔹 Aceita tanto id local como external_id da API
            $leagueRecord = DB::table('leagues')
                ->where('id', $league)
                ->orWhere('external_id', $league)
                ->first();

            if (!$leagueRecord) {
                \Log::warning('syncTeams: liga não encontrada', ['league_param' => $league]);
                return back()->with('error', "Liga com ID {$league} não encontrada na base de dados.");
            }

            \Log::info('syncTeams: liga encontrada', [
                'league_id' => $leagueRecord->id,
                'external_id' => $leagueRecord->external_id,
                'name' => $leagueRecord->name,
                'country' => $leagueRecord->country
            ]);

            $apiLeagueId = $leagueRecord->external_id;
            $fetched = 0;

            // 🔹 Configuração de headers
            $headers = ['x-apisports-key' => $this->apiKey];
            if (str_starts_with((string)$this->apiKey, 'x-') || config('services.football.rapidapi_key')) {
                $rapidKey = config('services.football.rapidapi_key') ?? $this->apiKey;
                $headers = [
                    'x-rapidapi-key' => $rapidKey,
                    'x-rapidapi-host' => 'v3.football.api-sports.io',
                ];
            }

            // 🔹 Primeira página
            $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/teams', [
                'league' => $apiLeagueId,
                'season' => $season,
            ]);

            \Log::info('syncTeams: primeira requisição', [
                'league' => $apiLeagueId,
                'season' => $season,
                'status' => $res->status(),
            ]);

            if (!$res->successful()) {
                \Log::warning('Erro na primeira request: ' . $res->status());
                return back()->with('error', 'Erro API Teams: ' . $res->status());
            }

            $json = $res->json();
            $items = $json['response'] ?? [];

            // 🔹 Inserir / Atualizar equipas
            foreach ($items as $item) {
                $t = $item['team'] ?? $item;
                $externalId = $t['id'] ?? null;
                if (!$externalId) continue;
                if ($teamId !== null && (int)$teamId !== (int)$externalId) continue;

                $venue = $item['venue'] ?? [];

                Team::updateOrCreate(
                    ['external_id' => $externalId],
                    [
                        'name' => $t['name'] ?? $t['team'] ?? null,
                        'country' => $t['country'] ?? null,
                        'code' => $t['code'] ?? null,
                        'founded' => $t['founded'] ?? null,
                        'logo' => $t['logo'] ?? "https://media.api-sports.io/football/teams/{$externalId}.png",
                        'venue' => $venue['name'] ?? null,
                        'league_id' =>  $apiLeagueId, // guarda o ID interno da BD
                        'meta' => json_encode($item),
                        'updated_at' => now(),
                    ]
                );

                $fetched++;
            }

            // 🔁 Paginação
            $current = data_get($json, 'paging.current', 1);
            $totalPages = data_get($json, 'paging.total', 1);
            $page = max(2, (int)$current + 1);

            while ($current < $totalPages && $page <= 200) {
                $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/teams', [
                    'league' => $apiLeagueId,
                    'season' => $season,
                    'page' => $page,
                ]);

                if (!$res->successful()) break;

                $json = $res->json();
                $items = $json['response'] ?? [];

                foreach ($items as $item) {
                    $t = $item['team'] ?? $item;
                    $externalId = $t['id'] ?? null;
                    if (!$externalId) continue;

                    $venue = $item['venue'] ?? [];

                    Team::updateOrCreate(
                        ['external_id' => $externalId],
                        [
                            'name' => $t['name'] ?? $t['team'] ?? null,
                            'country' => $t['country'] ?? null,
                            'code' => $t['code'] ?? null,
                            'founded' => $t['founded'] ?? null,
                            'logo' => $t['logo'] ?? "https://media.api-sports.io/football/teams/{$externalId}.png",
                            'venue' => $venue['name'] ?? null,
                            'league_id' => $leagueRecord->id,
                            'meta' => json_encode($item),
                            'updated_at' => now(),
                        ]
                    );

                    $fetched++;
                }

                $current = data_get($json, 'paging.current', $current);
                $page++;
            }

            \Log::info("syncTeams concluído", [
                'total_fetched' => $fetched,
                'league' => $leagueRecord->name,
                'season' => $season
            ]);

            return back()->with('success', "✅ Sincronização concluída: {$fetched} clubes atualizados.");
        } catch (\Throwable $e) {
            Log::error('syncTeams error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }

}

