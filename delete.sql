USE fullstack_app;

-- 檢查並添加 document_path 欄位
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'fullstack_app' 
AND TABLE_NAME = 'dbmemo' 
AND COLUMN_NAME = 'document_path';

SET @alter_sql = IF(@col_exists = 0, 
    'ALTER TABLE dbmemo ADD COLUMN document_path VARCHAR(255) COMMENT "文檔路徑（Word、PPT、PDF等）"',
    'SELECT "document_path 欄位已存在" AS message'
);
PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

-- 檢查並添加 document_type 欄位
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'fullstack_app' 
AND TABLE_NAME = 'dbmemo' 
AND COLUMN_NAME = 'document_type';

SET @alter_sql = IF(@col_exists = 0, 
    'ALTER TABLE dbmemo ADD COLUMN document_type VARCHAR(50) COMMENT "文檔類型（docx、pptx、pdf等）"',
    'SELECT "document_type 欄位已存在" AS message'
);
PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

-- 檢查並添加 document_name 欄位
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'fullstack_app' 
AND TABLE_NAME = 'dbmemo' 
AND COLUMN_NAME = 'document_name';

SET @alter_sql = IF(@col_exists = 0, 
    'ALTER TABLE dbmemo ADD COLUMN document_name VARCHAR(255) COMMENT "原始文件名"',
    'SELECT "document_name 欄位已存在" AS message'
);
PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

-- 檢查並添加 deleted_at 欄位
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'fullstack_app' 
AND TABLE_NAME = 'dbmemo' 
AND COLUMN_NAME = 'deleted_at';

SET @alter_sql = IF(@col_exists = 0, 
    'ALTER TABLE dbmemo ADD COLUMN deleted_at TIMESTAMP NULL COMMENT "軟刪除時間戳"',
    'SELECT "deleted_at 欄位已存在" AS message'
);
PREPARE alter_stmt FROM @alter_sql;
EXECUTE alter_stmt;
DEALLOCATE PREPARE alter_stmt;

-- 建立索引以加快查詢
ALTER TABLE dbmemo ADD INDEX idx_user_deleted (user_id, deleted_at);

-- 完成
SELECT 'Migration completed successfully! 數據庫已升級完成。' AS status;
