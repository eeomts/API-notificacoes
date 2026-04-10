# FCM Notification API

API REST para envio de notificações push via **Firebase Cloud Messaging (FCM V1)**, construída em PHP puro (sem framework), compatível com PHP 5.6+.

Projetada para ser um **serviço centralizado de notificações**: você faz um único deploy e consome as rotas a partir de qualquer aplicação (Laravel, Node.js, Python, Android, iOS, etc.), sem precisar distribuir credenciais do Firebase para cada sistema.

---

## Por que usar esta API como serviço centralizado?

```
┌──────────────┐
│  App Laravel │ ──────────────────────────────────────────┐
└──────────────┘                                           │
┌──────────────┐          ┌──────────────────────────┐    │
│  App Node.js │ ─────────▶  FCM Notification API    │────▶ FCM / Firebase
└──────────────┘          │  (único deploy)          │    │
┌──────────────┐          └──────────────────────────┘    │
│  App Android │ ──────────────────────────────────────────┘
└──────────────┘
```

- O arquivo `service-account.json` do Firebase fica **apenas no servidor desta API**
- Outras aplicações autenticam com uma **API key simples** via `Authorization: Bearer`
- Nenhuma dependência do Firebase SDK nas aplicações clientes

---

## Requisitos

- PHP 5.6 ou superior
- Extensões: `pdo_mysql`, `curl`, `openssl`, `json`
- MySQL 5.7 ou superior
- Apache com `mod_rewrite` ou Nginx
- Projeto Firebase com FCM habilitado
- Service Account com permissão `Firebase Cloud Messaging API`

---

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/fcm-notification-api.git
cd fcm-notification-api
```

### 2. Crie o banco de dados

```bash
mysql -u root -p < storage/schema.sql
```

### 3. Configure a aplicação

Copie e edite o arquivo de configuração:

```bash
cp config/config.php.example config/config.php
```

Edite `config/config.php`:

```php
define('FCM_PROJECT_ID', 'seu-projeto-firebase');
define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/../storage/service-account.json');
define('API_SECRET_KEY', 'gere-uma-chave-forte-aqui');
```

Edite `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fcm_notifications');
define('DB_USER', 'seu-usuario');
define('DB_PASS', 'sua-senha');
```

### 4. Adicione o arquivo da Service Account

Baixe o JSON da service account no Firebase Console:
> Configurações do Projeto → Contas de Serviço → Gerar nova chave privada

Salve em:
```
storage/service-account.json
```

> **Nunca commite este arquivo.** Ele já está no `.gitignore`.

### 5. Configure o servidor web

**Apache** — o `.htaccess` já está incluído na raiz.

**Nginx:**

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Estrutura do projeto

```
.
├── config/
│   ├── config.php          # Configurações da aplicação
│   └── database.php        # Credenciais do banco de dados
├── src/
│   ├── Controllers/
│   │   ├── NotificationController.php
│   │   └── TokenController.php
│   ├── Helpers/
│   │   ├── Logger.php
│   │   ├── Response.php
│   │   └── Validator.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── Models/
│   │   ├── DeviceToken.php
│   │   └── NotificationLog.php
│   └── Services/
│       ├── DatabaseService.php
│       ├── FcmService.php
│       └── GoogleAuthService.php
├── routes/
│   └── api.php
├── storage/
│   ├── schema.sql
│   ├── service-account.json  # NÃO commitar
│   ├── cache/                # Cache do token OAuth2
│   └── logs/
├── examples/                 # Exemplos de integração por linguagem
├── index.php
└── .htaccess
```

---

## Autenticação

Todas as rotas (exceto `/api/health`) exigem autenticação via **Bearer token** no header:

```
Authorization: Bearer sua-chave-secreta
```

---

## Endpoints

### Verificação de saúde

| Método | Rota | Auth |
|--------|------|------|
| `GET` | `/api/health` | Não |

**Resposta:**
```json
{
  "success": true,
  "message": "API funcionando.",
  "data": {
    "status": "ok",
    "version": "1.0.0",
    "time": "2025-01-01T12:00:00+00:00"
  }
}
```

---

### Tokens de dispositivo

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/tokens` | Registrar ou atualizar token FCM |
| `GET` | `/api/tokens` | Listar tokens ativos |
| `DELETE` | `/api/tokens` | Remover token |

