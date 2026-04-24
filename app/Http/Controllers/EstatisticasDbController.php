<?php

namespace App\Http\Controllers;

use App\Models\TeamStatistics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EstatisticasDbController extends Controller
{
    public function index(Request $request)
    {
        // Épocas (BD)
        $seasons = TeamStatistics::query()
            ->whereNotNull('season')
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season');

        $defaultSeason = (int) ($seasons->first() ?? now()->year);

        // Filtros (UI envia external ids)
        $country  = $request->query('country');           // string
        $leagueId = $request->query('league');            // leagues.external_id
        $teamId   = $request->query('team');              // teams.external_id
        $season   = (int) $request->query('season', $defaultSeason);

        $leagueExternal = is_numeric($leagueId) ? (int) $leagueId : null;
        $teamExternal   = is_numeric($teamId)   ? (int) $teamId   : null;

        // external -> internal (para bater em team_statistics)
        $leagueInternal = null;
        if ($leagueExternal) {
            $leagueInternal = Cache::remember("db:league:int:{$leagueExternal}", 60 * 60, function () use ($leagueExternal) {
                return DB::table('leagues')->where('external_id', $leagueExternal)->value('id');
            });
        }

        $teamInternal = null;
        if ($teamExternal) {
            $teamInternal = Cache::remember("db:team:int:{$teamExternal}", 60 * 60, function () use ($teamExternal) {
                return DB::table('teams')->where('external_id', $teamExternal)->value('id');
            });
        }

        /**
         * Dropdown countries:
         * ✅ só países que existem em team_statistics na época escolhida
         */
        $countries = Cache::remember("db:stats:countries:{$season}", 60 * 30, function () use ($season) {
            return DB::table('team_statistics as ts')
                ->where('ts.season', $season)
                ->whereNotNull('ts.league_country')
                ->where('ts.league_country', '<>', '')
                ->distinct()
                ->orderBy('ts.league_country')
                ->pluck('ts.league_country');
        });

        // Dropdowns via AJAX
        $leagues = [];
        $teams   = collect();

        /**
         * Query stats (team_statistics guarda IDs INTERNOS)
         * ✅ usa campos denormalizados para nomes/logos/país
         */
        $statsQ = TeamStatistics::query()
            ->from('team_statistics as ts')
            // joins só para devolver external_id na grid (podes remover se não precisares)
            ->leftJoin('leagues as l', 'l.id', '=', 'ts.league_id')
            ->leftJoin('teams as t', 't.id', '=', 'ts.team_id')
            ->where('ts.season', $season)
            ->whereNotNull('ts.league_id')
            ->whereNotNull('ts.team_id');

        // ✅ País: filtra por coluna indexável ts.league_country (source of truth)
        if (!empty($country)) {
            $statsQ->where('ts.league_country', $country);
        }

        // ✅ Liga (external -> internal)
        if ($leagueInternal) {
            $statsQ->where('ts.league_id', (int) $leagueInternal);
        }

        // ✅ Equipa (external -> internal) + coerência com pivot se houver liga
        if ($teamInternal) {
            $statsQ->where('ts.team_id', (int) $teamInternal);

            if ($leagueExternal) {
                $statsQ->whereExists(function ($q) use ($leagueExternal, $season, $teamInternal) {
                    $q->select(DB::raw(1))
                        ->from('league_teams as lt')
                        ->where('lt.league_external_id', (int) $leagueExternal)
                        ->where('lt.season', (int) $season)
                        ->where('lt.team_id', (int) $teamInternal);
                });
            }
        }

        $stats = $statsQ
            ->orderByDesc('ts.synced_at')
            ->orderByDesc('ts.id')
            ->select([
                'ts.*',

                // IDs externos (para a UI)
                DB::raw('l.external_id as league_id_external'),
                DB::raw('t.external_id as team_id_external'),

                // ✅ nomes/logos vêm do ts (mais barato e consistente)
                DB::raw('COALESCE(ts.league_name, l.name) as league_name'),
                DB::raw('COALESCE(ts.league_logo, l.logo) as league_logo'),
                DB::raw('COALESCE(ts.league_country, l.country) as league_country'),

                DB::raw('COALESCE(ts.team_name, t.name) as team_name'),
                DB::raw('COALESCE(ts.team_logo, t.logo) as team_logo'),
            ])
            ->simplePaginate(30)
            ->withQueryString();

        return view('estatisticas_db.layout.dashboard', compact(
            'countries',
            'seasons',
            'country',
            'leagues',
            'leagueId',
            'teams',
            'teamId',
            'season',
            'stats'
        ));
    }

    /**
     * AJAX: ligas por país + época
     * ✅ Source of truth: team_statistics (só ligas que têm stats na época/país)
     * Devolve external_id para o UI.
     */
    public function leaguesByCountry(Request $request)
    {
        $country = $request->query('country');
        $season  = (int) $request->query('season', now()->year);

        if (!$country) return response()->json(['leagues' => []]);

        $ttlSeconds = 60 * 30;
        $cacheKey = "db:stats:leagues:{$country}:{$season}";

        $leagues = Cache::remember($cacheKey, $ttlSeconds, function () use ($country, $season) {
            // Nota: team_statistics guarda league_id interno; precisamos do external_id via join.
            return DB::table('team_statistics as ts')
                ->join('leagues as l', 'l.id', '=', 'ts.league_id')
                ->where('ts.season', $season)
                ->where('ts.league_country', $country)
                ->whereNotNull('l.external_id')
                ->select([
                    DB::raw('l.external_id as id'),
                    DB::raw('COALESCE(ts.league_name, l.name) as name'),
                    DB::raw('COALESCE(ts.league_logo, l.logo) as logo'),
                ])
                ->distinct()
                ->orderBy('name')
                ->get()
                ->map(fn ($x) => [
                    'id'   => (int) $x->id,
                    'name' => $x->name,
                    'logo' => $x->logo,
                ])
                ->values()
                ->all();
        });

        return response()->json(['leagues' => $leagues]);
    }

    /**
     * AJAX: equipas por liga + época (+ country opcional)
     * ✅ Source of truth: team_statistics (só equipas que têm stats nessa liga/época/país)
     * Isto evita “equipas fantasma” vindas do pivot sem stats.
     */
    public function teamsByLeague(Request $request)
    {
        $leagueExternalId = $request->query('league');
        $season           = (int) $request->query('season', now()->year);
        $country          = $request->query('country');

        if (!is_numeric($leagueExternalId)) {
            return response()->json(['teams' => []]);
        }

        $leagueExternalId = (int) $leagueExternalId;

        $ttlSeconds = 60 * 30;
        $cacheKey = "db:stats:teams:{$leagueExternalId}:{$season}:".md5((string)$country);

        $teams = Cache::remember($cacheKey, $ttlSeconds, function () use ($leagueExternalId, $season, $country) {

            $q = DB::table('team_statistics as ts')
                ->join('leagues as l', 'l.id', '=', 'ts.league_id')
                ->join('teams as t', 't.id', '=', 'ts.team_id')
                ->where('ts.season', $season)
                ->where('l.external_id', $leagueExternalId)
                ->whereNotNull('t.external_id');

            if (!empty($country)) {
                $q->where('ts.league_country', $country);
            }

            return $q->select([
                    DB::raw('t.external_id as id'),
                    DB::raw('COALESCE(ts.team_name, t.name) as name'),
                    DB::raw('COALESCE(ts.team_logo, t.logo) as logo'),
                ])
                ->distinct()
                ->orderBy('name')
                ->get()
                ->map(fn ($x) => [
                    'id'   => (int) $x->id,
                    'name' => $x->name,
                    'logo' => $x->logo,
                ])
                ->values()
                ->all();
        });

        return response()->json(['teams' => $teams]);
    }
}
