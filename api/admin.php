<?php
session_start();
require_once '../databases.php';
require_once 'mailer.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => '權限不足：偵測到非管理員存取嘗試。'
    ]);
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS db_user_changes (
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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete_user') {
            $userId = intval($_POST['id'] ?? 0);
            if ($userId <= 0) {
                throw new Exception('無效的使用者 ID');
            }
            if ($userId === $_SESSION['user_id']) {
                throw new Exception('管理員不可刪除自己');
            }

            $stmt = $pdo->prepare("SELECT id, account, nickname FROM dbusers WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('使用者不存在或已被刪除');
            }

            $adminName = $_SESSION['nickname'] ?? '管理員';
            $insert = $pdo->prepare("INSERT INTO db_user_changes (user_id, account, changed_by, action_type, note) VALUES (?, ?, ?, 'delete_user', ?)");
            $insert->execute([$user['id'], $user['account'], $adminName, '管理員刪除使用者']);

            sendSystemNotice(
                $user['account'],
                $user['nickname'],
                '帳號已被刪除',
                "您好，您的帳號已由管理員刪除。如有疑問請聯絡系統管理員。"
            );

            $delete = $pdo->prepare("DELETE FROM dbusers WHERE id = ?");
            $delete->execute([$userId]);

            echo json_encode(['status' => 'success', 'message' => '使用者已刪除']);
            exit;
        }

        throw new Exception('不支援的管理操作');
    }

    $usersStmt = $pdo->query("SELECT id, account, nickname, gender, role, created_at FROM dbusers ORDER BY id ASC");
    $users = $usersStmt->fetchAll();

    $logsStmt = $pdo->query("SELECT * FROM dblog ORDER BY login_time DESC LIMIT 50");
    $logs = $logsStmt->fetchAll();

    $changesStmt = $pdo->query("SELECT * FROM db_user_changes ORDER BY changed_at DESC LIMIT 100");
    $changes = $changesStmt->fetchAll();

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

    echo json_encode([
        'status' => 'success',
        'data' => [
            'users' => $users,
            'logs' => $logs,
            'changes' => $changes,
            'memos' => $memos
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>