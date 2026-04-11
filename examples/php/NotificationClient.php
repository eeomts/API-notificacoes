<?php
/**
 * Exemplo de integração com a FCM Notification API — PHP Vanilla
 *
 * Copie esta classe para seu projeto e configure a URL e a chave.
 */
class NotificationClient
{
    private $baseUrl;
    private $apiKey;

    public function __construct($baseUrl, $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;
    }

    /**
     * Registra ou atualiza o token FCM de um dispositivo.
     *
     * @param string $fcmToken  Token gerado pelo Firebase SDK no dispositivo
     * @param string $platform  android | ios | web
     * @param string $userId    ID do usuário no seu sistema (opcional)
     * @param array  $extra     Dados extras: versão do app, modelo do device, etc.
     */
    public function registerToken($fcmToken, $platform, $userId = null, $extra = array())
    {
        $payload = array(
            'fcm_token' => $fcmToken,
            'platform'  => $platform,
        );

        if ($userId !== null) {
            $payload['user_id'] = $userId;
        }
        if (!empty($extra)) {
            $payload['extra'] = $extra;
        }

        return $this->post('/api/tokens', $payload);
    }

    /**
     * Remove o token FCM de um dispositivo.
     */
    public function removeToken($fcmToken)
    {
        return $this->delete('/api/tokens', array('fcm_token' => $fcmToken));
    }

    /**
     * Envia notificação para um dispositivo específico pelo token FCM.
     *
     * @param string $fcmToken Token do dispositivo destino
     * @param string $title    Título da notificação
     * @param string $body     Corpo da notificação
     * @param array  $data     Payload customizado (ex: order_id, action)
     */
    public function sendToToken($fcmToken, $title, $body, $data = array())
    {
        return $this->post('/api/notifications/send-to-token', array(
            'fcm_token' => $fcmToken,
            'title'     => $title,
            'body'      => $body,
            'data'      => $data,
        ));
    }

    /**
     * Envia notificação para um tópico FCM.
     * Dispositivos precisam estar inscritos no tópico via Firebase SDK.
     *
     * @param string $topic Nome do tópico (ex: "noticias", "promocoes")
     */
    public function sendToTopic($topic, $title, $body, $data = array())
    {
        return $this->post('/api/notifications/send-to-topic', array(
            'topic' => $topic,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ));
    }

    /**
     * Envia notificação para todos os dispositivos de um usuário.
     * Útil quando o usuário tem múltiplos dispositivos (celular + tablet, etc).
     *
     * @param string $userId ID do usuário no seu sistema
     */
    public function sendToUser($userId, $title, $body, $data = array())
    {
        return $this->post('/api/notifications/send-to-users', array(
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ));
    }

    /**
     * Envia notificação para todos os dispositivos ativos.
     *
     * @param string|null $platform Filtrar por plataforma: android | ios | web (opcional)
     */
    public function broadcast($title, $body, $data = array(), $platform = null)
    {
        $payload = array(
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        );

        if ($platform !== null) {
            $payload['platform'] = $platform;
        }

        return $this->post('/api/notifications/broadcast', $payload);
    }

    /**
     * Verifica se a API está no ar.
     */
    public function healthCheck()
    {
        return $this->get('/api/health');
    }

    // ----------------------------------------------------------------
    // HTTP helpers
    // ----------------------------------------------------------------

    private function post($path, $data)
    {
        return $this->request('POST', $path, $data);
    }

    private function get($path, $params = array())
    {
        $url = $this->baseUrl . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url);
    }

    private function delete($path, $data)
    {
        return $this->request('DELETE', $path, $data);
    }

    private function request($method, $path, $data = null)
    {
        $url     = $this->baseUrl . $path;
        $payload = $data !== null ? json_encode($data) : null;

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ));

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('Erro de conexão com a API: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = isset($decoded['message']) ? $decoded['message'] : 'Erro desconhecido';
            throw new RuntimeException('API retornou erro ' . $httpCode . ': ' . $message);
        }

        return $decoded;
    }
}


// ----------------------------------------------------------------
// Exemplos de uso
// ----------------------------------------------------------------

$api = new NotificationClient(
    'https://sua-api.com',
    'sua-chave-secreta'
);

// 1. Verificar saúde da API
$health = $api->healthCheck();
echo $health['data']['status']; // ok

// 2. Registrar token de um novo dispositivo Android
$api->registerToken(
    'token-fcm-do-dispositivo',
    'android',
    '123',                          // user_id do seu sistema
    array('app_version' => '2.1.0') // extra
);

// 3. Enviar notificação para um dispositivo específico
$api->sendToToken(
    'token-fcm-do-dispositivo',
    'Novo pedido!',
    'Você tem um novo pedido #1234',
    array('order_id' => '1234', 'action' => 'open_order')
);

// 4. Notificar todos os dispositivos do usuário 123
$api->sendToUser(
    '123',
    'Sua entrega chegou!',
    'O pedido #1234 foi entregue.'
);

// 5. Enviar para tópico
$api->sendToTopic('promocoes', 'Oferta especial!', '50% de desconto hoje.');

// 6. Broadcast para todos
$api->broadcast('Manutenção', 'O sistema ficará em manutenção às 02h.');

// 7. Broadcast só para Android
$api->broadcast('Atualização', 'Nova versão disponível.', array(), 'android');
