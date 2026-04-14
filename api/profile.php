<?php
session_start();
require_once '../databases.php';
require_once 'mailer.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '未授權']);
    exit;
}

$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT account, nickname, gender, interests, role FROM dbusers WHERE id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetch()]);
} elseif ($method === 'POST') {
    $gender = $_POST['gender'] ?? 'other';
    $gender = in_array($gender, ['male', 'female', 'other']) ? $gender : 'other';
    $interests = trim($_POST['interests'] ?? '');
    $nickname = trim($_POST['nickname'] ?? $_SESSION['nickname']);
    $nickname = $nickname === '' ? $_SESSION['nickname'] : $nickname;

    $stmt = $pdo->prepare("SELECT account, nickname, gender, interests FROM dbusers WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch();
    if (!$current) {
        echo json_encode(['status' => 'error', 'message' => '使用者不存在']);
        exit;
    }

    $sanitizedNickname = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
    $sanitizedInterests = htmlspecialchars($interests, ENT_QUOTES, 'UTF-8');

    $changes = [];
    if ($current['nickname'] !== $sanitizedNickname) {
        $changes[] = ['field' => 'nickname', 'old' => $current['nickname'], 'new' => $sanitizedNickname];
    }
    if ($current['gender'] !== $gender) {
        $changes[] = ['field' => 'gender', 'old' => $current['gender'], 'new' => $gender];
    }
    if ($current['interests'] !== $sanitizedInterests) {
        $changes[] = ['field' => 'interests', 'old' => $current['interests'], 'new' => $sanitizedInterests];
    }

    $stmt = $pdo->prepare("UPDATE dbusers SET gender = ?, interests = ?, nickname = ? WHERE id = ?");
    if ($stmt->execute([$gender, $sanitizedInterests, $sanitizedNickname, $user_id])) {
        $_SESSION['nickname'] = $sanitizedNickname;

        if (!empty($changes)) {
            $insert = $pdo->prepare("INSERT INTO db_user_changes (user_id, account, changed_by, action_type, field_name, old_value, new_value, note) VALUES (?, ?, ?, 'profile_update', ?, ?, ?, ?)");
            foreach ($changes as $change) {
                $insert->execute([
                    $user_id,
                    $current['account'],
                    $current['account'],
                    $change['field'],
                    $change['old'],
                    $change['new'],
                    '使用者自行更新'
                ]);
            }

            $emailContent = "您好，您的個人資料已更新：\n\n";
            foreach ($changes as $change) {
                $emailContent .= sprintf("%s：%s → %s\n", $change['field'], $change['old'], $change['new']);
            }
            $emailContent .= "\n若這不是您本人操作，請盡速聯絡系統管理員。";
            sendSystemNotice($current['account'], $sanitizedNickname, '更新了個人資料', $emailContent);
        }

        echo json_encode(['status' => 'success', 'message' => '資料儲存成功']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '儲存失敗']);
    }
}
?>