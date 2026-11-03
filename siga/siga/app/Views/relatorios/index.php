<h1>Relatórios</h1>
<p class="subtitulo">Consulta no ecrã e exportação para Excel (.xlsx). Cada utilizador só vê os dados a que a sua companhia dá acesso.</p>

<div class="grelha-admin">
    <?php foreach ($relatorios as $chave => [$nome, $descricao]): ?>
        <a href="<?= \App\Core\Url::para() ?>/relatorios/<?= htmlspecialchars($chave) ?>" class="cartao-admin">
            <strong><?= htmlspecialchars($nome) ?></strong>
            <span><?= htmlspecialchars($descricao) ?></span>
        </a>
    <?php endforeach; ?>
</div>
