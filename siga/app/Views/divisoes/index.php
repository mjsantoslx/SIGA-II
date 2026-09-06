<div class="cabecalho-pagina">
    <div>
        <h1>Divisões</h1>
        <p class="subtitulo"><a href="/admin">← Voltar à administração</a></p>
    </div>
    <a href="/admin/divisoes/criar" class="botao botao-primario">+ Nova divisão</a>
</div>

<?php if (empty($divisoes)): ?>
    <p class="texto-vazio">Ainda não existem divisões.</p>
<?php else: ?>
<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Designação</th>
                <th>Nominativo masculino</th>
                <th>Nominativo feminino</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($divisoes as $divisao): ?>
                <tr>
                    <td><?= htmlspecialchars($divisao['Designacao']) ?></td>
                    <td><?= htmlspecialchars($divisao['NominativoMasculino']) ?></td>
                    <td><?= htmlspecialchars($divisao['NominativoFeminino']) ?></td>
                    <td class="tabela-acoes">
                        <a href="/admin/divisoes/<?= (int) $divisao['Id'] ?>/editar">Editar</a>
                        <form action="/admin/divisoes/<?= (int) $divisao['Id'] ?>/eliminar" method="post" class="forma-inline" onsubmit="return confirm('Eliminar esta divisão? Só é possível se não estiver em uso.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="ligacao-botao ligacao-botao-perigo">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="ajuda-fieldset" style="margin-top: 1rem;">A ordem de progressão entre divisões (regra 28) é fixa no código: Colónia → Alcateia → Tribo Júnior → Tribo Sénior → Clã → Chefia. Acrescentar aqui uma divisão nova não a insere automaticamente nessa sequência.</p>
<?php endif; ?>
