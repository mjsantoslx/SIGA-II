<?php $c = $companhia; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Edite os dados base da companhia. A morada é gerida na ficha da companhia.</p>

<form action="/companhias/<?= (int) $c['Id'] ?>/editar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados da companhia</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Designacao">Designação *</label>
                <input type="text" id="Designacao" name="Designacao" required value="<?= htmlspecialchars($c['Designacao']) ?>">
            </div>
        </div>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="AmbitoGlobal" value="1" <?= $c['ambito_global'] ? 'checked' : '' ?>> Âmbito nacional (Chefia Nacional)</label>
            <label><input type="checkbox" name="Activo" value="1" <?= $c['Activo'] ? 'checked' : '' ?>> Activa</label>
        </div>
        <small>Só pode existir uma companhia de âmbito nacional em simultâneo.</small>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/companhias" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar alterações</button>
    </div>
</form>
