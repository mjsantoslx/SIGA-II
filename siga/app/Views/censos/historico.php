<div class="cabecalho-pagina">
    <div>
        <h1>Histórico do Censo — <?= htmlspecialchars($associado['Nome']) ?></h1>
        <p class="subtitulo">Ano escotista <?= (int) $ano['AnoInicio'] ?>/<?= (int) $ano['AnoInicio'] + 1 ?></p>
    </div>
    <a href="<?= \App\Core\Url::para() ?>/censos?ano=<?= (int) $ano['Id'] ?>" class="botao botao-secundario">← Voltar aos Censos</a>
</div>

<?php if (empty($historico)): ?>
    <p class="texto-vazio">Ainda não há nenhuma acção registada para este Censo.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Data/hora</th>
                <th>Acção</th>
                <th>Data de pagamento</th>
                <th>Utilizador</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historico as $entrada): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['DataHora']))) ?></td>
                    <td><?= $entrada['Operacao'] === 'PAGAMENTO' ? 'Marcado como pago' : 'Anulado (não pago)' ?></td>
                    <td><?= $entrada['DataPagamento'] ? htmlspecialchars(\App\Core\Data::paraApresentacao($entrada['DataPagamento'])) : '—' ?></td>
                    <td><?= htmlspecialchars($entrada['NomeUtilizador'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
