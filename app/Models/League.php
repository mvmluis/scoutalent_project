<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'country',
        'code',
        'type',
        'logo',
        'seasons',
        'coverage',
        'meta',
    ];

    protected $casts = [
        'seasons'  => 'array',
        'coverage' => 'array',
        'meta'     => 'array',
    ];
}
