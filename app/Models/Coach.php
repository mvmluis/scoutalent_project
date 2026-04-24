<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coach extends Model
{
    protected $table = 'coachs';

    protected $fillable = [
        'external_id',
        'team_id',
        'name',
        'nationality',
        'age',
        'birth_date',
        'photo',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'birth_date' => 'date:Y-m-d',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
