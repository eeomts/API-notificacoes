<?php
/**
 * Controller: envio de notificacoes push via FCM V1
 * POST /api/notifications/send-to-token   -> envia para um dispositivo
 * POST /api/notifications/send-to-topic   -> envia para um topico
 * POST /api/notifications/send-to-users   -> envia para todos os tokens de um user_id
 * POST /api/notifications/broadcast       -> envia para todos os tokens ativos
 * GET  /api/notifications/logs            -> historico de envios
 **/
class NotificationController
{
    private $fcmService;
    private $deviceToken;
    private $notificationLog;

    public function __construct()
    {
        $this->fcmService       = new FcmService();
        $this->deviceToken      = new DeviceToken();
        $this->notificationLog  = new NotificationLog();
    }

    /**
     * POST /api/notifications/send-to-token
     *
     * Body JSON:
     *   fcm_token  string (obrigatorio)
     *   title      string (obrigatorio)
     *   body       string (obrigatorio)
     *   data       object (opcional) - payload customizado
     *   android    object (opcional) - config Android especifica
     *   apns       object (opcional) - config iOS/APNS especifica
     */
    public function sendToToken()
    {
        $this->assertPost();
        $input = $this->getJsonInput();

        $v = new Validator($input);
        $v->required('fcm_token', 'FCM Token')
          ->required('title', 'Titulo')
          ->required('body', 'Corpo')
          ->maxLength('title', 200, 'Titulo')
          ->maxLength('body', 1000, 'Corpo');

        if ($v->fails()) {
            Response::error('Dados invalidos.', 422, $v->errors());
        }

        $token  = trim($v->get('fcm_token'));
        $title  = $v->get('title');
        $body   = $v->get('body');
        $data   = $v->get('data', array());
        $extras = array();

        if ($v->get('android')) $extras['android'] = $v->get('android');
        if ($v->get('apns'))    $extras['apns']    = $v->get('apns');

        try {
            $result = $this->fcmService->sendToToken($token, $title, $body, $data, $extras);
            $this->notificationLog->create('token', $token, $title, $body, $data, 'success', $result);

            Response::success(
                array('message_id' => isset($result['name']) ? $result['name'] : null),
                'Notificacao enviada com sucesso.'
            );
        } catch (Exception $e) {
            $this->notificationLog->create('token', $token, $title, $body, $data, 'error', null, $e->getMessage());
            Logger::error('Erro ao enviar notificacao: ' . $e->getMessage());
            Response::error('Falha ao enviar notificacao: ' . $e->getMessage(), 502);
        }
    }

    /**
     * POST /api/notifications/send-to-topic
     *
     * Body JSON:
     *   topic  string (obrigatorio) - ex: "news", "promo"
     *   title  string (obrigatorio)
     *   body   string (obrigatorio)
     *   data   object (opcional)
     */
    public function sendToTopic()
    {
        $this->assertPost();
        $input = $this->getJsonInput();

        $v = new Validator($input);
        $v->required('topic', 'Topico')
          ->required('title', 'Titulo')
          ->required('body', 'Corpo');

        if ($v->fails()) {
            Response::error('Dados invalidos.', 422, $v->errors());
        }

        $topic = trim($v->get('topic'));
        $title = $v->get('title');
        $body  = $v->get('body');
        $data  = $v->get('data', array());

        try {
            $result = $this->fcmService->sendToTopic($topic, $title, $body, $data);
            $this->notificationLog->create('topic', $topic, $title, $body, $data, 'success', $result);

            Response::success(
                array('message_id' => isset($result['name']) ? $result['name'] : null),
                'Notificacao enviada ao topico com sucesso.'
            );
        } catch (Exception $e) {
            $this->notificationLog->create('topic', $topic, $title, $body, $data, 'error', null, $e->getMessage());
            Logger::error('Erro ao enviar para topico: ' . $e->getMessage());
            Response::error('Falha ao enviar notificacao: ' . $e->getMessage(), 502);
        }
    }

