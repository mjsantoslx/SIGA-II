<h1>Painel principal</h1>
<p class="subtitulo">Resumo do estado atual dos associados da UEP.</p>

<div class="grelha-cartoes">
    <div class="cartao cartao-destaque">
        <span class="cartao-numero"><?= (int) $estados['ativos'] ?></span>
        <span class="cartao-legenda">Associados activos</span>
    </div>
    <div class="cartao">
        <span class="cartao-numero"><?= (int) $estados['inativos'] ?></span>
        <span class="cartao-legenda">Associados inactivos</span>
    </div>
    <div class="cartao">
        <span class="cartao-numero"><?= (int) $estados['ativos'] + (int) $estados['inativos'] ?></span>
        <span class="cartao-legenda">Total registado</span>
    </div>
</div>

<section class="seccao">
    <h2>Associados por secção</h2>
    <?php if (empty($porSecao)): ?>
        <p class="texto-vazio">Ainda não existem associados atribuídos a secções.</p>
    <?php else: ?>
        <ul class="lista-barras">
            <?php $maximo = max(array_column($porSecao, 'Total')); ?>
            <?php foreach ($porSecao as $linha): ?>
                <li>
                    <span class="lista-barras-rotulo"><?= htmlspecialchars($linha['Designacao']) ?></span>
                    <span class="lista-barras-barra">
                        <span style="width: <?= $maximo > 0 ? (int) round($linha['Total'] / $maximo * 100) : 0 ?>%"></span>
                    </span>
                    <span class="lista-barras-valor"><?= (int) $linha['Total'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<div class="acoes-rapidas">
    <a href="/associados/criar" class="botao botao-primario">+ Novo associado</a>
    <a href="/associados" class="botao botao-secundario">Ver todos os associados</a>
</div>
