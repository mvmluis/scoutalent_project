<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\FollowController;

use App\Http\Controllers\Countries;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\EstatisticasController;

use App\Http\Controllers\SearchController;
use App\Http\Controllers\ManualPlayerController;
use App\Http\Controllers\ScoutsReportsController;
use App\Http\Controllers\EstatisticasDbController;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => redirect()->route('login'))->name('landing');

Route::post('/access-request', [AccessRequestController::class, 'store'])
    ->name('access.request.store');

Route::get('/access-request/{id}/approve', [AccessRequestController::class, 'approve'])
    ->middleware(['signed'])
    ->name('access.request.approve');

Route::get('/access-request/{id}/reject', [AccessRequestController::class, 'reject'])
    ->middleware(['signed'])
    ->name('access.request.reject');

Route::view('/obrigado', 'access.thankyou')->name('access.thankyou');

Auth::routes(['verify' => true]);

/*
|--------------------------------------------------------------------------
| Área Autenticada (Scouts + Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 🏠 Dashboard e Definições de Conta
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/definicoes-conta', [AccountSettingsController::class, 'index'])->name('account.settings.index');
    Route::post('/definicoes-conta', [AccountSettingsController::class, 'store'])->name('account.settings.store');

    // ❤️ Follow players
    Route::post('/players/{player}/follow', [FollowController::class, 'toggle'])->name('players.follow');
    Route::get('/players/seguidos', [FollowController::class, 'index'])->name('players.followed');

    /*
    |----------------------------------------------------------------------
    | 🔒 ADMIN — API e Sincronizações
    |----------------------------------------------------------------------
    */
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {

        // 🌍 Países
        Route::prefix('profile/country')->name('profile.country.')->group(function () {
            Route::get('/', [Countries::class, 'index'])->name('index');
            Route::get('/{id}/edit', [Countries::class, 'edit'])->name('edit');
            Route::put('/{id}', [Countries::class, 'update'])->name('update');
            Route::delete('/{id}', [Countries::class, 'destroy'])->name('destroy');
            Route::post('/sync', [Countries::class, 'sync'])->name('sync');
        });

        // ⚽ Jogadores (admin)
        Route::get('/players', [FootballController::class, 'index'])->name('players.index');
        Route::post('/players/sync', [FootballController::class, 'sync'])->name('players.sync');
        Route::post('/players/export-csv', [FootballController::class, 'exportCsv'])->name('players.export.csv');
        Route::get('/players/leagues-by-country', [FootballController::class, 'leaguesByCountry'])->name('players.leagues_by_country');
        Route::get('/players/teams-by-league', [FootballController::class, 'teamsByLeague'])->name('players.teams_by_league');

        // 🏟️ Equipas
        Route::get('/teams', [FootballController::class, 'teamsIndex'])->name('teams.index');
        Route::post('/teams/sync', [FootballController::class, 'syncTeams'])->name('teams.sync');

        // 👔 Treinadores (admin)
        Route::get('/coachs', [CoachController::class, 'coachsIndex'])->name('coachs.index');
        Route::post('/coachs/sync', [CoachController::class, 'syncCoachs'])->name('coachs.sync');

        // 🏆 Competições / Ligas
        Route::get('/competition/leagues', [LeaguesController::class, 'leaguesIndex'])->name('competition.leagues.index');
        Route::post('/competition/leagues/sync', [LeaguesController::class, 'syncLeagues'])->name('competition.leagues.sync');

        // 📈 Estatísticas
        Route::get('/estatisticas', [EstatisticasController::class, 'index'])->name('estatisticas.index');
        Route::get('/estatisticas/leagues-by-country', [EstatisticasController::class, 'leaguesByCountry'])->name('estatisticas.leaguesByCountry');
        Route::get('/estatisticas/players', [EstatisticasController::class, 'playersByTeam'])->name('estatisticas.playersByTeam');
        Route::post('/estatisticas/sync-team', [EstatisticasController::class, 'syncTeam'])->name('estatisticas.syncTeam');

        // 📊 Ligas / Rankings / Jogos
        Route::get('/leagues/by-country', [DashboardController::class, 'leaguesByCountry'])->name('leagues.byCountry');
        Route::get('/leagues/standings', [DashboardController::class, 'standings'])->name('leagues.standings');
        Route::get('/leagues/rounds', [DashboardController::class, 'rounds'])->name('leagues.rounds');
        Route::get('/leagues/fixtures', [DashboardController::class, 'fixtures'])->name('leagues.fixtures');
        Route::post('/leagues/sync', [DashboardController::class, 'syncLeagues'])->name('leagues.sync');
    });

    /*
    |----------------------------------------------------------------------
    | 🔍 Pesquisa Geral
    |----------------------------------------------------------------------
    */
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/leagues/{country_id}', [SearchController::class, 'getLeagues']);
    Route::get('/search/teams/{league_id}', [SearchController::class, 'getTeams']);
    Route::get('/search/players/{team_id}', [SearchController::class, 'getPlayers']);
    Route::get('/search/coaches/{team_id}', [SearchController::class, 'getCoaches']);

    /*
    |----------------------------------------------------------------------
    | ⚽ Jogadores / Treinadores (frontend)
    |----------------------------------------------------------------------
    */
    Route::get('/players/{id}', [SearchController::class, 'showPlayers'])->name('players.show');

    Route::post('/players/{player}/profile', [SearchController::class, 'storeProfile'])->name('players.profile.store');
    Route::post('/players/{player}/reports', [SearchController::class, 'storeReport'])->name('players.reports.store');
    Route::put('/players/{player}/reports/{report}', [SearchController::class, 'updateReport'])->name('players.reports.update');
    Route::get('/players/{player}/reports/{report}/edit', [SearchController::class, 'editReport'])->name('players.reports.edit');
    Route::delete('/players/{player}/reports/{report}', [SearchController::class, 'destroyReport'])->name('players.reports.destroy');

    // Treinadores (frontend)
    Route::get('/coachs/{id}', [SearchController::class, 'showCoachs'])->name('coachs.show');
    Route::get('/coachs/{coach}/profile', [SearchController::class, 'showProfile'])->name('coachs.profile.show');
    Route::post('/coachs/{coach}/profile', [SearchController::class, 'storeCoach'])->name('coachs.profile.store');

    /*
    |----------------------------------------------------------------------
    | 🧾 Manual Coachs (AJAX)  ✅ CORRIGIDO (sem duplicados / sem conflito)
    |----------------------------------------------------------------------
    */
    Route::prefix('manual/coachs')->name('manual.coachs.')->group(function () {

        // AJAX primeiro (para não colidir com /{id})
        Route::get('data',    [CoachController::class, 'data'])->name('data');
        Route::get('leagues', [CoachController::class, 'leaguesByCountry'])->name('leagues');
        Route::get('teams',   [CoachController::class, 'teamsByLeague'])->name('teams');

        // view
        Route::get('/', [CoachController::class, 'index'])->name('index');

        // show só números
        Route::get('{id}', [CoachController::class, 'show'])
            ->whereNumber('id')
            ->name('show');
    });

    /*
    |----------------------------------------------------------------------
    | 🧾 Manual Players (AJAX)
    |----------------------------------------------------------------------
    */
    Route::prefix('manual/players')->name('manual.players.')->group(function () {

        // ✅ endpoint AJAX primeiro (evita conflito com /{player})
        Route::get('/data', [ManualPlayerController::class, 'data'])->name('data');

        Route::get('/', [ManualPlayerController::class, 'index'])->name('index');
        Route::get('/create', [ManualPlayerController::class, 'create'])->name('create');
        Route::post('/store', [ManualPlayerController::class, 'store'])->name('store');

        // ✅ show/edit/update/destroy só números
        Route::get('/{player}', [ManualPlayerController::class, 'show'])
            ->whereNumber('player')
            ->name('show');

        Route::get('/{player}/edit', [ManualPlayerController::class, 'edit'])
            ->whereNumber('player')
            ->name('edit');

        Route::put('/{player}', [ManualPlayerController::class, 'update'])
            ->whereNumber('player')
            ->name('update');

        Route::delete('/{player}', [ManualPlayerController::class, 'destroy'])
            ->whereNumber('player')
            ->name('destroy');

        Route::post('/{player}/reports', [ManualPlayerController::class, 'storeReport'])
            ->whereNumber('player')
            ->name('report.store');
    });

     // 📈 Estatísticas
    Route::get('/estatisticas-bd', [EstatisticasDbController::class, 'index'])->name('estatisticas.bd');

// AJAX (BD)
    Route::get('/estatisticas-bd/leagues', [EstatisticasDbController::class, 'leaguesByCountry'])->name('estatisticas.bd.leagues');
    Route::get('/estatisticas-bd/teams', [EstatisticasDbController::class, 'teamsByLeague'])->name('estatisticas.bd.teams');

    /*
    |----------------------------------------------------------------------
    | 📋 Relatórios dos Scouts
    |----------------------------------------------------------------------
    */
    Route::get('/reports/mine', [ScoutsReportsController::class, 'mine'])->name('reports.mine');

    /*
    |----------------------------------------------------------------------
    | 📚 Formação / Certificados
    |----------------------------------------------------------------------
    */
    Route::get('/formacoes', fn() => view('formacoes.index'))->name('formacoes');
    Route::get('/formacoes/novo', fn() => view('formacoes.novo'))->name('formacoes.novo');
    Route::get('/relatorios/formacao', fn() => view('formacoes.relatorios'))->name('relatorios.formacao');
    Route::get('/certificados', fn() => view('formacoes.certificados'))->name('certificados');
});
