<?php

namespace App\Core;

use PDO;

/**
 * Classe base para todos os modelos.
 * Fornece acesso à ligação PDO e pequenos auxiliares de consulta.
 */
abstract class Model
{
    protected PDO $bd;
    protected string $tabela = '';
    protected string $chavePrimaria = 'Id';

    public function __construct()
    {
        $this->bd = Database::ligacao();
    }

    public function todos(string $ordem = ''): array
    {
        $sql = "SELECT * FROM {$this->tabela}";
        if ($ordem !== '') {
            $sql .= " ORDER BY {$ordem}";
        }
        $stmt = $this->bd->query($sql);
        return $stmt->fetchAll();
    }

    public function encontrarPorId(int $id): ?array
    {
        $stmt = $this->bd->prepare("SELECT * FROM {$this->tabela} WHERE {$this->chavePrimaria} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->bd->prepare("DELETE FROM {$this->tabela} WHERE {$this->chavePrimaria} = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Insere um registo a partir de um array associativo [coluna => valor]
     * e devolve o Id inserido.
     */
    protected function inserir(string $tabela, array $dados): int
    {
        $colunas      = array_keys($dados);
        $parametros   = array_map(fn($c) => ':' . $c, $colunas);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tabela,
            implode(', ', $colunas),
            implode(', ', $parametros)
        );

        $stmt = $this->bd->prepare($sql);
        $stmt->execute($dados);

        return (int) $this->bd->lastInsertId();
    }

    /**
     * Actualiza um registo a partir de um array associativo [coluna => valor].
     */
    protected function actualizar(string $tabela, array $dados, string $colunaId, $valorId): bool
    {
        $atribuicoes = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($dados)));
        $sql = "UPDATE {$tabela} SET {$atribuicoes} WHERE {$colunaId} = :__id";

        $dados['__id'] = $valorId;

        $stmt = $this->bd->prepare($sql);
        return $stmt->execute($dados);
    }
}
