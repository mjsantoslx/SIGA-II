<?php

namespace App\Models;

use App\Core\Model;

class Associado extends Model
{
    protected string $tabela = 'associados';

    /**
     * Lista associados com o nome da pessoa, secção actual e companhia actual,
     * com filtros opcionais de pesquisa, secção e estado.
     */
    public function listar(array $filtros = []): array
    {
        $condicoes = [];
        $parametros = [];

        if (!empty($filtros['pesquisa'])) {
            $condicoes[] = '(p.Nome LIKE :pesquisa OR a.NumeroAssociado LIKE :pesquisa)';
            $parametros['pesquisa'] = '%' . $filtros['pesquisa'] . '%';
        }

        if (!empty($filtros['idSecao'])) {
            $condicoes[] = 'secaoActual.IdSecao = :idSecao';
            $parametros['idSecao'] = $filtros['idSecao'];
        }

        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $condicoes[] = 'a.Activo = :activo';
            $parametros['activo'] = $filtros['activo'];
        }

        $whereSql = $condicoes ? ('WHERE ' . implode(' AND ', $condicoes)) : '';

        $sql = "
            SELECT
                a.Id, a.NumeroAssociado, a.DataNascimento, a.Genero, a.Activo,
                p.Nome,
                secaoActual.Designacao AS SecaoActual,
                companhiaActual.Designacao AS CompanhiaActual,
                (
                    SELECT 1 FROM associados_companhias acn
                    INNER JOIN companhias cn ON cn.Id = acn.IdCompanhia
                    WHERE acn.IdAssociado = a.Id AND acn.Activo = 1 AND cn.ambito_global = 1
                    LIMIT 1
                ) AS NaChefiaNacional
            FROM associados a
            INNER JOIN pessoas p ON p.Id = a.IdPessoa
            LEFT JOIN (
                SELECT s.IdAssociado, sec.Designacao, sec.Id AS IdSecao
                FROM associados_secoes s
                INNER JOIN secoes sec ON sec.Id = s.IdSecao
                WHERE s.Activo = 1
            ) secaoActual ON secaoActual.IdAssociado = a.Id
            LEFT JOIN (
                SELECT ac.IdAssociado, c.Designacao
                FROM associados_companhias ac
                INNER JOIN companhias c ON c.Id = ac.IdCompanhia
                WHERE ac.Activo = 1 AND c.ambito_global = 0
            ) companhiaActual ON companhiaActual.IdAssociado = a.Id
            {$whereSql}
            ORDER BY p.Nome
        ";

        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /**
     * Devolve todos os dados de um associado (dados base + pessoa) para ecrãs de
     * visualização/edição. As entidades relacionadas (moradas, contactos, etc.)
     * são obtidas separadamente pelos respectivos modelos.
     */
    public function encontrarCompletoPorId(int $id): ?array
    {
        $stmt = $this->bd->prepare("
            SELECT a.*, p.Nome
            FROM associados a
            INNER JOIN pessoas p ON p.Id = a.IdPessoa
            WHERE a.Id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function secaoActual(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare("
            SELECT asec.*, sec.Designacao
            FROM associados_secoes asec
            INNER JOIN secoes sec ON sec.Id = asec.IdSecao
            WHERE asec.IdAssociado = :id AND asec.Activo = 1
            ORDER BY asec.DataInicio DESC LIMIT 1
        ");
        $stmt->execute(['id' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    /**
     * Companhia local (ambito_global = 0) actual do associado. A Chefia
     * Nacional é gerida à parte, porque pode coexistir com esta.
     */
    public function companhiaActual(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare("
            SELECT acomp.*, c.Designacao
            FROM associados_companhias acomp
            INNER JOIN companhias c ON c.Id = acomp.IdCompanhia
            WHERE acomp.IdAssociado = :id AND acomp.Activo = 1 AND c.ambito_global = 0
            ORDER BY acomp.DataInicio DESC LIMIT 1
        ");
        $stmt->execute(['id' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    /**
     * Ligação activa à Chefia Nacional (companhia com ambito_global = 1),
     * se existir.
     */
    public function chefiaNacionalActual(int $idAssociado): ?array
    {
        $stmt = $this->bd->prepare("
            SELECT acomp.*, c.Designacao
            FROM associados_companhias acomp
            INNER JOIN companhias c ON c.Id = acomp.IdCompanhia
            WHERE acomp.IdAssociado = :id AND acomp.Activo = 1 AND c.ambito_global = 1
            ORDER BY acomp.DataInicio DESC LIMIT 1
        ");
        $stmt->execute(['id' => $idAssociado]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    /**
     * Cria um associado completo dentro de uma única transacção:
     * pessoa, morada, contactos, associado, secção, companhia,
     * encarregados de educação, contactos de emergência, ficha de saúde,
     * consentimentos e evento de admissão.
     *
     * @return int Id do associado criado.
     * @throws \Throwable relançada após rollback, para o controlador tratar a mensagem.
     */
    public function criarCompleto(array $dados): int
    {
        $pessoaModelo    = new Pessoa();
        $moradaModelo    = new Morada();
        $contactoModelo  = new Contacto();
        $eeModelo        = new EncarregadoEducacao();
        $ceModelo        = new ContactoEmergencia();
        $fichaSaude      = new FichaSaude();
        $consentimento   = new Consentimento();
        $eventoModelo    = new EventoAssociado();

        $this->bd->beginTransaction();

        try {
            // 1. Pessoa (nome)
            $idPessoa = $pessoaModelo->criar($dados['Nome']);

            // 2. Morada (opcional)
            if (!empty($dados['Morada'])) {
                $idMorada = $moradaModelo->criar([
                    'Morada'     => $dados['Morada'],
                    'CodPostal'  => $dados['CodPostal'] ?? null,
                    'Localidade' => $dados['Localidade'] ?? null,
                ]);
                $moradaModelo->associarPessoa($idPessoa, $idMorada, $dados['DataInscricao']);
            }

            // 3. Contactos (telemóvel / telefone / email / email associativo)
            $tiposContactoCampo = [
                'Telemovel'        => 'Telemóvel',
                'Telefone'         => 'Telefone',
                'Email'            => 'Email',
                'EmailAssociativo' => 'Email Associativo',
            ];
            foreach ($tiposContactoCampo as $campo => $designacaoTipo) {
                if (!empty($dados[$campo])) {
                    $idTipo = $contactoModelo->idTipoPorDesignacao($designacaoTipo);
                    if ($idTipo) {
                        $contactoModelo->criar($idPessoa, $idTipo, $dados[$campo]);
                    }
                }
            }

            // 4. Associado
            $idAssociado = $this->inserir('associados', [
                'IdPessoa'                       => $idPessoa,
                'NumeroAssociado'                 => $dados['NumeroAssociado'] ?: null,
                'DataNascimento'                  => $dados['DataNascimento'],
                'Genero'                          => $dados['Genero'],
                'IdNacionalidade'                 => $dados['IdNacionalidade'] ?: null,
                'IdEstadoCivil'                   => $dados['IdEstadoCivil'] ?: null,
                'IdConfissaoReligiosa'             => $dados['IdConfissaoReligiosa'] ?: null,
                'IdTipoDocumentoIdentificacao'     => $dados['IdTipoDocumentoIdentificacao'] ?: null,
                'NumeroDocumentoIdentificacao'     => $dados['NumeroDocumentoIdentificacao'] ?: null,
                'NumeroCartaoUtente'               => $dados['NumeroCartaoUtente'] ?: null,
                'NominativoOutro'                  => $dados['Genero'] === 'O' ? $dados['NominativoOutro'] : null,
                'NomePai'                          => $dados['NomePai'] ?: null,
                'NomeMae'                          => $dados['NomeMae'] ?: null,
                'DataInscricao'                    => $dados['DataInscricao'],
                'Activo'                           => 1,
                'Formador'                         => !empty($dados['Formador']) ? 1 : 0,
                'InsigniaMadeira'                  => !empty($dados['InsigniaMadeira']) ? 1 : 0,
                'DataInsigniaMadeira'              => !empty($dados['InsigniaMadeira']) ? $dados['DataInsigniaMadeira'] : null,
            ]);

            // 5. Secção inicial
            if (!empty($dados['IdSecao'])) {
                $stmt = $this->bd->prepare(
                    "INSERT INTO associados_secoes (IdAssociado, IdSecao, DataInicio, Activo)
                     VALUES (:idAssociado, :idSecao, :dataInicio, 1)"
                );
                $stmt->execute([
                    'idAssociado' => $idAssociado,
                    'idSecao'     => $dados['IdSecao'],
                    'dataInicio'  => $dados['DataInscricao'],
                ]);
            }

            // 6. Companhia local inicial
            if (!empty($dados['IdCompanhia'])) {
                $stmt = $this->bd->prepare(
                    "INSERT INTO associados_companhias (IdAssociado, IdCompanhia, DataInicio, Activo)
                     VALUES (:idAssociado, :idCompanhia, :dataInicio, 1)"
                );
                $stmt->execute([
                    'idAssociado' => $idAssociado,
                    'idCompanhia' => $dados['IdCompanhia'],
                    'dataInicio'  => $dados['DataInscricao'],
                ]);
            }

            // 6.1 Chefia Nacional (independente da companhia local — podem coexistir)
            if (!empty($dados['ChefiaNacional']) && !empty($dados['IdCompanhiaChefiaNacional'])) {
                $stmt = $this->bd->prepare(
                    "INSERT INTO associados_companhias (IdAssociado, IdCompanhia, DataInicio, Activo)
                     VALUES (:idAssociado, :idCompanhia, :dataInicio, 1)"
                );
                $stmt->execute([
                    'idAssociado' => $idAssociado,
                    'idCompanhia' => $dados['IdCompanhiaChefiaNacional'],
                    'dataInicio'  => $dados['DataInscricao'],
                ]);
            }

            // 6.2 Órgãos (um dirigente pode estar em vários em simultâneo)
            if (!empty($dados['Orgaos'])) {
                foreach ($dados['Orgaos'] as $idOrgao) {
                    $stmt = $this->bd->prepare(
                        "INSERT INTO associados_orgaos (IdAssociado, IdOrgao, DataInicio, Activo)
                         VALUES (:idAssociado, :idOrgao, :dataInicio, 1)"
                    );
                    $stmt->execute([
                        'idAssociado' => $idAssociado,
                        'idOrgao'     => (int) $idOrgao,
                        'dataInicio'  => $dados['DataInscricao'],
                    ]);
                }
            }

            // 7. Encarregados de educação (arrays paralelos vindos do formulário)
            if (!empty($dados['EncarregadosNome'])) {
                foreach ($dados['EncarregadosNome'] as $i => $nomeEE) {
                    $nomeEE = trim($nomeEE);
                    if ($nomeEE === '') {
                        continue;
                    }
                    $idPessoaEE   = $pessoaModelo->criar($nomeEE);
                    $idTipoRelEE  = $dados['EncarregadosRelacao'][$i] ?? null;
                    if ($idTipoRelEE) {
                        $eeModelo->criar($idAssociado, $idPessoaEE, (int) $idTipoRelEE, $dados['DataInscricao']);
                    }
                    if (!empty($dados['EncarregadosContacto'][$i])) {
                        $idTipoTelemovel = $contactoModelo->idTipoPorDesignacao('Telemóvel');
                        if ($idTipoTelemovel) {
                            $contactoModelo->criar($idPessoaEE, $idTipoTelemovel, $dados['EncarregadosContacto'][$i]);
                        }
                    }
                }
            }

            // 8. Contactos de emergência — estrutura própria, não depende de "pessoas" (regra 17.1).
            if (!empty($dados['EmergenciaNome'])) {
                foreach ($dados['EmergenciaNome'] as $i => $nomeCE) {
                    $nomeCE = trim($nomeCE);
                    if ($nomeCE === '') {
                        continue;
                    }
                    $idTipoRelCE = $dados['EmergenciaRelacao'][$i] ?? null;
                    if ($idTipoRelCE) {
                        $ceModelo->criar($idAssociado, $nomeCE, $dados['EmergenciaContacto'][$i] ?? null, (int) $idTipoRelCE);
                    }
                }
            }

            // 9. Ficha de saúde (opcional, mas exige NumUente se preenchida)
            if (!empty($dados['NumUente'])) {
                $fichaSaude->criar($idAssociado, $dados);
            }

            // 10. Consentimentos RGPD
            $consentimento->criar($idAssociado, $dados);

            // 11. Evento de admissão (obrigatório por regra de negócio da associação)
            $idTipoEventoAdmissao = $eventoModelo->idTipoEventoPorDesignacao('Admissão');
            if ($idTipoEventoAdmissao) {
                $eventoModelo->registar($idAssociado, $idTipoEventoAdmissao, $dados['DataInscricao'], 'Admissão inicial na UEP.');
            }

            $this->bd->commit();
            return $idAssociado;
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza os dados base do associado e o nome da pessoa associada.
     */
    public function actualizarDadosBase(int $idAssociado, int $idPessoa, array $dados): bool
    {
        $this->bd->beginTransaction();
        try {
            (new Pessoa())->actualizarNome($idPessoa, $dados['Nome']);

            $this->actualizar('associados', [
                'NumeroAssociado'                => $dados['NumeroAssociado'] ?: null,
                'DataNascimento'                  => $dados['DataNascimento'],
                'Genero'                          => $dados['Genero'],
                'IdNacionalidade'                 => $dados['IdNacionalidade'] ?: null,
                'IdEstadoCivil'                    => $dados['IdEstadoCivil'] ?: null,
                'IdConfissaoReligiosa'             => $dados['IdConfissaoReligiosa'] ?: null,
                'IdTipoDocumentoIdentificacao'     => $dados['IdTipoDocumentoIdentificacao'] ?: null,
                'NumeroDocumentoIdentificacao'     => $dados['NumeroDocumentoIdentificacao'] ?: null,
                'NumeroCartaoUtente'               => $dados['NumeroCartaoUtente'] ?: null,
                'NominativoOutro'                  => $dados['Genero'] === 'O' ? $dados['NominativoOutro'] : null,
                'NomePai'                          => $dados['NomePai'] ?: null,
                'NomeMae'                          => $dados['NomeMae'] ?: null,
                'Formador'                         => !empty($dados['Formador']) ? 1 : 0,
                'InsigniaMadeira'                  => !empty($dados['InsigniaMadeira']) ? 1 : 0,
                'DataInsigniaMadeira'              => !empty($dados['InsigniaMadeira']) ? $dados['DataInsigniaMadeira'] : null,
            ], 'Id', $idAssociado);

            $this->bd->commit();
            return true;
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    /**
     * Fecha a secção actual (se existir e for diferente) e abre uma nova.
     */
    public function atribuirSecao(int $idAssociado, int $idSecao, string $dataInicio): void
    {
        $actual = $this->secaoActual($idAssociado);
        if ($actual && (int) $actual['IdSecao'] === $idSecao) {
            return;
        }

        if ($actual) {
            $stmt = $this->bd->prepare(
                "UPDATE associados_secoes SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
            );
            $stmt->execute(['dataFim' => $dataInicio, 'id' => $actual['Id']]);
        }

        $stmt = $this->bd->prepare(
            "INSERT INTO associados_secoes (IdAssociado, IdSecao, DataInicio, Activo) VALUES (:a, :s, :d, 1)"
        );
        $stmt->execute(['a' => $idAssociado, 's' => $idSecao, 'd' => $dataInicio]);
    }

    /**
     * Fecha a companhia LOCAL actual (se existir e for diferente) e abre uma
     * nova. Nunca mexe na ligação à Chefia Nacional — essa é independente
     * (ver entrarNaChefiaNacional()/sairDaChefiaNacional()), porque um
     * dirigente pode estar simultaneamente numa companhia local e na Chefia
     * Nacional.
     */
    public function atribuirCompanhia(int $idAssociado, int $idCompanhia, string $dataInicio): void
    {
        $actual = $this->companhiaActual($idAssociado);
        if ($actual && (int) $actual['IdCompanhia'] === $idCompanhia) {
            return;
        }

        if ($actual) {
            $stmt = $this->bd->prepare(
                "UPDATE associados_companhias SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
            );
            $stmt->execute(['dataFim' => $dataInicio, 'id' => $actual['Id']]);
        }

        $stmt = $this->bd->prepare(
            "INSERT INTO associados_companhias (IdAssociado, IdCompanhia, DataInicio, Activo) VALUES (:a, :c, :d, 1)"
        );
        $stmt->execute(['a' => $idAssociado, 'c' => $idCompanhia, 'd' => $dataInicio]);
    }

    /**
     * Cria a ligação à Chefia Nacional, se ainda não estiver activa. Não
     * fecha nem é afectada pela companhia local.
     */
    public function entrarNaChefiaNacional(int $idAssociado, int $idCompanhiaChefiaNacional, string $dataInicio): void
    {
        if ($this->chefiaNacionalActual($idAssociado)) {
            return;
        }

        $stmt = $this->bd->prepare(
            "INSERT INTO associados_companhias (IdAssociado, IdCompanhia, DataInicio, Activo) VALUES (:a, :c, :d, 1)"
        );
        $stmt->execute(['a' => $idAssociado, 'c' => $idCompanhiaChefiaNacional, 'd' => $dataInicio]);
    }

    public function sairDaChefiaNacional(int $idAssociado, string $dataFim): void
    {
        $actual = $this->chefiaNacionalActual($idAssociado);
        if (!$actual) {
            return;
        }

        $stmt = $this->bd->prepare(
            "UPDATE associados_companhias SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
        );
        $stmt->execute(['dataFim' => $dataFim, 'id' => $actual['Id']]);
    }

    /**
     * A desactivação de um associado é, em si mesma, consequência de um evento
     * (regra 9.2 das regras de negócio): nunca muda apenas o estado sem deixar
     * rasto no histórico de eventos.
     */
    public function desactivar(int $idAssociado, string $dataEvento, ?string $observacoes = null): bool
    {
        return $this->mudarEstadoComEvento($idAssociado, 0, 'Desactivação', $dataEvento, $observacoes);
    }

    /**
     * A reactivação (regra 10.2) restaura o estado activo e preserva o histórico
     * anterior; não associa automaticamente nenhuma companhia.
     */
    public function reactivar(int $idAssociado, string $dataEvento, ?string $observacoes = null): bool
    {
        return $this->mudarEstadoComEvento($idAssociado, 1, 'Reactivação', $dataEvento, $observacoes);
    }

    private function mudarEstadoComEvento(int $idAssociado, int $novoActivo, string $designacaoEvento, string $dataEvento, ?string $observacoes): bool
    {
        $eventoModelo = new EventoAssociado();

        $this->bd->beginTransaction();
        try {
            $this->actualizar('associados', ['Activo' => $novoActivo], 'Id', $idAssociado);

            $idTipoEvento = $eventoModelo->idTipoEventoPorDesignacao($designacaoEvento);
            if ($idTipoEvento) {
                $eventoModelo->registar($idAssociado, $idTipoEvento, $dataEvento, $observacoes);
            }

            $this->bd->commit();
            return true;
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    public function contarPorEstado(): array
    {
        $stmt = $this->bd->query("SELECT Activo, COUNT(*) AS Total FROM associados GROUP BY Activo");
        $linhas = $stmt->fetchAll();
        $resultado = ['ativos' => 0, 'inativos' => 0];
        foreach ($linhas as $linha) {
            if ((int) $linha['Activo'] === 1) {
                $resultado['ativos'] = (int) $linha['Total'];
            } else {
                $resultado['inativos'] = (int) $linha['Total'];
            }
        }
        return $resultado;
    }

    public function contarPorSecao(): array
    {
        $stmt = $this->bd->query("
            SELECT sec.Designacao, COUNT(*) AS Total
            FROM associados_secoes asec
            INNER JOIN secoes sec ON sec.Id = asec.IdSecao
            WHERE asec.Activo = 1
            GROUP BY sec.Designacao
            ORDER BY sec.Id
        ");
        return $stmt->fetchAll();
    }
}
