<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachProfile extends Model
{
    protected $fillable = [
        'coach_id',
        'contract_end',
        'stat1_label', 'stat1_value',
        'stat2_label', 'stat2_value',
        'stat3_label', 'stat3_value',
        'meta'
    ];

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}
