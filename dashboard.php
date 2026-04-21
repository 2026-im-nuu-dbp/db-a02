<?php
// dashboard.php
require_once 'auth_helper.php'; // 核心守衛：驗證 URL Token 並提供 $user_id, $nickname, $user_role, $token

// 1. 決定目前顯示模式：一般備忘錄 (active) 或 垃圾桶 (trash)
$mode = $_GET['mode'] ?? 'active';

// 2. 根據模式從資料庫抓取資料
if ($mode === 'trash') {
    $stmt = $pdo->prepare("SELECT * FROM dbmemo WHERE user_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM dbmemo WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
}
$stmt->execute([$user_id]);
$memos = $stmt->fetchAll();

// 3. 統計數據（選配，用於 UI 顯示）
$activeCount = $pdo->query("SELECT COUNT(*) FROM dbmemo WHERE user_id = $user_id AND deleted_at IS NULL")->fetchColumn();
$trashCount = $pdo->query("SELECT COUNT(*) FROM dbmemo WHERE user_id = $user_id AND deleted_at IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工作面板 - 備忘錄系統</title>
    <style>
        :root {
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --primary: #2563eb;
            --danger: #ef4444;
            --success: #22c55e;
            --text-main: #1e293b;
            --text-sub: #64748b;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-color); color: var(--text-main); margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: auto; }
        
        /* 導覽列 */
        nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .user-info h2 { margin: 0; font-size: 1.5rem; }
        .nav-actions { display: flex; gap: 10px; }

        /* 通用按鈕 */
        .btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; font-size: 0.9rem; display: inline-flex; align-items: center; }
        .btn-blue { background: var(--primary); color: white; }
        .btn-red { background: var(--danger); color: white; }
        .btn-gray { background: #64748b; color: white; }
        .btn-purple { background: #8b5cf6; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        /* 新增備忘錄區塊 */
        .editor-card { background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 30px; }
        textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 1rem; resize: vertical; min-height: 100px; box-sizing: border-box; margin-bottom: 15px; }
        .file-input { font-size: 0.85rem; color: var(--text-sub); }

        /* 內容列表 */
        .memo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .memo-card { background: var(--card-bg); border-radius: 12px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: 0.3s; }
        .memo-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .memo-content { flex-grow: 1; margin-bottom: 15px; white-space: pre-wrap; font-size: 1rem; }
        .memo-image { width: 100%; border-radius: 8px; margin-bottom: 12px; object-fit: cover; aspect-ratio: 16/9; cursor: pointer; }
        
        .card-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-sub); border-top: 1px solid #f1f5f9; padding-top: 10px; }
        .badge { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <nav>
        <div class="user-info">
            <h2>👋 平安，<?= htmlspecialchars($nickname) ?></h2>
            <div style="font-size: 0.85rem; color: var(--text-sub);">
                狀態：<span class="badge"><?= ($user_role === 'admin') ? '🔥 系統管理員' : '一般會員' ?></span>
            </div>
        </div>
        <div class="nav-actions">
            <?php if ($user_role === 'admin'): ?>
                <a href="admin_dashboard.php?token=<?= urlencode($token) ?>" class="btn btn-purple">👑 管理後台</a>
            <?php endif; ?>
            <a href="dashboard.php?token=<?= urlencode($token) ?>&mode=active" class="btn <?= $mode==='active' ? 'btn-blue' : 'btn-gray' ?>">備忘錄 (<?= $activeCount ?>)</a>
            <a href="dashboard.php?token=<?= urlencode($token) ?>&mode=trash" class="btn <?= $mode==='trash' ? 'btn-blue' : 'btn-gray' ?>">垃圾桶 (<?= $trashCount ?>)</a>
            <a href="logout.php?token=<?= urlencode($token) ?>" class="btn btn-red">登出</a>
        </div>
    </nav>

    <?php if ($mode === 'active'): ?>
    <div class="editor-card">
        <form action="memo_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="action" value="create">
            <textarea name="content" required placeholder="想記下什麼？用文字或圖片捕捉瞬間..."></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <input type="file" name="image" accept="image/*" class="file-input">
                <button type="submit" class="btn btn-blue">✨ 發佈備忘</button>
            </div>
        </form>
    </div>
    <?php else: ?>
        <h3 style="color: var(--text-sub);">🗑️ 垃圾桶中的內容 (會在過期後自動清理)</h3>
    <?php endif; ?>

    <div class="memo-grid">
        <?php if (empty($memos)): ?>
            <p style="color: var(--text-sub); text-align:center; grid-column: 1/-1; padding: 40px;">目前沒有任何內容。</p>
        <?php endif; ?>

        <?php foreach ($memos as $m): ?>
            <div class="memo-card">
                <?php if ($m['thumb_path']): ?>
                    <a href="<?= htmlspecialchars($m['image_path']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($m['thumb_path']) ?>" class="memo-image" alt="備忘圖片">
                    </a>
                <?php endif; ?>
                
                <div class="memo-content"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                
                <div class="card-footer">
                    <span><?= substr($m['created_at'], 5, 11) ?></span>
                    <div class="card-actions">
                        <?php if ($mode === 'active'): ?>
                            <form action="memo_process.php" method="POST" style="display:inline;">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                <input type="hidden" name="action" value="soft_delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-red" style="padding: 4px 8px; font-size: 0.7rem;" onclick="return confirm('要移到垃圾桶嗎？')">刪除</button>
                            </form>
                        <?php else: ?>
                            <form action="memo_process.php" method="POST" style="display:inline;">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-blue" style="padding: 4px 8px; font-size: 0.7rem; background: var(--success);">還原</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>