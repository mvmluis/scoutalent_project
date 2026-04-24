<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Aprovado — ScoutTalent</title>
    <link rel="icon" type="image/png" href="{{ asset('imagens/novologo.png') }}">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #fff;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        .card {
            padding: 40px 60px;
            border: 2px solid #e60000;
            border-radius: 14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .logo {
            width: 120px;
            margin-bottom: 25px;
        }
        h2 {
            font-weight: 600;
            color: #222;
        }
        p {
            margin-top: 10px;
            color: #555;
        }
        .success {
            color: #0d9426;
            font-size: 48px;
            margin-bottom: 10px;
        }
        a.button {
            margin-top: 25px;
            display: inline-block;
            background: #e60000;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        a.button:hover {
            background: #c40808;
        }
    </style>
</head>
<body>

<div class="card">
    <img src="{{ asset('imagens/LOGO.png') }}" alt="ScoutTalent Logo" class="logo">
    <div class="success">✅</div>
    <h2>Pedido aprovado com sucesso</h2>
    <p>O utilizador <strong>{{ $req->email }}</strong> foi aprovado.</p>
    <p>Foi enviado um email automático com as credenciais de acesso.</p>

    <a href="{{ url('/') }}" class="button">Fechar</a>
</div>

</body>
</html>
