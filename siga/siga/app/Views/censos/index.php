<div class="cabecalho-pagina">
    <div>
        <h1>Censos</h1>
        <p class="subtitulo">Seguro escotista + quota UEP + quota WFIS.</p>
    </div>
    <?php if (\App\Core\Sessao::ehAdministrador()): ?>
        <a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas" class="botao botao-secundario">Gerir valores anuais</a>
    <?php endif; ?>
</div>

<?php if (empty($anos)): ?>
    <p class="texto-vazio">Ainda não existem anos escotistas definidos. <?= \App\Core\Sessao::ehAdministrador() ? '<a href="' . \App\Core\Url::para() . '/admin/anos-escotistas/criar">Criar o primeiro</a>.' : 'Peça a um administrador para os definir.' ?></p>
<?php else: ?>

<form action="<?= \App\Core\Url::para() ?>/censos" method="get" class="formulario-filtros">
    <select name="ano" onchange="this.form.submit()">
        <?php foreach ($anos as $ano): ?>
            <option value="<?= (int) $ano['Id'] ?>" <?= $anoSeleccionado === (int) $ano['Id'] ? 'selected' : '' ?>>
                Ano escotista <?= (int) $ano['AnoInicio'] ?>/<?= (int) $ano['AnoInicio'] + 1 ?>
                (<?= number_format((float) $ano['ValorSeguroEscotista'] + (float) $ano['ValorQuotaUEP'] + (float) $ano['ValorQuotaWFIS'], 2, ',', ' ') ?> €)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($resumo): ?>
<div class="grelha-cartoes" style="margin-top: 1rem;">
    <div class="cartao cartao-destaque">
        <span class="cartao-numero"><?= (int) $resumo['pagos'] ?></span>
        <span class="cartao-legenda">Censos pagos</span>
    </div>
    <div class="cartao">
        <span class="cartao-numero"><?= (int) $resumo['porPagar'] ?></span>
        <span class="cartao-legenda">Por pagar</span>
    </div>
    <div class="cartao">
        <span class="cartao-numero"><?= (int) $resumo['total'] ?></span>
        <span class="cartao-legenda">Total de associados abrangidos</span>
    </div>
</div>
<?php endif; ?>

<?php if (empty($linhas)): ?>
    <p class="texto-vazio" style="margin-top: 1.5rem;">Não há associados abrangidos pelo Censo (activos e não honorários) para ver aqui.</p>
<?php else: ?>
<div class="tabela-envolvente" style="margin-top: 1rem;">
    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Nº associado</th>
                <th>Companhia</th>
                <th>Estado</th>
                <th>Data de pagamento</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($linha['Nome']) ?></td>
                    <td><?= htmlspecialchars($linha['NumeroAssociado'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($linha['CompanhiaActual'] ?? '—') ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $linha['Pago'] ? 'ativo' : 'inativo' ?>">
                            <?= $linha['Pago'] ? 'Pago' : 'Não pago' ?>
                        </span>
                    </td>
                    <td><?= $linha['DataPagamento'] ? htmlspecialchars(\App\Core\Data::paraApresentacao($linha['DataPagamento'])) : '—' ?></td>
                    <td class="tabela-acoes">
                        <?php if ($linha['Pago']): ?>
                            <form action="<?= \App\Core\Url::para() ?>/censos/<?= (int) $linha['IdAssociado'] ?>/<?= (int) $anoSeleccionado ?>/nao-pago" method="post" class="forma-inline" onsubmit="return confirm('Marcar como não pago?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="ligacao-botao">Marcar não pago</button>
                            </form>
                        <?php else: ?>
                            <form action="<?= \App\Core\Url::para() ?>/censos/<?= (int) $linha['IdAssociado'] ?>/<?= (int) $anoSeleccionado ?>/pago" method="post" class="linha-contacto-editar" style="border: none; padding: 0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="text" name="DataPagamento" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" value="<?= htmlspecialchars($hojePt) ?>" style="width: 110px;">
                                <button type="submit" class="botao botao-primario botao-pequeno">Marcar pago</button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= \App\Core\Url::para() ?>/censos/<?= (int) $linha['IdAssociado'] ?>/<?= (int) $anoSeleccionado ?>/historico" style="margin-left: 0.6rem; font-size: 0.85rem;">Histórico</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>
