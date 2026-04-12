<?php
class AuthMiddleware {
    public static function handle() {
        $headers = function_exists("getallheaders") ? getallheaders() : self::getHeaders();
        $authHeader = "";
        $appId = null;

        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            if ($lower === "authorization") {
                $authHeader = $value;
            }
            if ($lower === "x-app-id") {
                $appId = $value;
            }
        }

        if (empty($authHeader)) {
            Response::unauthorized("Token nao informado.");
        }
        if (strpos($authHeader, "Bearer ") !== 0) {
            Response::unauthorized("Formato do token invalido.");
        }
        $token = substr($authHeader, 7);
        if ($token !== API_SECRET_KEY) {
            Response::unauthorized("Token invalido");
        }

        if (empty($appId)) {
            Response::error('Header X-App-ID e obrigatorio.', 400);
        }

        $appId = preg_replace('/[^a-zA-Z0-9_-]/', '', $appId);
        $accountPath = __DIR__ . '/../../storage/accounts/' . $appId . '.json';
        if (!file_exists($accountPath)) {
            Response::error('App ID invalido ou nao configurado.', 400);
        }

        return $appId;
    }
    private static function getHeaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
