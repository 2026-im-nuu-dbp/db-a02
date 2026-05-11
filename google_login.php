<?php
// google_login.php
require_once 'databases.php';
require_once 'vendor/autoload.php';

// 1. 初始化 Google 客戶端
$client = new Google\Client();
$client->setAuthConfig('credentials.json');

// 🚨 這裡非常重要！必須設定回呼網址，而且要跟 Google Cloud 控制台設定的一模一樣
// 請將 db-a02 換成你實際的資料夾名稱
$client->setRedirectUri('http://localhost/db-a02/google_login.php'); 

// 我們只需要使用者的 Email 和 基本資料
$client->addScope("email");
$client->addScope("profile");

// =================================================================
// 階段 A：使用者剛點擊按鈕，還沒有 code，把他們導向 Google 授權畫面
// =================================================================
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
}

// =================================================================
// 階段 B：使用者同意授權，Google 帶著 code 跳轉回來了
// =================================================================
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

// 檢查是否發生錯誤
if (isset($token['error'])) {
    header('Location: index.php?error=Google登入驗證失敗');
    exit;
}
$client->setAccessToken($token);

// 2. 向 Google 請求該使用者的資料
$oauth2 = new Google\Service\Oauth2($client);
$userInfo = $oauth2->userinfo->get();

$email = $userInfo->email;
$name = $userInfo->name;

// 3. 去我們的資料庫找找看，這個 Email 註冊過了嗎？
$stmt = $pdo->prepare("SELECT * FROM dbusers WHERE account = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // 💡 貼心設計：如果是第一次用 Google 登入，直接幫他在資料庫無痛註冊！
    // 密碼隨便塞個亂碼即可，因為他是靠 Google 驗證的
    $random_password = bin2hex(random_bytes(10)); 
    $insert = $pdo->prepare("INSERT INTO dbusers (account, nickname, password) VALUES (?, ?, ?)");
    $insert->execute([$email, $name, $random_password]);
    $user_id = $pdo->lastInsertId();
} else {
    // 已經是老會員了，抓出他的 ID
    $user_id = $user['id'];
}

// 4. 核心機制：核發我們系統專屬的 URL Token！
$newToken = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+2 hours'));

$update = $pdo->prepare("UPDATE dbusers SET access_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE id = ?");
$update->execute([$newToken, $user_id]);

// 5. 帶著熱騰騰的 Token，華麗跳轉進儀表板！
header("Location: dashboard.php?token=" . urlencode($newToken));
exit;
?>