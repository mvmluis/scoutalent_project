<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Players extends Model
{
    // Se o nome da tabela não for o plural convencional, define explicitamente:
    // protected $table = 'players';

    protected $fillable = [
        'external_id',
        'name',
        'photo',
        'age',
        'nationality',
        'height',
        'weight',
        'birth_date',
        'team_id',
        'team_name',
        'position',     // 🆕
        'rating',       // 🆕
        'appearances',
        'minutes',
        'goals',
        'yellow_cards',
        'red_cards',
        'meta',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'meta' => 'array',
        'age' => 'integer',
        'rating' => 'decimal:2', // 🆕
        'appearances' => 'integer',
        'minutes' => 'integer',
        'goals' => 'integer',
        'yellow_cards' => 'integer',
        'red_cards' => 'integer',
    ];


    public function profile()
    {
        return $this->hasOne(PlayerProfile::class, 'player_id'); // forçar a FK correta
    }

    public function reports()
    {
        return $this->hasMany(PlayerReport::class);
    }

}
