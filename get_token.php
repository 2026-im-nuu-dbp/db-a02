<?php
// get_token.php (請放在專案根目錄，和 credentials.json 放一起)
require __DIR__ . '/vendor/autoload.php';

$client = new Google\Client();
$client->setApplicationName('Gmail API PHP Quickstart');
$client->setScopes(Google\Service\Gmail::GMAIL_SEND);
$client->setAuthConfig('credentials.json');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// 因為是本機端電腦版應用程式，設定 redirect URI 為 localhost
$client->setRedirectUri('http://localhost');

$tokenPath = 'token.json';

if (isset($_GET['code'])) {
    // 步驟 B：拿到網址上的 code 後，跟 Google 換取 Token
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($accessToken);

    if (array_key_exists('error', $accessToken)) {
        throw new Exception(join(', ', $accessToken));
    }
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    echo "<h1>🎉 太棒了！Token 已成功儲存至 token.json</h1>";
    echo "<p>你可以關閉這個網頁，回到系統去測試發信了。</p>";
    exit;
}

// 步驟 A：產生授權網址
if (!file_exists($tokenPath)) {
    $authUrl = $client->createAuthUrl();
    echo "<h1>第一步：請點擊下方連結授權</h1>";
    echo "<a href='" . htmlspecialchars($authUrl) . "' target='_blank' style='font-size: 20px; color: blue;'>👉 點我登入 Google 並授權寄信</a>";
    echo "<p>授權後，如果網頁顯示「無法連線」或跳轉到 localhost，請把網址列上的 <b>整個網址</b> 複製下來。</p>";
} else {
    echo "<h1>Token 已經存在囉！不需要再授權了。</h1>";
}
?>