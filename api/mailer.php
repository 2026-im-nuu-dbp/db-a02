<?php
// api/mailer.php
require_once '../vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

/**
 * 使用官方 Gmail API 發送通知
 */
function sendSystemNotice($toEmail, $toName, $action, $content) {
    $client = new Client();
    
    // 1. 認證設定
    // 這裡建議將下載的 credentials.json 放在安全的地方
    $client->setAuthConfig('../credentials.json'); 
    $client->addScope(Gmail::GMAIL_SEND);
    $client->setAccessType('offline');

    // 💡 實務提醒：你需要一個存好的 Refresh Token 或是 Access Token
    // 這裡假設你已經完成初次授權並拿到 token
    $accessToken = json_decode(file_get_contents('../token.json'), true);
    $client->setAccessToken($accessToken);

    // 如果 Token 過期，自動刷新
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents('../token.json', json_encode($client->getAccessToken()));
        } else {
            return false; // 需要重新授權
        }
    }

    $service = new Gmail($client);

    // 2. 準備郵件內容 (符合 RFC 2822 標準)
    $subject = "【系統通知】備忘錄異動通知";
    $boundary = uniqid(rand(), true);
    
    $rawMessageString = "To: {$toName} <{$toEmail}>\r\n";
    $rawMessageString .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
    $rawMessageString .= "MIME-Version: 1.0\r\n";
    $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $rawMessageString .= "<h3>您的備忘錄已更新</h3>";
    $rawMessageString .= "<p>動作類型：<b>{$action}</b></p>";
    $rawMessageString .= "<p>內容摘要：<br>" . nl2br(htmlspecialchars($content)) . "</p>";

    // 3. Google API 要求 Message 必須經過 Base64Url 編碼
    $rawMessage = strtr(base64_encode($rawMessageString), array('+' => '-', '/' => '_', '=' => ''));

    $message = new Message();
    $message->setRaw($rawMessage);

    try {
        $service->users_messages->send('me', $message);
        return true;
    } catch (Exception $e) {
        error_log("Gmail API 寄信失敗: " . $e->getMessage());
        return false;
    }
}