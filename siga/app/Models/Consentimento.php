<?php

namespace App\Models;

use App\Core\Model;

class Consentimento extends Model
{
    protected string $tabela = 'consentimentos';

    public function criar(int $idAssociado, array $dados): int
    {
        return $this->inserir('consentimentos', [
            'IdAssociado'     => $idAssociado,
            'DadosPessoais'   => !empty($dados['DadosPessoais']) ? 1 : 0,
            'DadosSaude'      => !empty($dados['DadosSaude']) ? 1 : 0,
            'DadosVozImagem'  => !empty($dados['DadosVozImagem']) ? 1 : 0,
        ]);
    }

    public function maisRecenteDoAssociado(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT * FROM consentimentos WHERE IdAssociado = :idAssociado ORDER BY Id DESC LIMIT 1"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
