<?php

namespace App\Models;

use App\Core\Model;

/**
 * Moradas (secção 11 das regras de negócio): entidade independente e
 * potencialmente partilhada por várias pessoas/companhias. Suporta duas
 * operações distintas:
 *   - corrigir(): altera os dados da morada existente — afecta todos os que a partilham;
 *   - substituirLigacaoPessoa()/substituirLigacaoCompanhia(): cria uma morada nova e
 *     fecha a ligação anterior, mantendo histórico (DataInicio/DataFim).
 */
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

    /**
     * Corrige os dados de uma morada já existente (ex.: corrigir um número de
     * porta mal escrito). Esta alteração é visível para todos os que partilham
     * a morada — é diferente de substituir a ligação por uma morada nova.
     */
    public function corrigir(int $idMorada, array $dados): bool
    {
        return $this->actualizar('moradas', [
            'Morada'     => $dados['Morada'],
            'CodPostal'  => $dados['CodPostal'] ?: null,
            'Localidade' => $dados['Localidade'] ?: null,
        ], 'Id', $idMorada);
    }

    /**
     * Quantas ligações activas (pessoas + companhias) existem para esta morada —
     * para avisar o utilizador do impacto de a corrigir.
     */
    public function contarLigacoesActivas(int $idMorada): int
    {
        $stmt = $this->bd->prepare(
            "SELECT
                (SELECT COUNT(*) FROM pessoas_moradas WHERE IdMorada = :m1 AND Activo = 1) +
                (SELECT COUNT(*) FROM companhias_moradas WHERE IdMorada = :m2 AND Activo = 1) AS Total"
        );
        $stmt->execute(['m1' => $idMorada, 'm2' => $idMorada]);
        return (int) $stmt->fetchColumn();
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
     * Devolve a ligação (morada + Id da ligação pessoas_moradas) activa mais
     * recente de uma pessoa, se existir.
     */
    public function ligacaoActivaDaPessoa(int $idPessoa): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT m.*, pm.Id AS IdLigacao, pm.DataInicio AS LigacaoDataInicio
             FROM moradas m
             INNER JOIN pessoas_moradas pm ON pm.IdMorada = m.Id
             WHERE pm.IdPessoa = :idPessoa AND pm.Activo = 1
             ORDER BY pm.DataInicio DESC LIMIT 1"
        );
        $stmt->execute(['idPessoa' => $idPessoa]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    /**
     * Compatibilidade: devolve apenas os dados da morada (sem o Id da ligação).
     */
    public function moradaActivaDaPessoa(int $idPessoa): ?array
    {
        return $this->ligacaoActivaDaPessoa($idPessoa);
    }

    /**
     * Substitui a morada de uma pessoa: fecha a ligação anterior (DataFim) e
     * cria uma morada nova com uma nova ligação a partir da data indicada.
     */
    public function substituirLigacaoPessoa(int $idPessoa, ?int $idLigacaoAntiga, array $novaMoradaDados, string $dataInicio): int
    {
        $this->bd->beginTransaction();
        try {
            if ($idLigacaoAntiga) {
                $stmt = $this->bd->prepare(
                    "UPDATE pessoas_moradas SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
                );
                $stmt->execute(['dataFim' => $dataInicio, 'id' => $idLigacaoAntiga]);
            }

            $idMorada = $this->criar($novaMoradaDados);
            $this->associarPessoa($idPessoa, $idMorada, $dataInicio);

            $this->bd->commit();
            return $idMorada;
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Companhias (secção 12 e 11.5 — a Chefia Nacional é uma companhia
    // como outra qualquer para efeitos de gestão de morada).
    // ------------------------------------------------------------------

    public function associarCompanhia(int $idCompanhia, int $idMorada, string $dataInicio): int
    {
        return $this->inserir('companhias_moradas', [
            'IdCompanhia' => $idCompanhia,
            'IdMorada'    => $idMorada,
            'DataInicio'  => $dataInicio,
            'Activo'      => 1,
        ]);
    }

    public function ligacaoActivaDaCompanhia(int $idCompanhia): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT m.*, cm.Id AS IdLigacao, cm.DataInicio AS LigacaoDataInicio
             FROM moradas m
             INNER JOIN companhias_moradas cm ON cm.IdMorada = m.Id
             WHERE cm.IdCompanhia = :idCompanhia AND cm.Activo = 1
             ORDER BY cm.DataInicio DESC LIMIT 1"
        );
        $stmt->execute(['idCompanhia' => $idCompanhia]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function substituirLigacaoCompanhia(int $idCompanhia, ?int $idLigacaoAntiga, array $novaMoradaDados, string $dataInicio): int
    {
        $this->bd->beginTransaction();
        try {
            if ($idLigacaoAntiga) {
                $stmt = $this->bd->prepare(
                    "UPDATE companhias_moradas SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
                );
                $stmt->execute(['dataFim' => $dataInicio, 'id' => $idLigacaoAntiga]);
            }

            $idMorada = $this->criar($novaMoradaDados);
            $this->associarCompanhia($idCompanhia, $idMorada, $dataInicio);

            $this->bd->commit();
            return $idMorada;
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }
}
