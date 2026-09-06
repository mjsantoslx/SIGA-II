<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\ReferenciaGenerica;

/**
 * Backoffice de tabelas de referência simples — acesso restrito a
 * administradores. O "tabela" que vem do URL é sempre validado contra
 * esta whitelist fixa antes de ser usado em qualquer consulta; nunca é
 * aceite directamente como nome de tabela/coluna SQL.
 */
class ReferenciasController extends Controller
{
    private const CONFIGURACAO = [
        'nacionalidades' => [
            'tabela' => 'nacionalidades', 'coluna' => 'Nacionalidade', 'activo' => false,
            'titulo' => 'Nacionalidades', 'singular' => 'nacionalidade',
        ],
        'estados-civis' => [
            'tabela' => 'estados_civis', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Estados civis', 'singular' => 'estado civil',
        ],
        'confissoes-religiosas' => [
            'tabela' => 'confissoes_religiosas', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Confissões religiosas', 'singular' => 'confissão religiosa',
        ],
        'tipos-documento' => [
            'tabela' => 'tipos_documento_identificacao', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Tipos de documento de identificação', 'singular' => 'tipo de documento',
        ],
        'tipos-contacto' => [
            'tabela' => 'tipos_contacto', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Tipos de contacto', 'singular' => 'tipo de contacto',
        ],
        'tipos-relacao' => [
            'tabela' => 'tipos_relacao', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Tipos de relação', 'singular' => 'tipo de relação',
        ],
        'tipos-evento' => [
            'tabela' => 'tipos_evento', 'coluna' => 'Designacao', 'activo' => false,
            'titulo' => 'Tipos de evento', 'singular' => 'tipo de evento',
        ],
        'orgaos' => [
            'tabela' => 'orgaos', 'coluna' => 'Designacao', 'activo' => true,
            'titulo' => 'Órgãos', 'singular' => 'órgão',
        ],
        'cargos' => [
            'tabela' => 'cargos', 'coluna' => 'Designacao', 'activo' => true,
            'titulo' => 'Cargos', 'singular' => 'cargo',
        ],
    ];

    private function configOuFalhar(string $chave): ?array
    {
        if (!array_key_exists($chave, self::CONFIGURACAO)) {
            Sessao::guardarMensagem('erro', 'Tabela de referência desconhecida.');
            $this->redirecionar('/admin');
            return null;
        }
        return self::CONFIGURACAO[$chave] + ['chave' => $chave];
    }

    public function index(string $chave): void
    {
        $this->exigirAdministrador();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $modelo = new ReferenciaGenerica();
        $this->vista('referencias/index', [
            'titulo'   => $cfg['titulo'],
            'cfg'      => $cfg,
            'registos' => $modelo->listar($cfg['tabela'], $cfg['coluna']),
        ]);
    }

    public function criar(string $chave): void
    {
        $this->exigirAdministrador();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $this->vista('referencias/form', [
            'titulo'   => 'Novo — ' . $cfg['titulo'],
            'cfg'      => $cfg,
            'registo'  => null,
        ]);
    }

    public function guardar(string $chave): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $modelo = new ReferenciaGenerica();
        $valor = trim($_POST['Valor'] ?? '');
        $erros = $this->validar($modelo, $cfg, $valor, null);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('referencias/form', [
                'titulo'  => 'Novo — ' . $cfg['titulo'],
                'cfg'     => $cfg,
                'registo' => [$cfg['coluna'] => $valor],
            ]);
            return;
        }

        $modelo->criar($cfg['tabela'], $cfg['coluna'], $valor, $cfg['activo']);
        Sessao::guardarMensagem('sucesso', ucfirst($cfg['singular']) . ' criado(a) com sucesso.');
        $this->redirecionar('/admin/referencias/' . $chave);
    }

    public function editar(string $chave, string $id): void
    {
        $this->exigirAdministrador();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $modelo = new ReferenciaGenerica();
        $registo = $modelo->encontrarPorId($cfg['tabela'], (int) $id);
        if (!$registo) {
            Sessao::guardarMensagem('erro', 'Registo não encontrado.');
            $this->redirecionar('/admin/referencias/' . $chave);
            return;
        }

        $this->vista('referencias/form', [
            'titulo'  => 'Editar — ' . $cfg['titulo'],
            'cfg'     => $cfg,
            'registo' => $registo,
        ]);
    }

    public function atualizar(string $chave, string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $idRegisto = (int) $id;
        $modelo = new ReferenciaGenerica();
        $valor = trim($_POST['Valor'] ?? '');
        $erros = $this->validar($modelo, $cfg, $valor, $idRegisto);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/admin/referencias/' . $chave . '/' . $idRegisto . '/editar');
            return;
        }

        $activo = $cfg['activo'] ? !empty($_POST['Activo']) : null;
        $modelo->actualizar($cfg['tabela'], $cfg['coluna'], $idRegisto, $valor, $activo);
        Sessao::guardarMensagem('sucesso', ucfirst($cfg['singular']) . ' actualizado(a) com sucesso.');
        $this->redirecionar('/admin/referencias/' . $chave);
    }

    public function eliminar(string $chave, string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();
        $cfg = $this->configOuFalhar($chave);
        if (!$cfg) {
            return;
        }

        $modelo = new ReferenciaGenerica();
        if ($modelo->eliminar($cfg['tabela'], (int) $id)) {
            Sessao::guardarMensagem('sucesso', ucfirst($cfg['singular']) . ' eliminado(a) com sucesso.');
        } else {
            Sessao::guardarMensagem('erro', 'Não é possível eliminar — este registo está em uso noutro sítio da aplicação. Se aplicável, desactive-o em vez de o eliminar.');
        }
        $this->redirecionar('/admin/referencias/' . $chave);
    }

    private function validar(ReferenciaGenerica $modelo, array $cfg, string $valor, ?int $idAIgnorar): array
    {
        $erros = [];
        if ($valor === '') {
            $erros[] = 'A designação é obrigatória.';
        } elseif ($modelo->valorEmUso($cfg['tabela'], $cfg['coluna'], $valor, $idAIgnorar)) {
            $erros[] = 'Já existe um registo com essa designação.';
        }
        return $erros;
    }
}
