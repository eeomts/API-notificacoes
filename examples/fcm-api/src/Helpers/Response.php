<?php
/**
 * Helper de resposta HTTP padronizada
 */
class Response
{
    /**
     * Envia resposta JSON de sucesso
     */
    public static function success($data = array(), $message = 'Sucesso', $statusCode = 200)
    {
        self::send(array(
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ), $statusCode);
    }

    /**
     * Envia resposta JSON de erro
     */
    public static function error($message = 'Erro', $statusCode = 400, $errors = array())
    {
        $body = array(
            'success' => false,
            'message' => $message,
        );
        if (!empty($errors)) {
            $body['errors'] = $errors;
        }
        self::send($body, $statusCode);
    }

    /**
     * Envia resposta 404
     */
    public static function notFound($message = 'Recurso não encontrado')
    {
        self::error($message, 404);
    }

    /**
     * Envia resposta 401
     */
    public static function unauthorized($message = 'Não autorizado')
    {
        self::error($message, 401);
    }

    /**
     * Envia resposta 405
     */
    public static function methodNotAllowed($message = 'Método não permitido')
    {
        self::error($message, 405);
    }

    /**
     * Envia a resposta com headers e encerra execução
     */
    private static function send($body, $statusCode)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
