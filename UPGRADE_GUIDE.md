# 📎 多格式文件上傳功能指南

## 🎯 新增功能概述

本次更新為系統添加了完整的多格式文件上傳支援，不再只限於圖片。現在用戶可以上傳以下類型的文件：

### 📄 支援的文檔格式

| 格式 | 副檔名 | 圖示 |
|------|--------|------|
| **Word 文檔** | .doc, .docx, .docm, .dot, .dotx, .dotm | 📄 |
| **PowerPoint 文檔** | .ppt, .pptx, .pptm, .pot, .potx, .potm, .thmx | 🎯 |
| **Excel 文檔** | .xls, .xlsx, .xlsm, .xlsb, .xlt, .xltx, .xltm | 📊 |
| **PDF 文件** | .pdf | 📕 |
| **文本格式** | .txt, .rtf, .odt, .ods, .odp | 📝 |
| **壓縮檔** | .zip, .rar, .7z | 📦 |
| **數據格式** | .csv, .json, .xml | 📊/{ }/< > |

### ⚙️ 系統限制
- 單個文件最大 50MB
- 圖片和文檔可同時上傳

---

## 🚀 部署步驟

### 第 1 步：備份數據庫
```bash
# 建議先備份現有數據
mysqldump -u root -p fullstack_app > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 第 2 步：執行數據庫遷移
執行 `migration_add_document_support.sql` 文件：

```bash
mysql -u root -p fullstack_app < migration_add_document_support.sql
```

**或者在 phpMyAdmin 中：**
1. 選擇 `fullstack_app` 資料庫
2. 進入 SQL 標籤
3. 複製貼上 `migration_add_document_support.sql` 的內容
4. 執行

### 第 3 步：確認文件夾結構
系統會自動建立以下文件夾（如不存在）：
```
uploads/
├── images/      （原有的圖片文件夾）
├── thumbs/      （原有的縮圖文件夾）
└── documents/   （新增的文檔文件夾）
```

確保 `uploads` 文件夾有寫入權限：
```bash
chmod 777 uploads/
chmod 777 uploads/documents/
```

---

## 📝 文件修改清單

### 1. **init.sql** ✅ 已更新
- 添加 `document_path` 欄位
- 添加 `document_type` 欄位
- 添加 `document_name` 欄位
- 添加 `deleted_at` 欄位（軟刪除支援）

### 2. **api/memo.php** ✅ 已更新
**新增函數：**
- `isValidDocumentType()` - 驗證檔案類型
- `getDocumentTypeInfo()` - 取得檔案類型資訊和圖示

**修改的 action：**
- `create` - 支援文檔上傳，檔案類型驗證
- `hard_delete` - 刪除時同時刪除文檔檔案

**限制：**
- 最大文檔大小：50MB
- 支援格式白名單驗證

### 3. **dashboard.html** ✅ 已更新
**前端介面改動：**
- 添加文檔上傳 input 控件
- 支援多種檔案格式選擇
- 備忘錄卡片顯示文檔附件
- 每個文檔都有對應的圖示和下載鏈接

**新增 JavaScript 函數：**
- `getDocumentIcon()` - 根據檔案類型返回對應圖示

---

## 🔒 安全性考慮

✅ **已實施的安全措施：**
- 檔案類型白名單驗證
- 檔案大小限制（50MB）
- 原始檔名保存，但使用 `uniqid()` 重命名存儲
- 刪除時完整清理文件和數據庫記錄
- 上傳目錄使用 `move_uploaded_file()` 驗證

⚠️ **建議進一步加強：**
- 配置 web 伺服器（Nginx/Apache）禁止執行 uploads 目錄中的腳本
- 定期掃描上傳的文件是否有病毒
- 設置訪問控制，確保只有文件所有者能下載

---

## 🧪 測試步驟

### 測試 1：上傳 PDF
1. 在前端頁面選擇 PDF 文件
2. 點擊「發佈備忘」
3. 驗證文檔出現在記錄中並可下載

### 測試 2：上傳 Word 文檔
1. 上傳 .docx 文件
2. 確認檔案類型圖示正確顯示（📄）
3. 確認可以正常下載

### 測試 3：同時上傳圖片和文檔
1. 同時選擇圖片和文檔
2. 驗證兩個都正確保存
3. 確認顯示無誤

### 測試 4：文件刪除
1. 刪除含有文檔的記錄
2. 檢查 `uploads/documents/` 文件夾中的文件是否已刪除

---

## 🐛 故障排除

### 問題：上傳時顯示「不支援的檔案格式」
**解決：** 檢查檔案副檔名是否在白名單中，或查看 `api/memo.php` 中的 `isValidDocumentType()` 函數

### 問題：檔案大小超過 50MB
**解決：** 修改 `api/memo.php` 中的 `$maxFileSize` 變數，同時確保 PHP 的 `upload_max_filesize` 和 `post_max_size` 也相應增大

### 問題：無法下載文檔
**解決：** 
- 確認文件確實存在於 `uploads/documents/` 目錄
- 檢查文件夾和文件的讀取權限
- 確認 Web 伺服器允許訪問 uploads 目錄

### 問題：刪除後文件未被清除
**解決：** 手動檢查 `uploads/documents/` 目錄，確認硬刪除邏輯已正確執行

---

## 📊 數據庫查詢參考

### 查看含文檔的記錄
```sql
SELECT id, user_id, content, document_name, document_type, created_at 
FROM dbmemo 
WHERE document_path IS NOT NULL 
ORDER BY created_at DESC;
```

### 查看進行中的刪除
```sql
SELECT id, user_id, content, deleted_at 
FROM dbmemo 
WHERE deleted_at IS NOT NULL 
ORDER BY deleted_at DESC;
```

---

## ✨ 未來增強建議

- [ ] 添加文檔預覽功能（使用 PDF.js 等庫）
- [ ] 支援拖拽上傳多個文件
- [ ] 添加文檔版本控制
- [ ] 支援批量下載
- [ ] 文檔搜索索引
- [ ] 自動病毒掃描整合

---

**部署日期：** 2026-04-11  
**版本：** 2.0（多格式文件支援）  
**負責人：** GitHub Copilot
