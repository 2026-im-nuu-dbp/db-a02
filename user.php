<?php
require_once '.github/db_config.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    die('無效的用戶 ID');
}

// 查詢用戶公開資訊
$stmt = $pdo->prepare("SELECT username, nickname, avatar, comment_bg_color FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die('用戶不存在');
}

// 查詢該用戶的所有歷史貼文
$stmt = $pdo->prepare("SELECT id, title, content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($user['nickname'] ?? $user['username']); ?> 的檔案</title>
</head>
<body>
    <h1><?php echo e($user['nickname'] ?? $user['username']); ?> 的檔案</h1>

    <div>
        <h2>公開資訊</h2>
        <p>暱稱: <?php echo e($user['nickname'] ?? $user['username']); ?></p>
        <?php if ($user['avatar']): ?>
            <img src="<?php echo e($user['avatar']); ?>" alt="頭貼" width="100">
        <?php endif; ?>
        <p>留言底色: <span style="background-color: <?php echo e($user['comment_bg_color']); ?>; padding: 5px;">範例</span></p>
    </div>

    <div>
        <h2>歷史貼文</h2>
        <?php if (empty($posts)): ?>
            <p>尚未發表任何貼文。</p>
        <?php else: ?>
            <ul>
                <?php foreach ($posts as $post): ?>
                    <li>
                        <h3><?php echo e($post['title']); ?></h3>
                        <p><?php echo e($post['content']); ?></p>
                        <small>發表時間: <?php echo e($post['created_at']); ?></small>
                        <a href="post.php?id=<?php echo $post['id']; ?>">查看詳情</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <a href="index.php">返回首頁</a>
</body>
</html>