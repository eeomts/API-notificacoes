<?php
/**
 * Model: tokens de dispositivos
 */
class DeviceToken
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Salva ou atualiza token de dispositivo
     * (upsert por token FCM)
     */
    public function saveOrUpdate($fcmToken, $platform, $userId = null, $extra = array(), $appId = 'default')
    {
        $existing = $this->findByToken($fcmToken);

        if ($existing) {
            $sql = "UPDATE device_tokens
                    SET platform = ?, user_id = ?, extra = ?, app_id = ?, updated_at = NOW()
                    WHERE fcm_token = ?";
            $this->db->query($sql, array(
                $platform,
                $userId,
                json_encode($extra),
                $appId,
                $fcmToken,
            ));
            return $existing['id'];
        }

        $sql = "INSERT INTO device_tokens (app_id, fcm_token, platform, user_id, extra, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $this->db->query($sql, array(
            $appId,
            $fcmToken,
            $platform,
            $userId,
            json_encode($extra),
        ));
        return $this->db->lastInsertId();
    }

    public function findByToken($fcmToken)
    {
        return $this->db->fetchOne(
            "SELECT * FROM device_tokens WHERE fcm_token = ? LIMIT 1",
            array($fcmToken)
        );
    }

    public function findByUserId($userId, $appId = 'default')
    {
        return $this->db->fetchAll(
            "SELECT * FROM device_tokens WHERE user_id = ? AND app_id = ? AND active = 1 ORDER BY updated_at DESC",
            array($userId, $appId)
        );
    }

    public function findAllActive($appId = 'default')
    {
        return $this->db->fetchAll(
            "SELECT * FROM device_tokens WHERE app_id = ? AND active = 1 ORDER BY updated_at DESC",
            array($appId)
        );
    }

    public function findByPlatform($platform, $appId = 'default')
    {
        return $this->db->fetchAll(
            "SELECT * FROM device_tokens WHERE platform = ? AND app_id = ? AND active = 1",
            array($platform, $appId)
        );
    }

    public function deactivate($fcmToken)
    {
        $this->db->query(
            "UPDATE device_tokens SET active = 0 WHERE fcm_token = ?",
            array($fcmToken)
        );
    }

    public function deleteByToken($fcmToken)
    {
        $this->db->query(
            "DELETE FROM device_tokens WHERE fcm_token = ?",
            array($fcmToken)
        );
    }
}
