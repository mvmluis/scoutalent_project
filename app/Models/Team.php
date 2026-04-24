<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'country',
        'code',
        'founded',
        'venue',
        'logo',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Getter utilitário para url do logo (API convention)
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return $this->logo;
        }
        if ($this->external_id) {
            return "https://media.api-sports.io/football/teams/{$this->external_id}.png";
        }
        return null;
    }

    // relacionamento opcional com players (se quiseres)
    public function players()
    {
        return $this->hasMany(\App\Models\Players::class, 'team_id');
    }
}
