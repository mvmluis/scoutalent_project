<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class
FootballApiService
{
    protected function apiHeaders(): array
    {
        return ['x-apisports-key' => config('services.football.key')];
    }

    protected function apiBase(): string
    {
        return 'https://v3.football.api-sports.io';
    }

    public function allowedCountriesTerms(): array
    {
        return [
            'Netherlands', 'Germany', 'Denmark', 'Italy', 'Mexico', 'Switzerland', 'Romania', 'Cyprus', 'Morocco',
            'Azerbaijan', 'Armenia', 'Colombia', 'Czech Republic', 'Russia', 'England', 'Brazil',
            'United Arab Emirates', 'USA', 'United States', 'Poland', 'Scotland', 'Greece', 'Turkey',
            'Norway', 'Hungary', 'Bulgaria', 'Croatia', 'Spain', 'France', 'Belgium', 'Portugal'
        ];
    }

    public function fetchCountries(): array
    {
        return Cache::remember('football:countries:whitelist', 60 * 60 * 24, function () {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/countries');
            if (!$res->ok()) return [];
            $all = $res->json()['response'] ?? [];
            $allowedTerms = array_map('strtolower', $this->allowedCountriesTerms());

            $filtered = array_filter($all, function ($c) use ($allowedTerms) {
                $name = strtolower(data_get($c, 'name', ''));
                $code = strtolower(data_get($c, 'code', ''));
                foreach ($allowedTerms as $term) {
                    if (str_contains($name, $term) || ($code && str_contains($code, $term))) return true;
                }
                return false;
            });

            return array_values(array_map(function ($c) {
                return [
                    'name' => data_get($c, 'name'),
                    'code' => data_get($c, 'code'),
                ];
            }, $filtered));
        });
    }

    public function fetchSeasons(): array
    {
        return Cache::remember('football:seasons', 60 * 60 * 24, function () {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/seasons');
            if (!$res->ok()) return [];
            return $res->json()['response'] ?? [];
        });
    }

    public function fetchLeagues(?string $country = null, ?string $season = null): array
    {
        $query = array_filter([
            'country' => $country ?: null,
            'season' => $season ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $cacheKey = 'football:leagues:' . md5(json_encode($query));
        return Cache::remember($cacheKey, 60 * 60 * 6, function () use ($query) {
            $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/leagues', $query);
            if (!$res->ok()) return [];
            return $res->json()['response'] ?? [];
        });
    }

    // Método utilitário para retornar dados simplificados para o frontend
    public function leaguesByCountryData(string $country, ?string $season = null): array
    {
        $leagues = $this->fetchLeagues($country, $season);

        return array_map(function ($l) {
            return [
                'id' => data_get($l, 'league.id'),
                'name' => data_get($l, 'league.name'),
                'type' => data_get($l, 'league.type'),
                'logo' => data_get($l, 'league.logo'),
                'seasons' => data_get($l, 'seasons'),
                'coverage' => data_get($l, 'coverage'),
            ];
        }, $leagues);
    }

    public function fetchStandings(int $leagueId, int $season)
    {
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/standings', [
                'league' => $leagueId,
                'season' => $season,
            ]);

        if (!$res->ok()) {
            return [];
        }

        return $res->json();
    }

    public function fetchRounds(int $leagueId, int $season): array
    {
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/fixtures/rounds', [
                'league' => $leagueId,
                'season' => $season,
            ]);

        if (!$res->ok()) return [];
        return $res->json()['response'] ?? [];
    }

    public function fetchPlayersByTeam(int $teamId, int $season): array
    {
        $cacheKey = "football:players:team:{$teamId}:{$season}";
        return Cache::remember($cacheKey, 60 * 60, function () use ($teamId, $season) {
            $res = Http::withHeaders($this->apiHeaders())
                ->get($this->apiBase() . '/players', [
                    'team' => $teamId,
                    'season' => $season,
                ]);

            if (!$res->ok()) return [];

            // A API devolve response[], cada item com ["player"] + ["statistics"]
            return $res->json()['response'] ?? [];
        });
    }


    public function fetchFixtures(int $leagueId, int $season, string $round): array
    {
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/fixtures', [
                'league' => $leagueId,
                'season' => $season,
                'round'  => $round,
            ]);

        if (!$res->ok()) return [];

        return collect($res->json()['response'] ?? [])->map(fn($f) => [
            'id'         => data_get($f, 'fixture.id'), // <-- ADICIONAR
            'date'       => \Carbon\Carbon::parse(data_get($f, 'fixture.date'))->format('d/m'),
            'home'       => data_get($f, 'teams.home.name'),
            'home_logo'  => data_get($f, 'teams.home.logo'),
            'away'       => data_get($f, 'teams.away.name'),
            'away_logo'  => data_get($f, 'teams.away.logo'),
            'goals_home' => data_get($f, 'goals.home'),
            'goals_away' => data_get($f, 'goals.away'),
        ])->all();
    }


    public function fetchMatchStatistics(int $fixtureId): array
    {
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/fixtures/statistics', [
                'fixture' => $fixtureId,
            ]);

        if (!$res->ok()) return [];
        return $res->json()['response'] ?? [];
    }

    public function fetchTeamStatistics(int $leagueId, int $season, int $teamId): array
    {
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/teams/statistics', [
                'league' => $leagueId,
                'season' => $season,
                'team' => $teamId,
            ]);

        if (!$res->ok()) return [];
        return $res->json()['response'] ?? [];
    }

    public function fetchPlayerStatistics(int $season, int $playerId, ?int $teamId = null, ?int $leagueId = null): array
    {
        $query = [
            'season' => $season,
            'player' => $playerId,
        ];

        if ($teamId) $query['team'] = $teamId;
        if ($leagueId) $query['league'] = $leagueId;

        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/players', $query);

        if ($res->ok() && !empty($res->json()['response'])) {
            return $res->json()['response'];
        }

        // fallback → tenta só com season+player
        $res = Http::withHeaders($this->apiHeaders())
            ->get($this->apiBase() . '/players', [
                'season' => $season,
                'player' => $playerId,
            ]);

        return $res->ok() ? $res->json()['response'] ?? [] : [];
    }

    public function fetchPlayerProfile(int $playerId): array
    {
        $res = Http::withHeaders($this->apiHeaders())->get($this->apiBase() . '/players/profiles', [
            'player' => $playerId,
        ]);

        return $res->ok() ? $res->json()['response'] ?? [] : [];
    }


}
