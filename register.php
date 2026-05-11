<?php
// register.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊帳號 - 圖文備忘錄</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; margin: 0; background: #f1f5f9; 
        }
        .login-box { 
            background: white; padding: 40px; border-radius: 12px; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 320px;
        }
        h2 { text-align: center; color: #1e293b; margin-top: 0; margin-bottom: 25px; }
        
        .error-msg { 
            color: #ef4444; background: #fee2e2; padding: 10px; 
            border-radius: 6px; text-align: center; margin-bottom: 15px; 
            font-size: 0.9rem; font-weight: bold;
        }
        
        input { 
            width: 100%; padding: 12px; margin-bottom: 15px; 
            border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; 
            font-size: 1rem; transition: all 0.2s;
        }
        input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-primary { 
            width: 100%; padding: 12px; background: #10b981; color: white; 
            border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; 
            font-weight: bold; transition: background 0.2s; margin-top: 10px;
        }
        .btn-primary:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>✨ 建立新帳號</h2>
        
        <?php if($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="register_process.php" method="POST">
            <input type="email" name="account" placeholder="登入信箱 (Email)" required>
            <input type="text" name="nickname" placeholder="您希望顯示的暱稱" required>
            <input type="password" name="password" placeholder="設定密碼" required>
            <input type="password" name="confirm_password" placeholder="再次確認密碼" required>
            
            <button type="submit" class="btn-primary">完成註冊</button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 0.95rem; color: #64748b;">
            已經有帳號了？ 
            <a href="index.php" style="color: #2563eb; font-weight: bold; text-decoration: none;">返回登入</a>
        </div>
    </div>
</body>
</html>