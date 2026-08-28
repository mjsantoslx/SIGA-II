<div class="cabecalho-pagina">
    <div>
        <h1>Contactos — <?= htmlspecialchars($associado['Nome']) ?></h1>
        <p class="subtitulo">Gestão de morada e contactos (telemóvel, telefone, email, ...).</p>
    </div>
    <a href="/associados/<?= (int) $associado['Id'] ?>" class="botao botao-secundario">← Voltar à ficha</a>
</div>

<section class="cartao-seccao" style="margin-bottom: 1.5rem;">
    <h2>Morada</h2>

    <?php if ($ligacaoMorada): ?>
        <p><?= htmlspecialchars($ligacaoMorada['Morada']) ?><br>
           <?= htmlspecialchars($ligacaoMorada['CodPostal'] ?? '') ?> <?= htmlspecialchars($ligacaoMorada['Localidade'] ?? '') ?></p>
        <?php if ($partilhasMorada > 1): ?>
            <p class="texto-vazio">Esta morada é partilhada por mais <?= $partilhasMorada - 1 ?> registo(s) — corrigi-la afecta todos eles.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="texto-vazio">Ainda não existe morada registada. Utilize "Registar morada" abaixo.</p>
    <?php endif; ?>

    <div class="grelha-detalhe" style="margin-top: 1rem;">
        <?php if ($ligacaoMorada): ?>
        <div class="cartao-seccao">
            <h3>Corrigir morada actual</h3>
            <p class="ajuda-fieldset">Para corrigir um erro (ex.: número de porta). Afecta todos os que partilham esta morada.</p>
            <form action="/associados/<?= (int) $associado['Id'] ?>/morada/corrigir" method="post" class="formulario-morada">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="campo">
                    <label for="MoradaCorrigir">Morada</label>
                    <input type="text" id="MoradaCorrigir" name="Morada" required value="<?= htmlspecialchars($ligacaoMorada['Morada']) ?>">
                </div>
                <div class="campo">
                    <label for="CodPostalCorrigir">Código postal</label>
                    <input type="text" id="CodPostalCorrigir" name="CodPostal" placeholder="0000-000" value="<?= htmlspecialchars($ligacaoMorada['CodPostal'] ?? '') ?>">
                </div>
                <div class="campo">
                    <label for="LocalidadeCorrigir">Localidade</label>
                    <input type="text" id="LocalidadeCorrigir" name="Localidade" value="<?= htmlspecialchars($ligacaoMorada['Localidade'] ?? '') ?>">
                </div>
                <button type="submit" class="botao botao-secundario">Guardar correcção</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="cartao-seccao">
            <h3><?= $ligacaoMorada ? 'Substituir por morada nova' : 'Registar morada' ?></h3>
            <p class="ajuda-fieldset">Use isto quando o associado se mudou de facto de morada. A anterior fica preservada no histórico.</p>
            <form action="/associados/<?= (int) $associado['Id'] ?>/morada/substituir" method="post" class="formulario-morada">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="campo">
                    <label for="MoradaNova">Morada</label>
                    <input type="text" id="MoradaNova" name="Morada" required>
                </div>
                <div class="campo">
                    <label for="CodPostalNovo">Código postal</label>
                    <input type="text" id="CodPostalNovo" name="CodPostal" placeholder="0000-000">
                </div>
                <div class="campo">
                    <label for="LocalidadeNova">Localidade</label>
                    <input type="text" id="LocalidadeNova" name="Localidade">
                </div>
                <div class="campo">
                    <label for="DataInicioNova">Data de início *</label>
                    <input type="text" id="DataInicioNova" name="DataInicio" class="campo-data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" required value="<?= htmlspecialchars($hojePt) ?>">
                </div>
                <button type="submit" class="botao botao-primario"><?= $ligacaoMorada ? 'Substituir morada' : 'Registar morada' ?></button>
            </form>
        </div>
    </div>
</section>

<section class="cartao-seccao">
    <h2>Contactos</h2>

    <?php if (empty($contactos)): ?>
        <p class="texto-vazio">Ainda não há contactos registados.</p>
    <?php else: ?>
        <div class="lista-contactos">
            <?php foreach ($contactos as $contacto): ?>
                <div class="linha-contacto">
                    <form action="/associados/<?= (int) $associado['Id'] ?>/contactos/<?= (int) $contacto['Id'] ?>/editar" method="post" class="linha-contacto-editar">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <select name="IdTipoContacto">
                            <?php foreach ($tiposContacto as $tc): ?>
                                <option value="<?= (int) $tc['Id'] ?>" <?= (int) $tc['Id'] === (int) $contacto['IdTipoContacto'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tc['Designacao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="Valor" value="<?= htmlspecialchars($contacto['Valor']) ?>" required>
                        <button type="submit" class="botao botao-secundario botao-pequeno">Guardar</button>
                    </form>
                    <form action="/associados/<?= (int) $associado['Id'] ?>/contactos/<?= (int) $contacto['Id'] ?>/remover" method="post" onsubmit="return confirm('Remover este contacto?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="botao botao-perigo botao-pequeno">Remover</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3>Adicionar contacto</h3>
    <form action="/associados/<?= (int) $associado['Id'] ?>/contactos/adicionar" method="post" class="formulario-morada" style="flex-direction: row; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="campo">
            <label for="IdTipoContactoNovo">Tipo</label>
            <select id="IdTipoContactoNovo" name="IdTipoContacto" required>
                <?php foreach ($tiposContacto as $tc): ?>
                    <option value="<?= (int) $tc['Id'] ?>"><?= htmlspecialchars($tc['Designacao']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="ValorNovo">Valor</label>
            <input type="text" id="ValorNovo" name="Valor" required placeholder="ex.: 912 345 678">
        </div>
        <button type="submit" class="botao botao-primario">+ Adicionar</button>
    </form>
</section>
