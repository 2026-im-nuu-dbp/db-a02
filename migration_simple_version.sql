-- 簡化版遷移腳本（相容所有 MySQL 版本）
-- 用途：添加文檔上傳和軟刪除功能
-- 注意：如果欄位已存在，執行會報錯但表不會被修改

USE fullstack_app;

-- 直接添加新欄位（最相容的方式）
ALTER TABLE dbmemo ADD COLUMN document_path VARCHAR(255) COMMENT '文檔路徑（Word、PPT、PDF等）';
ALTER TABLE dbmemo ADD COLUMN document_type VARCHAR(50) COMMENT '文檔類型（docx、pptx、pdf等）';
ALTER TABLE dbmemo ADD COLUMN document_name VARCHAR(255) COMMENT '原始文件名';
ALTER TABLE dbmemo ADD COLUMN deleted_at TIMESTAMP NULL COMMENT '軟刪除時間戳';

-- 建立索引以加快查詢
ALTER TABLE dbmemo ADD INDEX idx_user_deleted (user_id, deleted_at);

SELECT 'Migration completed successfully! 數據庫已升級完成。' AS status;
