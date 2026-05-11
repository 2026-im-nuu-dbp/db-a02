<?php
// login.php
require_once 'databases.php';

$account = $_POST['account'] ?? '';
$password = $_POST['password'] ?? '';

// 驗證帳密 (請依據你的資料庫實際欄位與加密方式調整)
$stmt = $pdo->prepare("SELECT * FROM dbusers WHERE account = ? AND password = ?");
$stmt->execute([$account, $password]);
$user = $stmt->fetch();

if ($user) {
    // 登入成功：產生高強度 Token
    $newToken = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+2 hours')); // 2小時後過期

    // 更新到資料庫
    $update = $pdo->prepare("UPDATE dbusers SET access_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE id = ?");
    $update->execute([$newToken, $user['id']]);
    // 將 Token 黏在網址上跳轉
    header("Location: dashboard.php?token=" . urlencode($newToken));
    exit;
} else {
    header("Location: index.php?error=" . urlencode('帳號或密碼錯誤'));
    exit;
}
?>