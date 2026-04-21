<?php
// admin_dashboard.php
require_once 'auth_helper.php'; // 引入核心守衛，取得 $user_id, $nickname, $user_role, $token

// 🚨 絕對防禦：如果角色不是 admin，直接無情踢回前台！
if ($user_role !== 'admin') {
    header("Location: dashboard.php?token=" . urlencode($token));
    exit;
}

// 1. 取得系統營運數據 (Dashboard Stats)
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM dbusers")->fetchColumn();
$stats['active_memos'] = $pdo->query("SELECT COUNT(*) FROM dbmemo WHERE deleted_at IS NULL")->fetchColumn();
$stats['trashed_memos'] = $pdo->query("SELECT COUNT(*) FROM dbmemo WHERE deleted_at IS NOT NULL")->fetchColumn();

// 2. 取得所有使用者列表 (上帝視角)
$users = $pdo->query("SELECT id, account, nickname, role, created_at FROM dbusers ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 系統管理後台 - 圖文備忘錄</title>
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --accent-purple: #8b5cf6;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --border-color: #334155;
        }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: var(--bg-dark); color: var(--text-main); margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: auto; }
        
        /* 頭部導覽 */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .header h2 { margin: 0; color: var(--accent-purple); display: flex; align-items: center; gap: 10px; }
        
        /* 按鈕樣式 */
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; border: none; cursor: pointer; color: white; }
        .btn-blue { background: var(--accent-blue); }
        .btn-blue:hover { background: #2563eb; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-outline:hover { background: var(--border-color); }

        /* 數據卡片 */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-dark); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); text-align: center; border: 1px solid var(--border-color); }
        .stat-card h3 { font-size: 2.5rem; margin: 0; }
        .stat-card p { color: var(--text-sub); margin: 5px 0 0 0; font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        /* 數據表單 */
        .table-container { background: var(--card-dark); border-radius: 12px; padding: 20px; border: 1px solid var(--border-color); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid var(--border-color); }
        th { color: var(--text-sub); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        /* 標籤 */
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; letter-spacing: 0.5px; }
        .badge-admin { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.5); }
        .badge-user { background: rgba(148, 163, 184, 0.1); color: var(--text-sub); border: 1px solid rgba(148, 163, 184, 0.3); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>👑 系統營運後台</h2>
        <div>
            <a href="dashboard.php?token=<?= urlencode($token) ?>" class="btn btn-outline" style="margin-right: 10px;">⬅ 返回前台</a>
            <a href="logout.php?token=<?= urlencode($token) ?>" class="btn btn-blue">登出</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3 style="color: var(--accent-blue);"><?= $stats['total_users'] ?></h3>
            <p>總註冊會員</p>
        </div>
        <div class="stat-card">
            <h3 style="color: var(--accent-green);"><?= $stats['active_memos'] ?></h3>
            <p>活躍備忘錄數量</p>
        </div>
        <div class="stat-card">
            <h3 style="color: var(--accent-red);"><?= $stats['trashed_memos'] ?></h3>
            <p>垃圾桶佔用數量</p>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin-top: 0; color: var(--text-main); margin-bottom: 20px;">👥 會員名冊</h3>
        <table>
            <thead>
                <tr>
                    <th>UID</th>
                    <th>登入帳號 (Email)</th>
                    <th>顯示暱稱</th>
                    <th>系統權限</th>
                    <th>註冊時間</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-sub);">目前無會員資料</td></tr>
                <?php endif; ?>
                
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color: var(--text-sub);">#<?= str_pad($u['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($u['account']) ?></td>
                        <td><?= htmlspecialchars($u['nickname']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="role-badge badge-admin">系統管理員</span>
                            <?php else: ?>
                                <span class="role-badge badge-user">一般會員</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-sub); font-size: 0.9rem;"><?= $u['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>