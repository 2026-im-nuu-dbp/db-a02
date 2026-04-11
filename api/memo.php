<?php
// api/memo.php
session_start();
require_once '../databases.php';
require_once 'mailer.php'; // 引入系統通知發信模組

header('Content-Type: application/json');

// 1. 權限基本檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '未授權，請重新登入']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

// 2. 取得當前操作者的 Email 與暱稱，準備寄信使用
$stmtUser = $pdo->prepare("SELECT account, nickname FROM dbusers WHERE id = ?");
$stmtUser->execute([$user_id]);
$userInfo = $stmtUser->fetch();
$userEmail = $userInfo['account'] ?? '';
$userName = $userInfo['nickname'] ?? '使用者';

/**
 * 專業縮圖處理函式 (GD 庫)
 */
function createThumbnail($sourcePath, $targetPath, $maxWidth, $maxHeight) {
    list($width, $height, $type) = getimagesize($sourcePath);
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);

    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($sourcePath); break;
        case IMAGETYPE_PNG:  $source = imagecreatefrompng($sourcePath); break;
        case IMAGETYPE_GIF:  $source = imagecreatefromgif($sourcePath); break;
        default: return false;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $targetPath, 85); break;
        case IMAGETYPE_PNG:  imagepng($thumb, $targetPath, 8); break;
        case IMAGETYPE_GIF:  imagegif($thumb, $targetPath); break;
    }
    imagedestroy($thumb);
    imagedestroy($source);
    return true;
}

/**
 * 驗證文檔檔案類型
 */
function isValidDocumentType($ext) {
    $allowedExtensions = [
        // Word 文檔
        'doc', 'docx', 'docm', 'dot', 'dotx', 'dotm',
        // PowerPoint 文檔
        'ppt', 'pptx', 'pptm', 'pot', 'potx', 'potm', 'thmx',
        // Excel 文檔
        'xls', 'xlsx', 'xlsm', 'xlsb', 'xlt', 'xltx', 'xltm',
        // PDF 檔案
        'pdf',
        // 文本格式
        'txt', 'rtf', 'odt', 'ods', 'odp',
        // 壓縮檔
        'zip', 'rar', '7z',
        // 其他常用格式
        'csv', 'json', 'xml'
    ];
    return in_array(strtolower($ext), $allowedExtensions);
}

/**
 * 取得文檔類型圖示和顯示名稱
 */
function getDocumentTypeInfo($ext) {
    $ext = strtolower($ext);
    $icons = [
        'doc' => '📄', 'docx' => '📄', 'docm' => '📄',
        'ppt' => '🎯', 'pptx' => '🎯', 'pptm' => '🎯',
        'xls' => '📊', 'xlsx' => '📊', 'xlsm' => '📊',
        'pdf' => '📕',
        'txt' => '📝', 'rtf' => '📝', 'odt' => '📝',
        'zip' => '📦', 'rar' => '📦', '7z' => '📦',
        'csv' => '📊', 'json' => '{ }', 'xml' => '< >'
    ];
    return $icons[$ext] ?? '📎';
}

