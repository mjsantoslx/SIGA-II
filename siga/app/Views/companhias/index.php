<div class="cabecalho-pagina">
    <div>
        <h1>Companhias</h1>
        <p class="subtitulo">Inclui a Chefia Nacional.</p>
    </div>
    <?php if (\App\Core\Sessao::ehAdministrador()): ?>
        <a href="/companhias/criar" class="botao botao-primario">+ Nova companhia</a>
    <?php endif; ?>
</div>

<?php if (empty($companhias)): ?>
    <p class="texto-vazio">Não existem companhias registadas.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('Designacao', 'Designação', $ordenar, $direcao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('ambito_global', 'Âmbito', $ordenar, $direcao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('Morada->Morada', 'Morada actual', $ordenar, $direcao) ?></th>
                <?php if (\App\Core\Sessao::ehAdministrador()): ?><th><?= \App\Core\Tabela::cabecalhoOrdenavel('Activo', 'Estado', $ordenar, $direcao) ?></th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companhias as $companhia): ?>
                <tr>
                    <td><?= htmlspecialchars($companhia['Designacao']) ?></td>
                    <td><?= $companhia['ambito_global'] ? 'Nacional' : 'Local' ?></td>
                    <td><?= $companhia['Morada'] ? htmlspecialchars($companhia['Morada']['Morada']) : '—' ?></td>
                    <?php if (\App\Core\Sessao::ehAdministrador()): ?>
                    <td>
                        <span class="etiqueta etiqueta-<?= $companhia['Activo'] ? 'ativo' : 'inativo' ?>">
                            <?= $companhia['Activo'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td class="tabela-acoes">
                        <a href="/companhias/<?= (int) $companhia['Id'] ?>">Ver</a>
                        <?php if (\App\Core\Sessao::ehAdministrador()): ?>
                            <a href="/companhias/<?= (int) $companhia['Id'] ?>/editar">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
