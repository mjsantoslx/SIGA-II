<?php

namespace App\Models;

use App\Core\Model;

/**
 * Histórico de alterações à ficha de saúde (tabela fichas_saude_historico,
 * já existente no schema, até agora sem ser usada). Cada criação ou
 * actualização da ficha de saúde de um associado fica registada aqui,
 * com quem a fez, quando, e o estado antes/depois (em JSON).
 */
class FichaSaudeHistorico extends Model
{
    protected string $tabela = 'fichas_saude_historico';

    public function registar(
        int $idFichaSaude,
        int $idAssociado,
        int $idUtilizador,
        string $operacao,
        ?array $dadosAnteriores,
        ?array $dadosNovos
    ): int {
        return $this->inserir('fichas_saude_historico', [
            'IdFichaSaude'    => $idFichaSaude,
            'IdAssociado'     => $idAssociado,
            'IdUtilizador'    => $idUtilizador,
            'Operacao'        => $operacao,
            'DadosAnteriores' => $dadosAnteriores !== null ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null,
            'DadosNovos'      => $dadosNovos !== null ? json_encode($dadosNovos, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function listarPorAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT h.*, u.Nome AS NomeUtilizador
             FROM fichas_saude_historico h
             LEFT JOIN utilizadores u ON u.Id = h.IdUtilizador
             WHERE h.IdAssociado = :idAssociado
             ORDER BY h.DataHora DESC, h.Id DESC"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }
}
