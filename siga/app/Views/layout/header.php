<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? $config['app']['nome']) ?> — SIGA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="cabecalho">
    <div class="cabecalho-marca">
        <a href="/" class="cabecalho-logo-fundo"><img src="/assets/img/logo-header-uep.png" alt="União dos Escoteiros Portugueses" class="cabecalho-logo"></a>
    </div>

    <?php if ($utilizadorAutenticado): ?>
    <nav class="cabecalho-nav">
        <a href="/">Painel</a>
        <a href="/associados">Associados</a>
        <a href="/companhias">Companhias</a>
        <?php if (\App\Core\Sessao::ehAdministrador()): ?>
            <a href="/utilizadores">Utilizadores</a>
        <?php endif; ?>
    </nav>
    <div class="cabecalho-utilizador">
        <span><?= htmlspecialchars($utilizadorAutenticado['Nome']) ?></span>
        <form action="/logout" method="post" class="forma-inline">
            <button type="submit" class="ligacao-botao">Sair</button>
        </form>
    </div>
    <?php endif; ?>
</header>

<main class="conteudo-principal">
    <?php foreach (\App\Core\Sessao::obterMensagens() as $mensagem): ?>
        <div class="alerta alerta-<?= htmlspecialchars($mensagem['tipo']) ?>">
            <?= htmlspecialchars($mensagem['texto']) ?>
        </div>
    <?php endforeach; ?>
