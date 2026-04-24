<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route as RouteFacade;

class CoachController extends Controller
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://v3.football.api-sports.io';
        $this->apiKey = config('services.football.key')
            ?? config('services.football.rapidapi_key')
            ?? env('FOOTBALL_API_KEY')
            ?? env('API_FOOTBALL_KEY')
            ?? env('X_RAPIDAPI_KEY');
    }

    /**
     * Lista treinadores (view).
     * Suporta querystring: ?team=85&search=Smith&id=123&country=Portugal&league=39&season=2024
     */
    // dentro do teu CoachController

    public function coachsIndex(Request $request)
    {
        $team    = $request->query('team');
        $search  = $request->query('search') ?? $request->query('coach');
        $id      = $request->query('id');
        $country = $request->query('country');
        $league  = $request->query('league_id');

        $season  = (int)($request->query('season') ?: date('Y'));

        $apiError = null;
        $coachs = [];

        try {
            $cacheKey = 'coachs_index_' . md5("team:{$team}|search:{$search}|id:{$id}|country:{$country}|league:{$league}|season:{$season}");
            $coachs = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($team, $search, $id, $country, $league, $season) {

                // 1️⃣ TEAM específico → apenas treinadores desse clube
                if (!empty($team)) {
                    $coachs = $this->fetchCoachsFromApi((int)$team, $search, $id);
                    return $this->filterCoachsBySeason($coachs, $season);
                }

                // 2️⃣ LEAGUE + SEASON → obter equipas dessa época e agregar treinadores
                if (!empty($league)) {
                    $teams = $this->fetchTeamsFromApi((int)$league, (int)$season);
                    $teamIds = array_values(array_filter(array_map(function ($t) {
                        return data_get($t, 'team.id') ?? data_get($t, 'id') ?? null;
                    }, $teams)));

                    if (empty($teamIds)) {
                        Log::warning("coachsIndex: league {$league} returned no teams for season {$season}, fallback to generic fetch");
                        $coachs = $this->fetchCoachsFromApi(null, $search, $id);
                        return $this->filterCoachsBySeason($coachs, $season);
                    }

                    $coachsAll = [];
                    foreach ($teamIds as $tid) {
                        try {
                            $c = $this->fetchCoachsFromApi((int)$tid, $search, null);
                        } catch (\Throwable $e) {
                            Log::warning("fetchCoachsFromApi for team {$tid} failed: " . $e->getMessage());
                            $c = [];
                        }
                        $coachsAll = array_merge($coachsAll, $c);
                    }

                    // 🔹 Eliminar duplicados
                    $unique = [];
                    foreach ($coachsAll as $c) {
                        $cid = $c['id'] ?? null;
                        if (!$cid) continue;
                        if (!isset($unique[$cid])) $unique[$cid] = $c;
                    }

                    // ✅ Aplicar filtro de época (só treinadores ativos na season)
                    $filtered = $this->filterCoachsBySeason(array_values($unique), $season);
                    return array_values($filtered);
                }

                // 3️⃣ Caso geral → fetch sem team
                $all = $this->fetchCoachsFromApi(null, $search, $id);

                // 4️⃣ Filtrar por país (opcional)
                if (!empty($country)) {
                    $countryNorm = mb_strtolower($country);
                    $all = array_values(array_filter($all, function ($c) use ($countryNorm) {
                        return !empty($c['nationality']) && mb_strtolower($c['nationality']) === $countryNorm;
                    }));
                }

                // ✅ Também aplicar filtro de season aqui (em caso geral)
                return $this->filterCoachsBySeason($all, $season);
            });
        } catch (\Throwable $e) {
            Log::error('coachsIndex: fetch failed', ['error' => $e->getMessage()]);
            $apiError = 'Erro ao carregar treinadores: ' . $e->getMessage();
            $coachs = [];
        }

        try {
            $countries = $this->fetchCountriesFromApi();
        } catch (\Throwable $e) {
            Log::warning('fetchCountriesFromApi failed: ' . $e->getMessage());
            $countries = [];
        }

        $leaguesByCountryRoute = \Illuminate\Support\Facades\Route::has('players.leagues_by_country')
            ? route('players.leagues_by_country')
            : '#';

        return view('coachs.layout.dashboard', [
            'coachs' => $coachs,
            'apiError' => $apiError,
            'team' => $team,
            'search' => $search,
            'id' => $id,
            'league' => $league,
            'countries' => $countries,
            'season' => $season,
            'leaguesByCountryRoute' => $leaguesByCountryRoute,
        ]);
    }


   public function index(Request $request)
{
    // dropdowns leves (caching)
    $countries = Cache::remember('coachsManual:countries', 21600, function () {
        return DB::table('countries')
            ->select('id', 'name', 'flag', 'norm_name')
            ->orderBy('name')
            ->get();
    });

    $years = Cache::remember('coachsManual:years', 21600, function () {
        return DB::table('coachs')
            ->selectRaw('YEAR(COALESCE(created_at, updated_at)) as y')
            ->whereRaw('COALESCE(created_at, updated_at) IS NOT NULL')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y');
    });

    // não carregamos leagues/teams aqui (vem por AJAX)
    $leagues = collect();
    $teams   = collect();

    // ✅ ALTERAÇÃO: passa filtros actuais para a view (para preencher o form ao voltar)
    // (isto não filtra nada no backend; só disponibiliza os valores)
    $filters = $request->only([
        'country_id', 'league_id', 'team', 'name', 'age_min', 'age_max', 'year',
        'sort', 'direction', 'page',
    ]);

    return view('coachsManual.layout.dashboard', compact(
        'countries', 'years', 'leagues', 'teams', 'filters'
    ));
}

