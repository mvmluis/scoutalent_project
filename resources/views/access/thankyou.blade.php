<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScoutTalent.pt</title>

    <!-- 🟩 Ícone do separador (Favicon) -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagens/novologo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('imagens/novologo.png') }}">
    <meta name="theme-color" content="#e60000">

    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #fff;
            color: #444;
            text-align: center;
            padding-top: 8%;
        }
        h2 { font-weight: 500; color: #333; }
        p  { color: #777; font-size: 14px; }
        .logo {
            margin-top: 30px;
            font-weight: 900;
            font-size: 28px;
        }
        .logo span {
            color: #e60000;
            font-weight: 800;
        }
        .user-info {
            margin-top: 15px;
            color: #555;
            font-size: 15px;
        }
        img.logo-main {
            width: 240px;
            height: auto;
            margin-bottom: 20px;
        }
        .btn-back {
            display: inline-block;
            margin-top: 40px;
            background-color: #d60c0c;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 34px;
            font-weight: 600;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s ease-in-out;
        }
        .btn-back:hover {
            background-color: #b70a0a;
            text-decoration: none;
            color: #fff;
        }
    </style>
</head>
<body>

<!-- 🟥 LOGO principal -->
<img src="{{ asset('imagens/LOGO.png') }}" alt="ScoutTalent" class="logo-main">

<h2>Obrigado por fazer o seu pedido de acesso!</h2>
<p>O seu pedido foi recebido e será analisado pela nossa equipa.</p>

<div class="user-info">
    <strong>Nome:</strong> {{ session('name') }}<br>
    <strong>Email:</strong> {{ session('email') }}<br>
    <strong>País:</strong> {{ session('country') }}
</div>

<p style="margin-top: 20px;">
    Receberá uma resposta por email assim que o seu pedido for avaliado.
</p>

<!-- 🔙 Botão voltar -->
<a href="{{ route('login') }}" class="btn-back">Voltar ao Login</a>

</body>
</html>
