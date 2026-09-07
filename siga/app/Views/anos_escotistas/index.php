<div class="cabecalho-pagina">
    <div>
        <h1>Anos escotistas</h1>
        <p class="subtitulo">Valores do Censo (seguro escotista + quota UEP + quota WFIS), definidos no início de cada ano escotista (Outubro). <a href="<?= \App\Core\Url::para() ?>/admin">← Voltar à administração</a></p>
    </div>
    <a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas/criar" class="botao botao-primario">+ Novo ano escotista</a>
</div>

<?php if (empty($anos)): ?>
    <p class="texto-vazio">Ainda não existem anos escotistas definidos.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Ano escotista</th>
                <th>Seguro escotista</th>
                <th>Quota UEP</th>
                <th>Quota WFIS</th>
                <th>Total</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($anos as $ano): ?>
                <?php $total = $ano['ValorSeguroEscotista'] + $ano['ValorQuotaUEP'] + $ano['ValorQuotaWFIS']; ?>
                <tr>
                    <td><?= (int) $ano['AnoInicio'] ?>/<?= (int) $ano['AnoInicio'] + 1 ?></td>
                    <td><?= number_format((float) $ano['ValorSeguroEscotista'], 2, ',', ' ') ?> €</td>
                    <td><?= number_format((float) $ano['ValorQuotaUEP'], 2, ',', ' ') ?> €</td>
                    <td><?= number_format((float) $ano['ValorQuotaWFIS'], 2, ',', ' ') ?> €</td>
                    <td><strong><?= number_format((float) $total, 2, ',', ' ') ?> €</strong></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $ano['Activo'] ? 'ativo' : 'inativo' ?>">
                            <?= $ano['Activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="tabela-acoes">
                        <a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas/<?= (int) $ano['Id'] ?>/editar">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
