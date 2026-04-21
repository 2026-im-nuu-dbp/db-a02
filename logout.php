<?php
// logout.php
require_once 'auth_helper.php'; 

// 銷毀資料庫裡的 Token
$stmt = $pdo->prepare("UPDATE dbusers SET access_token = NULL, token_expires_at = NULL WHERE id = ?");
$stmt->execute([$user_id]);

header("Location: index.php?error=已安全登出");
exit;
?>