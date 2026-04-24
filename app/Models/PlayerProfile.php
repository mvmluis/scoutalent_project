<?php

// app/Models/PlayerProfile.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerProfile extends Model
{
    protected $fillable = [
        'player_id',
        'market_value',
        'contract_end',
        'scoutalent_rentabilidade',
        'scoutalent_potencial',
        'stat1_label', 'stat1_value',
        'stat2_label', 'stat2_value',
        'stat3_label', 'stat3_value',
        'stat4_label', 'stat4_value',
        'stat5_label', 'stat5_value',
        'stat6_label', 'stat6_value',
        'stats',
        'position_metrics',
    ];


    protected $casts = [
        'stats' => 'array',
        'position_metrics' => 'array',
        'contract_end' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Players::class, 'player_id');
    }
}
