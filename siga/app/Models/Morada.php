<?php

namespace App\Models;

use App\Core\Model;

class Morada extends Model
{
    protected string $tabela = 'moradas';

    public function criar(array $dados): int
    {
        return $this->inserir('moradas', [
            'Morada'     => $dados['Morada'],
            'CodPostal'  => $dados['CodPostal'] ?: null,
            'Localidade' => $dados['Localidade'] ?: null,
            'IdConcelho' => null,
            'IdDistrito' => null,
        ]);
    }

    public function associarPessoa(int $idPessoa, int $idMorada, string $dataInicio): int
    {
        return $this->inserir('pessoas_moradas', [
            'IdPessoa'   => $idPessoa,
            'IdMorada'   => $idMorada,
            'DataInicio' => $dataInicio,
            'Activo'     => 1,
        ]);
    }

    /**
     * Devolve a morada activa mais recente de uma pessoa (se existir).
     */
    public function moradaActivaDaPessoa(int $idPessoa): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT m.* FROM moradas m
             INNER JOIN pessoas_moradas pm ON pm.IdMorada = m.Id
             WHERE pm.IdPessoa = :idPessoa AND pm.Activo = 1
             ORDER BY pm.DataInicio DESC LIMIT 1"
        );
        $stmt->execute(['idPessoa' => $idPessoa]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
