<?php

// app/Models/PlayerReport.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerReport extends Model
{
    protected $fillable = [
        'player_id',
        'report_date',
        'author',
        'observations',
        'scoutalent_rentabilidade',
        'scoutalent_potencial',
    ];

    protected $casts = [
        'report_date' => 'date',
        'indicators' => 'array',

    ];

    public function player()
    {
        return $this->belongsTo(Players::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'author', 'name');
    }

}

