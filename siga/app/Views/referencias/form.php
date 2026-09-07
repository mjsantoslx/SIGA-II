<?php $r = $registo ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo"><a href="<?= \App\Core\Url::para() ?>/admin/referencias/<?= htmlspecialchars($cfg['chave']) ?>">← Voltar a <?= htmlspecialchars(strtolower($cfg['titulo'])) ?></a></p>

<form action="<?= isset($r['Id']) ? '/admin/referencias/' . htmlspecialchars($cfg['chave']) . '/' . (int) $r['Id'] . '/editar' : '/admin/referencias/' . htmlspecialchars($cfg['chave']) . '/criar' ?>" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Valor">Designação *</label>
                <input type="text" id="Valor" name="Valor" required value="<?= htmlspecialchars($r[$cfg['coluna']] ?? '') ?>">
            </div>
        </div>
        <?php if ($cfg['activo'] && isset($r['Id'])): ?>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="Activo" value="1" <?= !empty($r['Activo']) ? 'checked' : '' ?>> Activo</label>
        </div>
        <?php endif; ?>
    </fieldset>

    <div class="acoes-formulario">
        <a href="<?= \App\Core\Url::para() ?>/admin/referencias/<?= htmlspecialchars($cfg['chave']) ?>" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar</button>
    </div>
</form>
