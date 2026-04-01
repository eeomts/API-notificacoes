<?php
class AuthMiddleware {
    public static function handle() {
        $headers = function_exists("getallheaders") ? getallheaders() : self::getHeaders();
        $authHeader = "";
        foreach ($headers as $name => $value) {
            if (strtolower($name) === "authorization") {
                $authHeader = $value;
                break;
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