/**
 * Endpoint AJAX para a tabela
 */
public function data(Request $request)
{
    $countryId = $request->filled('country_id') ? (int) $request->country_id : null;
    $leagueId  = $request->filled('league_id') ? (string) $request->league_id : null; // external_id
    $teamName  = $request->filled('team')      ? (string) $request->team      : null;

    $name   = $request->filled('name')    ? trim((string) $request->name) : null;
    $ageMin = $request->filled('age_min') ? (int) $request->age_min : null;
    $ageMax = $request->filled('age_max') ? (int) $request->age_max : null;
    $year   = $request->filled('year')    ? (int) $request->year : null;

    // ✅ ALTERAÇÃO: normaliza inputs textuais para evitar “filtros fantasma”
    if ($teamName !== null) $teamName = trim($teamName);
    if ($teamName === '') $teamName = null;

    // ✅ ALTERAÇÃO: protege paginação (evita page 0 / negativos / lixo)
    $perPage = 15;
    $page = (int) $request->get('page', 1);
    $page = $page > 0 ? $page : 1;

    $query = DB::table('coachs as c')
        ->leftJoin('teams as t', 't.id', '=', 'c.team_id')
        ->leftJoin('league_teams as lt', 'lt.team_id', '=', 't.id')
        ->leftJoin('leagues as l', 'l.external_id', '=', 'lt.league_external_id')
        ->leftJoin('countries as ctry', 'ctry.norm_name', '=', DB::raw('LOWER(TRIM(l.country))'))
        ->select([
            'c.id','c.external_id','c.name','c.photo','c.age','c.nationality','c.birth_date','c.created_at','c.updated_at',
            DB::raw('COALESCE(t.name, "—") as team_name'),
            't.logo as team_logo',
            DB::raw('COALESCE(l.name, "—") as league_name'),
            'l.logo as league_logo',
            DB::raw('COALESCE(l.country, "—") as league_country'),
            'ctry.flag as league_country_flag',
        ]);

    /**
     * ✅ ALTERAÇÃO: countryNorm com cache (evita query extra por request)
     * - Mantém o comportamento, só reduz custo.
     */
    if ($countryId) {
        $countryNormMap = Cache::remember('coachsManual:countries_norm_map', 21600, function () {
            return DB::table('countries')->pluck('norm_name', 'id')->toArray();
        });

        $countryNorm = $countryNormMap[$countryId] ?? null;
        if ($countryNorm) {
            $countryNorm = strtolower(trim((string) $countryNorm));
            $query->whereRaw('LOWER(TRIM(l.country)) = ?', [$countryNorm]);
        }
    }

    if ($leagueId) {
        $query->where('lt.league_external_id', $leagueId);
    }

    if ($teamName) {
        $query->whereRaw('LOWER(TRIM(t.name)) = LOWER(TRIM(?))', [$teamName]);
    }

    if ($name) $query->where('c.name', 'like', "%{$name}%");
    if ($ageMin !== null) $query->where('c.age', '>=', $ageMin);
    if ($ageMax !== null) $query->where('c.age', '<=', $ageMax);

    if ($year) {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();
        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('c.created_at', [$start, $end])
              ->orWhereBetween('c.updated_at', [$start, $end]);
        });
    }

    // total
    $total = (clone $query)->distinct('c.id')->count('c.id');

    // order
    $sort = (string) $request->get('sort', 'name');
    $dir  = strtolower((string) $request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

    $allowed = ['name','age','team_name','league_name','created_at'];
    if (!in_array($sort, $allowed, true)) $sort = 'name';

    switch ($sort) {
        case 'age':
            $query->orderBy('c.age', $dir);
            break;
        case 'team_name':
            $query->orderBy('t.name', $dir);
            break;
        case 'league_name':
            $query->orderBy('l.name', $dir);
            break;
        case 'created_at':
            $query->orderBy('c.created_at', $dir)->orderBy('c.updated_at', $dir);
            break;
        default:
            $query->orderBy('c.name', $dir);
            break;
    }

    $query->orderBy('c.id', 'desc');

    // paginação
    $rows = (clone $query)
        ->distinct('c.id')
        ->forPage($page, $perPage)
        ->get();

    $totalPages = (int) ceil($total / max(1, $perPage));

    return response()->json([
        'data' => $rows,
        'meta' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => $totalPages,
            'next'         => $page < $totalPages ? $request->fullUrlWithQuery(['page' => $page + 1]) : null,
            'prev'         => $page > 1 ? $request->fullUrlWithQuery(['page' => $page - 1]) : null,
        ],
    ]);
}

