<?php

namespace App\Http\Controllers;

use App\Models\TeamStatistics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\FootballApiService;
use Illuminate\Support\Facades\Log;

class EstatisticasController extends Controller
{
    protected $api;

    public function __construct(FootballApiService $api)
    {
        $this->api = $api;
    }

    public function index(Request $request)
    {

        // ▫️ Filtros selecionados vindos da querystring
        $country   = $request->query('country');
        $leagueId  = $request->query('league');
        $season    = (int) $request->query('season', now()->year);
        $fixtureId = $request->query('fixture');
        $teamId    = $request->query('team');
        $playerId  = $request->query('player');

        // ▫️ Dados base: países e épocas
        $countries = $this->api->fetchCountries();
        $seasons   = $this->api->fetchSeasons();

        // ▫️ Ligas disponíveis para o país escolhido
        $leagues = $country
            ? $this->api->fetchLeagues($country, $season)
            : [];

        /*
        |--------------------------------------------------------------------------
        | 🏟️ FIXTURES (JOGOS)
        |--------------------------------------------------------------------------
        | Filtramos apenas os jogos futuros (ou de hoje), ordenados por data.
        | Assim eliminamos os jogos antigos (ex.: Agosto, Setembro, etc.).
        */
        // -----------------------------------------------------------------
// FIXTURES (JOGOS) — chamada ao serviço com terceiro argumento (round)
// -----------------------------------------------------------------
        // -----------------------------------------------------------------
// FIXTURES (JOGOS) — chama fetchFixtures com terceiro argumento string
// -----------------------------------------------------------------
        $fixtures = [];
        if ($leagueId && $season) {
            // aceita ?round=... na query; se não vier, usa um round por defeito (string)
            // escolhi "Regular Season - 1" porque já o usaste antes — muda se precisares.
            $round = (string) $request->query('round', 'Regular Season - 1');

            try {
                // chama com 3 argumentos — o serviço espera (league, season, round:string)
                $allFixtures = $this->api->fetchFixtures($leagueId, $season, $round);

                \Log::info('estatisticas.index: fetchFixtures', [
                    'league' => $leagueId,
                    'season' => $season,
                    'round'  => $round,
                    'count'  => is_array($allFixtures) ? count($allFixtures) : 0,
                    'sample' => is_array($allFixtures) ? array_slice($allFixtures, 0, 3) : $allFixtures,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('estatisticas.index: fetchFixtures failed: '.$e->getMessage(), [
                    'league' => $leagueId, 'season' => $season, 'round' => $round
                ]);
                $allFixtures = [];
            }

            // timezone app evita problemas com UTC vs server
            $tz = config('app.timezone') ?? 'UTC';
            $today = Carbon::now($tz)->startOfDay();

            $fixtures = collect($allFixtures)
                ->filter(function ($f) use ($today, $tz) {
                    // tenta ler fixture.date ou date
                    $dateRaw = data_get($f, 'fixture.date') ?? data_get($f, 'date') ?? null;
                    if (!$dateRaw) return false;
                    try {
                        $dt = Carbon::parse($dateRaw)->setTimezone($tz);
                        return $dt->gte($today);
                    } catch (\Throwable $e) {
                        return false;
                    }
                })
                ->sortBy(function ($f) {
                    return data_get($f, 'fixture.date') ?? data_get($f, 'date') ?? null;
                })
                ->values()
                ->take(10) // mostra só os próximos 10 jogos (ajusta se quiseres mais)
                ->all();
        }
        /*
        |--------------------------------------------------------------------------
        | 📊 Estatísticas e Dados Detalhados
        |--------------------------------------------------------------------------
        */
        $matchStats = $fixtureId
            ? $this->api->fetchMatchStatistics((int) $fixtureId)
            : [];

        $teamStatsData = ($leagueId && $season && $teamId)
            ? $this->api->fetchTeamStatistics((int) $leagueId, $season, (int) $teamId)
            : [];

        $playersData = ($teamId && $season)
            ? $this->api->fetchPlayersByTeam((int) $teamId, $season)
            : [];

        /*
        |--------------------------------------------------------------------------
        | 👟 Estatísticas individuais do jogador
        |--------------------------------------------------------------------------
        */
        $playerStats = [];
        if ($playerId && $teamId && $leagueId) {
            $playerStats = $this->api->fetchPlayerStatistics(
                $season,
                (int) $playerId,
                (int) $teamId,
                (int) $leagueId
            );

            // Fallback: se não houver dados, busca perfil básico
            if (empty($playerStats)) {
                $basic = $this->api->fetchPlayerProfile((int) $playerId);

                if (!empty($basic)) {
                    $first = $basic[0]['player'] ?? null;
                    if ($first) {
                        $playerStats = [[
                            'player' => $first,
                            'statistics' => [],
                        ]];
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 🔁 Return da View
        |--------------------------------------------------------------------------
        */
        return view('estatisticas.layout.dashboard', compact(
            'countries',
            'seasons',
            'country',
            'leagues',
            'leagueId',
            'season',
            'fixtures',
            'fixtureId',
            'matchStats',
            'teamStatsData',
            'playersData',
            'playerStats',
            'teamId',
            'playerId'
        ));
    }


    // AJAX para carregar ligas de um país
    public function leaguesByCountry(Request $request)
    {
        $country = $request->query('country');
        $season = (int)$request->query('season', now()->year);

        if (!$country) {
            return response()->json(['leagues' => []]);
        }

        $leagues = $this->api->leaguesByCountryData($country, $season);

        return response()->json(['leagues' => $leagues]);
    }

    // AJAX para carregar jogadores de uma equipa
    // AJAX para carregar jogadores de uma equipa + estatísticas da equipa (fragment)
    public function playersByTeam(Request $request)
    {
        $teamId = $request->query('team');
        $season = (int)$request->query('season', now()->year);
        $leagueId = $request->query('league'); // passamos a liga também

        if (!$teamId) {
            return response()->json(['players' => [], 'team_stats' => null]);
        }

        $players = $this->api->fetchPlayersByTeam((int)$teamId, $season);

        // tenta também buscar estatísticas agregadas da equipa na época (se tivermos league)
        $teamStatsData = null;
        if ($leagueId) {
            // supondo que tens um método no serviço que recebe (leagueId, season, teamId)
            $teamStatsData = $this->api->fetchTeamStatistics((int)$leagueId, $season, (int)$teamId);
            // Normaliza os campos de fácil consumo no frontend (ex.: equipa, form, goals..)
            if (!empty($teamStatsData) && is_array($teamStatsData)) {
                // garantir shape simples: team.name, form, goals.for.average.total, goals.against...
                // se o teu serviço já devolve bem, podes simplesmente passar $teamStatsData
            } else {
                $teamStatsData = null;
            }
        }

        return response()->json([
            'players' => $players,
            'team_stats' => $teamStatsData
        ]);
    }

  public function syncTeam(Request $request)
{
    $data = $request->validate([
        'team'   => 'required|integer',
        'league' => 'nullable|integer',
        'season' => 'nullable|integer',
    ]);

    $teamId   = (int) $data['team'];
    $leagueId = isset($data['league']) ? (int) $data['league'] : null;
    $season   = isset($data['season']) ? (int) $data['season'] : now()->year;

    try {
        $teamStatsRaw = $this->api->fetchTeamStatistics((int) $leagueId, $season, (int) $teamId);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Erro ao obter estatísticas da API: ' . $e->getMessage()
        ], 500);
    }

    if (empty($teamStatsRaw)) {
        return response()->json([
            'ok' => false,
            'message' => 'Sem estatísticas devolvidas pela API.'
        ], 404);
    }

    $form           = data_get($teamStatsRaw, 'form', '');
    $goalsForAvg    = data_get($teamStatsRaw, 'goals.for.average.total', null);
    $goalsAgainstAvg= data_get($teamStatsRaw, 'goals.against.average.total', null);
    $fixturesPlayed = data_get($teamStatsRaw, 'fixtures.played.total', null);

    $leagueCountry = data_get($teamStatsRaw, 'league.country', null);

    // ✅ NOVO: nomes (faltava isto)
    $leagueName = data_get($teamStatsRaw, 'league.name', null);
    $teamName   = data_get($teamStatsRaw, 'team.name', null);

    $record = TeamStatistics::updateOrCreate(
        ['team_id' => $teamId, 'league_id' => $leagueId, 'season' => $season],
        [
            'league_country'   => $leagueCountry,
            'league_name'      => $leagueName,
            'team_name'        => $teamName,

            'data'             => $teamStatsRaw,
            'form'             => $form,
            'goals_for_avg'     => $goalsForAvg,
            'goals_against_avg' => $goalsAgainstAvg,
            'fixtures_played'   => $fixturesPlayed,
            'synced_at'         => now(),
        ]
    );

    return response()->json([
        'ok' => true,
        'message' => 'Estatísticas sincronizadas',
        'saved' => [
            'id'             => $record->id,
            'team_id'        => $record->team_id,
            'league_id'      => $record->league_id,
            'season'         => $record->season,
            'league_country' => $record->league_country,
            'league_name'    => $record->league_name,
            'team_name'      => $record->team_name,
            'synced_at'      => $record->synced_at ? $record->synced_at->toDateTimeString() : null,
        ]
    ]);
}


}
