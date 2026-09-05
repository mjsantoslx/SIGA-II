<div class="cabecalho-pagina">
    <div>
        <h1>Utilizadores</h1>
        <p class="subtitulo">Gestão de acessos à aplicação. Só administradores podem aceder a esta página.</p>
    </div>
    <a href="/utilizadores/criar" class="botao botao-primario">+ Novo utilizador</a>
</div>

<div class="tabela-envolvente">
    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Administrador</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utilizadores as $utilizador): ?>
                <tr>
                    <td><?= htmlspecialchars($utilizador['Nome']) ?></td>
                    <td><?= htmlspecialchars($utilizador['Email']) ?></td>
                    <td><?= $utilizador['Administrador'] ? 'Sim' : 'Não' ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $utilizador['Activo'] ? 'ativo' : 'inativo' ?>">
                            <?= $utilizador['Activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="tabela-acoes">
                        <a href="/utilizadores/<?= (int) $utilizador['Id'] ?>/editar">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
