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
