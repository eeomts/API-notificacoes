<?php
class FcmService
{
    private $authService;

    private $fcmUrl;

    public function __construct($appId = null)
    {
        $this->authService = new GoogleAuthService($appId);

        if ($appId !== null) {
            $accountPath = __DIR__ . '/../../storage/accounts/' . $appId . '.json';
            $account = json_decode(file_get_contents($accountPath), true);
            $projectId = $account['project_id'];
        } else {
            $projectId = FIREBASE_PROJECT_ID;
        }

        $this->fcmUrl = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
    }

    public function sendToToken($token, $title, $body, $data = array(), $extras = array())
    {
        $message = array(
            'token' => $token,
            'notification' => array(
                'title' => $title,
                'body' => $body,
            ),
        );

        if (!empty($data)) {
            // FCM exige que todos os valores do data payload sejam strings
            $message['data'] = array_map('strval', $data);
        }

        // Opcoes especificas por plataforma
        if (isset($extras['android'])) {
            $message['android'] = $extras['android'];
        }
        if (isset($extras['apns'])) {
            $message['apns'] = $extras['apns'];
        }
        if (isset($extras['webpush'])) {
            $message['webpush'] = $extras['webpush'];
        }

        return $this->send($message);
    }

    public function sendToTopic($topic, $title, $body, $data = array())
    {
        $message = array(
            'topic' => $topic,
            'notification' => array(
                'title' => $title,
                'body' => $body,
            ),
        );

        if (!empty($data)) {
            $message['data'] = array_map('strval', $data);
        }

        return $this->send($message);
    }

    public function sendToMultipleTokens($tokens, $title, $body, $data = array())
    {
        $results = array(
            'total' => count($tokens),
            'success' => 0,
            'failure' => 0,
            'details' => array(),
        );

        foreach ($tokens as $token) {
            try {
                $result = $this->sendToToken($token, $title, $body, $data);
                $results['success']++;
                $results['details'][] = array(
                    'token' => $this->maskToken($token),
                    'success' => true,
                    'message_id' => isset($result['name']) ? $result['name'] : null,
                );
            } catch (Exception $e) {
                $results['failure']++;
                $results['details'][] = array(
                    'token' => $this->maskToken($token),
                    'success' => false,
                    'error' => $e->getMessage(),
                );
            }
        }

        return $results;
    }

    private function send($message)
    {
        $accessToken = $this->authService->getAccessToken();

        $payload = json_encode(array('message' => $message), JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->fcmUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Logger::error('Curl error no envio FCM: ' . $curlError);
            throw new Exception('Erro de comunicacao com FCM: ' . $curlError);
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = isset($responseData['error']['message'])
                ? $responseData['error']['message']
                : 'Erro desconhecido do FCM';
            Logger::error('Erro FCM', array('http_code' => $httpCode, 'response' => $response));
            throw new Exception('FCM Error (' . $httpCode . '): ' . $errorMsg);
        }

        Logger::info('Notificacao enviada com sucesso', array('message_id' => $responseData['name']));
        return $responseData;
    }

    /**
     * Mascara o token para exibicao segura em logs
     */
    private function maskToken($token)
    {
        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }
        return substr($token, 0, 6) . '...' . substr($token, -6);
    }
}
