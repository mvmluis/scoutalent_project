<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $countries = [
            'PT' => 'Portugal', 'ES' => 'Espanha', 'FR' => 'França',
            'IT' => 'Itália', 'DE' => 'Alemanha', 'GB' => 'Reino Unido',
            'BR' => 'Brasil',
        ];

        return view('definicoesconta.layout.dashboard', compact('user', 'countries'));
    }


    public function store(Request $request)
    {
        $user = auth()->user();

        // Validação dos dados
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'birthdate' => 'nullable|date|before:today',
            'country' => 'nullable|string|max:100',
            'password' => 'nullable|confirmed|min:8',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'nif' => ['nullable','digits:9'], // NIF português tem 9 dígitos; ajusta se necessário
            'morada' => 'nullable|string|max:255',
            'nome_tecnico' => 'nullable|string|max:255',
        ]);

        // Avatar: guarda novo e apaga anterior se existir
        if ($request->hasFile('avatar')) {
            try {
                if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $path;
            } catch (\Throwable $e) {
                return back()->with('error', 'Erro a gravar a imagem.');
            }
        }

        // Password
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Não sobrescrever se deixado em branco
        if (!$request->filled('birthdate')) unset($data['birthdate']);
        if (!$request->filled('country')) unset($data['country']);
        if (!$request->filled('nif')) unset($data['nif']);
        if (!$request->filled('morada')) unset($data['morada']);
        if (!$request->filled('nome_tecnico')) unset($data['nome_tecnico']);

        try {
            $user->update($data);
            $user->refresh();
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao guardar: ' . $e->getMessage());
        }

        return back()->with('success', 'Definições atualizadas com sucesso.');
    }
}

