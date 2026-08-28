<?php

namespace App\Models;

use App\Core\Model;

class Secao extends Model
{
    protected string $tabela = 'secoes';

    /**
     * Sequência de progressão obrigatória entre secções (regra acordada):
     * cada associado só pode avançar para a secção imediatamente a seguir
     * nesta lista — nunca saltar secções nem recuar.
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

    public function designacaoPorId(int $idSecao): ?string
    {
        $stmt = $this->bd->prepare("SELECT Designacao FROM secoes WHERE Id = :id");
        $stmt->execute(['id' => $idSecao]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : null;
    }

    /**
     * Confirma se a transição de uma secção para outra é permitida:
     * só é permitido avançar para a secção imediatamente a seguir na
     * sequência Colónia → Alcateia → Tribo Júnior → Tribo Sénior → Clã → Chefia.
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

        return $posicaoNova === $posicaoAtual + 1;
    }

    /**
     * Designação da secção seguinte na sequência, para mensagens de erro.
     */
    public function proximaDaSequencia(string $designacaoAtual): ?string
    {
        $posicao = array_search($designacaoAtual, self::SEQUENCIA, true);
        if ($posicao === false || !isset(self::SEQUENCIA[$posicao + 1])) {
            return null;
        }
        return self::SEQUENCIA[$posicao + 1];
    }
}
