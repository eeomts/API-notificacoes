<?php
/**
 * Controller: gerenciamento de tokens de dispositivos
 * POST   /api/tokens          -> salvar/atualizar token
 * GET    /api/tokens          -> listar tokens (admin)
 * DELETE /api/tokens          -> remover token
 **/
class TokenController
{
    private $deviceToken;

    public function __construct()
    {
        $this->deviceToken = new DeviceToken();
    }

    /**
     * POST /api/tokens
     * Registra ou atualiza o token FCM de um dispositivo.
     * Body JSON:
     *   fcm_token  string (obrigatorio) - token gerado pelo SDK do Firebase no app
     *   platform   string (obrigatorio) - android | ios | web
     *   user_id    string (opcional)    - identificador do usuario logado
     *   extra      object (opcional)    - dados extras (versao do app, modelo do dispositivo, etc)
     **/
    public function saveToken()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::methodNotAllowed();
        }

        $input = $this->getJsonInput();

        $validator = new Validator($input);
        $validator
            ->required('fcm_token', 'FCM Token')
            ->minLength('fcm_token', 20, 'FCM Token')
            ->maxLength('fcm_token', 512, 'FCM Token')
            ->required('platform', 'Plataforma')
            ->in('platform', array('android', 'ios', 'web'), 'Plataforma');

        if ($validator->fails()) {
            Response::error('Dados invalidos.', 422, $validator->errors());
        }

        $fcmToken = trim($validator->get('fcm_token'));
        $platform = $validator->get('platform');
        $userId   = $validator->get('user_id');
        $extra    = $validator->get('extra', array());

        if (!is_array($extra)) {
            $extra = array();
        }

        try {
            $id = $this->deviceToken->saveOrUpdate($fcmToken, $platform, $userId, $extra);
            Logger::info('Token salvo/atualizado', array('id' => $id, 'platform' => $platform));

            Response::success(
                array('id' => $id),
                'Token registrado com sucesso.',
                201
            );
        } catch (Exception $e) {
            Logger::error('Erro ao salvar token: ' . $e->getMessage());
            Response::error('Erro ao salvar token.', 500);
        }
    }

    /**
     * GET /api/tokens
     * Lista todos os tokens ativos (uso administrativo).
     * Query params:
     *   platform  string (opcional) - filtrar por plataforma
     *   user_id   string (opcional) - filtrar por usuario
     */
    public function listTokens()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed();
        }

        try {
            $platform = isset($_GET['platform']) ? $_GET['platform'] : null;
            $userId   = isset($_GET['user_id']) ? $_GET['user_id'] : null;

            if ($userId) {
                $tokens = $this->deviceToken->findByUserId($userId);
            } elseif ($platform) {
                $tokens = $this->deviceToken->findByPlatform($platform);
            } else {
                $tokens = $this->deviceToken->findAllActive();
            }

            Response::success(array('tokens' => $tokens, 'total' => count($tokens)));
        } catch (Exception $e) {
            Logger::error('Erro ao listar tokens: ' . $e->getMessage());
            Response::error('Erro ao listar tokens.', 500);
        }
    }

    /**
     * DELETE /api/tokens
     * Remove (ou desativa) um token de dispositivo.
     * Body JSON:
     *   fcm_token  string (obrigatorio)
     */
    public function deleteToken()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            Response::methodNotAllowed();
        }

        $input = $this->getJsonInput();

        $validator = new Validator($input);
        $validator->required('fcm_token', 'FCM Token');

        if ($validator->fails()) {
            Response::error('Dados invalidos.', 422, $validator->errors());
        }

        $fcmToken = trim($validator->get('fcm_token'));

        try {
            $this->deviceToken->deactivate($fcmToken);
            Logger::info('Token desativado.');
            Response::success(array(), 'Token removido com sucesso.');
        } catch (Exception $e) {
            Logger::error('Erro ao remover token: ' . $e->getMessage());
            Response::error('Erro ao remover token.', 500);
        }
    }

    private function getJsonInput()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : array();
    }
}
