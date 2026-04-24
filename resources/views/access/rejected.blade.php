<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScoutTalent.pt</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #fff;
            color: #444;
            text-align: center;
            padding-top: 12%;
        }
        h2 {
            font-weight: 500;
            color: #a10000;
        }
        p {
            color: #777;
            font-size: 14px;
        }
        .logo {
            margin-top: 30px;
            font-weight: 900;
            font-size: 28px;
        }
        .logo span {
            color: #e60000;
            font-weight: 800;
        }
        .info {
            margin-top: 15px;
            color: #555;
            font-size: 15px;
        }
    </style>
</head>
<body>
<h2>O seu pedido de acesso foi recusado.</h2>
<p>Lamentamos, mas o seu pedido de registo não foi aprovado neste momento.</p>

<div class="info">
    <strong>Nome:</strong> {{ $name }}<br>
    <strong>Email:</strong> {{ $email }}
</div>

<p style="margin-top: 20px; font-size: 13px; color:#888;">
    Em caso de dúvida, contacte o suporte da plataforma.
</p>

<div class="logo">SCOUT<span>TALENT</span></div>
</body>
</html>
