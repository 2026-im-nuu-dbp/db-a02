<?php
// index.php
// 接收來自其他頁面（如登出、登入失敗）傳來的錯誤或提示訊息
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - 圖文備忘錄</title>
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
            width: 100%; padding: 12px; background: #2563eb; color: white; 
            border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; 
            font-weight: bold; transition: background 0.2s;
        }
        .btn-primary:hover { background: #1d4ed8; }
        
        .divider { 
            text-align: center; margin: 25px 0; position: relative; 
        }
        .divider::before {
            content: ""; position: absolute; top: 50%; left: 0; right: 0; 
            border-top: 1px solid #e2e8f0; z-index: 1;
        }
        .divider span { 
            background: white; padding: 0 15px; color: #94a3b8; 
            font-size: 0.85rem; position: relative; z-index: 2; 
        }

        .btn-google {
            display: flex; align-items: center; justify-content: center; 
            width: 100%; padding: 12px; background: white; color: #475569; 
            border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; 
            font-size: 1rem; font-weight: bold; box-sizing: border-box;
            transition: background 0.2s;
        }
        .btn-google:hover { background: #f8fafc; }
        .btn-google img { width: 20px; height: 20px; margin-right: 10px; }
        
        .register-link {
            text-align: center; margin-top: 25px; font-size: 0.95rem; color: #64748b;
        }
        .register-link a {
            color: #2563eb; font-weight: bold; text-decoration: none; transition: color 0.2s;
        }
        .register-link a:hover { color: #1d4ed8; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>系統登入</h2>
        
        <?php if($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="account" placeholder="帳號 (Email)" required>
            <input type="password" name="password" placeholder="密碼" required>
            <button type="submit" class="btn-primary">登入</button>
        </form>

        <div class="divider">
            <span>或者</span>
        </div>
        
        <a href="google_login.php" class="btn-google">
            <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google Logo">
            使用 Google 帳號登入
        </a>

        <div class="register-link">
            還沒有帳號嗎？ 
            <a href="register.php">立即註冊</a>
        </div>
    </div>
</body>
</html>