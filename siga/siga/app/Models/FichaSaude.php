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

    /**
     * Cria ou actualiza a ficha de saúde de um associado, registando
     * sempre a operação no histórico (fichas_saude_historico), com o
     * estado anterior e novo, e quem a fez. Abre e fecha a sua própria
     * transacção — use isto quando NÃO já estiver dentro de uma (ex.:
     * chamado directamente por um controlador).
     */
    public function guardarComHistorico(int $idAssociado, array $dados, int $idUtilizador): void
    {
        $this->bd->beginTransaction();
        try {
            $this->guardarComHistoricoSemTransacao($idAssociado, $dados, $idUtilizador);
            $this->bd->commit();
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /**
     * Mesma lógica que guardarComHistorico(), mas sem abrir/fechar
     * transacção própria — para usar quando o chamador já estiver dentro
     * de uma (ex.: Associado::criarCompleto()), evitando uma transacção
     * aninhada (que o PDO não suporta).
     */
    public function guardarComHistoricoSemTransacao(int $idAssociado, array $dados, int $idUtilizador): void
    {
        $existente = $this->porAssociado($idAssociado);
        $novos = [
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

        $historicoModelo = new FichaSaudeHistorico();

        if ($existente) {
            $this->actualizar('fichas_saude', $novos, 'Id', $existente['Id']);
            $historicoModelo->registar((int) $existente['Id'], $idAssociado, $idUtilizador, 'ACTUALIZACAO', $existente, $novos);
        } else {
            $idFicha = $this->criar($idAssociado, $dados);
            $historicoModelo->registar($idFicha, $idAssociado, $idUtilizador, 'CRIACAO', null, $novos);
        }
    }

    public function porAssociado(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare("SELECT * FROM fichas_saude WHERE IdAssociado = :idAssociado LIMIT 1");
        $stmt->execute(['idAssociado' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
