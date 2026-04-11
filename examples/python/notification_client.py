    """
    Integração com a FCM Notification API — Python

    Instalação:
        pip install requests
    """

    import requests
    from typing import Optional


    class NotificationClient:
        def __init__(self, base_url: str, api_key: str):
            """
            :param base_url: URL base da API (ex: https://sua-api.com)
            :param api_key:  Chave de autenticação Bearer
            """
            self.base_url = base_url.rstrip('/')
            self.session  = requests.Session()
            self.session.headers.update({
                'Authorization': f'Bearer {api_key}',
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            })

        def register_token(
            self,
            fcm_token: str,
            platform: str,
            user_id: Optional[str] = None,
            extra: Optional[dict]  = None,
        ) -> dict:
            """
            Registra ou atualiza o token FCM de um dispositivo.

            :param fcm_token: Token gerado pelo Firebase SDK
            :param platform:  android | ios | web
            :param user_id:   ID do usuário no seu sistema (opcional)
            :param extra:     Dados extras (versão do app, etc.) (opcional)
            """
            body = {'fcm_token': fcm_token, 'platform': platform}
            if user_id:
                body['user_id'] = user_id
            if extra:
                body['extra'] = extra

            return self._post('/api/tokens', body)

        def remove_token(self, fcm_token: str) -> dict:
            """Remove o token FCM de um dispositivo."""
            return self._request('DELETE', '/api/tokens', {'fcm_token': fcm_token})

        def send_to_token(
            self,
            fcm_token: str,
            title: str,
            body: str,
            data: Optional[dict] = None,
        ) -> dict:
            """Envia notificação para um dispositivo específico."""
            return self._post('/api/notifications/send-to-token', {
                'fcm_token': fcm_token,
                'title':     title,
                'body':      body,
                'data':      data or {},
            })

        def send_to_topic(
            self,
            topic: str,
            title: str,
            body: str,
            data: Optional[dict] = None,
        ) -> dict:
            """Envia notificação para um tópico FCM."""
            return self._post('/api/notifications/send-to-topic', {
                'topic': topic,
                'title': title,
                'body':  body,
                'data':  data or {},
            })

        def send_to_user(
            self,
            user_id: str,
            title: str,
            body: str,
            data: Optional[dict] = None,
        ) -> dict:
            """Envia notificação para todos os dispositivos de um usuário."""
            return self._post('/api/notifications/send-to-users', {
                'user_id': user_id,
                'title':   title,
                'body':    body,
                'data':    data or {},
            })

        def broadcast(
            self,
            title: str,
            body: str,
            data: Optional[dict]    = None,
            platform: Optional[str] = None,
        ) -> dict:
            """
            Envia notificação para todos os dispositivos ativos.

            :param platform: Filtrar por plataforma: android | ios | web (opcional)
            """
            payload = {'title': title, 'body': body, 'data': data or {}}
            if platform:
                payload['platform'] = platform

            return self._post('/api/notifications/broadcast', payload)

        def health_check(self) -> dict:
            """Verifica se a API está no ar."""
            return self._request('GET', '/api/health')

        # ------------------------------------------------------------------
        # Internals
        # ------------------------------------------------------------------

        def _post(self, path: str, data: dict) -> dict:
            return self._request('POST', path, data)

        def _request(self, method: str, path: str, data: Optional[dict] = None) -> dict:
            url      = self.base_url + path
            response = self.session.request(method, url, json=data, timeout=15)

            try:
                body = response.json()
            except ValueError:
                response.raise_for_status()
                return {}

            if not response.ok:
                message = body.get('message', f'HTTP {response.status_code}')
                raise RuntimeError(f'API Error: {message}')

            return body


    # ----------------------------------------------------------------------
    # Exemplos de uso
    # ----------------------------------------------------------------------

    if __name__ == '__main__':
        import os

        api = NotificationClient(
            base_url=os.getenv('NOTIFICATION_API_URL', 'https://sua-api.com'),
            api_key=os.getenv('NOTIFICATION_API_KEY',  'sua-chave-secreta'),
        )

        # 1. Verificar saúde da API
        health = api.health_check()
        print('Status:', health['data']['status'])

        # 2. Registrar token
        api.register_token(
            fcm_token='token-fcm-aqui',
            platform='android',
            user_id='user-123',
            extra={'app_version': '2.1.0'},
        )

        # 3. Enviar para um dispositivo específico
        result = api.send_to_token(
            fcm_token='token-fcm-aqui',
            title='Novo pedido!',
            body='Pedido #1234 recebido.',
            data={'order_id': '1234', 'action': 'open_order'},
        )
        print('Message ID:', result['data']['message_id'])

        # 4. Notificar todos os dispositivos do usuário
        api.send_to_user('user-123', 'Entrega confirmada!', 'Pedido #1234 entregue.')

        # 5. Broadcast
        api.broadcast('Manutenção', 'Sistema em manutenção às 02h.')
