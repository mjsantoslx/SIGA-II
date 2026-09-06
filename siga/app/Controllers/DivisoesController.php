<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Secao;

/**
 * Backoffice de gestão de divisões (tabela secoes) — acesso restrito a
 * administradores. Fora do mecanismo genérico de referências porque tem
 * campos próprios (nominativo masculino/feminino).
 */
class DivisoesController extends Controller
{
    public function index(): void
    {
        $this->exigirAdministrador();

        $this->vista('divisoes/index', [
            'titulo'   => 'Divisões',
            'divisoes' => (new Secao())->listarTodas(),
        ]);
    }

    public function criar(): void
    {
        $this->exigirAdministrador();

        $this->vista('divisoes/form', [
            'titulo'  => 'Nova divisão',
            'divisao' => null,
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $dados = $_POST;
        $secaoModelo = new Secao();
        $erros = $this->validar($secaoModelo, $dados, null);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('divisoes/form', [
                'titulo'  => 'Nova divisão',
                'divisao' => $dados,
            ]);
            return;
        }

        $secaoModelo->criar(trim($dados['Designacao']), trim($dados['NominativoMasculino']), trim($dados['NominativoFeminino']));
        Sessao::guardarMensagem('sucesso', 'Divisão criada com sucesso.');
        $this->redirecionar('/admin/divisoes');
    }

    public function editar(string $id): void
    {
        $this->exigirAdministrador();

        $divisao = (new Secao())->encontrarPorId((int) $id);
        if (!$divisao) {
            Sessao::guardarMensagem('erro', 'Divisão não encontrada.');
            $this->redirecionar('/admin/divisoes');
            return;
        }

        $this->vista('divisoes/form', [
            'titulo'  => 'Editar — ' . $divisao['Designacao'],
            'divisao' => $divisao,
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $idDivisao = (int) $id;
        $secaoModelo = new Secao();
        $divisaoExistente = $secaoModelo->encontrarPorId($idDivisao);

        if (!$divisaoExistente) {
            Sessao::guardarMensagem('erro', 'Divisão não encontrada.');
            $this->redirecionar('/admin/divisoes');
            return;
        }

        $dados = $_POST;
        $erros = $this->validar($secaoModelo, $dados, $idDivisao);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/admin/divisoes/' . $idDivisao . '/editar');
            return;
        }

        $secaoModelo->actualizarDados($idDivisao, trim($dados['Designacao']), trim($dados['NominativoMasculino']), trim($dados['NominativoFeminino']));
        Sessao::guardarMensagem('sucesso', 'Divisão actualizada com sucesso.');
        $this->redirecionar('/admin/divisoes');
    }

    public function eliminar(string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $secaoModelo = new Secao();
        if ($secaoModelo->eliminarSeguro((int) $id)) {
            Sessao::guardarMensagem('sucesso', 'Divisão eliminada com sucesso.');
        } else {
            Sessao::guardarMensagem('erro', 'Não é possível eliminar — existem associados ou outros registos ligados a esta divisão.');
        }
        $this->redirecionar('/admin/divisoes');
    }

    private function validar(Secao $secaoModelo, array $dados, ?int $idAIgnorar): array
    {
        $erros = [];

        $designacao = trim($dados['Designacao'] ?? '');
        if ($designacao === '') {
            $erros[] = 'A designação é obrigatória.';
        } elseif ($secaoModelo->designacaoEmUso($designacao, $idAIgnorar)) {
            $erros[] = 'Já existe uma divisão com essa designação.';
        }

        if (trim($dados['NominativoMasculino'] ?? '') === '') {
            $erros[] = 'O nominativo masculino é obrigatório.';
        }
        if (trim($dados['NominativoFeminino'] ?? '') === '') {
            $erros[] = 'O nominativo feminino é obrigatório.';
        }

        return $erros;
    }
}
