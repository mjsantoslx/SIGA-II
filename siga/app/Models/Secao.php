<?php

namespace App\Models;

use App\Core\Model;

class Secao extends Model
{
    protected string $tabela = 'secoes';

    /**
     * Sequência de progressão obrigatória entre secções: em uso normal, um
     * associado só pode avançar — nunca recuar — nesta sequência, mas pode
     * saltar secções (ex.: um lobito que se afasta pode voltar directamente
     * como Sénior ou Caminheiro). Um recuo só é possível através de uma
     * correcção explícita (ver AssociadosController::atualizar()).
     */
    private const SEQUENCIA = ['Colónia', 'Alcateia', 'Tribo Júnior', 'Tribo Sénior', 'Clã', 'Chefia'];

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
        return $this->designacaoPorId($idSecao) === 'Chefia';
    }

    /**
     * Regra 35: o cargo "Equipa Nacional de Clã" é exclusivo de associados
     * na secção "Clã".
     */
    public function ehCla(?int $idSecao): bool
    {
        if (!$idSecao) {
            return false;
        }
        return $this->designacaoPorId($idSecao) === 'Clã';
    }

    public function designacaoPorId(int $idSecao): ?string
    {
        $stmt = $this->bd->prepare("SELECT Designacao FROM secoes WHERE Id = :id");
        $stmt->execute(['id' => $idSecao]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : null;
    }

    /**
     * Confirma se a transição de uma secção para outra é permitida em uso
     * normal (sem ser uma correcção explícita): só se pode avançar — para a
     * mesma secção ou para qualquer secção posterior na sequência — nunca
     * recuar.
     *
     * Sem secção actual (primeira atribuição, na criação do associado), não
     * há restrição — o associado pode entrar directamente em qualquer secção
     * (ex.: um dirigente adulto que nunca passou pelas secções mais novas).
     * Secções fora desta sequência conhecida (ex.: acrescentadas mais tarde
     * via administração) também não ficam sujeitas a esta regra.
     */
    public function transicaoPermitida(?int $idSecaoAtual, int $idSecaoNova): bool
    {
        if ($idSecaoAtual === null) {
            return true;
        }

        $designacaoAtual = $this->designacaoPorId($idSecaoAtual);
        $designacaoNova  = $this->designacaoPorId($idSecaoNova);

        if ($designacaoAtual === null || $designacaoNova === null) {
            return false;
        }
        if ($designacaoAtual === $designacaoNova) {
            return true; // não é uma mudança de facto
        }

        $posicaoAtual = array_search($designacaoAtual, self::SEQUENCIA, true);
        $posicaoNova  = array_search($designacaoNova, self::SEQUENCIA, true);

        if ($posicaoAtual === false || $posicaoNova === false) {
            return true; // secção fora da sequência conhecida — não aplicar a regra
        }

        return $posicaoNova > $posicaoAtual;
    }

    public function designacaoEmUso(string $designacao, ?int $ignorarId = null): bool
    {
        $sql = "SELECT 1 FROM secoes WHERE Designacao = :designacao";
        $parametros = ['designacao' => $designacao];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $designacao, string $nominativoMasculino, string $nominativoFeminino): int
    {
        return $this->inserir('secoes', [
            'Designacao'          => $designacao,
            'NominativoMasculino' => $nominativoMasculino,
            'NominativoFeminino'  => $nominativoFeminino,
        ]);
    }

    public function actualizarDados(int $id, string $designacao, string $nominativoMasculino, string $nominativoFeminino): bool
    {
        return $this->actualizar('secoes', [
            'Designacao'          => $designacao,
            'NominativoMasculino' => $nominativoMasculino,
            'NominativoFeminino'  => $nominativoFeminino,
        ], 'Id', $id);
    }

    /**
     * Elimina uma divisão. Se estiver em uso (associados ligados a ela),
     * a base de dados recusa e devolvemos false em vez de deixar rebentar
     * uma excepção.
     */
    public function eliminarSeguro(int $id): bool
    {
        try {
            return $this->eliminar($id);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }
}
