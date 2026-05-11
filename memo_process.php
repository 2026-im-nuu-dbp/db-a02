<?php
// memo_process.php
require_once 'auth_helper.php';

$action = $_POST['action'] ?? '';

// GD 庫縮圖函式
function createThumbnail($sourcePath, $targetPath, $maxWidth, $maxHeight) {
    list($width, $height, $type) = getimagesize($sourcePath);
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // 處理透明背景
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

switch ($action) {
    case 'create':
        $content = htmlspecialchars($_POST['content'] ?? '');
        $image_path = null; $thumb_path = null;

        // 處理圖片上傳
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = uniqid('MEMO_') . '.' . $ext;
                
                if (!is_dir('uploads/images')) mkdir('uploads/images', 0777, true);
                if (!is_dir('uploads/thumbs')) mkdir('uploads/thumbs', 0777, true);

                $fullPath = 'uploads/images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                    $image_path = $fullPath;
                    $tPath = 'uploads/thumbs/' . $filename;
                    if (createThumbnail($fullPath, $tPath, 300, 300)) {
                        $thumb_path = $tPath;
                    }
                }
            }
        }
        
        // 寫入資料庫
        $stmt = $pdo->prepare("INSERT INTO dbmemo (user_id, content, image_path, thumb_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $content, $image_path, $thumb_path]);

        // 直接帶著 Token 跳轉回儀表板，不再等待寄信！
        header("Location: dashboard.php?token=" . urlencode($token));
        exit;

    case 'soft_delete':
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        header("Location: dashboard.php?token=" . urlencode($token));
        exit;

    case 'restore':
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE dbmemo SET deleted_at = NULL WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        header("Location: dashboard.php?token=" . urlencode($token) . "&mode=trash");
        exit;

    case 'update':
        $id = $_POST['id'] ?? 0;
        $content = htmlspecialchars($_POST['content'] ?? '');
        $image_path = null; $thumb_path = null;

        // 先查詢舊的圖片路徑
        $stmt = $pdo->prepare("SELECT image_path, thumb_path FROM dbmemo WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $old = $stmt->fetch();

        // 檢查是否上傳新圖片
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = uniqid('MEMO_') . '.' . $ext;
                
                if (!is_dir('uploads/images')) mkdir('uploads/images', 0777, true);
                if (!is_dir('uploads/thumbs')) mkdir('uploads/thumbs', 0777, true);

                $fullPath = 'uploads/images/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                    $image_path = $fullPath;
                    $tPath = 'uploads/thumbs/' . $filename;
                    if (createThumbnail($fullPath, $tPath, 300, 300)) {
                        $thumb_path = $tPath;
                    }
                    // 刪除舊圖片
                    if ($old['image_path'] && file_exists($old['image_path'])) unlink($old['image_path']);
                    if ($old['thumb_path'] && file_exists($old['thumb_path'])) unlink($old['thumb_path']);
                }
            }
        }
        
        // 更新資料庫
        if ($image_path) {
            // 如果有新圖片，更新圖片路徑
            $stmt = $pdo->prepare("UPDATE dbmemo SET content = ?, image_path = ?, thumb_path = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$content, $image_path, $thumb_path, $id, $user_id]);
        } else {
            // 否則只更新內容
            $stmt = $pdo->prepare("UPDATE dbmemo SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$content, $id, $user_id]);
        }

        header("Location: dashboard.php?token=" . urlencode($token));
        exit;
}

// 防呆導向
header("Location: dashboard.php?token=" . urlencode($token));
exit;
?>