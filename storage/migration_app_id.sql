ALTER TABLE device_tokens
    ADD COLUMN app_id VARCHAR(100) NOT NULL DEFAULT 'default' AFTER id,
    ADD INDEX idx_app_id (app_id);
