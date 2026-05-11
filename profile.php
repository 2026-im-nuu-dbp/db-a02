<?php
require_once '.github/db_config.php';
check_auth();

$user_id = $_SESSION['user_id'];

// 獲取當前用戶資料
$stmt = $pdo->prepare("SELECT username, email, nickname, avatar, comment_bg_color FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $comment_bg_color = $_POST['comment_bg_color'];

    // 處理頭貼上傳
    $avatar_path = $user['avatar']; // 預設保持原圖
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/images/';
        $file_name = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
            $avatar_path = $target_file;
        }
    }

    // 更新資料庫
    $stmt = $pdo->prepare("UPDATE users SET nickname = ?, avatar = ?, comment_bg_color = ? WHERE id = ?");
    $stmt->execute([$nickname, $avatar_path, $comment_bg_color]);

    // 重新載入頁面
    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>個人設定</title>
</head>
<body>
    <h1>個人設定</h1>
    <form method="POST" enctype="multipart/form-data">
        <label>顯示暱稱: <input type="text" name="nickname" value="<?php echo e($user['nickname'] ?? $user['username']); ?>"></label><br>
        <label>大頭貼圖片: <input type="file" name="avatar" accept="image/*"></label><br>
        <?php if ($user['avatar']): ?>
            <img src="<?php echo e($user['avatar']); ?>" alt="當前頭貼" width="100"><br>
        <?php endif; ?>
        <label>專屬留言底色: <input type="color" name="comment_bg_color" value="<?php echo e($user['comment_bg_color'] ?? '#ffffff'); ?>"></label><br>
        <button type="submit">儲存設定</button>
    </form>
    <a href="dashboard.php">返回儀表板</a>
</body>
</html>