<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Data;
use App\Core\Documentos;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\Companhia;
use App\Models\Consentimento;
use App\Models\Contacto;
use App\Models\ContactoEmergencia;
use App\Models\EncarregadoEducacao;
use App\Models\EventoAssociado;
use App\Models\FichaSaude;
use App\Models\Lookup;
use App\Models\Morada;
use App\Models\Secao;

class AssociadosController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $associadoModelo = new Associado();

        $filtros = [
            'pesquisa' => trim($_GET['pesquisa'] ?? ''),
            'idSecao'  => $_GET['idSecao'] ?? '',
            'activo'   => $_GET['activo'] ?? '1',
        ];

        $this->vista('associados/index', [
            'titulo'     => 'Associados',
            'associados' => $associadoModelo->listar($filtros),
            'secoes'     => (new Secao())->listarTodas(),
            'filtros'    => $filtros,
        ]);
    }

    public function criar(): void
    {
        $this->exigirAutenticacao();

        $this->vista('associados/form', [
            'titulo'      => 'Novo associado',
            'modo'        => 'criar',
            'associado'   => ['DataInscricao' => Data::hojePt()],
            ...$this->dadosListasFormulario(),
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $dados = $_POST;
        $erros = $this->validarDadosAssociado($dados);

        // Regra 27: email associativo obrigatório para a secção "Chefia".
        if ((new Secao())->ehChefia(!empty($dados['IdSecao']) ? (int) $dados['IdSecao'] : null)
            && trim($dados['EmailAssociativo'] ?? '') === '') {
            $erros[] = 'O email associativo é obrigatório para associados na secção "Chefia".';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('associados/form', [
                'titulo'    => 'Novo associado',
                'modo'      => 'criar',
                'associado' => $dados,
                ...$this->dadosListasFormulario(),
            ]);
            return;
        }

        try {
            // A partir daqui $dados['DataNascimento'] e ['DataInscricao'] já
            // estão convertidas para aaaa-mm-dd por validarDadosAssociado().
            $idAssociado = (new Associado())->criarCompleto($dados);
            Sessao::guardarMensagem('sucesso', 'Associado registado com sucesso.');
            $this->redirecionar('/associados/' . $idAssociado);
        } catch (\Throwable $e) {
            error_log('[SIGA] Erro ao criar associado: ' . $e->getMessage());
            Sessao::guardarMensagem('erro', 'Não foi possível guardar o associado. Verifique os dados introduzidos (por exemplo, número de associado ou documento duplicado) e tente novamente.');
            // Repor as datas em dd/mm/aaaa para o utilizador ver o formulário como o preencheu.
            $dados['DataNascimento'] = Data::paraApresentacao($dados['DataNascimento'] ?? null);
            $dados['DataInscricao']  = Data::paraApresentacao($dados['DataInscricao'] ?? null);
            $this->vista('associados/form', [
                'titulo'    => 'Novo associado',
                'modo'      => 'criar',
                'associado' => $dados,
                ...$this->dadosListasFormulario(),
            ]);
        }
    }

    public function ver(string $id): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $id;
        $associadoModelo = new Associado();
        $associado = $associadoModelo->encontrarCompletoPorId($idAssociado);

        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $this->vista('associados/show', [
            'titulo'      => $associado['Nome'],
            'associado'   => $associado,
            'secaoActual' => $associadoModelo->secaoActual($idAssociado),
            'companhiaActual' => $associadoModelo->companhiaActual($idAssociado),
            'morada'      => (new Morada())->moradaActivaDaPessoa((int) $associado['IdPessoa']),
            'contactos'   => (new Contacto())->listarDaPessoa((int) $associado['IdPessoa']),
            'encarregados' => (new EncarregadoEducacao())->listarDoAssociado($idAssociado),
            'emergencia'  => (new ContactoEmergencia())->listarDoAssociado($idAssociado),
            'fichaSaude'  => (new FichaSaude())->porAssociado($idAssociado),
            'consentimento' => (new Consentimento())->maisRecenteDoAssociado($idAssociado),
            'eventos'     => (new EventoAssociado())->listarDoAssociado($idAssociado),
            'hojePt'      => Data::hojePt(),
        ]);
    }

    public function editar(string $id): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $id;
        $associadoModelo = new Associado();
        $associado = $associadoModelo->encontrarCompletoPorId($idAssociado);

        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        // Apresentar as datas ao utilizador sempre em dd/mm/aaaa.
        $associado['DataNascimento'] = Data::paraApresentacao($associado['DataNascimento']);

        $this->vista('associados/editar', [
            'titulo'          => 'Editar — ' . $associado['Nome'],
            'associado'       => $associado,
            'secaoActual'     => $associadoModelo->secaoActual($idAssociado),
            'companhiaActual' => $associadoModelo->companhiaActual($idAssociado),
            ...$this->dadosListasFormulario(),
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $associadoModelo = new Associado();
        $associadoExistente = $associadoModelo->encontrarCompletoPorId($idAssociado);

        if (!$associadoExistente) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $dados = $_POST;
        // Na edição não se altera a data de inscrição — só validamos a de nascimento.
        $dados['DataInscricao'] = Data::paraApresentacao($associadoExistente['DataInscricao']);
        $erros = $this->validarDadosAssociado($dados, validarInscricao: false);

        // Regra 27: ao mudar para a secção "Chefia", o associado já tem de ter
        // um contacto "Email Associativo" registado (gerido em "Gerir contactos").
        if (!empty($dados['IdSecao']) && (new Secao())->ehChefia((int) $dados['IdSecao'])) {
            $temEmailAssociativo = (new Contacto())->temTipo((int) $associadoExistente['IdPessoa'], 'Email Associativo');
            if (!$temEmailAssociativo) {
                $erros[] = 'Para atribuir a secção "Chefia" é necessário que o associado já tenha um contacto "Email Associativo" — adicione-o primeiro em "Gerir contactos".';
            }
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/associados/' . $idAssociado . '/editar');
            return;
        }

        try {
            $associadoModelo->actualizarDadosBase($idAssociado, (int) $associadoExistente['IdPessoa'], $dados);

            $hoje = Data::hojeBd();
            if (!empty($dados['IdSecao'])) {
                $associadoModelo->atribuirSecao($idAssociado, (int) $dados['IdSecao'], $hoje);
            }
            if (!empty($dados['IdCompanhia'])) {
                $associadoModelo->atribuirCompanhia($idAssociado, (int) $dados['IdCompanhia'], $hoje);
            }

            Sessao::guardarMensagem('sucesso', 'Dados do associado actualizados com sucesso.');
            $this->redirecionar('/associados/' . $idAssociado);
        } catch (\Throwable $e) {
            error_log('[SIGA] Erro ao actualizar associado: ' . $e->getMessage());
            Sessao::guardarMensagem('erro', 'Não foi possível actualizar o associado. Tente novamente.');
            $this->redirecionar('/associados/' . $idAssociado . '/editar');
        }
    }

    /**
     * A desactivação é, em si mesma, consequência de um evento (regra 9.2):
     * regista sempre um evento "Desactivação", com a data indicada pelo utilizador
     * (sugerida como hoje, nunca posterior a hoje).
     */
    public function desativar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $dataBd = $this->validarDataEvento($_POST['DataEvento'] ?? '');

        if ($dataBd === null) {
            Sessao::guardarMensagem('erro', 'Indique uma data de desactivação válida (não pode ser posterior a hoje).');
            $this->redirecionar('/associados/' . $idAssociado);
            return;
        }

        $observacoes = trim($_POST['Observacoes'] ?? '') ?: null;

        (new Associado())->desactivar($idAssociado, $dataBd, $observacoes);
        Sessao::guardarMensagem('sucesso', 'Associado desactivado.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    /**
     * A reactivação (regra 10.2) restaura o estado activo, preserva o histórico
     * e NÃO associa automaticamente a nenhuma companhia — isso fica disponível
     * como uma acção separada, através da edição do associado.
     */
    public function reativar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $dataBd = $this->validarDataEvento($_POST['DataEvento'] ?? '');

        if ($dataBd === null) {
            Sessao::guardarMensagem('erro', 'Indique uma data de reactivação válida (não pode ser posterior a hoje).');
            $this->redirecionar('/associados/' . $idAssociado);
            return;
        }

        $observacoes = trim($_POST['Observacoes'] ?? '') ?: null;

        (new Associado())->reactivar($idAssociado, $dataBd, $observacoes);
        Sessao::guardarMensagem('sucesso', 'Associado reactivado.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    /**
     * Converte e valida uma data de evento vinda do formulário (dd/mm/aaaa):
     * tem de ser uma data válida e não pode ser posterior a hoje (regra 8.4).
     */
    private function validarDataEvento(string $dataPt): ?string
    {
        $dataBd = Data::paraBd($dataPt);
        if ($dataBd === null || Data::eFutura($dataBd)) {
            return null;
        }
        return $dataBd;
    }

    /**
     * Valida e, em caso de sucesso, converte no próprio array $dados as datas
     * de dd/mm/aaaa para aaaa-mm-dd (o formato usado a partir daqui pelos modelos).
     */
    private function validarDadosAssociado(array &$dados, bool $validarInscricao = true): array
    {
        $erros = [];

        if (empty(trim($dados['Nome'] ?? ''))) {
            $erros[] = 'O nome é obrigatório.';
        }

        // --- Data de nascimento: obrigatória, válida, nunca futura (regra 8.4). ---
        $nascimentoBd = Data::paraBd($dados['DataNascimento'] ?? '');
        if ($nascimentoBd === null) {
            $erros[] = 'A data de nascimento é obrigatória e deve estar em formato dd/mm/aaaa.';
        } elseif (Data::eFutura($nascimentoBd)) {
            $erros[] = 'A data de nascimento não pode ser futura.';
        } else {
            $dados['DataNascimento'] = $nascimentoBd;
        }

        // --- Data de inscrição: obrigatória, válida, pode ser passada, nunca futura. ---
        if ($validarInscricao) {
            $inscricaoBd = Data::paraBd($dados['DataInscricao'] ?? '');
            if ($inscricaoBd === null) {
                $erros[] = 'A data de inscrição é obrigatória e deve estar em formato dd/mm/aaaa.';
            } elseif (Data::eFutura($inscricaoBd)) {
                $erros[] = 'A data de inscrição não pode ser posterior a hoje.';
            } else {
                $dados['DataInscricao'] = $inscricaoBd;
            }
        }

        if (!in_array($dados['Genero'] ?? '', ['M', 'F', 'O'], true)) {
            $erros[] = 'O género é obrigatório.';
        }
        if (($dados['Genero'] ?? '') === 'O' && trim($dados['NominativoOutro'] ?? '') === '') {
            $erros[] = 'Para género "Outro" é obrigatório indicar o nominativo a utilizar.';
        }
        if (!empty($dados['NumeroCartaoUtente']) && !preg_match('/^\d{9}$/', $dados['NumeroCartaoUtente'])) {
            $erros[] = 'O número de utente de saúde deve ter exactamente 9 dígitos.';
        }

        // Regra 6: nunca perder zeros à esquerda; preenche automaticamente
        // assim que a largura for confirmada em config/config.php.
        if (!empty($dados['NumeroDocumentoIdentificacao'])) {
            $dados['NumeroDocumentoIdentificacao'] = Documentos::preencherComZeros(
                $dados['NumeroDocumentoIdentificacao'],
                $this->config['documentos']['largura_cc'] ?? null
            );
        }

        return $erros;
    }

    private function dadosListasFormulario(): array
    {
        return [
            'nacionalidades' => Lookup::listar('nacionalidades'),
            'estadosCivis'   => Lookup::listar('estados_civis'),
            'confissoes'     => Lookup::listar('confissoes_religiosas'),
            'tiposDocumento' => Lookup::listar('tipos_documento_identificacao'),
            'tiposRelacao'   => Lookup::listar('tipos_relacao'),
            'secoes'         => (new Secao())->listarTodas(),
            'companhias'     => (new Companhia())->listarAtivas(),
        ];
    }
}
