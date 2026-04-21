<?php
// auth_helper.php
require_once 'databases.php';

// 從 GET 或 POST 抓取 token
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    header("Location: index.php?error=請先登入");
    exit;
}

// 驗證 Token 是否有效且未過期
$stmt = $pdo->prepare("SELECT * FROM dbusers WHERE access_token = ? AND token_expires_at > NOW()");
$stmt->execute([$token]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    header("Location: index.php?error=登入已過期或無效的通行證");
    exit;
}

// 驗證成功，全域開放變數
$user_id = $currentUser['id'];
$nickname = $currentUser['nickname'];
$userEmail = $currentUser['account'];
$user_id = $currentUser['id'];
$nickname = $currentUser['nickname'];
$userEmail = $currentUser['account'];

// 👇 多加這一行：把角色權限也抓出來
$user_role = $currentUser['role'] ?? 'user';
?>