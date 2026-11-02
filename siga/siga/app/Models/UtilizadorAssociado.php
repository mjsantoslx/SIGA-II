<?php

namespace App\Models;

use App\Core\Model;

/**
 * Ligação 1:1 entre utilizador e associado (regra 4): com excepção do
 * utilizador especial "Administrador", todos os utilizadores têm de estar
 * ligados a um associado — e cada associado só pode estar ligado a um
 * utilizador.
 */
class UtilizadorAssociado extends Model
{
    protected string $tabela = 'utilizadores_associados';

    public function idAssociadoDoUtilizador(int $idUtilizador): ?int
    {
        $stmt = $this->bd->prepare(
            "SELECT IdAssociado FROM utilizadores_associados WHERE IdUtilizador = :id AND Activo = 1"
        );
        $stmt->execute(['id' => $idUtilizador]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? (int) $valor : null;
    }

    public function idUtilizadorDoAssociado(int $idAssociado): ?int
    {
        $stmt = $this->bd->prepare(
            "SELECT IdUtilizador FROM utilizadores_associados WHERE IdAssociado = :id AND Activo = 1"
        );
        $stmt->execute(['id' => $idAssociado]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? (int) $valor : null;
    }

    /**
     * Confirma se um associado já está ligado a OUTRO utilizador (para
     * validação amigável antes de tentar associar).
     */
    public function associadoJaLigadoAOutroUtilizador(int $idAssociado, ?int $idUtilizadorActual): bool
    {
        $idExistente = $this->idUtilizadorDoAssociado($idAssociado);
        return $idExistente !== null && $idExistente !== $idUtilizadorActual;
    }

    /**
     * Associa um utilizador a um associado, substituindo qualquer ligação
     * anterior desse utilizador.
     */
    public function associar(int $idUtilizador, int $idAssociado): void
    {
        $this->bd->beginTransaction();
        try {
            $stmt = $this->bd->prepare("DELETE FROM utilizadores_associados WHERE IdUtilizador = :id");
            $stmt->execute(['id' => $idUtilizador]);

            $stmt = $this->bd->prepare(
                "INSERT INTO utilizadores_associados (IdUtilizador, IdAssociado, Activo)
                 VALUES (:idUtilizador, :idAssociado, 1)"
            );
            $stmt->execute(['idUtilizador' => $idUtilizador, 'idAssociado' => $idAssociado]);

            $this->bd->commit();
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    public function desassociar(int $idUtilizador): void
    {
        $stmt = $this->bd->prepare("DELETE FROM utilizadores_associados WHERE IdUtilizador = :id");
        $stmt->execute(['id' => $idUtilizador]);
    }
}
