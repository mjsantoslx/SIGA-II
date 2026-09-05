<?php $c = $companhia ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Crie uma nova companhia. A morada pode ser adicionada depois, na ficha da companhia.</p>

<form action="/companhias/criar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados da companhia</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Designacao">Designação *</label>
                <input type="text" id="Designacao" name="Designacao" required value="<?= htmlspecialchars($c['Designacao'] ?? '') ?>">
            </div>
        </div>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="AmbitoGlobal" value="1" <?= !empty($c['AmbitoGlobal']) ? 'checked' : '' ?>> Âmbito nacional (Chefia Nacional)</label>
        </div>
        <small>Só pode existir uma companhia de âmbito nacional em simultâneo.</small>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/companhias" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Criar companhia</button>
    </div>
</form>
