<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Data;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\Contacto;
use App\Models\Lookup;
use App\Models\Morada;

/**
 * Página única de gestão de contactos do associado: morada (correcção vs.
 * substituição, reaproveitando as acções do MoradasController) e os
 * contactos generalizados — telemóvel, telefone, email, etc. (secção 16).
 */
class ContactosController extends Controller
{
    public function gerir(string $id): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $id;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);

        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $idPessoa = (int) $associado['IdPessoa'];
        $moradaModelo = new Morada();
        $ligacaoMorada = $moradaModelo->ligacaoActivaDaPessoa($idPessoa);

        $this->vista('associados/contactos', [
            'titulo'          => 'Contactos — ' . $associado['Nome'],
            'associado'       => $associado,
            'ligacaoMorada'   => $ligacaoMorada,
            'partilhasMorada' => $ligacaoMorada ? $moradaModelo->contarLigacoesActivas((int) $ligacaoMorada['Id']) : 0,
            'contactos'       => (new Contacto())->listarDaPessoa($idPessoa),
            'tiposContacto'   => Lookup::listar('tipos_contacto'),
            'hojePt'          => Data::hojePt(),
        ]);
    }

    public function adicionar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $idTipoContacto = (int) ($_POST['IdTipoContacto'] ?? 0);
        $valor = trim($_POST['Valor'] ?? '');

        if ($idTipoContacto <= 0 || $valor === '') {
            Sessao::guardarMensagem('erro', 'Indique o tipo de contacto e o respectivo valor.');
            $this->redirecionar('/associados/' . $idAssociado . '/contactos');
            return;
        }

        (new Contacto())->criar((int) $associado['IdPessoa'], $idTipoContacto, $valor);
        Sessao::guardarMensagem('sucesso', 'Contacto adicionado.');
        $this->redirecionar('/associados/' . $idAssociado . '/contactos');
    }

    public function editar(string $id, string $idContacto): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $idContacto = (int) $idContacto;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $contactoModelo = new Contacto();
        if (!$contactoModelo->pertenceAPessoa($idContacto, (int) $associado['IdPessoa'])) {
            Sessao::guardarMensagem('erro', 'Contacto não encontrado.');
            $this->redirecionar('/associados/' . $idAssociado . '/contactos');
            return;
        }

        $idTipoContacto = (int) ($_POST['IdTipoContacto'] ?? 0);
        $valor = trim($_POST['Valor'] ?? '');

        if ($idTipoContacto <= 0 || $valor === '') {
            Sessao::guardarMensagem('erro', 'Indique o tipo de contacto e o respectivo valor.');
            $this->redirecionar('/associados/' . $idAssociado . '/contactos');
            return;
        }

        $contactoModelo->actualizarValor($idContacto, $idTipoContacto, $valor);
        Sessao::guardarMensagem('sucesso', 'Contacto actualizado.');
        $this->redirecionar('/associados/' . $idAssociado . '/contactos');
    }

    public function remover(string $id, string $idContacto): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $idContacto = (int) $idContacto;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $contactoModelo = new Contacto();
        if (!$contactoModelo->pertenceAPessoa($idContacto, (int) $associado['IdPessoa'])) {
            Sessao::guardarMensagem('erro', 'Contacto não encontrado.');
            $this->redirecionar('/associados/' . $idAssociado . '/contactos');
            return;
        }

        $contactoModelo->eliminar($idContacto);
        Sessao::guardarMensagem('sucesso', 'Contacto removido.');
        $this->redirecionar('/associados/' . $idAssociado . '/contactos');
    }
}
