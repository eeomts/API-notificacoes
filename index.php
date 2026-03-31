<?php 
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Helpers/Response.php';
require_once __DIR__ . '/src/Helpers/Logger.php';
require_once __DIR__ . '/src/Helpers/Validator.php';
require_once __DIR__ . '/src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/src/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/src/Models/DeviceToken.php';
require_once __DIR__ . '/src/Models/NotificationLog.php';
require_once __DIR__ . '/src/Services/DatabaseService.php';
require_once __DIR__ . '/src/Services/GoogleAuthService.php';
require_once __DIR__ . '/src/Services/FcmService.php';
require_once __DIR__ . '/src/Controllers/TokenController.php';
require_once __DIR__ . '/src/Controllers/NotificationController.php';
require_once __DIR__ . '/routes/api.php';

?>