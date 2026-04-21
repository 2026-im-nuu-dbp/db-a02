<?php
// databases.php
$host = 'localhost';
$dbname = 'fullstack_app';
$username = 'root'; // Mac MAMP 預設是 root，Windows Laragon 預設是 root
$password = '0118Joel'; // Mac MAMP 預設是 root，Windows Laragon 預設是空字串 ''

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}
?>