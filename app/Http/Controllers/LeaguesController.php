<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Services\FootballApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaguesController extends Controller
{
    protected FootballApiService $api;

    public function __construct(FootballApiService $api)
    {

        $this->api = $api;
    }

    /**
     * Endpoint AJAX: /players/leagues-by-country
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
            Log::debug('leaguesByCountry called', [
                'country' => $country,
                'season' => $season,
                'count' => is_array($leagues) ? count($leagues) : 0,
            ]);

            // devolve também um debug pequeno para o cliente ver (só enquanto depuras)
            return response()->json([
                'leagues' => $leagues,
                'debug' => [
                    'country_received' => $country,
                    'season_received' => $season,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('leaguesByCountry error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['leagues' => [], 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * View: /competition/ligas
     * Nota: passa "countries" (plural) para a blade que espera $countries
     */
    public function leaguesIndex(Request $request)
    {
        $selectedCountry = $request->query('country', null);
        $season = $request->query('season', null);

        // carregar ligas se foi passado country (opcional)
        $leagues = [];
        if ($selectedCountry) {
            try {
                $leagues = $this->api->leaguesByCountryData($selectedCountry, $season);
            } catch (\Throwable $e) {
                Log::warning('leaguesIndex (fetch leagues) failed: ' . $e->getMessage());
                $leagues = [];
            }
        }

        // carregar countries (para popular o select) — método do serviço
        try {
            $countries = $this->api->fetchCountries(); // devolve array de ['name'=>'Portugal','code'=>'PT'] ou []
        } catch (\Throwable $e) {
            Log::warning('leaguesIndex (fetch countries) failed: ' . $e->getMessage());
            $countries = [];
        }

        return view('ligas.layout.dashboard', [
            'countries' => $countries,          // <--- variável que a view espera
            'leagues' => $leagues,
            'country' => $selectedCountry,    // opcional, caso uses esta chave noutros pontos
            'season' => $season,
        ]);
    }

    /**
     * Sincronização (POST).
     */
    public function syncLeagues(Request $request)
    {
        $data = $request->validate([
            'country' => 'nullable|string',
            'season'  => 'nullable|integer',
            'league'  => 'nullable|string', // caso seja sync de liga única
        ]);

        $country = $data['country'] ?? null;
        $season = $data['season'] ?? null;
        $leagueFilter = $data['league'] ?? null;

        if (empty($country)) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Indica um país para sincronizar.'], 422)
                : back()->with('error', 'Indica um país para sincronizar.');
        }

        try {
            $leagues = $this->api->leaguesByCountryData($country, $season);

            // Se for sync de uma única liga
            if ($leagueFilter) {
                $leagues = array_filter($leagues, fn($l) =>
                    (data_get($l, 'id') ?? data_get($l, 'league.id')) == $leagueFilter
                );
            }

            $saved = 0;
            foreach ($leagues as $l) {
                $externalId = data_get($l, 'id') ?? data_get($l, 'league.id');
                if (!$externalId) continue;

                // seasons novas vindas da API
                $newSeasons = collect(data_get($l, 'seasons', []))->keyBy('year');

                // procura se já existe a liga
                $existing = League::where('external_id', $externalId)->first();
                if ($existing) {
                    $oldSeasons = collect($existing->seasons ?? [])->keyBy('year');
                    // mescla (mantém antigas + substitui anos iguais por novos)
                    $mergedSeasons = $oldSeasons->merge($newSeasons)->values()->all();
                } else {
                    $mergedSeasons = $newSeasons->values()->all();
                }

                League::updateOrCreate(
                    ['external_id' => $externalId],
                    [
                        'external_id' => $externalId,
                        'name'        => data_get($l, 'name'),
                        'country'     => data_get($l, 'country') ?? $country,
                        'code'        => data_get($l, 'code'),
                        'type'        => data_get($l, 'type'),
                        'logo'        => data_get($l, 'logo') ?? "https://media.api-sports.io/football/leagues/{$externalId}.png",

                        'seasons'     => json_encode($mergedSeasons, JSON_UNESCAPED_UNICODE),
                        'coverage'    => json_encode(data_get($l, 'coverage', data_get($l,'league.coverage', [])), JSON_UNESCAPED_UNICODE),
                        'meta'        => json_encode($l, JSON_UNESCAPED_UNICODE),
                    ]
                );
                $saved++;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => "Sincronização completa — {$saved} ligas actualizadas."]);
            }

            return back()->with('success', "Sincronização completa — {$saved} ligas actualizadas.");
        } catch (\Throwable $e) {
            Log::error('syncLeagues error', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Erro ao sincronizar ligas: ' . $e->getMessage());
        }
    }

}
