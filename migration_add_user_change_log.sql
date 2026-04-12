-- 新增使用者變更紀錄表
CREATE TABLE IF NOT EXISTS db_user_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    account VARCHAR(100) NOT NULL,
    changed_by VARCHAR(100) DEFAULT NULL,
    action_type ENUM('profile_update','delete_user') NOT NULL,
    field_name VARCHAR(50) DEFAULT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
