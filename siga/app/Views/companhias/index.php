<h1>Companhias</h1>
<p class="subtitulo">Inclui a Chefia Nacional. A criação de novas companhias fica disponível na página de administração (próxima versão).</p>

<?php if (empty($companhias)): ?>
    <p class="texto-vazio">Não existem companhias registadas.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Designação</th>
                <th>Âmbito</th>
                <th>Morada actual</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companhias as $companhia): ?>
                <tr>
                    <td><?= htmlspecialchars($companhia['Designacao']) ?></td>
                    <td><?= $companhia['ambito_global'] ? 'Nacional' : 'Local' ?></td>
                    <td><?= $companhia['Morada'] ? htmlspecialchars($companhia['Morada']['Morada']) : '—' ?></td>
                    <td class="tabela-acoes">
                        <a href="/companhias/<?= (int) $companhia['Id'] ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
