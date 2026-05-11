[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/VOLNfwbe)
# 作業 5 資料庫基礎存取

## 繳交說明
1. 分組名稱請依照分組表上進行更名，甲班為 A01, A02, .. A12 乙班則為 B01, B02, .. B11。更名若有問題，請找老師協助！
2. 分組作業，不開 PR, 所以也不開branch。
3. 繳交期限  4/20
4. 繳交後可以找時間整組找老師進行demo，請組員理解你們的程式碼，老師會個別問問題。
   
## 作業說明
自行設計題目，但須具備下列功能: 
1. 三個資料表，一個用來存放註冊資料，一個用來存放 log 資料，一個用來存放圖文備忘資料。
   資料表命名分別為 dbusers, dblog 及 dememo 。完成後請將資料表匯出，填入到資料夾中。
2. 具備註冊功能，註冊資料包含
   a. 帳號
   b. 暱稱
   c. 密碼
   d. 性別
   e. 興趣
   ...
3. 具備登入功能，需註冊後才能登入
4. 任何人登入時，紀錄登入者帳號，日期時間以及是否登入成功
5. 登入後可以新增圖文備忘，至少包含
   a. 新增者(使用者id)
   b. 多行文字
   c. 上傳一張圖片，進行縮圖後存放
   d. ...
6. 圖文備忘功能具備 新增、刪除、修改、列出
7. 登入資料可以被瀏覽



/db-a02
├── index.php             (首頁，Google登入入口)
├── login.php             (登入頁面)
├── register.php          (註冊頁面)
├── register_process.php  (處理註冊邏輯)
├── dashboard.php         (使用者主控台，管理個人備忘錄)
├── admin_dashboard.php   (管理員後台，查看全站資料)
├── memo_process.php      (處理備忘錄操作)
├── google_login.php      (Google登入處理)
├── get_token.php         (獲取Google token)
├── auth_helper.php       (認證助手函數)
├── logout.php            (登出處理)
├── databases.php         (資料庫連線設定)
├── init.sql              (資料庫建置腳本)
├── credentials.json      (Google API憑證)
├── composer.json         (Composer設定)
├── uploads/              
│   ├── documents/        (存放文件)
│   ├── images/           (存放原始圖片)
│   └── thumbs/           (存放縮圖)
└── vendor/               (Composer安裝的套件)
## 自行設計的內容說明(同學自填)

### 作品介紹

這是一個「圖文備忘錄平台」，特色為：

- 採用 Google 登入，減少帳號密碼管理負擔。
- 支援圖文備忘錄新增、編輯、刪除。
- 支援使用者註冊與登入。
- 圖片上傳後自動產生縮圖，提高頁面載入效能。
- 管理員可在後台一覽全站使用者、備忘錄與登入紀錄。

系統分兩個主要角色：

- 一般使用者：可以撰寫備忘、上傳圖片、管理自己的備忘。
- 管理員：可查看全站資訊，包含使用者資料、備忘內容、登入紀錄。

### 架構說明

此系統為PHP網頁應用程式，主要檔案包括：

1. **資料庫層**
   - `databases.php`：建立資料庫連線。

2. **使用者介面層**
   - `index.php`：首頁，Google登入入口與新使用者資料補填。
   - `login.php`：登入頁面。
   - `register.php`：註冊頁面。
   - `dashboard.php`：使用者主控台，管理個人備忘錄與垃圾桶功能。
   - `admin_dashboard.php`：管理員後台，查詢全站資料與登入日誌。
   - `logout.php`：登出提示頁。

3. **處理邏輯層**
   - `register_process.php`：處理註冊邏輯。
   - `memo_process.php`：處理備忘錄新增、讀取、刪除、還原與圖片縮圖。
   - `google_login.php`：Google登入驗證與身份判斷。
   - `get_token.php`：獲取Google token。
   - `auth_helper.php`：認證助手函數。
   - `credentials.json`：Google API憑證。
   - `composer.json` & `vendor/`：Composer依賴管理與安裝的套件。

### 互動流程概述

- 使用者在 `index.php` 使用 Google 登入，系統判斷是否為新用戶。
- 若為新用戶，系統引導補填基本資料，再進入 `dashboard.php`。
- 使用者在 `dashboard.php` 建立備忘錄時，`memo_process.php` 會保存文字內容、上傳圖片並生成縮圖，最後將資料寫入資料庫。
- 管理員在 `admin_dashboard.php` 取得全站資料，相關PHP檔案會處理查詢。

這樣的流程整合了前端與後端在PHP檔案中，資料庫負責儲存與查詢。