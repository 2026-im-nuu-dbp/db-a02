-- 建立資料庫 (若尚未建立)
CREATE DATABASE IF NOT EXISTS fullstack_app DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fullstack_app;

-- 資料表 1: 使用者資料 (dbusers)
CREATE TABLE IF NOT EXISTS dbusers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account VARCHAR(100) NOT NULL UNIQUE COMMENT '帳號或 Meta Email/ID',
    nickname VARCHAR(50) NOT NULL,
    password VARCHAR(255) COMMENT '若為純 Meta 登入可允許 NULL',
    gender ENUM('male', 'female', 'other') NOT NULL DEFAULT 'other',
    interests TEXT COMMENT 'JSON 格式或逗號分隔字串',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 資料表 2: 登入日誌 (dblog)
CREATE TABLE IF NOT EXISTS dblog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account VARCHAR(100) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_success TINYINT(1) NOT NULL COMMENT '1: 成功, 0: 失敗'
);

-- 資料表 3: 圖文備忘 (dbmemo)
CREATE TABLE IF NOT EXISTS dbmemo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) COMMENT '原圖路徑',
    thumb_path VARCHAR(255) COMMENT '縮圖路徑',
    document_path VARCHAR(255) COMMENT '文檔路徑（Word、PPT、PDF等）',
    document_type VARCHAR(50) COMMENT '文檔類型（docx、pptx、pdf等）',
    document_name VARCHAR(255) COMMENT '原始文件名',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL COMMENT '軟刪除時間戳',
    FOREIGN KEY (user_id) REFERENCES dbusers(id) ON DELETE CASCADE
);