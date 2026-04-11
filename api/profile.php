<?php
session_start();
require_once '../databases.php';
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
} 
elseif ($method === 'POST') {
    $gender = $_POST['gender'] ?? 'other';
    $interests = htmlspecialchars($_POST['interests'] ?? '');
    $nickname = htmlspecialchars($_POST['nickname'] ?? $_SESSION['nickname']);

    $stmt = $pdo->prepare("UPDATE dbusers SET gender = ?, interests = ?, nickname = ? WHERE id = ?");
    if ($stmt->execute([$gender, $interests, $nickname, $user_id])) {
        $_SESSION['nickname'] = $nickname;
        echo json_encode(['status' => 'success', 'message' => '資料儲存成功']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '儲存失敗']);
    }
}
?>