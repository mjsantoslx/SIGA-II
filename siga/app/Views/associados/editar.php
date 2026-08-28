<?php $a = $associado; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Edite os dados base do associado. Para alterar moradas, contactos ou encarregados de educação adicionais, contacte o backoffice.</p>

<form action="/associados/<?= (int) $a['Id'] ?>/editar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Identificação</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Nome">Nome completo *</label>
                <input type="text" id="Nome" name="Nome" required value="<?= htmlspecialchars($a['Nome']) ?>">
            </div>
            <div class="campo">
                <label for="NumeroAssociado">Número de associado</label>
                <input type="text" id="NumeroAssociado" name="NumeroAssociado" value="<?= htmlspecialchars($a['NumeroAssociado'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="DataNascimento">Data de nascimento *</label>
                <input type="date" id="DataNascimento" name="DataNascimento" required value="<?= htmlspecialchars($a['DataNascimento']) ?>">
            </div>
            <div class="campo">
                <label for="Genero">Género *</label>
                <select id="Genero" name="Genero" required onchange="document.getElementById('grupo-nominativo-outro').style.display = this.value === 'O' ? 'block' : 'none';">
                    <option value="M" <?= $a['Genero'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= $a['Genero'] === 'F' ? 'selected' : '' ?>>Feminino</option>
                    <option value="O" <?= $a['Genero'] === 'O' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
            <div class="campo" id="grupo-nominativo-outro" style="<?= $a['Genero'] === 'O' ? '' : 'display:none' ?>">
                <label for="NominativoOutro">Nominativo a utilizar *</label>
                <input type="text" id="NominativoOutro" name="NominativoOutro" value="<?= htmlspecialchars($a['NominativoOutro'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="IdNacionalidade">Nacionalidade</label>
                <select id="IdNacionalidade" name="IdNacionalidade">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($nacionalidades as $n): ?>
                        <option value="<?= (int) $n['Id'] ?>" <?= (string) ($a['IdNacionalidade'] ?? '') === (string) $n['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($n['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="IdEstadoCivil">Estado civil</label>
                <select id="IdEstadoCivil" name="IdEstadoCivil">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($estadosCivis as $ec): ?>
                        <option value="<?= (int) $ec['Id'] ?>" <?= (string) ($a['IdEstadoCivil'] ?? '') === (string) $ec['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($ec['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="IdConfissaoReligiosa">Confissão religiosa</label>
                <select id="IdConfissaoReligiosa" name="IdConfissaoReligiosa">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($confissoes as $c): ?>
                        <option value="<?= (int) $c['Id'] ?>" <?= (string) ($a['IdConfissaoReligiosa'] ?? '') === (string) $c['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="NomePai">Nome do pai</label>
                <input type="text" id="NomePai" name="NomePai" value="<?= htmlspecialchars($a['NomePai'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="NomeMae">Nome da mãe</label>
                <input type="text" id="NomeMae" name="NomeMae" value="<?= htmlspecialchars($a['NomeMae'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Documento de identificação</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="IdTipoDocumentoIdentificacao">Tipo de documento</label>
                <select id="IdTipoDocumentoIdentificacao" name="IdTipoDocumentoIdentificacao">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= (int) $td['Id'] ?>" <?= (string) ($a['IdTipoDocumentoIdentificacao'] ?? '') === (string) $td['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($td['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="NumeroDocumentoIdentificacao">Número do documento</label>
                <input type="text" id="NumeroDocumentoIdentificacao" name="NumeroDocumentoIdentificacao" value="<?= htmlspecialchars($a['NumeroDocumentoIdentificacao'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="NumeroCartaoUtente">Número de utente de saúde (9 dígitos)</label>
                <input type="text" id="NumeroCartaoUtente" name="NumeroCartaoUtente" pattern="\d{9}" maxlength="9" value="<?= htmlspecialchars($a['NumeroCartaoUtente'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Enquadramento na UEP</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="IdSecao">Secção actual</label>
                <select id="IdSecao" name="IdSecao">
                    <option value="">Manter secção actual</option>
                    <?php foreach ($secoes as $s): ?>
                        <option value="<?= (int) $s['Id'] ?>" <?= isset($secaoActual['IdSecao']) && (int) $secaoActual['IdSecao'] === (int) $s['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Seleccionar uma secção diferente encerra a atribuição actual e regista a nova, com histórico.</small>
            </div>
            <div class="campo">
                <label for="IdCompanhia">Companhia actual</label>
                <select id="IdCompanhia" name="IdCompanhia">
                    <option value="">Manter companhia actual</option>
                    <?php foreach ($companhias as $c): ?>
                        <option value="<?= (int) $c['Id'] ?>" <?= isset($companhiaActual['IdCompanhia']) && (int) $companhiaActual['IdCompanhia'] === (int) $c['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/associados/<?= (int) $a['Id'] ?>" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar alterações</button>
    </div>
</form>
