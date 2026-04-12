<?php
CorsMiddleware::handle();

$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
$path = parse_url($requestUri, PHP_URL_PATH);

if ($scriptName && $scriptName !== '/' && strpos($path, $scriptName) === 0) {
    $path = substr($path, strlen($scriptName));
}

$path = '/' . ltrim($path, '/');

if ($path === '/api/health' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Response::success(array(
        'status'  => 'ok',
        'version' => APP_VERSION,
        'time'    => date('c'),
    ), 'API funcionando.');
}

$appId = AuthMiddleware::handle();

$tokenController = new TokenController();
$notificationController = new NotificationController($appId);

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

Response::notFound('Rota nao encontrada: ' . $path);