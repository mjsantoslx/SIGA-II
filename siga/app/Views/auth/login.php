<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — SIGA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="pagina-login">
    <div class="painel-login">
        <div class="painel-login-marca">
            <span class="cabecalho-brasao">⚜</span>
            <h1>SIGA</h1>
            <p>Sistema Integrado de Gestão de Associados<br>União dos Escoteiros Portugueses</p>
        </div>

        <?php foreach (\App\Core\Sessao::obterMensagens() as $mensagem): ?>
            <div class="alerta alerta-<?= htmlspecialchars($mensagem['tipo']) ?>">
                <?= htmlspecialchars($mensagem['texto']) ?>
            </div>
        <?php endforeach; ?>

        <form action="/login" method="post" class="formulario-login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <label for="identificador">Utilizador ou email</label>
            <input type="text" id="identificador" name="identificador" autocomplete="username" required autofocus>

            <label for="palavra_passe">Palavra-passe</label>
            <input type="password" id="palavra_passe" name="palavra_passe" autocomplete="current-password" required>

            <button type="submit" class="botao botao-primario botao-largo">Entrar</button>
        </form>
    </div>
</body>
</html>
