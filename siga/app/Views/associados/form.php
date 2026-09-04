<?php $a = $associado ?? []; ?>

<h1><?= htmlspecialchars($titulo) ?></h1>
<p class="subtitulo">Preencha os dados abaixo para registar um novo associado na UEP.</p>

<form action="/associados/criar" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>1. Identificação</legend>

        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Nome">Nome completo *</label>
                <input type="text" id="Nome" name="Nome" required value="<?= htmlspecialchars($a['Nome'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="NumeroAssociado">Número de associado</label>
                <input type="text" id="NumeroAssociado" name="NumeroAssociado" value="<?= htmlspecialchars($a['NumeroAssociado'] ?? '') ?>">
                <small>Deixe em branco para atribuição posterior.</small>
            </div>

            <div class="campo">
                <label for="DataNascimento">Data de nascimento *</label>
                <input type="text" id="DataNascimento" name="DataNascimento" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($a['DataNascimento'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="DataInscricao">Data de inscrição *</label>
                <input type="text" id="DataInscricao" name="DataInscricao" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($a['DataInscricao'] ?? \App\Core\Data::hojePt()) ?>">
                <small>Sugerida a data de hoje; pode ser alterada para uma data anterior, mas nunca posterior a hoje.</small>
            </div>

            <div class="campo">
                <label for="Genero">Género *</label>
                <select id="Genero" name="Genero" required onchange="document.getElementById('grupo-nominativo-outro').style.display = this.value === 'O' ? 'block' : 'none';">
                    <option value="">Seleccionar…</option>
                    <option value="M" <?= ($a['Genero'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= ($a['Genero'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                    <option value="O" <?= ($a['Genero'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div class="campo" id="grupo-nominativo-outro" style="<?= ($a['Genero'] ?? '') === 'O' ? '' : 'display:none' ?>">
                <label for="NominativoOutro">Nominativo a utilizar *</label>
                <input type="text" id="NominativoOutro" name="NominativoOutro" value="<?= htmlspecialchars($a['NominativoOutro'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="IdNacionalidade">Nacionalidade</label>
                <select id="IdNacionalidade" name="IdNacionalidade">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($nacionalidades as $n): ?>
                        <option value="<?= (int) $n['Id'] ?>" <?= (string) ($a['IdNacionalidade'] ?? '') === (string) $n['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($n['Designacao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="IdEstadoCivil">Estado civil</label>
                <select id="IdEstadoCivil" name="IdEstadoCivil">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($estadosCivis as $ec): ?>
                        <option value="<?= (int) $ec['Id'] ?>" <?= (string) ($a['IdEstadoCivil'] ?? '') === (string) $ec['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ec['Designacao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="IdConfissaoReligiosa">Confissão religiosa</label>
                <select id="IdConfissaoReligiosa" name="IdConfissaoReligiosa">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($confissoes as $c): ?>
                        <option value="<?= (int) $c['Id'] ?>" <?= (string) ($a['IdConfissaoReligiosa'] ?? '') === (string) $c['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['Designacao']) ?>
                        </option>
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
        <legend>2. Documento de identificação</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="IdTipoDocumentoIdentificacao">Tipo de documento</label>
                <select id="IdTipoDocumentoIdentificacao" name="IdTipoDocumentoIdentificacao">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($tiposDocumento as $td): ?>
                        <option value="<?= (int) $td['Id'] ?>" <?= (string) ($a['IdTipoDocumentoIdentificacao'] ?? '') === (string) $td['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($td['Designacao']) ?>
                        </option>
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
        <legend>3. Morada e contactos</legend>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="Morada">Morada</label>
                <input type="text" id="Morada" name="Morada" value="<?= htmlspecialchars($a['Morada'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="CodPostal">Código postal</label>
                <input type="text" id="CodPostal" name="CodPostal" placeholder="0000-000" value="<?= htmlspecialchars($a['CodPostal'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="Localidade">Localidade</label>
                <input type="text" id="Localidade" name="Localidade" value="<?= htmlspecialchars($a['Localidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="Telemovel">Telemóvel</label>
                <input type="text" id="Telemovel" name="Telemovel" value="<?= htmlspecialchars($a['Telemovel'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="Telefone">Telefone</label>
                <input type="text" id="Telefone" name="Telefone" value="<?= htmlspecialchars($a['Telefone'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="Email">Email</label>
                <input type="email" id="Email" name="Email" value="<?= htmlspecialchars($a['Email'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="EmailAssociativo">Email associativo</label>
                <input type="email" id="EmailAssociativo" name="EmailAssociativo" value="<?= htmlspecialchars($a['EmailAssociativo'] ?? '') ?>">
                <small id="ajudaEmailAssociativo">Obrigatório para dirigentes (secção "Chefia").</small>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>4. Enquadramento na UEP</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="IdSecao">Secção</label>
                <select id="IdSecao" name="IdSecao" onchange="siga.actualizarObrigatoriedadeEmailAssociativo(this)">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($secoes as $s): ?>
                        <option value="<?= (int) $s['Id'] ?>" data-designacao="<?= htmlspecialchars($s['Designacao']) ?>" <?= (string) ($a['IdSecao'] ?? '') === (string) $s['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['Designacao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="IdCompanhia">Companhia (local)</label>
                <select id="IdCompanhia" name="IdCompanhia">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($companhias as $c): ?>
                        <option value="<?= (int) $c['Id'] ?>" <?= (string) ($a['IdCompanhia'] ?? '') === (string) $c['Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['Designacao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($chefiaNacional): ?>
        <div class="grelha-checkboxes" style="margin-top: 1rem;">
            <input type="hidden" name="IdCompanhiaChefiaNacional" value="<?= (int) $chefiaNacional['Id'] ?>">
            <label><input type="checkbox" name="ChefiaNacional" value="1" <?= !empty($a['ChefiaNacional']) ? 'checked' : '' ?>> Pertence também à Chefia Nacional</label>
        </div>
        <?php endif; ?>

        <?php if (!empty($orgaos)): ?>
        <div class="campo" style="margin-top: 1rem;">
            <label>Órgãos (pode seleccionar vários)</label>
            <div class="grelha-checkboxes">
                <?php foreach ($orgaos as $orgao): ?>
                    <label><input type="checkbox" name="Orgaos[]" value="<?= (int) $orgao['Id'] ?>"> <?= htmlspecialchars($orgao['Designacao']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($cargos)): ?>
        <div class="campo" style="margin-top: 1rem;">
            <label>Cargos (pode seleccionar vários — alguns acumulam)</label>
            <div class="grelha-checkboxes">
                <?php foreach ($cargos as $cargo): ?>
                    <label><input type="checkbox" name="Cargos[]" value="<?= (int) $cargo['Id'] ?>"> <?= htmlspecialchars($cargo['Designacao']) ?></label>
                <?php endforeach; ?>
            </div>
            <small>Aplicável apenas a dirigentes (secção "Chefia").</small>
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

    <fieldset>
        <legend>5. Encarregados de educação</legend>
        <p class="ajuda-fieldset">Preencha uma linha por encarregado de educação (normalmente aplicável a associados menores).</p>
        <div id="lista-encarregados">
            <?php for ($i = 0; $i < 2; $i++): ?>
            <div class="linha-repetivel">
                <input type="text" name="EncarregadosNome[]" placeholder="Nome completo">
                <select name="EncarregadosRelacao[]">
                    <option value="">Relação…</option>
                    <?php foreach ($tiposRelacao as $tr): ?>
                        <option value="<?= (int) $tr['Id'] ?>"><?= htmlspecialchars($tr['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="EncarregadosContacto[]" placeholder="Contacto (telemóvel)">
            </div>
            <?php endfor; ?>
        </div>
        <button type="button" class="botao botao-secundario botao-pequeno" onclick="siga.adicionarLinha('lista-encarregados', 'EncarregadosNome[]', 'EncarregadosRelacao[]', 'EncarregadosContacto[]')">+ Adicionar encarregado</button>
    </fieldset>

    <fieldset>
        <legend>6. Contactos de emergência</legend>
        <div id="lista-emergencia">
            <?php for ($i = 0; $i < 2; $i++): ?>
            <div class="linha-repetivel">
                <input type="text" name="EmergenciaNome[]" placeholder="Nome completo">
                <select name="EmergenciaRelacao[]">
                    <option value="">Relação…</option>
                    <?php foreach ($tiposRelacao as $tr): ?>
                        <option value="<?= (int) $tr['Id'] ?>"><?= htmlspecialchars($tr['Designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="EmergenciaContacto[]" placeholder="Contacto (telemóvel)">
            </div>
            <?php endfor; ?>
        </div>
        <button type="button" class="botao botao-secundario botao-pequeno" onclick="siga.adicionarLinha('lista-emergencia', 'EmergenciaNome[]', 'EmergenciaRelacao[]', 'EmergenciaContacto[]')">+ Adicionar contacto</button>
    </fieldset>

    <fieldset>
        <legend>7. Ficha de saúde</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="NumUente">Número de utente (ficha de saúde)</label>
                <input type="text" id="NumUente" name="NumUente" maxlength="9" value="<?= htmlspecialchars($a['NumUente'] ?? '') ?>">
            </div>
        </div>
        <div class="grelha-checkboxes">
            <label><input type="checkbox" name="Asma" value="1"> Asma</label>
            <label><input type="checkbox" name="Epilepsia" value="1"> Epilepsia</label>
            <label><input type="checkbox" name="Diabetes" value="1"> Diabetes</label>
            <label><input type="checkbox" name="Alergias" value="1"> Alergias</label>
        </div>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="DescAlergias">Descrição de alergias</label>
                <textarea id="DescAlergias" name="DescAlergias" rows="2"><?= htmlspecialchars($a['DescAlergias'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="MedicacaoRegular">Medicação regular</label>
                <textarea id="MedicacaoRegular" name="MedicacaoRegular" rows="2"><?= htmlspecialchars($a['MedicacaoRegular'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="RestricoesAlimentares">Restrições alimentares</label>
                <textarea id="RestricoesAlimentares" name="RestricoesAlimentares" rows="2"><?= htmlspecialchars($a['RestricoesAlimentares'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="Outros">Outras informações de saúde relevantes</label>
                <textarea id="Outros" name="Outros" rows="2"><?= htmlspecialchars($a['Outros'] ?? '') ?></textarea>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>8. Consentimentos (RGPD)</legend>
        <div class="grelha-checkboxes">
            <label><input type="checkbox" name="DadosPessoais" value="1"> Autorizo o tratamento de dados pessoais</label>
            <label><input type="checkbox" name="DadosSaude" value="1"> Autorizo o tratamento de dados de saúde</label>
            <label><input type="checkbox" name="DadosVozImagem" value="1"> Autorizo a captação de voz/imagem</label>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <a href="/associados" class="botao botao-secundario">Cancelar</a>
        <button type="submit" class="botao botao-primario">Guardar associado</button>
    </div>
</form>
