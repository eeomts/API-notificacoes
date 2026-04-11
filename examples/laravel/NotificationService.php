<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

/**
 * Integração com a FCM Notification API — Laravel
 *
 * Instalação:
 *   1. Copie este arquivo para app/Services/NotificationService.php
 *   2. Adicione as variáveis no seu .env:
 *        NOTIFICATION_API_URL=https://sua-api.com
 *        NOTIFICATION_API_KEY=sua-chave-secreta
 *   3. Registre no AppServiceProvider ou use injeção de dependência
 */
class NotificationService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.notifications.url'), '/');
        $this->apiKey  = config('services.notifications.key');
    }

    /**
     * Registra ou atualiza o token FCM de um dispositivo.
     */
    public function registerToken(string $fcmToken, string $platform, ?string $userId = null, array $extra = []): array
    {
        return $this->client()
            ->post('/api/tokens', array_filter([
                'fcm_token' => $fcmToken,
                'platform'  => $platform,
                'user_id'   => $userId,
                'extra'     => $extra ?: null,
            ]))
            ->throw()
            ->json();
    }

    /**
     * Remove o token FCM de um dispositivo.
     */
    public function removeToken(string $fcmToken): array
    {
        return $this->client()
            ->delete('/api/tokens', ['fcm_token' => $fcmToken])
            ->throw()
            ->json();
    }

    /**
     * Envia notificação para um dispositivo específico.
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): array
    {
        return $this->client()
            ->post('/api/notifications/send-to-token', [
                'fcm_token' => $fcmToken,
                'title'     => $title,
                'body'      => $body,
                'data'      => $data,
            ])
            ->throw()
            ->json();
    }

    /**
     * Envia notificação para um tópico FCM.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        return $this->client()
            ->post('/api/notifications/send-to-topic', [
                'topic' => $topic,
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ])
            ->throw()
            ->json();
    }

    /**
     * Envia notificação para todos os dispositivos de um usuário.
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): array
    {
        return $this->client()
            ->post('/api/notifications/send-to-users', [
                'user_id' => $userId,
                'title'   => $title,
                'body'    => $body,
                'data'    => $data,
            ])
            ->throw()
            ->json();
    }

    /**
     * Envia notificação para todos os dispositivos ativos.
     */
    public function broadcast(string $title, string $body, array $data = [], ?string $platform = null): array
    {
        return $this->client()
            ->post('/api/notifications/broadcast', array_filter([
                'title'    => $title,
                'body'     => $body,
                'data'     => $data ?: null,
                'platform' => $platform,
            ]))
            ->throw()
            ->json();
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->timeout(15);
    }
}
