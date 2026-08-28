<div class="cabecalho-pagina">
    <div>
        <h1>Associados</h1>
        <p class="subtitulo">Consulte, pesquise e faça a gestão dos associados da UEP.</p>
    </div>
    <a href="/associados/criar" class="botao botao-primario">+ Novo associado</a>
</div>

<form action="/associados" method="get" class="formulario-filtros">
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

<?php if (empty($associados)): ?>
    <p class="texto-vazio">Não foram encontrados associados com os critérios indicados.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Nº associado</th>
                <th>Secção</th>
                <th>Companhia</th>
                <th>Data nasc.</th>
                <th>Estado</th>
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
