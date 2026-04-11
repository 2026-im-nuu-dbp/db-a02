<?php
session_start();
require_once '../databases.php';
header('Content-Type: application/json');

// 1. 權限基本檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '未授權，請重新登入']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

/**
 * 專業縮圖處理函式 (GD 庫)
 * 針對 G16 頂規硬體，我們保持高品質運算
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

        // C. 新增備忘錄 (含圖片處理)
        case 'create':
            $content = htmlspecialchars($_POST['content'] ?? '');
            $image_path = null;
            $thumb_path = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $filename = uniqid('MEMO_') . '.' . $ext;
                    
                    // 確保目錄存在
                    if (!is_dir('../uploads/images')) mkdir('../uploads/images', 0777, true);
                    if (!is_dir('../uploads/thumbs')) mkdir('../uploads/thumbs', 0777, true);

                    $fullPath = '../uploads/images/' . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        $image_path = 'uploads/images/' . $filename;
                        $tPath = '../uploads/thumbs/' . $filename;
                        if (createThumbnail($fullPath, $tPath, 300, 300)) {
                            $thumb_path = 'uploads/thumbs/' . $filename;
                        }
                    }
                }
            }
            $stmt = $pdo->prepare("INSERT INTO dbmemo (user_id, content, image_path, thumb_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $content, $image_path, $thumb_path]);
            echo json_encode(['status' => 'success']);
            break;

        // D. 修改文字內容 (僅限未刪除項目)
        case 'update':
            $id = $_POST['id'] ?? 0;
            $content = htmlspecialchars($_POST['content'] ?? '');
            $stmt = $pdo->prepare("UPDATE dbmemo SET content = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
            $stmt->execute([$content, $id, $user_id]);
            echo json_encode(['status' => 'success']);
            break;

        // E. 移至垃圾桶 (軟刪除)
        case 'soft_delete':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['status' => 'success']);
            break;

        // F. 還原備忘錄
        case 'restore':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = NULL WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['status' => 'success']);
            break;

        // G. 徹底刪除 (刪除 DB 紀錄 + 清理硬碟實體檔案)
        case 'hard_delete':
            $id = $_POST['id'] ?? 0;
            // 先抓出路徑
            $stmt = $pdo->prepare("SELECT image_path, thumb_path FROM dbmemo WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $memo = $stmt->fetch();

            if ($memo) {
                // 刪除實體檔案 (加上 ../ 回到根目錄找路徑)
                if ($memo['image_path'] && file_exists('../' . $memo['image_path'])) unlink('../' . $memo['image_path']);
                if ($memo['thumb_path'] && file_exists('../' . $memo['thumb_path'])) unlink('../' . $memo['thumb_path']);
                
                // 刪除資料庫紀錄
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