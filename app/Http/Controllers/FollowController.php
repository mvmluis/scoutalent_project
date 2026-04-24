<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FollowController extends Controller
{
    public function toggle($playerId)
    {
        $userId = Auth::id();

        $exists = DB::table('player_follows')
            ->where('user_id', $userId)
            ->where('player_id', $playerId)
            ->exists();

        if ($exists) {
            DB::table('player_follows')
                ->where('user_id', $userId)
                ->where('player_id', $playerId)
                ->delete();

            return response()->json(['status' => 'unfollowed']);
        } else {
            DB::table('player_follows')->insert([
                'user_id' => $userId,
                'player_id' => $playerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => 'followed']);
        }
    }


    // Mostra apenas os jogadores seguidos pelo utilizador autenticado
    public function index()
    {
        $userId = Auth::id();

        // Detecta qual tabela de relatórios existe
        if (Schema::hasTable('player_reports')) {
            $reportsTable = 'player_reports';
        } elseif (Schema::hasTable('reports')) {
            $reportsTable = 'reports';
        } else {
            $reportsTable = null;
        }

        // Subquery: conta relatórios feitos pelo utilizador autenticado
        $reportsSub = $reportsTable
            ? DB::raw("(SELECT COUNT(1)
                    FROM {$reportsTable} r
                    WHERE r.player_id = p.id
                      AND r.user_id = {$userId}
                   ) as reports_count")
            : DB::raw("0 as reports_count");

        // 🔹 Jogadores seguidos pelo utilizador autenticado
        $players = DB::table('players as p')
            ->join('player_follows as f', function ($join) use ($userId) {
                $join->on('f.player_id', '=', 'p.id')
                    ->where('f.user_id', '=', $userId); // 🔹 só os seguidos pelo user autenticado
            })
            ->select(
                'p.id',
                'p.name',
                'p.photo',
                'p.age',
                'p.team_name',
                'p.position',
                'p.rating',
                'p.goals',
                'p.appearances',
                $reportsSub
            )
            ->orderBy('p.name')
            ->paginate(15);

        return view('playersManualfollowed.layout.dashboard', compact('players'));
    }
}
