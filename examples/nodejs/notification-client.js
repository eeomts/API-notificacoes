/**
 * Integração com a FCM Notification API — Node.js com Axios
 *
 * Instalação:
 *   npm install axios
 */

const axios = require('axios');

class NotificationClient {
  /**
   * @param {string} baseUrl  URL base da API (ex: https://sua-api.com)
   * @param {string} apiKey   Chave de autenticação Bearer
   */
  constructor(baseUrl, apiKey) {
    this.client = axios.create({
      baseURL: baseUrl.replace(/\/$/, ''),
      timeout: 15000,
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
      },
    });

    // Normaliza erros da API para mensagens legíveis
    this.client.interceptors.response.use(
      (res) => res.data,
      (err) => {
        const message = err.response?.data?.message ?? err.message;
        return Promise.reject(new Error(`API Error: ${message}`));
      }
    );
  }

  registerToken(fcmToken, platform, userId = null, extra = {}) {
    const body = { fcm_token: fcmToken, platform };
    if (userId)                       body.user_id = userId;
    if (Object.keys(extra).length)    body.extra   = extra;

    return this.client.post('/api/tokens', body);
  }

  removeToken(fcmToken) {
    return this.client.delete('/api/tokens', { data: { fcm_token: fcmToken } });
  }

  sendToToken(fcmToken, title, body, data = {}) {
    return this.client.post('/api/notifications/send-to-token', {
      fcm_token: fcmToken, title, body, data,
    });
  }

  sendToTopic(topic, title, body, data = {}) {
    return this.client.post('/api/notifications/send-to-topic', {
      topic, title, body, data,
    });
  }

  sendToUser(userId, title, body, data = {}) {
    return this.client.post('/api/notifications/send-to-users', {
      user_id: userId, title, body, data,
    });
  }

  broadcast(title, body, data = {}, platform = null) {
    const payload = { title, body, data };
    if (platform) payload.platform = platform;

    return this.client.post('/api/notifications/broadcast', payload);
  }

  healthCheck() {
    return this.client.get('/api/health');
  }
}

// -------------------------------------------------------------------
// Exemplos de uso
// -------------------------------------------------------------------

const api = new NotificationClient(
  process.env.NOTIFICATION_API_URL,
  process.env.NOTIFICATION_API_KEY
);

async function main() {
  // Verificar saúde
  const health = await api.healthCheck();
  console.log('Status:', health.data.status);

  // Registrar token
  await api.registerToken('token-fcm-aqui', 'android', 'user-123');

  // Enviar para um dispositivo
  await api.sendToToken(
    'token-fcm-aqui',
    'Novo pedido!',
    'Pedido #1234 recebido.',
    { order_id: '1234' }
  );

  // Notificar usuário
  await api.sendToUser('user-123', 'Entrega confirmada!', 'Pedido #1234 entregue.');
}

main().catch(console.error);

module.exports = NotificationClient;
