<?php
$parametrosFiltro = array_filter($filtros, static fn($v) => $v !== '');
$ligacaoPagina = static function (int $p) use ($parametrosFiltro): string {
    return '?' . htmlspecialchars(http_build_query($parametrosFiltro + ['pagina' => $p]));
};
?>
<div class="cabecalho-pagina">
    <div>
        <h1>Registo de actividade</h1>
        <p class="subtitulo">Quem fez o quê e quando. Só o utilizador "Administrador". O registo não pode ser alterado nem apagado. <a href="<?= \App\Core\Url::para() ?>/admin">← Voltar à administração</a></p>
    </div>
</div>

<form action="<?= \App\Core\Url::para() ?>/admin/registo-actividade" method="get" class="formulario-filtros">
    <input type="text" name="pesquisa" placeholder="Pesquisar na descrição ou no utilizador"
           value="<?= htmlspecialchars($filtros['pesquisa']) ?>">

    <select name="idUtilizador">
        <option value="">Todos os utilizadores</option>
        <?php foreach ($utilizadores as $u): ?>
            <option value="<?= (int) $u['IdUtilizador'] ?>" <?= $filtros['idUtilizador'] === (string) $u['IdUtilizador'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['Nome'] ?? '') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="accao">
        <option value="">Todas as acções</option>
        <?php foreach ($accoes as $chave => $rotulo): ?>
            <option value="<?= htmlspecialchars($chave) ?>" <?= $filtros['accao'] === $chave ? 'selected' : '' ?>>
                <?= htmlspecialchars($rotulo) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="resultado">
        <option value="">Qualquer resultado</option>
        <option value="sucesso" <?= $filtros['resultado'] === 'sucesso' ? 'selected' : '' ?>>Concluído</option>
        <option value="falhou" <?= $filtros['resultado'] === 'falhou' ? 'selected' : '' ?>>Não concluído</option>
    </select>

    <label>De <input type="date" name="de" value="<?= htmlspecialchars($filtros['de']) ?>"></label>
    <label>Até <input type="date" name="ate" value="<?= htmlspecialchars($filtros['ate']) ?>"></label>

    <button type="submit" class="botao botao-secundario">Filtrar</button>
    <a href="<?= \App\Core\Url::para() ?>/admin/registo-actividade" class="botao botao-secundario">Limpar</a>
</form>

<p class="subtitulo"><?= (int) $total ?> registo(s) — página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></p>

<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Data e hora</th>
                <th>Utilizador</th>
                <th>Acção</th>
                <th>Descrição</th>
                <th>Resultado</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$linhas): ?>
                <tr><td colspan="6">Sem registos para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            <?php foreach ($linhas as $l): ?>
                <tr>
                    <td style="white-space: nowrap;"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($l['DataHora']))) ?></td>
                    <td><?= htmlspecialchars($l['NomeUtilizador'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($accoes[$l['Accao']] ?? $l['Accao']) ?></td>
                    <td><?= htmlspecialchars($l['Descricao']) ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $l['Resultado'] === 'sucesso' ? 'ativo' : 'inativo' ?>">
                            <?= $l['Resultado'] === 'sucesso' ? 'Concluído' : 'Não concluído' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($l['EnderecoIP'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
    <p style="margin-top: 1rem; display: flex; gap: 0.6rem; align-items: center;">
        <?php if ($pagina > 1): ?>
            <a class="botao botao-secundario" href="<?= $ligacaoPagina($pagina - 1) ?>">← Anterior</a>
        <?php endif; ?>
        <span>Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span>
        <?php if ($pagina < $totalPaginas): ?>
            <a class="botao botao-secundario" href="<?= $ligacaoPagina($pagina + 1) ?>">Seguinte →</a>
        <?php endif; ?>
    </p>
<?php endif; ?>
