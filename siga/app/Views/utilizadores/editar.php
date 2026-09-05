<?php $u = $utilizador; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Edite os dados de acesso. Deixe a palavra-passe em branco para a manter inalterada.</p>

<form action="/utilizadores/<?= (int) $u['Id'] ?>/editar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados de acesso</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="Nome">Nome de utilizador *</label>
                <input type="text" id="Nome" name="Nome" required value="<?= htmlspecialchars($u['Nome']) ?>">
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
        </div>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="Administrador" value="1" <?= $u['Administrador'] ? 'checked' : '' ?>> É administrador</label>
            <label><input type="checkbox" name="Activo" value="1" <?= $u['Activo'] ? 'checked' : '' ?>> Activo</label>
        </div>
        <small>Não é possível remover os seus próprios privilégios de administrador, desactivar a sua própria conta, nem remover o único administrador activo do sistema.</small>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/utilizadores" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar alterações</button>
    </div>
</form>
