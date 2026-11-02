<div class="cabecalho-pagina">
    <div>
        <h1><?= htmlspecialchars($cfg['titulo']) ?></h1>
        <p class="subtitulo"><a href="<?= \App\Core\Url::para() ?>/admin">← Voltar à administração</a></p>
    </div>
    <a href="<?= \App\Core\Url::para() ?>/admin/referencias/<?= htmlspecialchars($cfg['chave']) ?>/criar" class="botao botao-primario">+ Novo(a) <?= htmlspecialchars($cfg['singular']) ?></a>
</div>

<?php if (empty($registos)): ?>
    <p class="texto-vazio">Ainda não existem registos.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Designação</th>
                <?php if ($cfg['activo']): ?><th>Estado</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registos as $registo): ?>
                <tr>
                    <td><?= htmlspecialchars($registo[$cfg['coluna']]) ?></td>
                    <?php if ($cfg['activo']): ?>
                    <td>
                        <span class="etiqueta etiqueta-<?= $registo['Activo'] ? 'ativo' : 'inativo' ?>">
                            <?= $registo['Activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td class="tabela-acoes">
                        <a href="<?= \App\Core\Url::para() ?>/admin/referencias/<?= htmlspecialchars($cfg['chave']) ?>/<?= (int) $registo['Id'] ?>/editar">Editar</a>
                        <form action="<?= \App\Core\Url::para() ?>/admin/referencias/<?= htmlspecialchars($cfg['chave']) ?>/<?= (int) $registo['Id'] ?>/eliminar" method="post" class="forma-inline" onsubmit="return confirm('Eliminar este registo? Só é possível se não estiver em uso.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="ligacao-botao ligacao-botao-perigo">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
