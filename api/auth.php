<?php
// api/auth.php

// 🛡️ 資安防護：高強度 Session Cookie 設定
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,   // ⚠️ 注意：因為你在 localhost 開發沒有 HTTPS，這裡先設為 false。未來上線若有 SSL 請改為 true
    'httponly' => true,  // 禁止 JavaScript 讀取 Cookie，防禦 XSS
    'samesite' => 'Lax'  // 防禦 CSRF 跨站請求偽造
]);
session_start();

require_once '../databases.php';
header('Content-Type: application/json');

// 接收 Google 登入傳來的資料
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['account'])) {
    $acc = $input['account']; 
    $nick = $input['nickname'];
    
    $stmt = $pdo->prepare("SELECT id, nickname, role FROM dbusers WHERE account = ?");
    $stmt->execute([$acc]); 
    $user = $stmt->fetch();
    
    $is_new = false;
    
    if ($user) { 
        $uid = $user['id']; 
        $role = $user['role']; 
        $nick = $user['nickname']; 
    } else {
        $stmt = $pdo->prepare("INSERT INTO dbusers (account, nickname, role) VALUES (?, ?, 'user')");
        $stmt->execute([$acc, $nick]); 
        $uid = $pdo->lastInsertId(); 
        $role = 'user'; 
        $is_new = true;
    }
    
    $pdo->prepare("INSERT INTO dblog (account, is_success) VALUES (?, 1)")->execute([$acc]);
    
    // 🛡️ 資安防護：登入成功瞬間，作廢舊的 Session ID，發配新的
    session_regenerate_id(true); 
    
    $_SESSION['user_id'] = $uid; 
    $_SESSION['role'] = $role; 
    $_SESSION['nickname'] = $nick;
    
    echo json_encode(['status' => 'success', 'is_new_user' => $is_new, 'nickname' => $nick, 'role' => $role]);
}
?>