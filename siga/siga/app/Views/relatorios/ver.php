<?php
$parametros = [];
foreach ($filtros as $f) {
    if ($f['valor'] !== '' && $f['valor'] !== null) {
        $parametros[$f['nome']] = $f['valor'];
    }
}
$urlExcel = '?' . htmlspecialchars(http_build_query($parametros + ['formato' => 'xlsx']));
$formatar = static function ($valor, string $tipo): string {
    if ($valor === null || $valor === '') {
        return '';
    }
    return match ($tipo) {
        'data'     => \App\Core\Data::paraApresentacao((string) $valor),
        'dinheiro' => number_format((float) $valor, 2, ',', ' ') . ' €',
        'inteiro'  => (string) (int) $valor,
        default    => (string) $valor,
    };
};
?>
<div class="cabecalho-pagina">
    <div>
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p class="subtitulo"><a href="<?= \App\Core\Url::para() ?>/relatorios">← Voltar aos relatórios</a></p>
    </div>
    <?php if (!empty($colunas)): ?>
        <a href="<?= $urlExcel ?>" class="botao botao-primario">Exportar para Excel (.xlsx)</a>
    <?php endif; ?>
</div>

<?php if (!empty($filtros)): ?>
<form action="<?= \App\Core\Url::para() ?>/relatorios/<?= htmlspecialchars($chave) ?>" method="get" class="formulario-filtros">
    <?php foreach ($filtros as $f): ?>
        <select name="<?= htmlspecialchars($f['nome']) ?>" title="<?= htmlspecialchars($f['rotulo']) ?>">
            <?php foreach ($f['opcoes'] as $valor => $rotulo): ?>
                <option value="<?= htmlspecialchars((string) $valor) ?>" <?= (string) $f['valor'] === (string) $valor ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rotulo) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endforeach; ?>
    <button type="submit" class="botao botao-secundario">Aplicar</button>
</form>
<?php endif; ?>

<?php if (!empty($nota)): ?>
    <p class="ajuda-fieldset"><?= htmlspecialchars($nota) ?></p>
<?php endif; ?>

<?php if (!empty($resumo)): ?>
    <ul class="resumo-relatorio">
        <?php foreach ($resumo as $rotulo => $valor): ?>
            <li><strong><?= htmlspecialchars($rotulo) ?>:</strong> <?= htmlspecialchars((string) $valor) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($colunas)): ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <?php foreach ($colunas as $c): ?>
                    <th <?= in_array($c['tipo'], ['inteiro', 'dinheiro'], true) ? 'style="text-align: right;"' : '' ?>><?= htmlspecialchars($c['rotulo']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!$linhas): ?>
                <tr><td colspan="<?= count($colunas) ?>">Sem resultados para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            <?php foreach ($linhas as $linha): ?>
                <tr>
                    <?php foreach ($colunas as $i => $c): ?>
                        <td <?= in_array($c['tipo'], ['inteiro', 'dinheiro'], true) ? 'style="text-align: right;"' : '' ?>><?= htmlspecialchars($formatar($linha[$i] ?? null, $c['tipo'])) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if ($totais !== null): ?>
        <tfoot>
            <tr>
                <?php foreach ($colunas as $i => $c): ?>
                    <th <?= in_array($c['tipo'], ['inteiro', 'dinheiro'], true) ? 'style="text-align: right;"' : '' ?>><?= htmlspecialchars($formatar($totais[$i] ?? null, $c['tipo'])) ?></th>
                <?php endforeach; ?>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php endif; ?>
