<?php
session_start();

// 1. 清空 Session 變數
$_SESSION = array();

// 2. 如果有使用 Cookie 儲存 Session ID，也一併清除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. 銷毀 Session
session_destroy();

// 4. 導向登出成功的視覺畫面
header("Location: ../logout.html");
exit;
?>