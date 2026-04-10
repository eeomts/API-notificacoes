# FCM V1 Notification API — PHP 5.6

API REST em PHP puro para envio de notificacoes push via **Firebase Cloud Messaging V1**.

## Estrutura

```
fcm-api/
├── index.php                      # Entry point
├── .htaccess                      # Mod_rewrite para Apache
├── config/
│   ├── config.php                 # Configuracoes gerais e FCM
│   └── database.php               # Credenciais MySQL
├── routes/
│   └── api.php                    # Definicao de todas as rotas
├── src/
│   ├── Controllers/
│   │   ├── TokenController.php    # CRUD de tokens de dispositivo
│   │   └── NotificationController.php  # Envio de notificacoes
│   ├── Services/
│   │   ├── DatabaseService.php    # Singleton PDO
│   │   ├── GoogleAuthService.php  # OAuth2 via JWT (Service Account)
│   │   └── FcmService.php         # Envio HTTP para FCM V1
│   ├── Models/
│   │   ├── DeviceToken.php        # Model de tokens
│   │   └── NotificationLog.php   # Model de log de envios
│   ├── Middleware/
│   │   ├── AuthMiddleware.php     # Autenticacao Bearer Token
│   │   └── CorsMiddleware.php     # Headers CORS
│   └── Helpers/
│       ├── Response.php           # Respostas JSON padronizadas
│       ├── Logger.php             # Log em arquivo
│       └── Validator.php          # Validacao de inputs
└── storage/
    ├── schema.sql                 # SQL para criar o banco
    ├── service-account.json       # (NAO versionar) Credencial Firebase
    ├── google_token_cache.json    # Cache automatico do Access Token
    └── logs/
        └── app.log                # Log da aplicacao
```

## Instalacao

### 1. Firebase: Service Account

1. Acesse o [Firebase Console](https://console.firebase.google.com)
2. Configuracoes do Projeto → Contas de Servico
3. Clique em **Gerar nova chave privada**
4. Salve como `storage/service-account.json`
5. **NUNCA commite este arquivo!**

### 2. Banco de dados

```bash
mysql -u root -p < storage/schema.sql
```

### 3. Configuracao

Edite `config/config.php`:
```php
define('FCM_PROJECT_ID',  'meu-projeto-firebase');
define('API_SECRET_KEY',  'minha-chave-secreta-forte');
```

Edite `config/database.php` com suas credenciais MySQL.

### 4. Permissoes

```bash
chmod 755 storage/
chmod 755 storage/logs/
```

---

## Endpoints

### Autenticacao
Todos os endpoints (exceto `/api/health`) exigem header:
```
Authorization: Bearer sua-chave-secreta
```

---

### Saude da API

```http
GET /api/health
```

---

### Tokens de Dispositivo

#### Salvar token (app chama ao iniciar)
```http
POST /api/tokens
Content-Type: application/json

{
    "fcm_token": "dAFxxxxx...",
    "platform":  "android",
    "user_id":   "user-123",
    "extra": {
        "app_version": "2.1.0",
        "device_model": "Samsung Galaxy S22"
    }
}
```

#### Listar tokens
```http
GET /api/tokens
GET /api/tokens?platform=android
GET /api/tokens?user_id=user-123
```

#### Remover token (logout)
```http
DELETE /api/tokens
Content-Type: application/json

{ "fcm_token": "dAFxxxxx..." }
```

---

### Envio de Notificacoes

#### Para um dispositivo especifico
```http
POST /api/notifications/send-to-token
Content-Type: application/json

{
    "fcm_token": "dAFxxxxx...",
    "title":     "Novo pedido!",
    "body":      "Voce recebeu um novo pedido #4521",
    "data": {
        "order_id": "4521",
        "action":   "open_order"
    }
}
```

#### Para um topico
```http
POST /api/notifications/send-to-topic
Content-Type: application/json

{
    "topic": "promocoes",
    "title": "Oferta especial!",
    "body":  "50% de desconto hoje"
}
```

#### Para todos os dispositivos de um usuario
```http
POST /api/notifications/send-to-users
Content-Type: application/json

{
    "user_id": "user-123",
    "title":   "Mensagem recebida",
    "body":    "Voce tem uma nova mensagem"
}
```

#### Broadcast (todos os dispositivos ativos)
```http
POST /api/notifications/broadcast
Content-Type: application/json

{
    "title":    "Manutencao programada",
    "body":     "O sistema ficara indisponivel amanha das 2h às 4h",
    "platform": "android"
}
```

#### Historico de envios
```http
GET /api/notifications/logs?limit=20&offset=0
```

---

## Fluxo FCM V1

```
App Mobile
    |-- SDK Firebase --> gera FCM Token
    |
POST /api/tokens  (salva token no MySQL)
    |
POST /api/notifications/send-to-token
    |
GoogleAuthService
    |-- Le service-account.json
    |-- Assina JWT com chave RSA privada
    |-- POST https://oauth2.googleapis.com/token
    |-- Recebe Access Token (cache por 1h)
    |
FcmService
    |-- POST https://fcm.googleapis.com/v1/projects/{id}/messages:send
    |-- Authorization: Bearer <access_token>
    |
FCM Google --> entrega ao dispositivo
```

## Notas PHP 5.6

- Sem `...` spread operator
- Sem tipos de retorno declarados
- Sem classes anonimas
- Sem `??` null coalescing (usa `isset()` + ternario)
- `openssl_free_key()` necessario explicitamente
- Arrays com `array()` em vez de `[]`
