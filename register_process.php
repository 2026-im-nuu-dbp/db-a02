<?php
// register_process.php
require_once 'databases.php';

$account = trim($_POST['account'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 1. 基本防呆檢查
if (empty($account) || empty($nickname) || empty($password)) {
    header("Location: register.php?error=請填寫所有欄位");
    exit;
}

if ($password !== $confirm_password) {
    header("Location: register.php?error=兩次輸入的密碼不一致");
    exit;
}

// 2. 檢查信箱是否已經被註冊過
$stmt = $pdo->prepare("SELECT id FROM dbusers WHERE account = ?");
$stmt->execute([$account]);
if ($stmt->fetch()) {
    header("Location: register.php?error=這個信箱已經被註冊過了");
    exit;
}

// 3. 建立新使用者 (預設角色為一般 user)
$insert = $pdo->prepare("INSERT INTO dbusers (account, nickname, password, role) VALUES (?, ?, ?, 'user')");
$insert->execute([$account, $nickname, $password]);

// 取得剛剛新增的使用者 ID
$user_id = $pdo->lastInsertId();

// 4. 註冊成功，自動登入！(核發專屬 URL Token)
$newToken = bin2hex(random_bytes(32));

// 這裡我們一樣使用 MySQL 的 DATE_ADD 來避免時區問題
$update = $pdo->prepare("UPDATE dbusers SET access_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE id = ?");
$update->execute([$newToken, $user_id]);

// 5. 帶著 Token 華麗跳轉進儀表板！
header("Location: dashboard.php?token=" . urlencode($newToken));
exit;
?>