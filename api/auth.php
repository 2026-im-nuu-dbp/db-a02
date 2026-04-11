<?php
session_start();
require_once '../databases.php'; 
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['action']) && $data['action'] === 'google_login') {
    $account = trim($data['account'] ?? '');
    $nickname = trim($data['nickname'] ?? '未命名使用者');

    try {
        $stmt = $pdo->prepare("SELECT id, nickname, role FROM dbusers WHERE account = ?");
        $stmt->execute([$account]);
        $user = $stmt->fetch();

        $is_new_user = false;
        if ($user) {
            $user_id = $user['id'];
            $role = $user['role'];
            $nickname = $user['nickname'];
        } else {
            // 新註冊：role 預設為 user
            $insertStmt = $pdo->prepare("INSERT INTO dbusers (account, nickname, role) VALUES (?, ?, 'user')");
            $insertStmt->execute([$account, $nickname]);
            $user_id = $pdo->lastInsertId();
            $role = 'user';
            $is_new_user = true;
        }

        // 紀錄日誌
        $pdo->prepare("INSERT INTO dblog (account, is_success) VALUES (?, 1)")->execute([$account]);

        // 寫入 Session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['account'] = $account;
        $_SESSION['nickname'] = $nickname;
        $_SESSION['role'] = $role;

        echo json_encode([
            'status' => 'success', 
            'is_new_user' => $is_new_user, 
            'nickname' => $nickname,
            'role' => $role
        ]);

    } catch (PDOException $e) {
        $pdo->prepare("INSERT INTO dblog (account, is_success) VALUES (?, 0)")->execute([$account]);
        echo json_encode(['status' => 'error', 'message' => '資料庫錯誤']);
    }
}
?>