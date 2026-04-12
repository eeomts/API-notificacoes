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

if ($path === '/api/docs/openapi.yaml' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $yamlFile = __DIR__ . '/../docs/openapi.yaml';
    header('Content-Type: application/yaml');
    readfile($yamlFile);
    exit;
}

if ($path === '/api/docs' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $specUrl = '/api/api-notificacoes/docs/openapi.yaml';
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>API Notificações - Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    SwaggerUIBundle({
      url: "{$specUrl}",
      dom_id: '#swagger-ui',
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
      layout: "BaseLayout",
      deepLinking: true,
    });
  </script>
</body>
</html>
HTML;
    exit;
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
