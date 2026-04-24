<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


// app/Models/Country.php
class Country extends Model
{
    protected $fillable = ['name','code','continent','flag','meta'];
    protected $casts = ['meta' => 'array'];

    // Laravel irá usar "code" quando fizer implicit binding (route model binding)
    public function getRouteKeyName()
    {
        return 'code';
    }
}

