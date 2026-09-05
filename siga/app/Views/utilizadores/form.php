<?php $u = $utilizador ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Crie um novo acesso à aplicação, ligado a um associado.</p>

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
            <div class="campo campo-largo">
                <label for="IdAssociado">Associado ligado *</label>
                <select id="IdAssociado" name="IdAssociado" required>
                    <option value="">Seleccionar…</option>
                    <?php foreach ($associados as $associado): ?>
                        <option value="<?= (int) $associado['Id'] ?>" <?= (string) ($u['IdAssociado'] ?? '') === (string) $associado['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($associado['Nome']) ?><?= $associado['NumeroAssociado'] ? ' (nº ' . htmlspecialchars($associado['NumeroAssociado']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>
                    Obrigatório para todos os utilizadores (regra 4). O estatuto de administrador é automático:
                    se o associado ligado estiver na Chefia Nacional, o utilizador passa a administrador; caso
                    contrário, tem de ter uma companhia local.
                </small>
            </div>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/utilizadores" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Criar utilizador</button>
    </div>
</form>
