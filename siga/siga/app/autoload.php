<?php

/**
 * Autoloader mínimo, compatível com PSR-4, para o namespace App\.
 * Evita a dependência do Composer para correr a aplicação; se preferir
 * usar Composer (ex.: para instalar bibliotecas adicionais), o composer.json
 * incluído define o mesmo mapeamento e pode substituir este ficheiro por
 * vendor/autoload.php sem alterações ao restante código.
 */

spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    $diretorioBase = __DIR__ . '/';

    if (!str_starts_with($classe, $prefixo)) {
        return;
    }

    $classeRelativa = substr($classe, strlen($prefixo));
    $caminho = $diretorioBase . str_replace('\\', '/', $classeRelativa) . '.php';

    if (file_exists($caminho)) {
        require $caminho;
    }
});
