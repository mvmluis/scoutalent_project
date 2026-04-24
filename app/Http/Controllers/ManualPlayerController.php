<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ManualPlayerController extends Controller
{
    /**
     * Lista jogadores
     */
// ✅ Controller (PlayersManualController.php)

public function index(Request $request)
{
    $countryId = $request->filled('country_id') ? (int) $request->country_id : null;

    // league_id pode vir interno (leagues.id) ou externo (leagues.external_id)
    $leagueIdRaw = $request->filled('league_id') ? (int) $request->league_id : null;

    // Resolver para: $leagueInternalId + $leagueExternalId
    $leagueInternalId = null;
    $leagueExternalId = null;

    if ($leagueIdRaw) {
        $leagueRow = DB::table('leagues')
            ->select('id', 'external_id')
            ->where('id', $leagueIdRaw)
            ->orWhere('external_id', $leagueIdRaw)
            ->first();

        if ($leagueRow) {
            $leagueInternalId = (int) $leagueRow->id;
            $leagueExternalId = (int) $leagueRow->external_id;
        }
    }

    $countries = Cache::remember('playersManual:countries', 21600, function () {
        return DB::table('countries')
            ->select('id', 'name', 'flag', 'norm_name')
            ->orderBy('name')
            ->get();
    });

    $leagues = Cache::remember('playersManual:leagues:' . ($countryId ?: 'all'), 21600, function () use ($countryId) {
        $q = DB::table('leagues')
            ->select('id', 'name', 'country', 'logo', 'external_id')
            ->orderBy('country')
            ->orderBy('name');

        if ($countryId) {
            $countryNorm = DB::table('countries')->where('id', $countryId)->value('norm_name');
            if ($countryNorm) {
                $q->whereRaw('LOWER(TRIM(country)) = ?', [$countryNorm]);
            }
        }

        return $q->get();
    });

    // ✅ Equipas: via pivot league_teams (liga externa)
    $teams = collect();
    if ($leagueExternalId) {
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->select('t.id', 't.name', 't.logo')
            ->where('lt.league_external_id', $leagueExternalId)
            ->orderBy('t.name')
            ->distinct()
            ->get();
    }

    $positions = Cache::remember('playersManual:positions', 21600, function () {
        return DB::table('players')
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');
    });

    $years = Cache::remember('playersManual:years', 21600, function () {
        return DB::table('players')
            ->selectRaw('YEAR(created_at) as y')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y');
    });

    $players = collect();
    $followedIds = [];

    // ✅ Normalizar URL para league_id interno (evita inconsistências)
    if ($leagueIdRaw && $leagueInternalId && $leagueIdRaw !== $leagueInternalId) {
        return redirect()->route('manual.players.index', array_merge($request->query(), [
            'league_id' => $leagueInternalId,
        ]));
    }

    return view('playersManual.layout.dashboard', compact(
        'players', 'countries', 'leagues', 'teams', 'positions', 'followedIds', 'years'
    ));
}

public function data(Request $request)
{
    $countryId = $request->filled('country_id') ? (int) $request->country_id : null;

    // league_id pode vir interno (leagues.id) ou externo (leagues.external_id)
    $leagueIdRaw = $request->filled('league_id') ? (int) $request->league_id : null;

    $leagueInternalId = null;
    $leagueExternalId = null;

    if ($leagueIdRaw) {
        $leagueRow = DB::table('leagues')
            ->select('id', 'external_id')
            ->where('id', $leagueIdRaw)
            ->orWhere('external_id', $leagueIdRaw)
            ->first();

        if ($leagueRow) {
            $leagueInternalId = (int) $leagueRow->id;
            $leagueExternalId = (int) $leagueRow->external_id;
        }
    }

    // ✅ equipa vem por ID
    $teamId = $request->filled('team_id') ? (int) $request->team_id : null;

    $name      = $request->filled('name') ? trim((string) $request->name) : null;
    $position  = $request->filled('position') ? trim((string) $request->position) : null;
    $ageMin    = $request->filled('age_min') ? (int) $request->age_min : null;
    $ageMax    = $request->filled('age_max') ? (int) $request->age_max : null;
    $year      = $request->filled('year') ? (int) $request->year : null;

    $query = DB::table('players as p')
        ->leftJoin('teams as t', 't.id', '=', 'p.team_id');

    // ----------------
    // Filtros
    // ----------------
    if ($countryId) {
        $countryNorm = DB::table('countries')->where('id', $countryId)->value('norm_name');
        if ($countryNorm) {
            if (Schema::hasColumn('players', 'nationality_norm')) {
                $query->where('p.nationality_norm', $countryNorm);
            } else {
                $query->whereRaw('LOWER(TRIM(p.nationality)) = ?', [$countryNorm]);
            }
        }
    }

    // ✅ filtrar liga via pivot league_teams (liga externa -> equipas -> jogadores)
    if ($leagueExternalId) {
        $query->whereExists(function ($q) use ($leagueExternalId) {
            $q->select(DB::raw(1))
                ->from('league_teams as lt')
                ->whereColumn('lt.team_id', 'p.team_id')
                ->where('lt.league_external_id', $leagueExternalId);
        });
    }

    if ($teamId) {
        $query->where('p.team_id', $teamId);
    }

    if ($name) {
        $query->where('p.name', 'like', "%{$name}%");
    }

    if ($position) {
        $query->where('p.position', 'like', "%{$position}%");
    }

    if ($ageMin !== null) $query->where('p.age', '>=', $ageMin);
    if ($ageMax !== null) $query->where('p.age', '<=', $ageMax);

    if ($year) {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('p.created_at', [$start, $end])
              ->orWhereBetween('p.updated_at', [$start, $end]);
        });
    }

    // ----------------
    // TOTAL (antes do paginate)
    // ----------------
    $total = (clone $query)->count();

    // ----------------
    // SELECT
    // ----------------
    $query->select([
        'p.id',
        'p.external_id',
        'p.name',
        'p.photo',
        'p.age',
        'p.nationality',
        'p.team_id',
        'p.team_name',
        'p.position',
        'p.height',
        'p.weight',
        'p.height_cm',
        'p.weight_kg',
        'p.birth_date',
        'p.appearances',
        'p.goals',
        'p.rating',
        DB::raw('COALESCE(t.name, p.team_name) as resolved_team'),
        't.logo as team_logo',
    ]);

    // ----------------
    // SORT (✅ corrigido)
    // ----------------
    $sort = $request->get('sort', 'name');
    $dir  = strtolower($request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

    if ($sort === 'name') {
        $query->orderByRaw("COALESCE(p.name, '') {$dir}");
        $query->orderBy('p.id', $dir);
    }
    elseif ($sort === 'team_name') {
        $query->orderByRaw("COALESCE(t.name, p.team_name, '') {$dir}");
        $query->orderBy('p.id', $dir);
    }
    else {
        switch ($sort) {
            case 'age':
                $query->orderBy('p.age', $dir);
                break;

            case 'position':
                $query->orderBy('p.position', $dir);
                break;

            case 'height':
                // ✅ numérico (cm)
                $query->orderByRaw("COALESCE(p.height_cm, 0) {$dir}");
                break;

            case 'weight':
                // ✅ numérico (kg)
                $query->orderByRaw("COALESCE(p.weight_kg, 0) {$dir}");
                break;

            case 'rating':
                // ✅ garante ordenação numérica mesmo se for string
                $query->orderByRaw("COALESCE(CAST(p.rating AS DECIMAL(5,2)), 0) {$dir}");
                break;

            case 'appearances':
                $query->orderByRaw("COALESCE(CAST(p.appearances AS UNSIGNED), 0) {$dir}");
                break;

            case 'goals':
                $query->orderByRaw("COALESCE(CAST(p.goals AS UNSIGNED), 0) {$dir}");
                break;

            case 'id':
                $query->orderBy('p.id', $dir);
                break;

            default:
                $query->orderByRaw("COALESCE(p.name, '') {$dir}");
                $query->orderBy('p.id', $dir);
                break;
        }

        // ✅ tie-breaker estável para cursor paginate
        if ($sort !== 'id') {
            $query->orderBy('p.id', $dir);
        }
    }

    // ----------------
    // CURSOR PAGINATE
    // ----------------
    $perPage = 15;
    $players = $query->cursorPaginate($perPage);

    // ✅ Equipas por liga para repovoar select
    $teams = [];
    if ($leagueExternalId) {
        $teams = DB::table('league_teams as lt')
            ->join('teams as t', 't.id', '=', 'lt.team_id')
            ->select('t.id', 't.name', 't.logo')
            ->where('lt.league_external_id', $leagueExternalId)
            ->orderBy('t.name')
            ->distinct()
            ->get()
            ->values();
    }

    $items = collect($players->items());

    return response()->json([
        'data'        => $players->items(),
        'followedIds' => Auth::check()
            ? DB::table('player_follows')
                ->where('user_id', Auth::id())
                ->whereIn('player_id', $items->pluck('id'))
                ->pluck('player_id')
                ->map(fn ($v) => (int) $v)
                ->all()
            : [],
        'total'      => $total,
        'perPage'    => $perPage,
        'nextCursor' => optional($players->nextCursor())->encode(),
        'prevCursor' => optional($players->previousCursor())->encode(),

        'teams'      => $teams,

        'league'     => [
            'id' => $leagueInternalId,
            'external_id' => $leagueExternalId,
        ],
    ]);
}


    /**
     * Mostra o formulário de criação
     */
    public function create()
    {
        $teams = DB::table('teams')->select('id', 'name')->orderBy('name')->get();
        return view('playersManual.create', compact('teams'));
    }

    /**
     * Guarda um novo jogador
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'photo' => 'nullable|image|max:2048',
                'age' => 'nullable|integer|min:14|max:60',
                'nationality' => 'nullable|string|max:255',
                'height' => 'nullable|string|max:50',
                'weight' => 'nullable|string|max:50',
                'birth_date' => 'nullable|date',
                'team_id' => 'nullable|integer|exists:teams,id',
                'position' => 'nullable|string|max:255',
                'rating' => 'nullable|numeric|min:0|max:10',
                'appearances' => 'nullable|integer|min:0',
                'minutes' => 'nullable|integer|min:0',
                'goals' => 'nullable|integer|min:0',
                'yellow_cards' => 'nullable|integer|min:0',
                'red_cards' => 'nullable|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // 📸 Upload da foto
        $photoPath = $request->hasFile('photo')
            ? '/storage/' . $request->file('photo')->store('players', 'public')
            : '/images/default-player.png';

        // 🧱 Montar dados
        $playerData = [
            'name' => $validated['name'],
            'photo' => $photoPath,
            'age' => $validated['age'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'appearances' => $validated['appearances'] ?? 0,
            'minutes' => $validated['minutes'] ?? 0,
            'goals' => $validated['goals'] ?? 0,
            'yellow_cards' => $validated['yellow_cards'] ?? 0,
            'red_cards' => $validated['red_cards'] ?? 0,
            'position' => $validated['position'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Nome da equipa (opcional)
        if (!empty($validated['team_id'])) {
            $playerData['team_name'] = DB::table('teams')
                ->where('id', $validated['team_id'])
                ->value('name');
        }

        $playerId = DB::table('players')->insertGetId($playerData);

        return redirect()->route('manual.players.show', $playerId)
            ->with('success', '✅ Jogador criado com sucesso!');
    }

    /**
     * Mostra detalhes de um jogador
     */
    public function show($id)
    {
        $player = DB::table('players')->where('id', $id)->first();

        if (!$player) {
            return redirect()->route('manual.players.index')
                ->with('error', '❌ Jogador não encontrado.');
        }

        return view('playersManual.show', compact('player'));
    }

    /**
     * Mostra o formulário de edição
     */
    public function edit($id)
    {
        $player = DB::table('players')->where('id', $id)->first();
        if (!$player) {
            return redirect()->route('manual.players.index')
                ->with('error', '❌ Jogador não encontrado.');
        }

        $teams = DB::table('teams')->select('id', 'name')->orderBy('name')->get();

        return view('playersManual.edit', compact('player', 'teams'));
    }

    /**
     * Atualiza jogador
     */
    public function update(Request $request, $id)
    {
        $player = DB::table('players')->where('id', $id)->first();
        if (!$player) {
            return redirect()->route('manual.players.index')
                ->with('error', '❌ Jogador não encontrado.');
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'photo' => 'nullable|image|max:2048',
                'age' => 'nullable|integer|min:14|max:60',
                'nationality' => 'nullable|string|max:255',
                'height' => 'nullable|string|max:50',
                'weight' => 'nullable|string|max:50',
                'birth_date' => 'nullable|date',
                'team_id' => 'nullable|integer|exists:teams,id',
                'position' => 'nullable|string|max:255',
                'rating' => 'nullable|numeric|min:0|max:10',
                'appearances' => 'nullable|integer|min:0',
                'minutes' => 'nullable|integer|min:0',
                'goals' => 'nullable|integer|min:0',
                'yellow_cards' => 'nullable|integer|min:0',
                'red_cards' => 'nullable|integer|min:0',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $photoPath = $player->photo;
        if ($request->hasFile('photo')) {
            $photoPath = '/storage/' . $request->file('photo')->store('players', 'public');
        }

        DB::table('players')->where('id', $id)->update([
            'name' => $validated['name'],
            'photo' => $photoPath,
            'age' => $validated['age'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'appearances' => $validated['appearances'] ?? 0,
            'minutes' => $validated['minutes'] ?? 0,
            'goals' => $validated['goals'] ?? 0,
            'yellow_cards' => $validated['yellow_cards'] ?? 0,
            'red_cards' => $validated['red_cards'] ?? 0,
            'updated_at' => now(),
        ]);

        return redirect()->route('manual.players.show', $id)
            ->with('success', '✅ Jogador atualizado com sucesso!');
    }

    /**
     * Elimina jogador
     */
    public function destroy($id)
    {
        $player = DB::table('players')->where('id', $id)->first();
        if (!$player) {
            return redirect()->route('manual.players.index')
                ->with('error', '❌ Jogador não encontrado.');
        }

        DB::table('players')->where('id', $id)->delete();

        return redirect()->route('manual.players.index')
            ->with('success', '🗑️ Jogador eliminado com sucesso!');
    }
}
