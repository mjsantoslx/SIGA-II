<?php

namespace App\Core;

/**
 * Router minimalista, sem dependências externas.
 * Regista rotas GET/POST associadas a [Controlador, método] e
 * suporta parâmetros dinâmicos do tipo /associados/{id}.
 */
class Router
{
    private array $rotas = ['GET' => [], 'POST' => []];

    public function get(string $caminho, array $accao): void
    {
        $this->rotas['GET'][$caminho] = $accao;
    }

    public function post(string $caminho, array $accao): void
    {
        $this->rotas['POST'][$caminho] = $accao;
    }

    public function despachar(string $metodo, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        $rotasMetodo = $this->rotas[$metodo] ?? [];

        foreach ($rotasMetodo as $padrao => $accao) {
            $parametros = $this->corresponde($padrao, $uri);
            if ($parametros !== null) {
                [$classe, $metodoAccao] = $accao;
                $controlador = new $classe();
                call_user_func_array([$controlador, $metodoAccao], $parametros);
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../Views/erros/404.php';
    }

    /**
     * Verifica se o URI corresponde ao padrão da rota (com {parametros}).
     * Devolve o array de parâmetros extraídos, ou null se não corresponder.
     */
    private function corresponde(string $padrao, string $uri): ?array
    {
        $nomesParametros = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($m) use (&$nomesParametros) {
            $nomesParametros[] = $m[1];
            return '([^/]+)';
        }, $padrao);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $valores)) {
            array_shift($valores);
            return array_values($valores);
        }

        return null;
    }
}
