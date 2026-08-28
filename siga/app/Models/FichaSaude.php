<?php

namespace App\Models;

use App\Core\Model;

class FichaSaude extends Model
{
    protected string $tabela = 'fichas_saude';

    public function criar(int $idAssociado, array $dados): int
    {
        return $this->inserir('fichas_saude', [
            'IdAssociado'           => $idAssociado,
            'NumUente'              => $dados['NumUente'],
            'Asma'                  => !empty($dados['Asma']) ? 1 : 0,
            'Epilepsia'             => !empty($dados['Epilepsia']) ? 1 : 0,
            'Diabetes'              => !empty($dados['Diabetes']) ? 1 : 0,
            'Alergias'              => !empty($dados['Alergias']) ? 1 : 0,
            'DescAlergias'          => $dados['DescAlergias'] ?: null,
            'MedicacaoRegular'      => $dados['MedicacaoRegular'] ?: null,
            'RestricoesAlimentares' => $dados['RestricoesAlimentares'] ?: null,
            'Outros'                => $dados['Outros'] ?: null,
        ]);
    }

    public function actualizar_ficha(int $idAssociado, array $dados): bool
    {
        $existente = $this->porAssociado($idAssociado);
        $campos = [
            'NumUente'              => $dados['NumUente'],
            'Asma'                  => !empty($dados['Asma']) ? 1 : 0,
            'Epilepsia'             => !empty($dados['Epilepsia']) ? 1 : 0,
            'Diabetes'              => !empty($dados['Diabetes']) ? 1 : 0,
            'Alergias'              => !empty($dados['Alergias']) ? 1 : 0,
            'DescAlergias'          => $dados['DescAlergias'] ?: null,
            'MedicacaoRegular'      => $dados['MedicacaoRegular'] ?: null,
            'RestricoesAlimentares' => $dados['RestricoesAlimentares'] ?: null,
            'Outros'                => $dados['Outros'] ?: null,
        ];

        if ($existente) {
            return $this->actualizar('fichas_saude', $campos, 'Id', $existente['Id']);
        }

        $this->criar($idAssociado, $dados);
        return true;
    }

    public function porAssociado(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare("SELECT * FROM fichas_saude WHERE IdAssociado = :idAssociado LIMIT 1");
        $stmt->execute(['idAssociado' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