// --- 路由邏輯 ---
try {
    switch ($action) {
        // A. 讀取正常備忘錄
        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM dbmemo WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        // B. 讀取垃圾桶內容
        case 'trash_list':
            $stmt = $pdo->prepare("SELECT * FROM dbmemo WHERE user_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $stmt->execute([$user_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        // C. 新增備忘錄
        case 'create':
            $content = htmlspecialchars($_POST['content'] ?? '');
            $image_path = null;
            $thumb_path = null;
            $document_path = null;
            $document_type = null;
            $document_name = null;
            $maxFileSize = 50 * 1024 * 1024; // 50MB 限制

            // 處理圖片上傳
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $filename = uniqid('MEMO_') . '.' . $ext;
                    
                    if (!is_dir('../uploads/images')) mkdir('../uploads/images', 0777, true);
                    if (!is_dir('../uploads/thumbs')) mkdir('../uploads/thumbs', 0777, true);

                    $fullPath = '../uploads/images/' . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        $image_path = 'uploads/images/' . $filename;
                        $tPath = '../uploads/thumbs/' . $filename;
                        // 產生縮圖
                        if (createThumbnail($fullPath, $tPath, 300, 300)) {
                            $thumb_path = 'uploads/thumbs/' . $filename;
                        }
                    }
                }
            }

            // 處理文檔上傳
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['document']['size'] > $maxFileSize) {
                    echo json_encode(['status' => 'error', 'message' => '檔案大小超過 50MB 限制']);
                    exit;
                }

                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                $originalName = basename($_FILES['document']['name']);

                if (isValidDocumentType($ext)) {
                    $filename = uniqid('DOC_') . '.' . $ext;
                    
                    if (!is_dir('../uploads/documents')) mkdir('../uploads/documents', 0777, true);

                    $fullPath = '../uploads/documents/' . $filename;
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $fullPath)) {
                        $document_path = 'uploads/documents/' . $filename;
                        $document_type = $ext;
                        $document_name = $originalName;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => '不支援的檔案格式']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO dbmemo (user_id, content, image_path, thumb_path, document_path, document_type, document_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $content, $image_path, $thumb_path, $document_path, $document_type, $document_name]);
            
            // 寄信並包含文檔信息
            sendSystemNotice($userEmail, $userName, '新增了內容', $content, $image_path, $document_path, $document_name);
            
            echo json_encode(['status' => 'success']);
            break;

        // D. 修改文字內容
        case 'update':
            $id = $_POST['id'] ?? 0;
            $content = htmlspecialchars($_POST['content'] ?? '');
            
            // 先取得圖片和文檔路徑，以便寄信時附上
            $stmtGet = $pdo->prepare("SELECT image_path, document_path, document_name FROM dbmemo WHERE id = ? AND user_id = ?");
            $stmtGet->execute([$id, $user_id]);
            $memo = $stmtGet->fetch();

            $stmt = $pdo->prepare("UPDATE dbmemo SET content = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
            $stmt->execute([$content, $id, $user_id]);
            
            sendSystemNotice($userEmail, $userName, '修改了內容', $content, $memo['image_path'] ?? null, $memo['document_path'] ?? null, $memo['document_name'] ?? null);
            
            echo json_encode(['status' => 'success']);
            break;

        // E. 移至垃圾桶 (軟刪除)
        case 'soft_delete':
            $id = $_POST['id'] ?? 0;
            
            $stmtGet = $pdo->prepare("SELECT content, image_path, document_path, document_name FROM dbmemo WHERE id = ? AND user_id = ?");
            $stmtGet->execute([$id, $user_id]);
            $memo = $stmtGet->fetch();

            $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            if ($memo) sendSystemNotice($userEmail, $userName, '將內容移至垃圾桶', $memo['content'], $memo['image_path'], $memo['document_path'] ?? null, $memo['document_name'] ?? null);
            
            echo json_encode(['status' => 'success']);
            break;

        // F. 還原備忘錄
        case 'restore':
            $id = $_POST['id'] ?? 0;
            
            $stmtGet = $pdo->prepare("SELECT content, image_path, document_path, document_name FROM dbmemo WHERE id = ? AND user_id = ?");
            $stmtGet->execute([$id, $user_id]);
            $memo = $stmtGet->fetch();

            $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = NULL WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            if ($memo) sendSystemNotice($userEmail, $userName, '從垃圾桶還原了內容', $memo['content'], $memo['image_path'], $memo['document_path'] ?? null, $memo['document_name'] ?? null);
            
            echo json_encode(['status' => 'success']);
            break;

        // G. 徹底刪除
        case 'hard_delete':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT content, image_path, thumb_path, document_path, document_name FROM dbmemo WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $memo = $stmt->fetch();

            if ($memo) {
                // 為了讓信件還能顯示圖片和文檔，我們先寄信，再刪除實體檔案！
                sendSystemNotice($userEmail, $userName, '徹底銷毀了紀錄', $memo['content'], $memo['image_path'], $memo['document_path'] ?? null, $memo['document_name'] ?? null);

                if ($memo['image_path'] && file_exists('../' . $memo['image_path'])) unlink('../' . $memo['image_path']);
                if ($memo['thumb_path'] && file_exists('../' . $memo['thumb_path'])) unlink('../' . $memo['thumb_path']);
                if ($memo['document_path'] && file_exists('../' . $memo['document_path'])) unlink('../' . $memo['document_path']);
                
                $del = $pdo->prepare("DELETE FROM dbmemo WHERE id = ?");
                $del->execute([$id]);
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '找不到檔案或無權限']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => '未知動作']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>