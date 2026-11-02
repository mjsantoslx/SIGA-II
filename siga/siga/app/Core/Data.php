<?php

namespace App\Core;

/**
 * Regras de datas do SIGA (secção 8 das regras de negócio):
 * - O utilizador vê e introduz sempre no formato dd/mm/aaaa;
 * - A base de dados guarda sempre em aaaa-mm-dd (YYYY-MM-DD);
 * - A conversão entre os dois formatos é sempre feita pela aplicação,
 *   nunca pelo browser (daí não usarmos <input type="date">, cujo
 *   formato de apresentação depende da configuração regional do utilizador).
 */
class Data
{
    /**
     * Converte uma data dd/mm/aaaa (como o utilizador escreveu) para
     * aaaa-mm-dd (formato de armazenamento). Devolve null se a data
     * não for válida (incluindo datas como 31/02/2024).
     */
    public static function paraBd(?string $dataPt): ?string
    {
        $dataPt = trim((string) $dataPt);
        if ($dataPt === '') {
            return null;
        }

        if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $dataPt, $partes)) {
            return null;
        }

        [, $dia, $mes, $ano] = $partes;

        if (!checkdate((int) $mes, (int) $dia, (int) $ano)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $ano, (int) $mes, (int) $dia);
    }

    /**
     * Converte uma data aaaa-mm-dd (armazenada na base de dados) para
     * dd/mm/aaaa, para apresentação ao utilizador.
     */
    public static function paraApresentacao(?string $dataBd): string
    {
        if (!$dataBd) {
            return '';
        }

        $timestamp = strtotime($dataBd);
        if ($timestamp === false) {
            return '';
        }

        return date('d/m/Y', $timestamp);
    }

    /**
     * Indica se uma data (em formato aaaa-mm-dd) é futura relativamente a hoje.
     */
    public static function eFutura(string $dataBd): bool
    {
        return $dataBd > date('Y-m-d');
    }

    public static function hojeBd(): string
    {
        return date('Y-m-d');
    }

    public static function hojePt(): string
    {
        return date('d/m/Y');
    }
}
