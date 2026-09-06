<?php $d = $divisao ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo"><a href="/admin/divisoes">← Voltar às divisões</a></p>

<form action="<?= isset($d['Id']) ? '/admin/divisoes/' . (int) $d['Id'] . '/editar' : '/admin/divisoes/criar' ?>" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados da divisão</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Designacao">Designação *</label>
                <input type="text" id="Designacao" name="Designacao" required value="<?= htmlspecialchars($d['Designacao'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="NominativoMasculino">Nominativo masculino *</label>
                <input type="text" id="NominativoMasculino" name="NominativoMasculino" required value="<?= htmlspecialchars($d['NominativoMasculino'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="NominativoFeminino">Nominativo feminino *</label>
                <input type="text" id="NominativoFeminino" name="NominativoFeminino" required value="<?= htmlspecialchars($d['NominativoFeminino'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/admin/divisoes" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar</button>
    </div>
</form>
