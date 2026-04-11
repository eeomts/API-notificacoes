<?php
/**
 * Adicione este bloco ao seu config/services.php
 *
 * E no seu .env:
 *   NOTIFICATION_API_URL=https://sua-api.com
 *   NOTIFICATION_API_KEY=sua-chave-secreta
 */
return [

    // ... outros services ...

    'notifications' => [
        'url' => env('NOTIFICATION_API_URL'),
        'key' => env('NOTIFICATION_API_KEY'),
    ],

];
