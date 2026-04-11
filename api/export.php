<?php
// api/export.php
session_start();
require_once '../databases.php';

// 🛡️ 嚴格權限檢查
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('權限不足，拒絕存取。');
}

// 取得前端傳來的格式要求，預設為 sql
$format = $_GET['format'] ?? 'sql';
$timestamp = date('Ymd_His');

// 取得所有資料表名稱
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// --------------------------------------------------------
// 模式 1：匯出 SQL (資料庫完整備份)
// --------------------------------------------------------
if ($format === 'sql') {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_' . $timestamp . '.sql"');
    
    echo "-- 系統資料庫 SQL 備份\n-- 備份時間: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        echo "DROP TABLE IF EXISTS `$table`;\n" . $createStmt['Create Table'] . ";\n\n";
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            foreach ($rows as $row) {
                $vals = array_map(fn($val) => $val === null ? 'NULL' : $pdo->quote($val), array_values($row));
                echo "INSERT INTO `$table` VALUES (" . implode(", ", $vals) . ");\n";
            }
            echo "\n";
        }
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
}

// --------------------------------------------------------
// 模式 2：匯出 CSV (試算表報表格式)
// --------------------------------------------------------
elseif ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $timestamp . '.csv"');
    // 寫入 BOM 檔頭，防止 Excel 打開中文變亂碼
    echo "\xEF\xBB\xBF"; 
    
    $output = fopen('php://output', 'w');
    foreach ($tables as $table) {
        fputcsv($output, ["--- 資料表: $table ---"]); // 表格分隔提示
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            fputcsv($output, array_keys($rows[0])); // 寫入欄位名稱 (標題列)
            foreach ($rows as $row) {
                fputcsv($output, $row); // 寫入資料
            }
        } else {
            fputcsv($output, ["(尚無資料)"]);
        }
        fputcsv($output, []); // 空行區隔
    }
    fclose($output);
}

// --------------------------------------------------------
// 模式 3：匯出 TXT (純文字排版格式)
// --------------------------------------------------------
elseif ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="data_' . $timestamp . '.txt"');
    
    echo "系統資料純文字報表\n匯出時間: " . date('Y-m-d H:i:s') . "\n\n";
    foreach ($tables as $table) {
        echo "========================================================\n";
        echo " 📁 資料表: $table \n";
        echo "========================================================\n";
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $headers = array_keys($rows[0]);
            echo implode(" \t| ", $headers) . "\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($rows as $row) {
                // 將換行符號替換掉，避免破壞排版
                $cleanRow = array_map(fn($val) => str_replace(["\r", "\n"], " ", (string)$val), $row);
                echo implode(" \t| ", $cleanRow) . "\n";
            }
        } else {
            echo "無資料\n";
        }
        echo "\n\n";
    }
}
exit;
?>