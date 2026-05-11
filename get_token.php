<?php
// get_token.php
require __DIR__ . '/vendor/autoload.php';

$client = new Google\Client();
$client->setAuthConfig('credentials.json');
$client->setScopes([Google\Service\Gmail::GMAIL_SEND]);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');
$client->setRedirectUri('http://localhost');

$tokenPath = 'token.json';

if (isset($_GET['code'])) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    file_put_contents($tokenPath, json_encode($accessToken));
    echo "<h1>Token 已更新 (僅限 Gmail 權限)</h1>";
    exit;
}

if (!file_exists($tokenPath)) {
    echo "<a href='".htmlspecialchars($client->createAuthUrl())."'>👉 點我授權 Gmail 寄信功能</a>";
} else {
    echo "<h1>Token 已存在</h1><p>如需重新授權，請刪除 token.json 後重新整理此頁。</p>";
}
?>