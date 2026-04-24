<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * ✅ Logout "forte": termina sessão, invalida e regenera CSRF
     * + mensagem para o utilizador.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // fecha mesmo a sessão (evita tokens antigos e 419 intermitente)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sessão terminada com sucesso.');
    }
}
