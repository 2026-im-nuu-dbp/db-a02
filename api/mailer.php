<?php
// api/mailer.php
require_once '../vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

/**
 * RFC 2047 編碼函式（用於郵件頭中的中文字串）
 */
function encodeHeaderUTF8($text) {
    if (preg_match('/[\x80-\xFF]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
    return $text;
}

/**
 * 使用官方 Gmail API 發送圖文通知信（支援圖片和文檔）
 * @param string $toEmail 收件者信箱
 * @param string $toName 收件者暱稱
 * @param string $action 動作類型
 * @param string $content 備忘錄內容
 * @param string|null $imagePath 圖片路徑 (選填)
 * @param string|null $documentPath 文檔路徑 (選填)
 * @param string|null $documentName 文檔原始名稱 (選填)
 */
function sendSystemNotice($toEmail, $toName, $action, $content, $imagePath = null, $documentPath = null, $documentName = null) {
    $client = new Client();
    
    // 1. 讀取憑證與 Token
    $client->setAuthConfig('../credentials.json'); 
    $client->addScope(Gmail::GMAIL_SEND);
    $client->setAccessType('offline');

    $tokenFile = '../token.json';
    if (!file_exists($tokenFile)) {
        // 如果還沒產生 Token，直接略過發信，避免系統崩潰
        error_log("缺少 token.json，發信功能暫停");
        return false; 
    }

    $accessToken = json_decode(file_get_contents($tokenFile), true);
    $client->setAccessToken($accessToken);

    // 處理 Token 過期
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenFile, json_encode($client->getAccessToken()));
        } else {
            error_log("Token 已過期且無法刷新，請重新授權");
            return false;
        }
    }

    $service = new Gmail($client);

    // 2. 準備郵件標頭與內容格式
    $subject = "【系統通知】備忘錄異動通知";
    $boundary = uniqid("part_"); // 多媒體分隔線
    
    // 取得發件人郵箱（從 Token 的授權帳戶取得）
    $senderEmail = isset($accessToken['email']) ? $accessToken['email'] : 'system@notification.com';
    
    // 編碼包含中文的郵件標頭
    $encodedToName = encodeHeaderUTF8($toName);
    $encodedSubject = encodeHeaderUTF8($subject);

    $rawMessageString = "From: {$senderEmail}\r\n";
    $rawMessageString .= "To: {$encodedToName} <{$toEmail}>\r\n";
    $rawMessageString .= "Subject: {$encodedSubject}\r\n";
    $rawMessageString .= "MIME-Version: 1.0\r\n";
    $rawMessageString .= "Content-Language: zh-TW\r\n";

    // 3. 準備郵件內容
    $hasImage = $imagePath && file_exists('../' . $imagePath);
    $hasDocument = $documentPath && file_exists('../' . $documentPath);

    if ($hasImage || $hasDocument) {
        // --- 有附件：使用 multipart/related 格式 ---
        $rawMessageString .= "Content-Type: multipart/related; boundary=\"{$boundary}\"\r\n\r\n";

        // 區塊 A：HTML 文字內容
        $rawMessageString .= "--{$boundary}\r\n";
        $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessageString .= "<div style='font-family: sans-serif; padding: 20px; max-width: 600px; margin: auto;'>";
        $rawMessageString .= "<h2 style='color: #2563eb; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>圖文備忘錄系統通知</h2>";
        $rawMessageString .= "<p>親愛的 <strong>{$toName}</strong> 您好，系統偵測到您剛剛 <b>{$action}</b>：</p>";
        $rawMessageString .= "<div style='background: #f8fafc; padding: 15px; border-left: 4px solid #3b82f6; margin: 20px 0; font-size: 16px; line-height: 1.6;'>";
        $rawMessageString .= nl2br(htmlspecialchars($content));
        $rawMessageString .= "</div>";

        // 顯示文檔信息
        if ($hasDocument) {
            $docIcon = getEmailDocumentIcon(pathinfo($documentPath, PATHINFO_EXTENSION));
            $rawMessageString .= "<div style='background: #f0f9ff; padding: 12px 15px; border-left: 4px solid #0ea5e9; margin: 15px 0; border-radius: 4px;'>";
            $rawMessageString .= "<p style='margin: 0 0 8px 0; color: #0369a1; font-weight: bold;'>📎 已附加文檔</p>";
            $rawMessageString .= "<p style='margin: 0; color: #334155;'>{$docIcon} <strong>" . htmlspecialchars($documentName) . "</strong></p>";
            $rawMessageString .= "</div>";
        }

        // 顯示圖片
        if ($hasImage) {
            $rawMessageString .= "<div style='text-align: center; margin: 20px 0;'>";
            $rawMessageString .= "<img src=\"cid:memo_image\" style=\"max-width: 100%; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);\">";
            $rawMessageString .= "</div>";
        }

        $rawMessageString .= "</div>\r\n\r\n";

        // 區塊 B：實體圖片（如果存在）
        if ($hasImage) {
            $fileData = file_get_contents('../' . $imagePath);
            $mimeType = mime_content_type('../' . $imagePath);
            $base64Data = chunk_split(base64_encode($fileData));

            $rawMessageString .= "--{$boundary}\r\n";
            $rawMessageString .= "Content-Type: {$mimeType}; name=\"" . basename($imagePath) . "\"\r\n";
            $rawMessageString .= "Content-ID: <memo_image>\r\n";
            $rawMessageString .= "Content-Transfer-Encoding: base64\r\n";
            $rawMessageString .= "Content-Disposition: inline; filename=\"" . basename($imagePath) . "\"\r\n\r\n";
            $rawMessageString .= $base64Data . "\r\n";
        }

        // 區塊 C：實體文檔（如果存在）
        if ($hasDocument) {
            $fileData = file_get_contents('../' . $documentPath);
            $mimeType = mime_content_type('../' . $documentPath);
            $base64Data = chunk_split(base64_encode($fileData));

            $rawMessageString .= "--{$boundary}\r\n";
            $rawMessageString .= "Content-Type: {$mimeType}; name=\"" . basename($documentPath) . "\"\r\n";
            $rawMessageString .= "Content-Transfer-Encoding: base64\r\n";
            $rawMessageString .= "Content-Disposition: attachment; filename=\"" . basename($documentPath) . "\"\r\n\r\n";
            $rawMessageString .= $base64Data . "\r\n";
        }

        $rawMessageString .= "--{$boundary}--\r\n";
        
    } else {
        // --- 沒附件：使用標準的 text/html 格式 ---
        $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessageString .= "<div style='font-family: sans-serif; padding: 20px; max-width: 600px; margin: auto;'>";
        $rawMessageString .= "<h2 style='color: #2563eb; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>圖文備忘錄系統通知</h2>";
        $rawMessageString .= "<p>親愛的 <strong>{$toName}</strong> 您好，系統偵測到您剛剛 <b>{$action}</b>：</p>";
        $rawMessageString .= "<div style='background: #f8fafc; padding: 15px; border-left: 4px solid #3b82f6; margin: 20px 0; font-size: 16px; line-height: 1.6;'>";
        $rawMessageString .= nl2br(htmlspecialchars($content));
        $rawMessageString .= "</div></div>";
    }

    // 4. Google API 規定要轉成 Base64Url 格式並發送
    $rawMessage = strtr(base64_encode($rawMessageString), array('+' => '-', '/' => '_', '=' => ''));
    $message = new Message();
    $message->setRaw($rawMessage);

    try {
        $service->users_messages->send('me', $message);
        return true;
    } catch (Exception $e) {
        error_log("Gmail API 發信失敗: " . $e->getMessage());
        return false;
    }
}

/**
 * 取得郵件中顯示的文檔圖示
 */
function getEmailDocumentIcon($ext) {
    if (!$ext) return '📎';
    $ext = strtolower($ext);
    $icons = [
        'doc' => '📄', 'docx' => '📄', 'docm' => '📄',
        'ppt' => '🎯', 'pptx' => '🎯', 'pptm' => '🎯',
        'xls' => '📊', 'xlsx' => '📊', 'xlsm' => '📊',
        'pdf' => '📕',
        'txt' => '📝', 'rtf' => '📝', 'odt' => '📝',
        'zip' => '📦', 'rar' => '📦', '7z' => '📦',
        'csv' => '📊', 'json' => '{ }', 'xml' => '< >'
    ];
    return $icons[$ext] ?? '📎';
}
?>