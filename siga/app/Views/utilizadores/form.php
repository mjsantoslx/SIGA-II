<?php $u = $utilizador ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Crie um novo acesso à aplicação.</p>

<form action="/utilizadores/criar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados de acesso</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="Nome">Nome de utilizador *</label>
                <input type="text" id="Nome" name="Nome" required value="<?= htmlspecialchars($u['Nome'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="Email">Email *</label>
                <input type="email" id="Email" name="Email" required value="<?= htmlspecialchars($u['Email'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="PalavraPasse">Palavra-passe *</label>
                <input type="password" id="PalavraPasse" name="PalavraPasse" required autocomplete="new-password">
                <small>Mínimo 8 caracteres.</small>
            </div>
        </div>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="Administrador" value="1" <?= !empty($u['Administrador']) ? 'checked' : '' ?>> É administrador</label>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/utilizadores" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Criar utilizador</button>
    </div>
</form>
