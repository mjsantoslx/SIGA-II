<?php

namespace App\Core;

/**
 * Gestão centralizada da sessão: início seguro, autenticação e mensagens flash.
 */
class Sessao
{
    public static function iniciar(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($config['sessao']['nome']);

        session_set_cookie_params([
            'lifetime' => $config['sessao']['tempo_vida_minutos'] * 60,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function autenticar(array $utilizador, ?int $idAssociado): void
    {
        session_regenerate_id(true);
        $_SESSION['utilizador'] = [
            'Id'            => $utilizador['Id'],
            'Nome'          => $utilizador['Nome'],
            'Email'         => $utilizador['Email'],
            'Administrador' => (bool) $utilizador['Administrador'],
            'IdAssociado'   => $idAssociado,
        ];
    }

    public static function terminar(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function autenticado(): bool
    {
        return !empty($_SESSION['utilizador']);
    }

    public static function utilizador(): ?array
    {
        return $_SESSION['utilizador'] ?? null;
    }

    public static function ehAdministrador(): bool
    {
        return self::autenticado() && !empty($_SESSION['utilizador']['Administrador']);
    }

    public static function idAssociado(): ?int
    {
        return $_SESSION['utilizador']['IdAssociado'] ?? null;
    }

    public static function guardarMensagem(string $tipo, string $texto): void
    {
        $_SESSION['mensagens'][] = ['tipo' => $tipo, 'texto' => $texto];
    }

    /**
     * Devolve e limpa as mensagens flash acumuladas (sucesso/erro).
     */
    public static function obterMensagens(): array
    {
        $mensagens = $_SESSION['mensagens'] ?? [];
        unset($_SESSION['mensagens']);
        return $mensagens;
    }
}
