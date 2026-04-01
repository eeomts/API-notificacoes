<?php
/**
 * Roteador HTTP simples - PHP 5.6
 *
 * Todas as rotas passam pelo index.php via .htaccess
 */

// Aplica CORS em todas as requisicoes
CorsMiddleware::handle();

// Normaliza o path
$requestUri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$scriptName    = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
$path          = parse_url($requestUri, PHP_URL_PATH);

// Remove o base path se a API estiver em subdiretorio
if ($scriptName && $scriptName !== '/' && strpos($path, $scriptName) === 0) {
    $path = substr($path, strlen($scriptName));
}

$path = '/' . ltrim($path, '/');

// -------------------------------------------------------
// Rota de saude (sem autenticacao)
// -------------------------------------------------------
if ($path === '/api/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Response::success(array(
        'status'  => 'ok',
        'version' => APP_VERSION,
        'time'    => date('c'),
    ), 'API funcionando.');
}

// -------------------------------------------------------
// Todas as rotas abaixo requerem autenticacao Bearer
// -------------------------------------------------------
AuthMiddleware::handle();

// Instancia controllers
$tokenController        = new TokenController();
$notificationController = new NotificationController();

// -------------------------------------------------------
// Rotas de Tokens de Dispositivos
// -------------------------------------------------------
// POST   /api/tokens  - registrar/atualizar token
// GET    /api/tokens  - listar tokens
// DELETE /api/tokens  - remover token
if ($path === '/api/tokens') {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $tokenController->saveToken();
            break;
        case 'GET':
            $tokenController->listTokens();
            break;
        case 'DELETE':
            $tokenController->deleteToken();
            break;
        default:
            Response::methodNotAllowed();
    }
}

// -------------------------------------------------------
// Rotas de Notificacoes
// -------------------------------------------------------
// POST /api/notifications/send-to-token
if ($path === '/api/notifications/send-to-token') {
    $notificationController->sendToToken();
}

// POST /api/notifications/send-to-topic
if ($path === '/api/notifications/send-to-topic') {
    $notificationController->sendToTopic();
}

// POST /api/notifications/send-to-users
if ($path === '/api/notifications/send-to-users') {
    $notificationController->sendToUsers();
}

// POST /api/notifications/broadcast
if ($path === '/api/notifications/broadcast') {
    $notificationController->broadcast();
}

// GET /api/notifications/logs
if ($path === '/api/notifications/logs') {
    $notificationController->logs();
}

// -------------------------------------------------------
// Rota nao encontrada
// -------------------------------------------------------
Response::notFound('Rota nao encontrada: ' . $path);
