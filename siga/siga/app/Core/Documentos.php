<?php

namespace App\Core;

/**
 * Regra 6 das regras de negócio: o número do Cartão de Cidadão / documento
 * de identificação nunca pode ser tratado como número inteiro (perderia
 * zeros à esquerda). É sempre guardado e manipulado como texto.
 *
 * A largura exacta para preenchimento automático com zeros ainda não foi
 * confirmada (ver config/config.php -> 'documentos' -> 'largura_cc').
 * Enquanto não for definida, preencherComZeros() devolve o valor tal como
 * foi introduzido, sem qualquer alteração.
 */
class Documentos
{
    public static function preencherComZeros(string $numero, ?int $largura): string
    {
        $numero = trim($numero);

        if ($numero === '' || $largura === null) {
            return $numero;
        }

        return str_pad($numero, $largura, '0', STR_PAD_LEFT);
    }
}
