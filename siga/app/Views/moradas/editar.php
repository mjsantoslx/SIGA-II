<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">
    Existem duas formas distintas de alterar uma morada: corrigir um erro na morada
    actual (afecta todos os que a partilham) ou substituí-la por uma morada nova,
    mantendo a anterior no histórico.
</p>

<?php if ($ligacao): ?>
    <section class="cartao-seccao" style="margin-bottom: 1.5rem;">
        <h2>Morada actual</h2>
        <p><?= htmlspecialchars($ligacao['Morada']) ?><br>
           <?= htmlspecialchars($ligacao['CodPostal'] ?? '') ?> <?= htmlspecialchars($ligacao['Localidade'] ?? '') ?></p>
        <?php if ($partilhas > 1): ?>
            <p class="texto-vazio">Esta morada é partilhada por mais <?= $partilhas - 1 ?> registo(s) — corrigi-la afecta todos eles.</p>
        <?php endif; ?>
    </section>
<?php else: ?>
    <p class="texto-vazio">Ainda não existe morada registada. Utilize o formulário "Registar morada" abaixo.</p>
<?php endif; ?>

<div class="grelha-detalhe">
    <?php if ($ligacao): ?>
    <section class="cartao-seccao">
        <h2>Corrigir morada actual</h2>
        <p class="ajuda-fieldset">Use isto para corrigir um erro (ex.: número de porta). A alteração é visível para todos os que partilham esta morada.</p>
        <form action="<?= htmlspecialchars($urlCorrigir) ?>" method="post" class="formulario-morada">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="campo">
                <label for="MoradaCorrigir">Morada</label>
                <input type="text" id="MoradaCorrigir" name="Morada" required value="<?= htmlspecialchars($ligacao['Morada']) ?>">
            </div>
            <div class="campo">
                <label for="CodPostalCorrigir">Código postal</label>
                <input type="text" id="CodPostalCorrigir" name="CodPostal" placeholder="0000-000" value="<?= htmlspecialchars($ligacao['CodPostal'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="LocalidadeCorrigir">Localidade</label>
                <input type="text" id="LocalidadeCorrigir" name="Localidade" value="<?= htmlspecialchars($ligacao['Localidade'] ?? '') ?>">
            </div>
            <button type="submit" class="botao botao-secundario">Guardar correcção</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="cartao-seccao">
        <h2><?= $ligacao ? 'Substituir por morada nova' : 'Registar morada' ?></h2>
        <p class="ajuda-fieldset">Use isto quando a entidade se mudou de facto de morada. A morada anterior fica preservada no histórico.</p>
        <form action="<?= htmlspecialchars($urlSubstituir) ?>" method="post" class="formulario-morada">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="campo">
                <label for="MoradaNova">Morada</label>
                <input type="text" id="MoradaNova" name="Morada" required>
            </div>
            <div class="campo">
                <label for="CodPostalNovo">Código postal</label>
                <input type="text" id="CodPostalNovo" name="CodPostal" placeholder="0000-000">
            </div>
            <div class="campo">
                <label for="LocalidadeNova">Localidade</label>
                <input type="text" id="LocalidadeNova" name="Localidade">
            </div>
            <div class="campo">
                <label for="DataInicioNova">Data de início *</label>
                <input type="text" id="DataInicioNova" name="DataInicio" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($hojePt) ?>">
            </div>
            <button type="submit" class="botao botao-primario"><?= $ligacao ? 'Substituir morada' : 'Registar morada' ?></button>
        </form>
    </section>
</div>

<p style="margin-top: 1.5rem;"><a href="<?= htmlspecialchars($urlVoltar) ?>" class="botao botao-secundario">← Voltar</a></p>
