<?php

namespace App\Http\Controllers;

use App\Models\PlayerReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoutsReportsController extends Controller
{
    public function mine()
    {
        $user = Auth::user();

        $reports = PlayerReport::with(['player', 'user'])
            ->orderByDesc('report_date')
            ->get();

        $scoutPhoto = asset('imagens/novologo.png');

        // ✅ View correta:
        return view('reports.layout.dashboard', compact('reports', 'user', 'scoutPhoto'));
    }

}
