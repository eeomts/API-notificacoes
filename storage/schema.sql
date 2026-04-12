CREATE DATABASE IF NOT EXISTS fcm_notifications CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE fcm_notifications;

CREATE TABLE IF NOT EXISTS device_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id VARCHAR(100) NOT NULL DEFAULT 'default',
    fcm_token VARCHAR(512) NOT NULL UNIQUE,
    platform ENUM('android','ios','web') NOT NULL,
    user_id VARCHAR(128) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    extra TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    INDEX idx_app_id (app_id),
    INDEX idx_user_id (user_id),
    INDEX idx_platform (platform),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('token','topic','user','broadcast') NOT NULL,
    target VARCHAR(512) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    data TEXT NULL,
    status ENUM('success','error') NOT NULL,
    fcm_response TEXT NULL,
    error_message TEXT NULL,
    sent_at DATETIME NOT NULL,

    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
