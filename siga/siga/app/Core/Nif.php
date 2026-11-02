<?php

namespace App\Core;

/**
 * Validação do NIF português: formato (9 dígitos) e dígito de controlo,
 * pelo algoritmo oficial (módulo 11) usado pela Autoridade Tributária.
 *
 * Não restringe o primeiro dígito a uma categoria específica (pessoa
 * singular, colectiva, não residente, etc.) — só confirma que o dígito de
 * controlo é matematicamente consistente com os primeiros 8 dígitos, para
 * não rejeitar NIFs legítimos de categorias menos comuns.
 */
class Nif
{
    public static function valido(string $nif): bool
    {
        if (!preg_match('/^\d{9}$/', $nif)) {
            return false;
        }

        $digitos = array_map('intval', str_split($nif));

        $soma = 0;
        for ($i = 0; $i < 8; $i++) {
            $soma += $digitos[$i] * (9 - $i);
        }

        $resto = $soma % 11;
        $digitoControlo = $resto < 2 ? 0 : 11 - $resto;

        return $digitoControlo === $digitos[8];
    }
}
