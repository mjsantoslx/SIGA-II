<div class="cabecalho-pagina">
    <div>
        <h1><?= htmlspecialchars($companhia['Designacao']) ?></h1>
        <p class="subtitulo"><?= $companhia['ambito_global'] ? 'Âmbito nacional' : 'Âmbito local' ?></p>
    </div>
    <a href="/companhias/<?= (int) $companhia['Id'] ?>/morada/editar" class="botao botao-secundario">Gerir morada</a>
</div>

<section class="cartao-seccao">
    <h2>Morada</h2>
    <?php if ($morada): ?>
        <p><?= htmlspecialchars($morada['Morada']) ?><br>
           <?= htmlspecialchars($morada['CodPostal'] ?? '') ?> <?= htmlspecialchars($morada['Localidade'] ?? '') ?></p>
        <p class="texto-vazio">Morada activa desde <?= htmlspecialchars(\App\Core\Data::paraApresentacao($morada['LigacaoDataInicio'])) ?>.</p>
    <?php else: ?>
        <p class="texto-vazio">Sem morada registada.</p>
    <?php endif; ?>
</section>

<p style="margin-top: 1.5rem;"><a href="/companhias" class="botao botao-secundario">← Voltar às companhias</a></p>
