<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Services\FootballApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\Uri\Http;


class DashboardController extends Controller
{
    protected FootballApiService $api;

    public function __construct(FootballApiService $api)
    {
        $this->api = $api;
    }

    /**
     * View principal: /scouttalent/dashboard
     * Apenas carrega os países + época atual.
     * As ligas e standings são buscados depois via AJAX.
     */
    public function index(Request $request)
    {
        try {
            $countries = collect($this->api->fetchCountries())
                ->map(fn($c) => [
                    'name' => data_get($c, 'name'),
                    'code' => data_get($c, 'code'),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Erro a carregar countries: ' . $e->getMessage());
            $countries = [];
        }

        $currentYear = now()->year;
        $years = range($currentYear, $currentYear - 5);
        $selectedSeason = $currentYear;
        // buscar última sincronização da sessão
        $lastSync = session('last_sync', '—');

        return view('viewprincipal.layout.dashboard', [
            'countries' => $countries,
            'season' => $currentYear,
            'years' => $years,
            'selectedSeason' => $selectedSeason,
            'lastSync' => $lastSync,
        ]);
    }


    /**
     * Endpoint AJAX: devolve ligas por país/época
     */
    public function leaguesByCountry(Request $request)
    {
        $country = trim($request->query('country', ''));
        $season = $request->query('season', null);

        if (empty($country)) {
            return response()->json(['leagues' => []]);
        }

        try {
            $leagues = $this->api->leaguesByCountryData($country, $season);
            return response()->json(['leagues' => $leagues]);
        } catch (\Throwable $e) {
            Log::error('Erro em leaguesByCountry: ' . $e->getMessage());
            return response()->json(['leagues' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint AJAX: devolve standings (classificação), com paginação
     */
    public function standings(Request $request)
    {
        $leagueId = (int)$request->query('league');
        $season = (int)$request->query('season', now()->year);
        $page = max(1, (int)$request->query('page', 1));
        $perPage = min(10, (int)$request->query('per_page', 10));

        if (!$leagueId) {
            return response()->json(['error' => 'Liga não indicada'], 422);
        }

        try {
            $raw = $this->api->fetchStandings($leagueId, $season);

            // garantir que vais ao array correto da API
            $allStandings = collect(data_get($raw, 'response.0.league.standings.0', []));
            session(['last_sync' => now()->format('d/m/Y')]);
            $paged = $allStandings
                ->forPage($page, $perPage)
                ->values()
                ->map(fn($t) => [
                    'team_name' => data_get($t, 'team.name'),
                    'team_logo' => data_get($t, 'team.logo'),
                    'points' => data_get($t, 'points'),
                    'played' => data_get($t, 'all.played'),
                    'win' => data_get($t, 'all.win'),
                    'draw' => data_get($t, 'all.draw'),
                    'lose' => data_get($t, 'all.lose'),
                    'gf' => data_get($t, 'all.goals.for'),
                    'ga' => data_get($t, 'all.goals.against'),
                    'gd' => data_get($t, 'goalsDiff'),
                ]);

            return response()->json([
                'standings' => $paged,
                'total' => $allStandings->count(),
                'page' => $page,
                'per_page' => $perPage,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro em standings: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rounds(Request $request)
    {
        $leagueId = (int)$request->query('league');
        $season = (int)$request->query('season', now()->year);

        if (!$leagueId) {
            return response()->json(['rounds' => []], 422);
        }

        try {
            $rounds = $this->api->fetchRounds($leagueId, $season);
            return response()->json(['rounds' => $rounds]);
        } catch (\Throwable $e) {
            Log::error('Erro em rounds: ' . $e->getMessage());
            return response()->json(['rounds' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function fixtures(Request $request)
    {
        $leagueId = (int)$request->query('league');
        $season = (int)$request->query('season', now()->year);
        $round = $request->query('round');

        if (!$leagueId || !$round) {
            return response()->json(['fixtures' => []], 422);
        }

        try {
            $fixtures = $this->api->fetchFixtures($leagueId, $season, $round);
            return response()->json(['fixtures' => $fixtures]);
        } catch (\Throwable $e) {
            Log::error('Erro em fixtures: ' . $e->getMessage());
            return response()->json(['fixtures' => [], 'error' => $e->getMessage()], 500);
        }
    }

}
