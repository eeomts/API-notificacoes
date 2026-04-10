<?php
/**
 * Servico de autenticacao OAuth2 com Google (Service Account)
 * Para FCM V1 - PHP 5.6
 *
 * O FCM V1 exige um Access Token OAuth2 obtido via JWT assinado
 * com a chave privada da Service Account.
 */
class GoogleAuthService
{
    private $serviceAccountPath;
    private $cacheFile;

    public function __construct()
    {
        $this->serviceAccountPath = FCM_SERVICE_ACCOUNT_PATH;
        $this->cacheFile          = TOKEN_CACHE_FILE;
    }

    /**
     * Retorna um Access Token valido (usa cache se nao expirou)
     */
    public function getAccessToken()
    {
        // Verifica cache
        $cached = $this->getCachedToken();
        if ($cached !== null) {
            return $cached;
        }

        // Gera novo token
        $token = $this->requestNewToken();
        $this->cacheToken($token);
        return $token['access_token'];
    }

    /**
     * Retorna token em cache se ainda valido (folga de 60s)
     */
    private function getCachedToken()
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->cacheFile), true);
        if (!$data || !isset($data['access_token'], $data['expires_at'])) {
            return null;
        }
        if (time() >= ($data['expires_at'] - 60)) {
            return null;
        }
        return $data['access_token'];
    }

    /**
     * Armazena token em cache com timestamp de expiracao
     */
    private function cacheToken($token)
    {
        $data = array(
            'access_token' => $token['access_token'],
            'expires_at'   => time() + (int)$token['expires_in'],
        );
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->cacheFile, json_encode($data), LOCK_EX);
    }

    /**
     * Solicita novo token via JWT ao Google OAuth2
     */
    private function requestNewToken()
    {
        if (!file_exists($this->serviceAccountPath)) {
            Logger::error('Arquivo service account nao encontrado: ' . $this->serviceAccountPath);
            Response::error('Configuracao do servidor incompleta.', 500);
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (!$serviceAccount) {
            Logger::error('Arquivo service account invalido.');
            Response::error('Configuracao do servidor incompleta.', 500);
        }

        $jwt = $this->buildJwt($serviceAccount);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => GOOGLE_TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Logger::error('Curl error ao obter token Google: ' . $curlError);
            Response::error('Erro de comunicacao com Google Auth.', 502);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !isset($data['access_token'])) {
            Logger::error('Erro ao obter token Google', array('http_code' => $httpCode, 'response' => $response));
            Response::error('Falha na autenticacao com Google.', 502);
        }

        Logger::info('Novo token Google obtido com sucesso.');
        return $data;
    }

    /**
     * Constroi o JWT assinado com a chave privada RSA da service account
     */
    private function buildJwt($serviceAccount)
    {
        $now = time();

        // Header
        $header = json_encode(array('alg' => 'RS256', 'typ' => 'JWT'));

        // Payload (Claims)
        $payload = json_encode(array(
            'iss'   => $serviceAccount['client_email'],
            'sub'   => $serviceAccount['client_email'],
            'aud'   => GOOGLE_TOKEN_URL,
            'scope' => FCM_SCOPE,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ));

        $base64Header   = $this->base64UrlEncode($header);
        $base64Payload  = $this->base64UrlEncode($payload);
        $signingInput   = $base64Header . '.' . $base64Payload;

        // Assina com chave privada RSA
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if (!$privateKey) {
            Logger::error('Chave privada da service account invalida.');
            Response::error('Configuracao do servidor incompleta.', 500);
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, 'SHA256')) {
            Logger::error('Falha ao assinar JWT.');
            Response::error('Erro interno de autenticacao.', 500);
        }

        // PHP 5.6: liberar recurso manualmente
        openssl_free_key($privateKey);

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
