<?php
/**
 * Model: log de notificacoes enviadas
 */
class NotificationLog
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    public function create($type, $target, $title, $body, $data, $status, $fcmResponse = null, $errorMsg = null)
    {
        $sql = "INSERT INTO notification_logs
                    (type, target, title, body, data, status, fcm_response, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->db->query($sql, array(
            $type,
            $target,
            $title,
            $body,
            json_encode($data),
            $status,
            $fcmResponse ? json_encode($fcmResponse) : null,
            $errorMsg,
        ));

        return $this->db->lastInsertId();
    }

    public function findAll($limit = 50, $offset = 0)
    {
        return $this->db->fetchAll(
            "SELECT * FROM notification_logs ORDER BY sent_at DESC LIMIT ? OFFSET ?",
            array($limit, $offset)
        );
    }

    public function countByStatus($status)
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM notification_logs WHERE status = ?",
            array($status)
        );
        return $row ? (int)$row['total'] : 0;
    }
}
