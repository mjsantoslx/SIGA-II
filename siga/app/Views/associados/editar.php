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
                <input type="text" id="NumeroAssociado" value="<?= htmlspecialchars($a['NumeroAssociado'] ?? '') ?>" readonly disabled>
                <small>Atribuído automaticamente no registo — não é editável.</small>
            </div>
            <div class="campo">
                <label for="DataNascimento">Data de nascimento *</label>
                <input type="text" id="DataNascimento" name="DataNascimento" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($a['DataNascimento']) ?>">
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
                <small>Em uso normal só é possível avançar (ou saltar para a frente) na sequência Colónia → Alcateia → Tribo Júnior → Tribo Sénior → Clã → Chefia — nunca recuar. Para "Chefia", o associado já tem de ter um contacto "Email Associativo" (em "Gerir contactos").</small>
            </div>
            <div class="campo campo-largo">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <input type="checkbox" name="CorrecaoSecao" value="1" style="width: auto;">
                    Isto é uma correcção (a mudança de secção pode representar um recuo)
                </label>
                <small>Só assinale isto para corrigir um erro. A correcção fica sempre registada no histórico de eventos do associado.</small>
                <input type="text" name="MotivoCorrecaoSecao" placeholder="Motivo da correcção (opcional, mas recomendado)" style="margin-top: 0.4rem;">
            </div>
            <div class="campo">
                <label for="IdCompanhia">Companhia local actual</label>
                <select id="IdCompanhia" name="IdCompanhia">
                    <option value="">Manter companhia local actual</option>
                    <?php foreach ($companhias as $c): ?>
                        <option value="<?= (int) $c['Id'] ?>" <?= isset($companhiaActual['IdCompanhia']) && (int) $companhiaActual['IdCompanhia'] === (int) $c['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Pode coexistir com a Chefia Nacional e com órgãos, abaixo.</small>
            </div>
        </div>

        <?php if ($chefiaNacional): ?>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="ChefiaNacional" value="1" <?= $naChefiaNacional ? 'checked' : '' ?>> Pertence à Chefia Nacional</label>
        </div>
        <?php endif; ?>

        <?php if (!empty($orgaos)): ?>
        <div class="campo" style="margin-top: 1rem;">
            <label>Órgãos (pode seleccionar vários)</label>
            <div class="grelha-checkboxes">
                <?php foreach ($orgaos as $orgao): ?>
                    <label><input type="checkbox" name="Orgaos[]" value="<?= (int) $orgao['Id'] ?>" <?= in_array((int) $orgao['Id'], $idsOrgaosActuais, true) ? 'checked' : '' ?>> <?= htmlspecialchars($orgao['Designacao']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($cargos)): ?>
        <div class="campo" style="margin-top: 1rem;">
            <label>Cargos (pode seleccionar vários — alguns acumulam)</label>
            <div class="grelha-checkboxes">
                <?php foreach ($cargos as $cargo): ?>
                    <label><input type="checkbox" name="Cargos[]" value="<?= (int) $cargo['Id'] ?>" <?= in_array((int) $cargo['Id'], $idsCargosActuais, true) ? 'checked' : '' ?>> <?= htmlspecialchars($cargo['Designacao']) ?></label>
                <?php endforeach; ?>
            </div>
            <small>Aplicável apenas a dirigentes (secção "Chefia"), excepto "Equipa Nacional de Clã", exclusivo de associados na secção "Clã".</small>
        </div>
        <?php endif; ?>

        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <label><input type="checkbox" name="Formador" value="1" <?= !empty($a['Formador']) ? 'checked' : '' ?>> É formador</label>
            <label><input type="checkbox" id="InsigniaMadeira" name="InsigniaMadeira" value="1" <?= !empty($a['InsigniaMadeira']) ? 'checked' : '' ?> onchange="siga.actualizarObrigatoriedadeDataInsignia(this)"> Tem insígnia de madeira</label>
        </div>
        <small>Aplicável apenas a dirigentes (secção "Chefia").</small>
        <div class="campo" id="grupo-data-insignia" style="margin-top: 0.6rem; max-width: 220px; <?= !empty($a['InsigniaMadeira']) ? '' : 'display:none' ?>">
            <label for="DataInsigniaMadeira">Data de atribuição *</label>
            <input type="text" id="DataInsigniaMadeira" name="DataInsigniaMadeira" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" value="<?= htmlspecialchars($a['DataInsigniaMadeira'] ?? '') ?>">
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/associados/<?= (int) $a['Id'] ?>" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar alterações</button>
    </div>
</form>