public function leagues(Request $request)
{
    $countryId = $request->filled('country_id') ? (int)$request->country_id : null;

    $cacheKey = 'coachsManual:leagues:' . ($countryId ?: 'all');

    $leagues = Cache::remember($cacheKey, 21600, function () use ($countryId) {
        $q = DB::table('leagues')
            ->select('external_id', 'name', 'country', 'logo')
            ->orderBy('country')->orderBy('name');

        if ($countryId) {
            $countryNorm = DB::table('countries')->where('id', $countryId)->value('norm_name');
            if ($countryNorm) {
                $q->whereRaw('LOWER(TRIM(country)) = ?', [$countryNorm]);
            }
        }

        return $q->get();
    });

    return response()->json($leagues);
}



/**
 * Dropdown: ligas por país (AJAX)
 */
public function leaguesByCountry(Request $request)
{
    $countryId = $request->filled('country_id') ? (int)$request->country_id : null;

    $q = DB::table('leagues')
        ->select('external_id', 'name', 'country', 'logo')
        ->whereNotNull('external_id')
        ->orderBy('country')->orderBy('name');

    if ($countryId) {
        $countryNorm = DB::table('countries')->where('id', $countryId)->value('norm_name');
        if ($countryNorm) {
            $q->whereRaw('LOWER(TRIM(country)) = ?', [$countryNorm]);
        }
    }

    return response()->json($q->get());
}

/**
 * Dropdown: equipas por liga (AJAX)
 */
