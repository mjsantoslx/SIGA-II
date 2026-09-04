<div class="cabecalho-pagina">
    <div>
        <h1><?= htmlspecialchars($associado['Nome']) ?></h1>
        <p class="subtitulo">
            Nº <?= htmlspecialchars($associado['NumeroAssociado'] ?? '—') ?>
            &middot;
            <span class="etiqueta etiqueta-<?= $associado['Activo'] ? 'ativo' : 'inativo' ?>">
                <?= $associado['Activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
        </p>
    </div>
    <div class="acoes-rapidas">
        <a href="/associados/<?= (int) $associado['Id'] ?>/editar" class="botao botao-secundario">Editar</a>
        <a href="/associados/<?= (int) $associado['Id'] ?>/contactos" class="botao botao-secundario">Gerir contactos</a>
    </div>
</div>

<?php if ($associado['Activo']): ?>
    <details class="cartao-accao">
        <summary class="botao botao-perigo">Desactivar associado</summary>
        <form action="/associados/<?= (int) $associado['Id'] ?>/desativar" method="post" class="formulario-accao" onsubmit="return confirm('Confirma a desactivação deste associado?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="campo">
                <label for="DataEventoDesactivacao">Data de desactivação *</label>
                <input type="text" id="DataEventoDesactivacao" name="DataEvento" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($hojePt) ?>">
            </div>
            <div class="campo campo-largo">
                <label for="ObservacoesDesactivacao">Observações (opcional)</label>
                <input type="text" id="ObservacoesDesactivacao" name="Observacoes" placeholder="Motivo da desactivação">
            </div>
            <button type="submit" class="botao botao-perigo">Confirmar desactivação</button>
        </form>
    </details>
<?php else: ?>
    <details class="cartao-accao" open>
        <summary class="botao botao-primario">Reactivar associado</summary>
        <form action="/associados/<?= (int) $associado['Id'] ?>/reativar" method="post" class="formulario-accao">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="campo">
                <label for="DataEventoReactivacao">Data de reactivação *</label>
                <input type="text" id="DataEventoReactivacao" name="DataEvento" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($hojePt) ?>">
            </div>
            <div class="campo campo-largo">
                <label for="ObservacoesReactivacao">Observações (opcional)</label>
                <input type="text" id="ObservacoesReactivacao" name="Observacoes" placeholder="Motivo da reactivação">
            </div>
            <p class="ajuda-fieldset">A reactivação não associa automaticamente nenhuma companhia — se necessário, faça-o depois em "Editar".</p>
            <button type="submit" class="botao botao-primario">Confirmar reactivação</button>
        </form>
    </details>
<?php endif; ?>

<div class="grelha-detalhe">
    <section class="cartao-seccao">
        <h2>Identificação</h2>
        <dl class="lista-definicao">
            <dt>Data de nascimento</dt><dd><?= htmlspecialchars(\App\Core\Data::paraApresentacao($associado['DataNascimento'])) ?></dd>
            <dt>Género</dt><dd><?= htmlspecialchars(['M' => 'Masculino', 'F' => 'Feminino', 'O' => $associado['NominativoOutro'] ?? 'Outro'][$associado['Genero']] ?? '—') ?></dd>
            <dt>Data de inscrição</dt><dd><?= htmlspecialchars(\App\Core\Data::paraApresentacao($associado['DataInscricao'])) ?></dd>
            <dt>Nome do pai</dt><dd><?= htmlspecialchars($associado['NomePai'] ?? '—') ?></dd>
            <dt>Nome da mãe</dt><dd><?= htmlspecialchars($associado['NomeMae'] ?? '—') ?></dd>
            <dt>Documento</dt><dd><?= htmlspecialchars($associado['NumeroDocumentoIdentificacao'] ?? '—') ?></dd>
            <dt>Nº utente saúde</dt><dd><?= htmlspecialchars($associado['NumeroCartaoUtente'] ?? '—') ?></dd>
            <dt>Formador</dt><dd><?= $associado['Formador'] ? 'Sim' : 'Não' ?></dd>
            <dt>Insígnia de madeira</dt>
            <dd>
                <?php if ($associado['InsigniaMadeira']): ?>
                    Sim (desde <?= htmlspecialchars(\App\Core\Data::paraApresentacao($associado['DataInsigniaMadeira'])) ?>)
                <?php else: ?>
                    Não
                <?php endif; ?>
            </dd>
        </dl>
    </section>

    <section class="cartao-seccao">
        <h2>Enquadramento na UEP</h2>
        <dl class="lista-definicao">
            <dt>Secção</dt><dd><?= htmlspecialchars($secaoActual['Designacao'] ?? '—') ?></dd>
            <dt>Companhia local</dt><dd><?= htmlspecialchars($companhiaActual['Designacao'] ?? '—') ?></dd>
            <dt>Chefia Nacional</dt><dd><?= $chefiaNacionalActual ? 'Sim' : 'Não' ?></dd>
            <dt>Órgãos</dt><dd><?= $orgaos ? htmlspecialchars(implode(', ', array_column($orgaos, 'Designacao'))) : '—' ?></dd>
            <dt>Cargos</dt><dd><?= $cargos ? htmlspecialchars(implode(', ', array_column($cargos, 'Designacao'))) : '—' ?></dd>
        </dl>
    </section>

    <section class="cartao-seccao">
        <h2>Morada e contactos</h2>
        <?php if ($morada): ?>
            <p><?= htmlspecialchars($morada['Morada']) ?><br>
               <?= htmlspecialchars($morada['CodPostal'] ?? '') ?> <?= htmlspecialchars($morada['Localidade'] ?? '') ?></p>
        <?php else: ?>
            <p class="texto-vazio">Sem morada registada.</p>
        <?php endif; ?>
        <?php if ($contactos): ?>
            <ul class="lista-simples">
                <?php foreach ($contactos as $c): ?>
                    <li><strong><?= htmlspecialchars($c['TipoContacto']) ?>:</strong> <?= htmlspecialchars($c['Valor']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="cartao-seccao">
        <h2>Encarregados de educação</h2>
        <?php if ($encarregados): ?>
            <ul class="lista-simples">
                <?php foreach ($encarregados as $ee): ?>
                    <li><?= htmlspecialchars($ee['Nome']) ?> — <?= htmlspecialchars($ee['TipoRelacao']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="texto-vazio">Nenhum encarregado de educação registado.</p>
        <?php endif; ?>
    </section>

    <section class="cartao-seccao">
        <h2>Contactos de emergência</h2>
        <?php if ($emergencia): ?>
            <ul class="lista-simples">
                <?php foreach ($emergencia as $ce): ?>
                    <li><?= htmlspecialchars($ce['Nome']) ?> — <?= htmlspecialchars($ce['TipoRelacao']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="texto-vazio">Nenhum contacto de emergência registado.</p>
        <?php endif; ?>
    </section>

    <section class="cartao-seccao">
        <h2>Ficha de saúde</h2>
        <?php if ($fichaSaude): ?>
            <ul class="lista-simples">
                <li>Asma: <?= $fichaSaude['Asma'] ? 'Sim' : 'Não' ?></li>
                <li>Epilepsia: <?= $fichaSaude['Epilepsia'] ? 'Sim' : 'Não' ?></li>
                <li>Diabetes: <?= $fichaSaude['Diabetes'] ? 'Sim' : 'Não' ?></li>
                <li>Alergias: <?= $fichaSaude['Alergias'] ? 'Sim' : 'Não' ?><?= $fichaSaude['DescAlergias'] ? ' — ' . htmlspecialchars($fichaSaude['DescAlergias']) : '' ?></li>
                <?php if ($fichaSaude['MedicacaoRegular']): ?><li>Medicação: <?= htmlspecialchars($fichaSaude['MedicacaoRegular']) ?></li><?php endif; ?>
                <?php if ($fichaSaude['RestricoesAlimentares']): ?><li>Restrições alimentares: <?= htmlspecialchars($fichaSaude['RestricoesAlimentares']) ?></li><?php endif; ?>
            </ul>
        <?php else: ?>
            <p class="texto-vazio">Sem ficha de saúde registada.</p>
        <?php endif; ?>
    </section>

    <section class="cartao-seccao">
        <h2>Consentimentos</h2>
        <?php if ($consentimento): ?>
            <ul class="lista-simples">
                <li>Dados pessoais: <?= $consentimento['DadosPessoais'] ? 'Autorizado' : 'Não autorizado' ?></li>
                <li>Dados de saúde: <?= $consentimento['DadosSaude'] ? 'Autorizado' : 'Não autorizado' ?></li>
                <li>Voz/imagem: <?= $consentimento['DadosVozImagem'] ? 'Autorizado' : 'Não autorizado' ?></li>
            </ul>
        <?php else: ?>
            <p class="texto-vazio">Sem registo de consentimentos.</p>
        <?php endif; ?>
    </section>

    <section class="cartao-seccao cartao-seccao-larga">
        <h2>Histórico de eventos</h2>
        <?php if ($eventos): ?>
            <ul class="lista-simples">
                <?php foreach ($eventos as $ev): ?>
                    <li><?= htmlspecialchars(\App\Core\Data::paraApresentacao($ev['DataEvento'])) ?> — <?= htmlspecialchars($ev['TipoEvento']) ?><?= $ev['Observacoes'] ? ' (' . htmlspecialchars($ev['Observacoes']) . ')' : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="texto-vazio">Sem eventos registados.</p>
        <?php endif; ?>
    </section>
</div>
