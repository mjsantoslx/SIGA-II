<?php $f = $ficha ?? []; ?>

<div class="cabecalho-pagina">
    <div>
        <h1>Ficha de saúde — <?= htmlspecialchars($associado['Nome']) ?></h1>
        <p class="subtitulo">Cada alteração fica registada no histórico, com quem a fez e quando.</p>
    </div>
    <a href="<?= \App\Core\Url::para() ?>/associados/<?= (int) $associado['Id'] ?>" class="botao botao-secundario">← Voltar à ficha</a>
</div>

<form action="<?= \App\Core\Url::para() ?>/associados/<?= (int) $associado['Id'] ?>/ficha-saude" method="post" class="formulario-associado">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <fieldset>
        <legend>Dados de saúde</legend>
        <div class="grelha-formulario">
            <div class="campo">
                <label for="NumUente">Número de utente *</label>
                <input type="text" id="NumUente" name="NumUente" maxlength="9" required value="<?= htmlspecialchars($f['NumUente'] ?? '') ?>">
            </div>
        </div>
        <div class="grelha-checkboxes">
            <label><input type="checkbox" name="Asma" value="1" <?= !empty($f['Asma']) ? 'checked' : '' ?>> Asma</label>
            <label><input type="checkbox" name="Epilepsia" value="1" <?= !empty($f['Epilepsia']) ? 'checked' : '' ?>> Epilepsia</label>
            <label><input type="checkbox" name="Diabetes" value="1" <?= !empty($f['Diabetes']) ? 'checked' : '' ?>> Diabetes</label>
            <label><input type="checkbox" name="Alergias" value="1" <?= !empty($f['Alergias']) ? 'checked' : '' ?>> Alergias</label>
        </div>
        <div class="grelha-formulario">
            <div class="campo campo-largo">
                <label for="DescAlergias">Descrição de alergias</label>
                <textarea id="DescAlergias" name="DescAlergias" rows="2"><?= htmlspecialchars($f['DescAlergias'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="MedicacaoRegular">Medicação regular</label>
                <textarea id="MedicacaoRegular" name="MedicacaoRegular" rows="2"><?= htmlspecialchars($f['MedicacaoRegular'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="RestricoesAlimentares">Restrições alimentares</label>
                <textarea id="RestricoesAlimentares" name="RestricoesAlimentares" rows="2"><?= htmlspecialchars($f['RestricoesAlimentares'] ?? '') ?></textarea>
            </div>
            <div class="campo campo-largo">
                <label for="Outros">Outras informações de saúde relevantes</label>
                <textarea id="Outros" name="Outros" rows="2"><?= htmlspecialchars($f['Outros'] ?? '') ?></textarea>
            </div>
        </div>
    </fieldset>

    <div class="acoes-formulario">
        <button type="submit" class="botao botao-primario">Guardar</button>
    </div>
</form>

<section class="cartao-seccao" style="margin-top: 1.5rem;">
    <h2>Histórico de alterações</h2>
    <?php if (empty($historico)): ?>
        <p class="texto-vazio">Ainda não há alterações registadas.</p>
    <?php else: ?>
        <div class="tabela-envolvente">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Data/hora</th>
                        <th>Operação</th>
                        <th>Utilizador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $entrada): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['DataHora']))) ?></td>
                            <td><?= $entrada['Operacao'] === 'CRIACAO' ? 'Criação' : 'Actualização' ?></td>
                            <td><?= htmlspecialchars($entrada['NomeUtilizador'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="ajuda-fieldset" style="margin-top: 0.8rem;">O detalhe de cada alteração fica guardado (antes/depois) na base de dados, para efeitos de auditoria — não é mostrado aqui por conter dados de saúde.</p>
    <?php endif; ?>
</section>
