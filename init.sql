-- 資料庫初始化腳本
-- db-a02 項目
-- 建立時間: 2026-04-21

-- ==========================================
-- 1. 建立 dbusers 表 (使用者帳號表)
-- ==========================================
CREATE TABLE IF NOT EXISTS `dbusers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '使用者ID',
  `account` VARCHAR(255) NOT NULL UNIQUE COMMENT '帳號(信箱)',
  `nickname` VARCHAR(100) NOT NULL COMMENT '暱稱',
  `password` VARCHAR(255) COMMENT '密碼(純文字或雜湊)',
  `gender` ENUM('M', 'F', 'Other') DEFAULT NULL COMMENT '性別',
  `interests` TEXT COMMENT '興趣(JSON格式或逗號分隔)',
  `access_token` VARCHAR(255) UNIQUE COMMENT 'API存取令牌',
  `token_expires_at` DATETIME COMMENT '令牌過期時間',
  `role` ENUM('user', 'admin') DEFAULT 'user' COMMENT '角色(user:一般使用者, admin:管理員)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '帳號建立時間',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
  `last_login_at` DATETIME COMMENT '最後登入時間',
  `is_active` BOOLEAN DEFAULT TRUE COMMENT '帳號是否有效',
  INDEX idx_account (account),
  INDEX idx_access_token (access_token),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='使用者帳號表';

-- ==========================================
-- 2. 建立 dblog 表 (登入日誌表)
-- ==========================================
CREATE TABLE IF NOT EXISTS `dblog` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '日誌ID',
  `user_id` INT UNSIGNED NOT NULL COMMENT '使用者ID',
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登入時間',
  `login_success` BOOLEAN NOT NULL DEFAULT TRUE COMMENT '登入是否成功(1:成功, 0:失敗)',
  `ip_address` VARCHAR(45) COMMENT 'IP地址(支援IPv4和IPv6)',
  `user_agent` TEXT COMMENT '使用者代理程式',
  `failure_reason` VARCHAR(255) COMMENT '失敗原因(登入失敗時記錄)',
  INDEX idx_user_id (user_id),
  INDEX idx_login_time (login_time),
  INDEX idx_login_success (login_success),
  FOREIGN KEY (user_id) REFERENCES dbusers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登入日誌表';

-- ==========================================
-- 3. 建立 dbmemo 表 (圖文備忘表)
-- ==========================================
CREATE TABLE IF NOT EXISTS `dbmemo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '備忘ID',
  `user_id` INT UNSIGNED NOT NULL COMMENT '建立者ID',
  `content` LONGTEXT NOT NULL COMMENT '備忘文字內容',
  `image_path` VARCHAR(500) COMMENT '原始圖片路徑',
  `thumb_path` VARCHAR(500) COMMENT '縮圖路徑',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改時間',
  `deleted_at` DATETIME COMMENT '軟式刪除時間(NULL表示未刪除)',
  `attachment_count` INT DEFAULT 0 COMMENT '附件數量',
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at),
  INDEX idx_deleted_at (deleted_at),
  FOREIGN KEY (user_id) REFERENCES dbusers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='圖文備忘表';

-- ==========================================
-- 4. 測試資料 (選填 - 開發測試用)
-- ==========================================
-- 插入測試使用者
INSERT INTO `dbusers` (`account`, `nickname`, `password`, `gender`, `role`) VALUES 
('admin@example.com', 'Admin User', 'password123', 'M', 'admin'),
('user1@example.com', 'John Doe', 'password123', 'M', 'user'),
('user2@example.com', 'Jane Smith', 'password123', 'F', 'user')
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 插入測試登入日誌
INSERT INTO `dblog` (`user_id`, `login_success`, `ip_address`) VALUES 
(1, TRUE, '192.168.1.100'),
(2, TRUE, '192.168.1.101'),
(1, FALSE, '192.168.1.102')
ON DUPLICATE KEY UPDATE `login_time` = NOW();

-- 插入測試備忘錄
INSERT INTO `dbmemo` (`user_id`, `content`) VALUES 
(1, '這是我的第一筆備忘錄'),
(2, '重要提醒：完成作業提交')
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ==========================================
-- 5. 查詢檢查 (執行後查看)
-- ==========================================
-- SELECT * FROM dbusers;
-- SELECT * FROM dblog;
-- SELECT * FROM dbmemo;
