<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {

            // Guarda a mensagem para mostrar no login
            session()->flash('error', 'A tua sessão expirou ou não estás autenticado. Por favor, inicia sessão novamente.');

            // (opcional) guardar a URL para voltar depois do login
            session()->put('url.intended', url()->current());

            return route('login');
        }

        return null;
    }
}