    /**
     * POST /api/notifications/send-to-users
     * Envia para todos os tokens cadastrados de um user_id.
     *
     * Body JSON:
     *   user_id  string (obrigatorio)
     *   title    string (obrigatorio)
     *   body     string (obrigatorio)
     *   data     object (opcional)
     */
    public function sendToUsers()
    {
        $this->assertPost();
        $input = $this->getJsonInput();

        $v = new Validator($input);
        $v->required('user_id', 'User ID')
          ->required('title', 'Titulo')
          ->required('body', 'Corpo');

        if ($v->fails()) {
            Response::error('Dados invalidos.', 422, $v->errors());
        }

        $userId = $v->get('user_id');
        $title  = $v->get('title');
        $body   = $v->get('body');
        $data   = $v->get('data', array());

        $userTokens = $this->deviceToken->findByUserId($userId);
        if (empty($userTokens)) {
            Response::error('Nenhum dispositivo encontrado para este usuario.', 404);
        }

        $tokens = array_map(function($t) { return $t['fcm_token']; }, $userTokens);

        try {
            $results = $this->fcmService->sendToMultipleTokens($tokens, $title, $body, $data);
            $this->notificationLog->create('user', $userId, $title, $body, $data, 'success', $results);

            Response::success($results, 'Notificacoes enviadas.');
        } catch (Exception $e) {
            $this->notificationLog->create('user', $userId, $title, $body, $data, 'error', null, $e->getMessage());
            Logger::error('Erro ao enviar para usuario: ' . $e->getMessage());
            Response::error('Falha ao enviar notificacoes.', 502);
        }
    }

    /**
     * POST /api/notifications/broadcast
     * Envia para TODOS os dispositivos ativos.
     *
     * Body JSON:
     *   title   string (obrigatorio)
     *   body    string (obrigatorio)
     *   data    object (opcional)
     *   platform string (opcional) - filtrar por plataforma
     */
    public function broadcast()
    {
        $this->assertPost();
        $input = $this->getJsonInput();

        $v = new Validator($input);
        $v->required('title', 'Titulo')
          ->required('body', 'Corpo');

        if ($v->fails()) {
            Response::error('Dados invalidos.', 422, $v->errors());
        }

        $title    = $v->get('title');
        $body     = $v->get('body');
        $data     = $v->get('data', array());
        $platform = $v->get('platform');

        $allTokens = $platform
            ? $this->deviceToken->findByPlatform($platform)
            : $this->deviceToken->findAllActive();

        if (empty($allTokens)) {
            Response::error('Nenhum dispositivo ativo encontrado.', 404);
        }

        $tokens = array_map(function($t) { return $t['fcm_token']; }, $allTokens);

        try {
            $results = $this->fcmService->sendToMultipleTokens($tokens, $title, $body, $data);
            $this->notificationLog->create('broadcast', $platform ?: 'all', $title, $body, $data, 'success', $results);

            Response::success($results, 'Broadcast enviado.');
        } catch (Exception $e) {
            $this->notificationLog->create('broadcast', $platform ?: 'all', $title, $body, $data, 'error', null, $e->getMessage());
            Logger::error('Erro no broadcast: ' . $e->getMessage());
            Response::error('Falha no broadcast.', 502);
        }
    }

    /**
     * GET /api/notifications/logs
     * Retorna historico paginado de notificacoes enviadas.
     */
    public function logs()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed();
        }

        $limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit  = min($limit, 200);

        try {
            $logs = $this->notificationLog->findAll($limit, $offset);
            Response::success(array('logs' => $logs, 'total' => count($logs)));
        } catch (Exception $e) {
            Logger::error('Erro ao buscar logs: ' . $e->getMessage());
            Response::error('Erro ao buscar logs.', 500);
        }
    }

    private function assertPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::methodNotAllowed();
        }
    }

    private function getJsonInput()
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : array();
    }
}
