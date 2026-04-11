<?php
session_start();
require_once '../databases.php';
header('Content-Type: application/json');

/**
 * 🛡️ 權限硬核檢查
 * 確保只有 role 為 admin 的 Session 才能繼續執行
 */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // 回傳「禁止存取」狀態碼
    echo json_encode([
        'status' => 'error', 
        'message' => '權限不足：偵測到非管理員存取嘗試。'
    ]);
    exit;
}

try {
    // 1. 抓取所有註冊使用者
    $usersStmt = $pdo->query("SELECT id, account, nickname, gender, role, created_at FROM dbusers ORDER BY id ASC");
    $users = $usersStmt->fetchAll();

    // 2. 抓取最新 50 筆登入活動日誌
    $logsStmt = $pdo->query("SELECT * FROM dblog ORDER BY login_time DESC LIMIT 50");
    $logs = $logsStmt->fetchAll();

    // 3. 抓取全站備忘錄 (重點：使用 JOIN 取得作者暱稱，且不篩選 deleted_at)
    // 這樣管理員才能看到所有人「正常」與「垃圾桶」中的所有內容
    $memoSql = "
        SELECT 
            m.id, 
            m.content, 
            m.image_path, 
            m.thumb_path, 
            m.created_at, 
            m.deleted_at, 
            u.nickname 
        FROM dbmemo m
        JOIN dbusers u ON m.user_id = u.id
        ORDER BY m.created_at DESC
    ";
    $memosStmt = $pdo->query($memoSql);
    $memos = $memosStmt->fetchAll();

    // 4. 封裝並回傳所有資料
    echo json_encode([
        'status' => 'success',
        'data' => [
            'users' => $users,
            'logs'  => $logs,
            'memos' => $memos
        ]
    ]);

} catch (PDOException $e) {
    // 針對資料庫連線或查詢錯誤進行處理
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => '資料庫讀取失敗：' . $e->getMessage()
    ]);
}
?>