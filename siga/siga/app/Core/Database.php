<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Encapsula a ligação PDO à base de dados MariaDB.
 * Usa o padrão Singleton para reaproveitar uma única ligação por pedido HTTP.
 */
class Database
{
    private static ?PDO $instancia = null;

    public static function ligacao(): PDO
    {
        if (self::$instancia === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $db     = $config['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            try {
                self::$instancia = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false,
                ]);
            } catch (PDOException $e) {
                // Nunca expor detalhes de ligação ao utilizador final.
                error_log('[SIGA] Falha de ligação à base de dados: ' . $e->getMessage());
                http_response_code(500);
                die('Não foi possível ligar à base de dados. Contacte o administrador do sistema.');
            }
        }

        return self::$instancia;
    }
}
