<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Acesso aprovado — Scoutalent</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #fff; padding: 20px;">
<h2 style="color: #e60000;">Bem-vindo(a) à Scoutalent, {{ $user->name }}!</h2>

<p>
    O seu pedido de acesso à plataforma <strong>Scoutalent</strong> foi <strong>aprovado com sucesso</strong>.
</p>

<p>
    Já pode aceder à sua conta utilizando os seguintes dados:
</p>

<div style="background-color:#f8f8f8; border:1px solid #ddd; border-radius:8px; padding:15px; width:fit-content; margin:15px auto;">
    <strong>Email:</strong> {{ $user->email }}<br>
    <strong>Password inicial:</strong> {{ $plainPassword }}
</div>

<p>
    Por motivos de segurança, recomendamos que altere a sua password após o primeiro acesso.
</p>

<!-- 🟥 Botão de acesso -->
<p style="margin-top: 25px;">
    <a href="{{ url('/') }}"
       style="background-color:#e60000; color:#fff; padding:12px 26px; border-radius:8px;
                  text-decoration:none; font-weight:bold; display:inline-block;">
        Aceder à Plataforma
    </a>
</p>

<p style="margin-top: 20px;">
    Em caso de dúvida, poderá contactar o suporte através de
    <a href="mailto:geral@scoutalent.com" style="color:#e60000; font-weight:bold; text-decoration:none;">
        geral@scoutalent.com
    </a>.
</p>

<hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

<p style="font-size:13px; color:#888;">
    Esta é uma mensagem automática. Por favor, não responda a este email.
</p>
</body>
</html>
