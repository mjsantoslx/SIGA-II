<?php

namespace App\Core;

/**
 * Classe base para todos os controladores.
 * Fornece renderização de vistas dentro do layout comum, redireccionamentos,
 * acesso a dados da sessão e proteção de rotas autenticadas.
 */
abstract class Controller
{
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    /**
     * Renderiza uma vista dentro do layout principal (cabeçalho + rodapé).
     */
    protected function vista(string $caminho, array $dados = [], bool $comLayout = true): void
    {
        extract($dados);
        $ficheiroVista = __DIR__ . '/../Views/' . $caminho . '.php';

        if (!file_exists($ficheiroVista)) {
            http_response_code(500);
            die("Vista não encontrada: {$caminho}");
        }

        if ($comLayout) {
            $config = $this->config;
            $utilizadorAutenticado = Sessao::utilizador();
            require __DIR__ . '/../Views/layout/header.php';
            require $ficheiroVista;
            require __DIR__ . '/../Views/layout/footer.php';
        } else {
            require $ficheiroVista;
        }
    }

    protected function redirecionar(string $caminho): void
    {
        $base = rtrim($this->config['app']['base_url'], '/');
        header('Location: ' . $base . $caminho);
        exit;
    }

    protected function pedidoPost(): array
    {
        return $_POST;
    }

    /**
     * Garante que existe um utilizador autenticado; caso contrário, envia para o login.
     */
    protected function exigirAutenticacao(): void
    {
        if (!Sessao::autenticado()) {
            Sessao::guardarMensagem('erro', 'Por favor, autentique-se para continuar.');
            $this->redirecionar('/login');
        }
    }

    /**
     * Garante autenticação e que o utilizador pertence ao grupo de
     * administradores; caso contrário, envia para o painel principal.
     */
    protected function exigirAdministrador(): void
    {
        $this->exigirAutenticacao();

        if (!Sessao::ehAdministrador()) {
            Sessao::guardarMensagem('erro', 'Não tem permissões para aceder a essa página.');
            $this->redirecionar('/');
        }
    }

    /**
     * Regra 2: um utilizador não-administrador só pode ver/alterar
     * informação de associados da SUA companhia. Administradores têm
     * sempre acesso total. Se o utilizador não-administrador não tiver
     * companhia (não devia acontecer, dada a regra 2, mas por segurança) ou
     * a companhia não corresponder à do associado-alvo, bloqueia o acesso.
     */
    protected function exigirAcessoAssociado(int $idAssociadoAlvo): void
    {
        $this->exigirAutenticacao();

        if (Sessao::ehAdministrador()) {
            return;
        }

        $idAssociadoUtilizador = Sessao::idAssociado();
        $associadoModelo = new \App\Models\Associado();

        $idCompanhiaUtilizador = $idAssociadoUtilizador
            ? ($associadoModelo->companhiaActual($idAssociadoUtilizador)['IdCompanhia'] ?? null)
            : null;
        $idCompanhiaAlvo = $associadoModelo->companhiaActual($idAssociadoAlvo)['IdCompanhia'] ?? null;

        if (!$idCompanhiaUtilizador || $idCompanhiaUtilizador !== $idCompanhiaAlvo) {
            Sessao::guardarMensagem('erro', 'Não tem permissões para aceder a informação de associados de outra companhia.');
            $this->redirecionar('/associados');
        }
    }

    /**
     * Idem, para uma companhia directamente (ex.: ficha da própria companhia).
     */
    protected function exigirAcessoCompanhia(int $idCompanhiaAlvo): void
    {
        $this->exigirAutenticacao();

        if (Sessao::ehAdministrador()) {
            return;
        }

        $idAssociadoUtilizador = Sessao::idAssociado();
        $idCompanhiaUtilizador = $idAssociadoUtilizador
            ? ((new \App\Models\Associado())->companhiaActual($idAssociadoUtilizador)['IdCompanhia'] ?? null)
            : null;

        if (!$idCompanhiaUtilizador || $idCompanhiaUtilizador !== $idCompanhiaAlvo) {
            Sessao::guardarMensagem('erro', 'Não tem permissões para aceder a informação de outra companhia.');
            $this->redirecionar('/companhias');
        }
    }

    /**
     * Verifica um token CSRF simples baseado em sessão.
     */
    protected function validarCsrf(): void
    {
        $tokenPedido  = $_POST['csrf_token'] ?? '';
        $tokenSessao  = $_SESSION['csrf_token'] ?? '';

        if ($tokenPedido === '' || !hash_equals($tokenSessao, $tokenPedido)) {
            http_response_code(419);
            die('Pedido inválido ou expirado (CSRF). Regresse atrás e tente novamente.');
        }
    }
}
