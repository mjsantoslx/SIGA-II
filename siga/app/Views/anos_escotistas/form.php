<?php $a = $ano ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo"><a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas">← Voltar aos anos escotistas</a></p>

<form action="<?= isset($a['Id']) ? '/admin/anos-escotistas/' . (int) $a['Id'] . '/editar' : '/admin/anos-escotistas/criar' ?>" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Valores do Censo</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="AnoInicio">Ano de início (Outubro) *</label>
                <input type="number" id="AnoInicio" name="AnoInicio" required min="2000" max="2100" value="<?= htmlspecialchars($a['AnoInicio'] ?? '') ?>">
                <small>Ex.: 2026 para o ano escotista 2026/2027.</small>
            </div>
            <div class="campo">
                <label for="ValorSeguroEscotista">Seguro escotista (€) *</label>
                <input type="text" id="ValorSeguroEscotista" name="ValorSeguroEscotista" required inputmode="decimal" value="<?= htmlspecialchars($a['ValorSeguroEscotista'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="ValorQuotaUEP">Quota UEP (€) *</label>
                <input type="text" id="ValorQuotaUEP" name="ValorQuotaUEP" required inputmode="decimal" value="<?= htmlspecialchars($a['ValorQuotaUEP'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="ValorQuotaWFIS">Quota WFIS (€) *</label>
                <input type="text" id="ValorQuotaWFIS" name="ValorQuotaWFIS" required inputmode="decimal" value="<?= htmlspecialchars($a['ValorQuotaWFIS'] ?? '') ?>">
            </div>
        </div>
        <?php if (isset($a['Id'])): ?>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="Activo" value="1" <?= !empty($a['Activo']) ? 'checked' : '' ?>> Activo</label>
        </div>
        <?php endif; ?>
    </fieldset>

    <div class="acoes-formulario">
        <a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar</button>
    </div>
</form>