#### POST /api/tokens

```json
{
  "fcm_token": "token-gerado-pelo-firebase-sdk",
  "platform": "android",
  "user_id": "123",
  "extra": {
    "app_version": "2.1.0",
    "device_model": "Pixel 7"
  }
}
```

| Campo | Tipo | Obrigatório | Valores |
|-------|------|-------------|---------|
| `fcm_token` | string | Sim | Token FCM do dispositivo |
| `platform` | string | Sim | `android`, `ios`, `web` |
| `user_id` | string | Não | ID do usuário no seu sistema |
| `extra` | object | Não | Dados extras livres |

#### GET /api/tokens

Query params opcionais: `?platform=android`, `?user_id=123`

#### DELETE /api/tokens

```json
{
  "fcm_token": "token-a-ser-removido"
}
```

---

### Notificações

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/notifications/send-to-token` | Envia para um dispositivo específico |
| `POST` | `/api/notifications/send-to-topic` | Envia para um tópico FCM |
| `POST` | `/api/notifications/send-to-users` | Envia para todos os tokens de um usuário |
| `POST` | `/api/notifications/broadcast` | Envia para todos os dispositivos ativos |
| `GET` | `/api/notifications/logs` | Histórico de notificações enviadas |

#### POST /api/notifications/send-to-token

```json
{
  "fcm_token": "token-do-dispositivo",
  "title": "Novo pedido!",
  "body": "Você tem um novo pedido #1234",
  "data": {
    "order_id": "1234",
    "action": "open_order"
  },
  "android": {
    "priority": "high"
  },
  "apns": {
    "headers": {
      "apns-priority": "10"
    }
  }
}
```

#### POST /api/notifications/send-to-topic

```json
{
  "topic": "promocoes",
  "title": "Oferta especial!",
  "body": "50% de desconto hoje.",
  "data": {
    "promo_id": "PROMO50"
  }
}
```

#### POST /api/notifications/send-to-users

```json
{
  "user_id": "123",
  "title": "Sua entrega chegou!",
  "body": "O pedido #1234 foi entregue.",
  "data": {
    "order_id": "1234"
  }
}
```

#### POST /api/notifications/broadcast

```json
{
  "title": "Manutenção programada",
  "body": "O sistema ficará indisponível às 02h.",
  "platform": "android"
}
```

`platform` é opcional. Se omitido, envia para todos.

#### GET /api/notifications/logs

Query params: `?limit=50&offset=0`

---

## Formato de resposta

**Sucesso:**
```json
{
  "success": true,
  "message": "Notificação enviada com sucesso.",
  "data": {
    "message_id": "projects/projeto/messages/123456"
  }
}
```

**Erro:**
```json
{
  "success": false,
  "message": "Dados inválidos.",
  "errors": {
    "fcm_token": ["FCM Token é obrigatório."]
  }
}
```

---

## Códigos HTTP

| Código | Situação |
|--------|----------|
| `200` | Sucesso |
| `201` | Criado com sucesso |
| `401` | Token de autenticação inválido ou ausente |
| `404` | Rota ou recurso não encontrado |
| `405` | Método HTTP não permitido |
| `422` | Dados de entrada inválidos |
| `500` | Erro interno do servidor |
| `502` | Falha na comunicação com o FCM |

---

## Exemplos de integração

Veja a pasta [`examples/`](./examples) para exemplos prontos em:

- [cURL / Shell](./examples/curl/)
- [PHP Vanilla](./examples/php/)
- [Laravel](./examples/laravel/)
- [JavaScript / Fetch](./examples/javascript/)
- [Node.js / Axios](./examples/nodejs/)
- [Python](./examples/python/)
- [Android / Kotlin](./examples/android/)

---

## Variáveis de ambiente recomendadas para produção

Em vez de editar os arquivos de config diretamente, use variáveis de ambiente com `getenv()`:

```php
define('API_SECRET_KEY', getenv('API_SECRET_KEY'));
define('DB_PASS',        getenv('DB_PASS'));
```

---

## Licença

MIT
