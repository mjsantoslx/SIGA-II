<?php

namespace App\Core;

/**
 * Permite que a aplicação corra num subcaminho do domínio (ex.:
 * http://www.uep.pt/siga em vez de http://www.uep.pt/), sem alterar todos
 * os sítios onde um caminho absoluto é gerado. Definida uma única vez, no
 * arranque (public/index.php), a partir de config/config.php ->
 * app.base_url.
 */
class Url
{
    private static string $base = '';

    public static function definirBase(string $base): void
    {
        self::$base = rtrim($base, '/');
    }

    /**
     * Antepõe o prefixo base a um caminho absoluto (que começa por "/").
     * Chamado sem argumentos, devolve só o prefixo (útil para colar a
     * seguir a um "/" já escrito no HTML).
     */
    public static function para(string $caminho = ''): string
    {
        return self::$base . $caminho;
    }
}
