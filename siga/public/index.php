<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\AssociadosController;
use App\Controllers\AuthController;
use App\Controllers\CompanhiasController;
use App\Controllers\ContactosController;
use App\Controllers\DashboardController;
use App\Controllers\MoradasController;
use App\Controllers\UtilizadoresController;
use App\Core\Router;
use App\Core\Sessao;

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone']);
mb_internal_encoding('UTF-8');

Sessao::iniciar($config);

// Cabeçalhos de segurança básicos.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$router = new Router();

// Autenticação
$router->get('/login', [AuthController::class, 'mostrarLogin']);
$router->post('/login', [AuthController::class, 'autenticar']);
$router->post('/logout', [AuthController::class, 'terminarSessao']);

// Painel
$router->get('/', [DashboardController::class, 'index']);

// Associados
$router->get('/associados', [AssociadosController::class, 'index']);
$router->get('/associados/criar', [AssociadosController::class, 'criar']);
$router->post('/associados/criar', [AssociadosController::class, 'guardar']);
$router->get('/associados/{id}', [AssociadosController::class, 'ver']);
$router->get('/associados/{id}/editar', [AssociadosController::class, 'editar']);
$router->post('/associados/{id}/editar', [AssociadosController::class, 'atualizar']);
$router->post('/associados/{id}/desativar', [AssociadosController::class, 'desativar']);
$router->post('/associados/{id}/reativar', [AssociadosController::class, 'reativar']);

// Morada dos associados
$router->get('/associados/{id}/morada/editar', [MoradasController::class, 'editarPessoa']);
$router->post('/associados/{id}/morada/corrigir', [MoradasController::class, 'corrigirPessoa']);
$router->post('/associados/{id}/morada/substituir', [MoradasController::class, 'substituirPessoa']);

// Gestão de contactos do associado (morada + contactos generalizados)
$router->get('/associados/{id}/contactos', [ContactosController::class, 'gerir']);
$router->post('/associados/{id}/contactos/adicionar', [ContactosController::class, 'adicionar']);
$router->post('/associados/{id}/contactos/{idContacto}/editar', [ContactosController::class, 'editar']);
$router->post('/associados/{id}/contactos/{idContacto}/remover', [ContactosController::class, 'remover']);

// Companhias
$router->get('/companhias', [CompanhiasController::class, 'index']);
$router->get('/companhias/{id}', [CompanhiasController::class, 'ver']);
$router->get('/companhias/{id}/morada/editar', [MoradasController::class, 'editarCompanhia']);
$router->post('/companhias/{id}/morada/corrigir', [MoradasController::class, 'corrigirCompanhia']);
$router->post('/companhias/{id}/morada/substituir', [MoradasController::class, 'substituirCompanhia']);

// Utilizadores (acesso restrito a administradores)
$router->get('/utilizadores', [UtilizadoresController::class, 'index']);
$router->get('/utilizadores/criar', [UtilizadoresController::class, 'criar']);
$router->post('/utilizadores/criar', [UtilizadoresController::class, 'guardar']);
$router->get('/utilizadores/{id}/editar', [UtilizadoresController::class, 'editar']);
$router->post('/utilizadores/{id}/editar', [UtilizadoresController::class, 'atualizar']);

$router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
