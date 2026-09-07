<?php

namespace App\Models;

use App\Core\Model;

/**
 * Definição anual dos valores do Censo (seguro escotista + quota UEP +
 * quota WFIS), estabelecida no início de cada ano escotista (Outubro).
 */
class AnoEscotista extends Model
{
    protected string $tabela = 'anos_escotistas';

    public function listarTodos(): array
    {
        $stmt = $this->bd->query("SELECT * FROM anos_escotistas ORDER BY AnoInicio DESC");
        return $stmt->fetchAll();
    }

    public function listarAtivos(): array
    {
        $stmt = $this->bd->query("SELECT * FROM anos_escotistas WHERE Activo = 1 ORDER BY AnoInicio DESC");
        return $stmt->fetchAll();
    }

    public function anoEmUso(int $anoInicio, ?int $ignorarId = null): bool
    {
        $sql = "SELECT 1 FROM anos_escotistas WHERE AnoInicio = :ano";
        $parametros = ['ano' => $anoInicio];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    public function criar(int $anoInicio, float $valorSeguro, float $valorUep, float $valorWfis): int
    {
        return $this->inserir('anos_escotistas', [
            'AnoInicio'             => $anoInicio,
            'ValorSeguroEscotista'  => $valorSeguro,
            'ValorQuotaUEP'         => $valorUep,
            'ValorQuotaWFIS'        => $valorWfis,
            'Activo'                => 1,
        ]);
    }

    public function actualizarDados(int $id, int $anoInicio, float $valorSeguro, float $valorUep, float $valorWfis, bool $activo): bool
    {
        return $this->actualizar('anos_escotistas', [
            'AnoInicio'             => $anoInicio,
            'ValorSeguroEscotista'  => $valorSeguro,
            'ValorQuotaUEP'         => $valorUep,
            'ValorQuotaWFIS'        => $valorWfis,
            'Activo'                => $activo ? 1 : 0,
        ], 'Id', $id);
    }
}
