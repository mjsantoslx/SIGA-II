<?php $u = $utilizador; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Edite os dados de acesso. Deixe a palavra-passe em branco para a manter inalterada.</p>

<?php if ($ehSuperAdmin): ?>
    <div class="alerta alerta-erro">
        Este é o utilizador especial "Administrador": nunca pode ser desactivado, perder o estatuto de
        administrador, mudar de nome, ou ser ligado a um associado — essa é a regra 1 do sistema. Só o
        email e a palavra-passe podem ser alterados aqui.
    </div>
<?php endif; ?>

<form action="/utilizadores/<?= (int) $u['Id'] ?>/editar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados de acesso</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="Nome">Nome de utilizador *</label>
                <input type="text" id="Nome" name="Nome" required value="<?= htmlspecialchars($u['Nome']) ?>" <?= $ehSuperAdmin ? 'readonly' : '' ?>>
            </div>
            <div class="campo">
                <label for="Email">Email *</label>
                <input type="email" id="Email" name="Email" required value="<?= htmlspecialchars($u['Email']) ?>">
            </div>
            <div class="campo">
                <label for="PalavraPasse">Nova palavra-passe</label>
                <input type="password" id="PalavraPasse" name="PalavraPasse" autocomplete="new-password">
                <small>Deixe em branco para manter a actual. Mínimo 8 caracteres, se preenchida.</small>
            </div>

            <?php if (!$ehSuperAdmin): ?>
            <div class="campo campo-largo">
                <label for="IdAssociado">Associado ligado *</label>
                <select id="IdAssociado" name="IdAssociado" required>
                    <option value="">Seleccionar…</option>
                    <?php foreach ($associados as $associado): ?>
                        <option value="<?= (int) $associado['Id'] ?>" <?= $idAssociadoActual === (int) $associado['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($associado['Nome']) ?><?= $associado['NumeroAssociado'] ? ' (nº ' . htmlspecialchars($associado['NumeroAssociado']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>
                    Obrigatório (regra 4). O estatuto de administrador é automático: se o associado ligado
                    estiver na Chefia Nacional, o utilizador passa a administrador; caso contrário, tem de
                    ter uma companhia local.
                </small>
            </div>
            <?php endif; ?>
        </div>

        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label>
                Estatuto de administrador:
                <strong><?= $u['Administrador'] ? 'Sim' : 'Não' ?></strong>
            </label>
            <?php if (!$ehSuperAdmin): ?>
                <label><input type="checkbox" name="Activo" value="1" <?= $u['Activo'] ? 'checked' : '' ?>> Activo</label>
            <?php endif; ?>
        </div>
        <?php if (!$ehSuperAdmin): ?>
            <small>O estatuto de administrador (acima) é apenas informativo — recalculado automaticamente a partir do associado ligado. Não é possível remover os seus próprios privilégios, desactivar a sua própria conta, nem remover o único administrador activo do sistema.</small>
        <?php endif; ?>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/utilizadores" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar alterações</button>
    </div>
</form>
