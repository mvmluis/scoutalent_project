<!DOCTYPE html>
<html lang="pt">
<body style="font-family: Arial, sans-serif; color:#333;">
<h2>Novo pedido de acesso à plataforma Scoutalent</h2>

<p><strong>Nome:</strong> {{ $req->name }}</p>
<p><strong>Email:</strong> {{ $req->email }}</p>
<p><strong>País:</strong> {{ $req->country }}</p>

<p>O utilizador acima enviou um pedido de acesso. Escolha uma das opções abaixo:</p>

<div style="margin-top: 24px;">
    <a href="{{ $approveUrl }}"
       style="background:#28a745;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;margin-right:10px;">
        ✅ Aprovar Pedido
    </a>

    <a href="{{ $rejectUrl }}"
       style="background:#dc3545;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">
        ❌ Rejeitar Pedido
    </a>
</div>

<br><br>
<p style="font-size:13px;color:#888;">
    Este link expira em 7 dias.<br>
    Mensagem automática da plataforma <strong>Scoutalent</strong>.
</p>
</body>
</html>
