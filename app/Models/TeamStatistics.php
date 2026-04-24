<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamStatistics extends Model
{
    protected $table = 'team_statistics';

    protected $fillable = [
        'team_id',
        'league_id',
        'season',

        'league_country',
        'league_name',
        'league_logo',

        'team_name',
        'team_logo',

        'data',
        'form',
        'goals_for_avg',
        'goals_against_avg',
        'fixtures_played',
        'synced_at',
    ];

    protected $casts = [
        'data' => 'array',
        'synced_at' => 'datetime',

        'goals_for_avg' => 'decimal:2',
        'goals_against_avg' => 'decimal:2',

        'season' => 'integer',
        'team_id' => 'integer',
        'league_id' => 'integer',
        'fixtures_played' => 'integer',

        'league_country' => 'string',
        'league_name' => 'string',
        'league_logo' => 'string',

        'team_name' => 'string',
        'team_logo' => 'string',
    ];
}
