<?php

namespace App\Models;

use App\Core\Model;

class Secao extends Model
{
    protected string $tabela = 'secoes';

    public function listarTodas(): array
    {
        return $this->todos('Id');
    }

    /**
     * Regra 27: os dirigentes correspondem aos associados enquadrados na
     * secção "Chefia". Confirma se um dado Id de secção é essa secção.
     */
    public function ehChefia(?int $idSecao): bool
    {
        if (!$idSecao) {
            return false;
        }
        $stmt = $this->bd->prepare("SELECT 1 FROM secoes WHERE Id = :id AND Designacao = 'Chefia'");
        $stmt->execute(['id' => $idSecao]);
        return (bool) $stmt->fetchColumn();
    }
}
