<div class="cabecalho-pagina">
    <div>
        <h1>Associados</h1>
        <p class="subtitulo">Consulte, pesquise e faça a gestão dos associados da UEP.</p>
    </div>
    <a href="/associados/criar" class="botao botao-primario">+ Novo associado</a>
</div>

<form action="/associados" method="get" class="formulario-filtros">
    <input type="hidden" name="ordenar" value="<?= htmlspecialchars($ordenar) ?>">
    <input type="hidden" name="direcao" value="<?= htmlspecialchars($direcao) ?>">
    <input type="text" name="pesquisa" placeholder="Pesquisar por nome ou número de associado"
           value="<?= htmlspecialchars($filtros['pesquisa']) ?>">

    <select name="idSecao">
        <option value="">Todas as secções</option>
        <?php foreach ($secoes as $secao): ?>
            <option value="<?= (int) $secao['Id'] ?>" <?= (string) $filtros['idSecao'] === (string) $secao['Id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($secao['Designacao']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="activo">
        <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activos</option>
        <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivos</option>
        <option value=""  <?= $filtros['activo'] === '' ? 'selected' : '' ?>>Todos</option>
    </select>

    <button type="submit" class="botao botao-secundario">Filtrar</button>
</form>

<?php
$parametrosOrdenacao = [
    'pesquisa' => $filtros['pesquisa'],
    'idSecao'  => $filtros['idSecao'],
    'activo'   => $filtros['activo'],
];
?>

<?php if (empty($associados)): ?>
    <p class="texto-vazio">Não foram encontrados associados com os critérios indicados.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('nome', 'Nome', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('numero', 'Nº associado', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('secao', 'Secção', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('companhia', 'Companhia', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('nascimento', 'Data nasc.', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th><?= \App\Core\Tabela::cabecalhoOrdenavel('estado', 'Estado', $ordenar, $direcao, $parametrosOrdenacao) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($associados as $associado): ?>
                <tr>
                    <td><?= htmlspecialchars($associado['Nome']) ?></td>
                    <td><?= htmlspecialchars($associado['NumeroAssociado'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($associado['SecaoActual'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($associado['CompanhiaActual'] ?? '—') ?></td>
                    <td><?= htmlspecialchars(\App\Core\Data::paraApresentacao($associado['DataNascimento'])) ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $associado['Activo'] ? 'ativo' : 'inativo' ?>">
                            <?= $associado['Activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="tabela-acoes">
                        <a href="/associados/<?= (int) $associado['Id'] ?>">Ver</a>
                        <a href="/associados/<?= (int) $associado['Id'] ?>/editar">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
