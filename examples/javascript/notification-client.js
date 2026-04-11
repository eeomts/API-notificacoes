/**
 * Integração com a FCM Notification API — JavaScript (Fetch API)
 * Funciona no browser e no Node.js 18+
 */

class NotificationClient {
  /**
   * @param {string} baseUrl  URL base da API (ex: https://sua-api.com)
   * @param {string} apiKey   Chave de autenticação Bearer
   */
  constructor(baseUrl, apiKey) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.apiKey  = apiKey;
  }

  /**
   * Registra ou atualiza o token FCM de um dispositivo.
   *
   * @param {string} fcmToken  Token gerado pelo Firebase SDK
   * @param {string} platform  android | ios | web
   * @param {string} [userId]  ID do usuário no seu sistema
   * @param {object} [extra]   Dados extras (versão do app, etc.)
   */
  registerToken(fcmToken, platform, userId = null, extra = {}) {
    const body = { fcm_token: fcmToken, platform };
    if (userId) body.user_id = userId;
    if (Object.keys(extra).length) body.extra = extra;

    return this.#post('/api/tokens', body);
  }

  /**
   * Remove o token FCM de um dispositivo.
   */
  removeToken(fcmToken) {
    return this.#request('DELETE', '/api/tokens', { fcm_token: fcmToken });
  }

  /**
   * Envia notificação para um dispositivo específico.
   */
  sendToToken(fcmToken, title, body, data = {}) {
    return this.#post('/api/notifications/send-to-token', {
      fcm_token: fcmToken,
      title,
      body,
      data,
    });
  }

  /**
   * Envia notificação para um tópico FCM.
   */
  sendToTopic(topic, title, body, data = {}) {
    return this.#post('/api/notifications/send-to-topic', {
      topic,
      title,
      body,
      data,
    });
  }

  /**
   * Envia notificação para todos os dispositivos de um usuário.
   */
  sendToUser(userId, title, body, data = {}) {
    return this.#post('/api/notifications/send-to-users', {
      user_id: userId,
      title,
      body,
      data,
    });
  }

  /**
   * Envia notificação para todos os dispositivos ativos.
   *
   * @param {string} [platform]  Filtrar por plataforma (opcional)
   */
  broadcast(title, body, data = {}, platform = null) {
    const payload = { title, body, data };
    if (platform) payload.platform = platform;

    return this.#post('/api/notifications/broadcast', payload);
  }

  /** Verifica se a API está no ar. */
  healthCheck() {
    return this.#request('GET', '/api/health');
  }

  // -------------------------------------------------------------------
  // Internals
  // -------------------------------------------------------------------

  async #post(path, data) {
    return this.#request('POST', path, data);
  }

  async #request(method, path, data = null) {
    const url = this.baseUrl + path;

    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
      },
    };

    if (data !== null) {
      options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    const json     = await response.json();

    if (!response.ok) {
      const message = json?.message ?? `HTTP ${response.status}`;
      throw new Error(`API Error: ${message}`);
    }

    return json;
  }
}

// -------------------------------------------------------------------
// Exemplos de uso
// -------------------------------------------------------------------

const api = new NotificationClient('https://sua-api.com', 'sua-chave-secreta');

// 1. Registrar token (ex: após login no app web com Firebase)
await api.registerToken(
  'token-fcm-do-dispositivo',
  'web',
  'user-456',
  { browser: 'Chrome', os: 'Windows' }
);

// 2. Enviar notificação para um token específico
await api.sendToToken(
  'token-fcm-do-dispositivo',
  'Novo pedido!',
  'Você tem um novo pedido #1234',
  { order_id: '1234', action: 'open_order' }
);

// 3. Notificar todos os dispositivos do usuário
await api.sendToUser('user-456', 'Sua entrega chegou!', 'Pedido #1234 entregue.');

// 4. Broadcast
await api.broadcast('Aviso', 'O sistema ficará em manutenção às 02h.');

export default NotificationClient;
