<h1>Administração</h1>
<p class="subtitulo">Acesso restrito a administradores.</p>

<div class="grelha-admin">
    <a href="<?= \App\Core\Url::para() ?>/admin/referencias/tipos-evento" class="cartao-admin">
        <strong>Tipos de evento</strong>
        <span>Admissão, desactivação, reactivação, correcção de secção, etc.</span>
    </a>
    <a href="<?= \App\Core\Url::para() ?>/utilizadores" class="cartao-admin">
        <strong>Utilizadores</strong>
        <span>Gestão de acessos à aplicação.</span>
    </a>
    <a href="<?= \App\Core\Url::para() ?>/admin/anos-escotistas" class="cartao-admin">
        <strong>Anos escotistas (Censos)</strong>
        <span>Valores anuais do Censo: seguro escotista, quota UEP, quota WFIS.</span>
    </a>
</div>