public function teamsByLeague(Request $request)
{
    $leagueId = $request->filled('league_id') ? (string)$request->league_id : null;
    if (!$leagueId) return response()->json([]);

    $teams = DB::table('league_teams as lt')
        ->join('teams as t', 't.id', '=', 'lt.team_id')
        ->where('lt.league_external_id', $leagueId)
        ->select('t.id','t.name','t.logo')
        ->distinct()
        ->orderBy('t.name')
        ->get();

    return response()->json($teams);
}


    public function show($id)
{
    // Subquery leagues únicas
    $leaguesUniq = DB::table('leagues')
        ->selectRaw('external_id, MIN(name) as name, MIN(country) as country, MIN(logo) as logo')
        ->whereNotNull('external_id')
        ->groupBy('external_id');

    // Subquery countries únicas
    $countriesUniq = DB::table('countries')
        ->selectRaw('LOWER(TRIM(name)) as norm_name, MIN(id) as id, MIN(name) as name, MIN(flag) as flag')
        ->groupByRaw('LOWER(TRIM(name))');

    $coach = DB::table('coachs as c')
        ->select(
            'c.id',
            'c.external_id',
            'c.name',
            'c.photo',
            'c.age',
            'c.nationality',
            'c.birth_date',
            'c.created_at',
            'c.updated_at',
            'c.team_id',
            DB::raw('COALESCE(t.name, "—") as team_name'),
            't.logo as team_logo',
            DB::raw('COALESCE(l.name, "—") as league_name'),
            'l.logo as league_logo',
            DB::raw('COALESCE(ctry.name, "—") as league_country'),
            'ctry.flag as country_flag'
        )
        ->leftJoin('teams as t', 't.id', '=', 'c.team_id')
        ->leftJoinSub($leaguesUniq, 'l', function ($join) {
            $join->on('l.external_id', '=', 't.league_id');
        })
        ->leftJoinSub($countriesUniq, 'ctry', function ($join) {
            $join->on(DB::raw('LOWER(TRIM(l.country))'), '=', 'ctry.norm_name');
        })
        ->where('c.id', (int)$id)
        ->first();

    abort_unless($coach, 404);

    return view('coachsManual.show', compact('coach'));
}

    /**
     * Filtra treinadores para devolver apenas os ativos na época pedida.
     * Usa o campo career.start / career.end.
     */
    private function filterCoachsBySeason(array $coachs, int $season): array
    {
        $seasonStart = "{$season}-07-01"; // início da época
        $seasonEnd   = ($season + 1) . "-06-30"; // fim da época seguinte

        return array_values(array_filter($coachs, function ($c) use ($seasonStart, $seasonEnd) {
            $career = $c['career'] ?? [];
            foreach ($career as $job) {
                $start = $job['start'] ?? null;
                $end   = $job['end'] ?? null;
                // ativo se começou antes do fim da época e terminou depois do início
                if ($start && $start <= $seasonEnd && (empty($end) || $end >= $seasonStart)) {
                    return true;
                }
            }
            return false;
        }));
    }



    /**
     * Lista (whitelist) de termos/países permitidos.
     */
    public function allowedCountriesTerms(): array
    {
        return [
            'Netherlands', 'Germany', 'Denmark', 'Italy', 'Mexico', 'Switzerland', 'Romania', 'Cyprus', 'Morocco',
            'Azerbaijan', 'Armenia', 'Colombia', 'Czech Republic', 'Russia', 'England', 'Brazil',
            'United Arab Emirates', 'USA', 'United States', 'Poland', 'Scotland', 'Greece', 'Turkey',
            'Norway', 'Hungary', 'Bulgaria', 'Croatia', 'Spain', 'France', 'Belgium', 'Portugal'
        ];
    }

    /**
     * Faz fetch paginado do endpoint /coachs
     * Retorna array de coach objects (normalizados).
     */
    /**
     * Faz fetch paginado do endpoint /coachs
     * Parâmetros suportados: team (int), search (string), id (int), season (int)
     * Retorna array de coach objects (normalizados).
     */
    private function fetchCoachsFromApi(?int $team = null, ?string $search = null, ?int $id = null): array
{
    $all = [];

    if (empty($this->apiKey)) {
        Log::warning('fetchCoachsFromApi: missing API key');
        return [];
    }

    $headers = $this->makeHeaders();

    $query = array_filter([
        'team'   => $team ?: null,
        'search' => $search ?: null,
        'id'     => $id ?: null,
    ], fn($v) => $v !== null && $v !== '');

    $endpoint = rtrim($this->baseUrl, '/') . '/coachs';
    $start = microtime(true);

    // Logs iniciais (contexto)
    Log::debug('fetchCoachsFromApi:start', [
        'endpoint' => $endpoint,
        'query'    => $query,
        'api_key_present' => !empty($this->apiKey),
        'api_key_prefix'  => substr((string)$this->apiKey, 0, 6) . '***',
        'headers_keys'    => array_keys($headers), // só as chaves
    ]);

    // IMPORTANT: logar URL final (com querystring)
    $fullUrl = $endpoint . (empty($query) ? '' : ('?' . http_build_query($query)));
    Log::debug('fetchCoachsFromApi:request', ['url' => $fullUrl]);

    $res = Http::withHeaders($headers)
        ->acceptJson()
        ->timeout(30)
        ->get($endpoint, $query);

    $durationMs = (int) round((microtime(true) - $start) * 1000);

    // Headers relevantes (rate-limit / request-id)
    $respHeaders = [
        'x-ratelimit-requests-limit'     => $res->header('x-ratelimit-requests-limit'),
        'x-ratelimit-requests-remaining' => $res->header('x-ratelimit-requests-remaining'),
        'x-ratelimit-requests-reset'     => $res->header('x-ratelimit-requests-reset'),
        'x-ratelimit-seconds-limit'      => $res->header('x-ratelimit-seconds-limit'),
        'x-ratelimit-seconds-remaining'  => $res->header('x-ratelimit-seconds-remaining'),
        'x-ratelimit-seconds-reset'      => $res->header('x-ratelimit-seconds-reset'),
        'x-request-id'                   => $res->header('x-request-id'),
    ];

    Log::debug('fetchCoachsFromApi:response_meta', [
        'status'      => $res->status(),
        'ok'          => $res->successful(),
        'duration_ms' => $durationMs,
        'resp_headers'=> array_filter($respHeaders, fn($v) => $v !== null && $v !== ''),
        'body_head'   => substr($res->body(), 0, 400), // só o início
    ]);

    if (!$res->successful()) {
        Log::error('fetchCoachsFromApi:failed', [
            'status' => $res->status(),
            'url'    => $fullUrl,
            'body'   => substr($res->body(), 0, 1200),
        ]);
        throw new \RuntimeException("HTTP {$res->status()} - " . substr($res->body(), 0, 800));
    }

    $json = $res->json();

    // Estrutura do payload (para apanhar “estou a olhar para a chave errada”)
    Log::debug('fetchCoachsFromApi:json_shape', [
        'top_keys' => is_array($json) ? array_keys($json) : gettype($json),
        'errors'   => data_get($json, 'errors', null),
        'results'  => data_get($json, 'results', null),
        'paging'   => data_get($json, 'paging', null),
    ]);

    $items = $json['response'] ?? [];
    Log::debug('fetchCoachsFromApi:page_items', [
        'count' => is_array($items) ? count($items) : 0,
        'first_item_keys' => (is_array($items) && isset($items[0]) && is_array($items[0])) ? array_keys($items[0]) : null,
        'first_item_sample' => (is_array($items) && isset($items[0])) ? array_slice((array)$items[0], 0, 8, true) : null,
    ]);

    $all = array_merge($all, is_array($items) ? $items : []);

    $current = (int) data_get($json, 'paging.current', 1);
    $total   = (int) data_get($json, 'paging.total', 1);
    $page    = max(2, $current + 1);

    // Proteção contra loop “current não muda”
    $lastCurrent = $current;

    Log::debug('fetchCoachsFromApi:paging_init', [
        'current' => $current,
        'total'   => $total,
        'next_page' => $page,
    ]);

    while ($current < $total && $page <= 200) {
        $query['page'] = $page;
        $pageUrl = $endpoint . '?' . http_build_query($query);

        $t0 = microtime(true);
        $res = Http::withHeaders($headers)
            ->acceptJson()
            ->timeout(30)
            ->get($endpoint, $query);

        $ms = (int) round((microtime(true) - $t0) * 1000);

        Log::debug('fetchCoachsFromApi:paging_http', [
            'page' => $page,
            'url'  => $pageUrl,
            'status' => $res->status(),
            'duration_ms' => $ms,
            'rate_remaining' => $res->header('x-ratelimit-requests-remaining') ?? $res->header('x-ratelimit-seconds-remaining'),
        ]);

        if (!$res->successful()) {
            Log::warning('fetchCoachsFromApi:paging_failed', [
                'page'   => $page,
                'status' => $res->status(),
                'body'   => substr($res->body(), 0, 800),
            ]);
            break;
        }

        $json  = $res->json();
        $items = $json['response'] ?? [];
        $all   = array_merge($all, is_array($items) ? $items : []);

        $current = (int) data_get($json, 'paging.current', $current);
        $total   = (int) data_get($json, 'paging.total', $total);

        Log::debug('fetchCoachsFromApi:paging_progress', [
            'page'    => $page,
            'current' => $current,
            'total'   => $total,
            'items_count' => is_array($items) ? count($items) : 0,
        ]);

        // Se o "current" não mexe, estás num loop (API ou parsing)
        if ($current === $lastCurrent && $total > 1) {
            Log::warning('fetchCoachsFromApi:paging_stuck', [
                'page' => $page,
                'current' => $current,
                'total' => $total,
                'note' => 'paging.current não está a avançar; a sair para evitar loop',
            ]);
            break;
        }

        $lastCurrent = $current;
        $page++;
    }

    Log::debug('fetchCoachsFromApi:done', [
        'total_items' => count($all),
    ]);

    return array_map(fn($it) => $it['coach'] ?? $it, $all);
}

    /**
     * Fetch teams for a league (used when user selects league -> aggregate coaches).
     */
    private function fetchTeamsFromApi(int $leagueId, int $season = 0): array
    {
        if (empty($this->apiKey)) return [];

        $headers = $this->makeHeaders();
        $query = array_filter([
            'league' => $leagueId,
            'season' => $season ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/teams', $query);
        if (!$res->successful()) {
            Log::warning('fetchTeamsFromApi failed', ['status' => $res->status()]);
            return [];
        }

        $json = $res->json();
        return $json['response'] ?? [];
    }

    /**
     * Fetch countries (para popular o select) — agora filtrado pela whitelist allowedCountriesTerms().
     * Retorna array de ['name' => ..., 'code' => ...]
     */
    private function fetchCountriesFromApi(): array
    {
        if (empty($this->apiKey)) return [];

        // cache para não sobrecarregar a API
        return Cache::remember('coachs:allowed_countries', 60 * 60 * 24, function () {
            $headers = $this->makeHeaders();
            $res = Http::withHeaders($headers)->acceptJson()->get($this->baseUrl . '/countries');

            if (!$res->successful()) {
                Log::warning('fetchCountriesFromApi failed', ['status' => $res->status()]);
                return [];
            }

            $json = $res->json();
            $items = $json['response'] ?? $json;

            // construir lista de termos permitidos em lowercase
            $allowedTerms = array_map('mb_strtolower', $this->allowedCountriesTerms());

            // filtrar por name ou code (substring match)
            $filtered = array_filter($items, function ($c) use ($allowedTerms) {
                $name = mb_strtolower(data_get($c, 'name', ''));
                $code = mb_strtolower(data_get($c, 'code', ''));
                foreach ($allowedTerms as $term) {
                    if ($term === '') continue;
                    if (strpos($name, $term) !== false) return true;
                    if ($code && strpos($code, $term) !== false) return true;
                }
                return false;
            });

            // normalizar estrutura (name + code)
            $out = array_values(array_map(function ($it) {
                return [
                    'name' => data_get($it, 'name'),
                    'code' => data_get($it, 'code'),
                ];
            }, $filtered));

            return $out;
        });
    }

    /**
     * Constrói headers apropriados para o provider (x-apisports-key ou rapidapi).
     */
    private function makeHeaders(): array
    {
        $headers = ['x-apisports-key' => $this->apiKey];
        if (str_starts_with((string)$this->apiKey, 'x-') || config('services.football.rapidapi_key')) {
            $rapidKey = config('services.football.rapidapi_key') ?? $this->apiKey;
            $headers = [
                'x-rapidapi-key' => $rapidKey,
                'x-rapidapi-host' => 'v3.football.api-sports.io',
            ];
        }
        return $headers;
    }

    /**
     * Sincroniza coaches na BD (updateOrCreate por external id).
     */
    public function syncCoachs(Request $request)
    {
        $data = $request->validate([
            'team' => 'nullable|integer',
            'search' => 'nullable|string',
            'coach' => 'nullable|string',
            'league' => 'nullable|integer',
            'season' => 'nullable|integer',
        ]);

        $team   = $data['team'] ?? null;
        $search = $data['search'] ?? $data['coach'] ?? null;
        $league = $data['league'] ?? null;
        $season = $data['season'] ?? null;

        try {
            $items = [];

            if (!empty($team)) {
                $items = $this->fetchCoachsFromApi((int)$team, $search, null);
            } elseif (!empty($league)) {
                $teams = $this->fetchTeamsFromApi((int)$league, (int)($season ?: date('Y')));
                $teamIds = collect($teams)
                    ->map(fn($t) => data_get($t, 'team.id') ?? data_get($t, 'id'))
                    ->filter()
                    ->unique()
                    ->values();

                $all = [];
                foreach ($teamIds as $tid) {
                    $coaches = $this->fetchCoachsFromApi((int)$tid, $search, null);
                    $all = array_merge($all, $coaches);
                }
                $items = collect($all)->unique('id')->values()->all();
            } else {
                $items = $this->fetchCoachsFromApi(null, $search, null);
            }

            $fetched = 0;

            foreach ($items as $c) {
                $coachId = data_get($c, 'id');
                if (!$coachId) continue;

                // 🔹 Obter o external_id do clube na API
                $teamExternalId = data_get($c, 'team.id');

                // 🔹 Converter para o ID local da tabela teams
                $localTeamId = null;
                if ($teamExternalId) {
                    $localTeamId = DB::table('teams')
                        ->where('external_id', $teamExternalId)
                        ->value('id');
                }

                // 🔹 Criar ou atualizar o treinador
                Coach::updateOrCreate(
                    ['external_id' => $coachId],
                    [
                        'team_id'     => $localTeamId,
                        'name'        => data_get($c, 'name'),
                        'nationality' => data_get($c, 'nationality'),
                        'age'         => data_get($c, 'age'),
                        'birth_date'  => data_get($c, 'birth.date')
                            ? date('Y-m-d', strtotime(data_get($c, 'birth.date')))
                            : null,
                        'photo'       => data_get($c, 'photo')
                            ?? "https://media.api-sports.io/football/coachs/{$coachId}.png",
                        'meta'        => json_encode($c, JSON_UNESCAPED_UNICODE),
                        'updated_at'  => now(),
                    ]
                );

                $fetched++;
            }

            return back()->with('success', "Sincronização completa — {$fetched} treinadores actualizados.");
        } catch (\Throwable $e) {
            \Log::error('syncCoachs error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro ao sincronizar treinadores: ' . $e->getMessage());
        }
    }
}
