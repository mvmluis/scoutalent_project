<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'country',
        'birthdate',
        'avatar',
        'nif',
        'morada',
        'nome_tecnico',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Use property $casts (método diferente do que tinhas)
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthdate' => 'date', // facilita trabalhar como Carbon
    ];
}
