<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Post.php';

try {
    $db = Database::getInstance()->getConnection();
    $postModel = new Post($db);
    
    $content = trim($_POST['content'] ?? '');
    $imageUrl = null;
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Le contenu est requis']);
        exit;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/assets/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $videoExtensions = ['mp4', 'webm', 'mov', 'm4v'];
        $allowedExtensions = array_merge($imageExtensions, $videoExtensions);

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'error' => 'Format de fichier non autorisé (images ou vidéos MP4, WebM, MOV).']);
            exit;
        }

        $iniBytes = static function (string $key): int {
            $v = strtolower(trim((string)ini_get($key)));
            if ($v === '' || $v === '0' || $v === 'off') {
                return PHP_INT_MAX;
            }
            if (!preg_match('/^(\d+)([gmk])?$/', $v, $m)) {
                return PHP_INT_MAX;
            }
            $n = (int)$m[1];
            $u = $m[2] ?? '';
            if ($u === 'g') {
                return $n << 30;
            }
            if ($u === 'm') {
                return $n << 20;
            }
            if ($u === 'k') {
                return $n << 10;
            }
            return $n;
        };
        $phpUploadMax = min($iniBytes('upload_max_filesize'), $iniBytes('post_max_size'));
        $size = (int)($_FILES['image']['size'] ?? 0);
        $isVideo = in_array($fileExtension, $videoExtensions, true);
        $maxImage = 15 * 1024 * 1024;
        $maxVideo = min(100 * 1024 * 1024, $phpUploadMax > 0 ? $phpUploadMax : 100 * 1024 * 1024);
        $maxAllowed = $isVideo ? $maxVideo : min($maxImage, $phpUploadMax > 0 ? $phpUploadMax : $maxImage);

        if ($size <= 0) {
            echo json_encode([
                'success' => false,
                'error' => $isVideo
                    ? 'Désolé, la vidéo semble vide ou illisible. Merci d’en choisir une autre.'
                    : 'Désolé, la photo semble vide ou illisible. Merci d’en choisir une autre.',
            ]);
            exit;
        }

        if ($size > $maxAllowed) {
            $msg = $isVideo
                ? 'Désolé, votre vidéo est trop volumineuse. Merci d’en choisir une plus légère.'
                : 'Désolé, votre photo est trop volumineuse. Merci d’en choisir une plus légère.';
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }

        $tmp = $_FILES['image']['tmp_name'];
        if (is_uploaded_file($tmp) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            $allowedMime = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/webm', 'video/quicktime',
            ];
            if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
                $mimeOk = ($mime === 'application/octet-stream' && in_array($fileExtension, $allowedExtensions, true));
                if (!$mimeOk) {
                    echo json_encode(['success' => false, 'error' => 'Type MIME du fichier non autorisé.']);
                    exit;
                }
            }
        }

        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmp, $targetPath)) {
            $imageUrl = 'assets/posts/' . $fileName;
        }
    } elseif (isset($_FILES['image'])) {
        $upErr = (int)($_FILES['image']['error'] ?? 0);
        if ($upErr === UPLOAD_ERR_INI_SIZE || $upErr === UPLOAD_ERR_FORM_SIZE) {
            $name = (string)($_FILES['image']['name'] ?? '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'webm', 'mov', 'm4v'];
            $isVid = in_array($ext, $videoExtensions, true);
            $msg = $isVid
                ? 'Désolé, votre vidéo est trop volumineuse. Merci d’en choisir une plus légère.'
                : 'Désolé, votre photo est trop volumineuse. Merci d’en choisir une plus légère.';
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }
    }
    
    if ($postModel->create($_SESSION['user_id'], $content, $imageUrl)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du post']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
