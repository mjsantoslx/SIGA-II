<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\AssociadosController;
use App\Controllers\AuthController;
use App\Controllers\CompanhiasController;
use App\Controllers\DashboardController;
use App\Controllers\MoradasController;
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

// Companhias
$router->get('/companhias', [CompanhiasController::class, 'index']);
$router->get('/companhias/{id}', [CompanhiasController::class, 'ver']);
$router->get('/companhias/{id}/morada/editar', [MoradasController::class, 'editarCompanhia']);
$router->post('/companhias/{id}/morada/corrigir', [MoradasController::class, 'corrigirCompanhia']);
$router->post('/companhias/{id}/morada/substituir', [MoradasController::class, 'substituirCompanhia']);

$router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
