<?php
/**
 * Configurações principais da aplicação
 */

// Ambiente: 'development' ou 'production'
define('APP_ENV', 'development');
define('APP_VERSION', '1.0.0');

// -------------------------------------------------------
// Firebase / FCM V1
// -------------------------------------------------------
// ID do projeto Firebase (encontrado no console do Firebase)
define('FCM_PROJECT_ID', 'seu-projeto-id');

// Caminho para o arquivo JSON da service account do Google
// Gere em: Firebase Console > Configurações > Contas de Serviço
define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/../storage/service-account.json');

// Endpoint FCM V1
define('FCM_API_URL', 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');

// Endpoint para obter token OAuth2 do Google
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');

// Escopo necessário para FCM V1
define('FCM_SCOPE', 'https://www.googleapis.com/auth/firebase.messaging');

// -------------------------------------------------------
// Autenticação da API
// -------------------------------------------------------
// Chave secreta para proteger os endpoints (Bearer token)
define('API_SECRET_KEY', 'sua-chave-secreta-aqui');

// -------------------------------------------------------
// Cache do token Google OAuth2
// -------------------------------------------------------
// Arquivo onde o access token do Google será cacheado
define('TOKEN_CACHE_FILE', __DIR__ . '/../storage/google_token_cache.json');

// -------------------------------------------------------
// Logging
// -------------------------------------------------------
define('LOG_FILE', __DIR__ . '/../storage/logs/app.log');
define('LOG_LEVEL', APP_ENV === 'development' ? 'debug' : 'error');
