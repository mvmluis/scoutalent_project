<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccessRequestMail;

// 👈 este é o correto para o responsável
use App\Mail\AccessApprovedMail;

// 👈 este é para o utilizador
use App\Mail\AccessRejectedMail;

class AccessRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
        ]);

        // Cria ou atualiza o pedido de acesso
        $accessRequest = AccessRequest::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'country' => $data['country'],
                'status' => 'pending',
            ]
        );

        // 📧 Envia email ao responsável com links Aprovar/Rejeitar
        Mail::to(config('mail.responsavel_access', 'geral@scoutalent.com'))
            ->send(new AccessRequestMail($accessRequest));

        // Redireciona para página de confirmação
        return redirect()
            ->route('access.thankyou')
            ->with([
                'name' => $data['name'],
                'email' => $data['email'],
                'country' => $data['country'],
            ]);
    }


    public function approve(Request $request, $id)
    {
        $req = AccessRequest::findOrFail($id);
        $plain = config('auth.default_password', 'scoutalent2025!');

        try {
            DB::transaction(function () use ($req, $plain) {

                // 🔢 Gera nome_tecnico sequencial (scout1, scout2, ...)
                $maxNum = DB::table('users')
                    ->where('nome_tecnico', 'like', 'scout%')
                    ->selectRaw('MAX(CAST(SUBSTRING(nome_tecnico, 6) AS UNSIGNED)) AS maxnum')
                    ->value('maxnum') ?? 0;

                $next = (int)$maxNum + 1;
                $nomeTecnico = 'scout' . $next;

                // 🔹 Cria ou atualiza o utilizador
                $user = User::updateOrCreate(
                    ['email' => $req->email],
                    [
                        'name' => $req->name ?: $req->email,
                        'country' => $req->country,
                        'password' => Hash::make($plain),
                        'nome_tecnico' => $nomeTecnico,
                    ]
                );

                // 🔹 Atualiza estado do pedido
                if ($req->isFillable('approved_at')) $req->approved_at = now();
                if ($req->isFillable('status')) $req->status = 'approved';
                $req->save();

                // 📧 Envia email automático ao utilizador
                Mail::to($req->email)->send(new AccessApprovedMail($user, $plain));
            }, 5);

        } catch (\Throwable $e) {
            report($e);
            return response('Erro ao aprovar: ' . $e->getMessage(), 500);
        }

        // ✅ Página visual de confirmação para o administrador (após clique no email)
        return view('access.approved_admin', ['req' => $req]);
    }


    public function reject(Request $request, $id)
    {
        $req = AccessRequest::findOrFail($id);

        try {
            DB::transaction(function () use ($req) {
                if ($req->isFillable('status')) $req->status = 'rejected';
                if ($req->isFillable('rejected_at')) $req->rejected_at = now();
                $req->save();

                // 📧 Envia email ao utilizador a informar da recusa
                Mail::to($req->email)->send(new AccessRejectedMail($req));
            });
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response('Erro ao rejeitar: ' . $e->getMessage(), 500);
        }

        return view('access.rejected_admin', ['req' => $req]);
    }
}
