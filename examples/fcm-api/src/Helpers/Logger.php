<?php
/**
 * Logger simples compatível com PHP 5.6
 */
class Logger
{
    private static $levels = array('debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3);

    public static function debug($message, $context = array())
    {
        self::write('DEBUG', $message, $context);
    }

    public static function info($message, $context = array())
    {
        self::write('INFO', $message, $context);
    }

    public static function warning($message, $context = array())
    {
        self::write('WARNING', $message, $context);
    }

    public static function error($message, $context = array())
    {
        self::write('ERROR', $message, $context);
    }

    private static function write($level, $message, $context)
    {
        $configLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'debug';
        $configLevelInt = isset(self::$levels[$configLevel]) ? self::$levels[$configLevel] : 0;
        $levelInt = isset(self::$levels[strtolower($level)]) ? self::$levels[strtolower($level)] : 0;

        if ($levelInt < $configLevelInt) {
            return;
        }

        $logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/../../storage/logs/app.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
