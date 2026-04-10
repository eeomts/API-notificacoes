# Exemplos de integração

Exemplos de como consumir a FCM Notification API a partir de diferentes linguagens e plataformas.

| Pasta | Linguagem / Plataforma |
|-------|------------------------|
| [`curl/`](./curl/) | cURL / Shell — teste rápido via terminal |
| [`php/`](./php/) | PHP Vanilla — integração sem framework |
| [`laravel/`](./laravel/) | Laravel — com `Http` facade e `config/services.php` |
| [`javascript/`](./javascript/) | JavaScript — Fetch API (browser + Node.js 18+) |
| [`nodejs/`](./nodejs/) | Node.js — Axios |
| [`python/`](./python/) | Python — `requests` |
| [`android/`](./android/) | Android / Kotlin — Retrofit2 |

## Como funciona

Todas as aplicações clientes precisam apenas de:

1. **URL da API** — onde o serviço está hospedado
2. **API Key** — chave Bearer para autenticação

```
Authorization: Bearer sua-chave-secreta
```

O `service-account.json` do Firebase fica **somente no servidor da API**. As aplicações clientes nunca precisam de credenciais do Firebase.
