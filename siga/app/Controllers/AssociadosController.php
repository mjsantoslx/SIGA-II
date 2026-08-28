<?php

namespace App\Controllers;

use App\Core\Controller;
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
            'associado'   => null,
            ...$this->dadosListasFormulario(),
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $dados = $_POST;
        $erros = $this->validarDadosAssociado($dados);

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
            $idAssociado = (new Associado())->criarCompleto($dados);
            Sessao::guardarMensagem('sucesso', 'Associado registado com sucesso.');
            $this->redirecionar('/associados/' . $idAssociado);
        } catch (\Throwable $e) {
            error_log('[SIGA] Erro ao criar associado: ' . $e->getMessage());
            Sessao::guardarMensagem('erro', 'Não foi possível guardar o associado. Verifique os dados introduzidos (por exemplo, número de associado ou documento duplicado) e tente novamente.');
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
        $erros = $this->validarDadosAssociado($dados);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/associados/' . $idAssociado . '/editar');
            return;
        }

        try {
            $associadoModelo->actualizarDadosBase($idAssociado, (int) $associadoExistente['IdPessoa'], $dados);

            $hoje = date('Y-m-d');
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

    public function desativar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        (new Associado())->desactivar($idAssociado);
        Sessao::guardarMensagem('sucesso', 'Associado desactivado.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    public function reativar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        (new Associado())->reactivar($idAssociado);
        Sessao::guardarMensagem('sucesso', 'Associado reactivado.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    /**
     * Validações mínimas de negócio, alinhadas com as restrições da base de dados
     * (ex.: NominativoOutro obrigatório quando Genero = 'O').
     */
    private function validarDadosAssociado(array $dados): array
    {
        $erros = [];

        if (empty(trim($dados['Nome'] ?? ''))) {
            $erros[] = 'O nome é obrigatório.';
        }
        if (empty($dados['DataNascimento'] ?? '')) {
            $erros[] = 'A data de nascimento é obrigatória.';
        }
        if (empty($dados['DataInscricao'] ?? '')) {
            $erros[] = 'A data de inscrição é obrigatória.';
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
