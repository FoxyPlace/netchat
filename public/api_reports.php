<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/User.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$user = $userModel->findById($_SESSION['user_id']);

$isAdmin = false;
if ($user) {
    if (!empty($user['account_type']) && in_array($user['account_type'], ['administrator', 'moderator'], true)) $isAdmin = true;
    if (!empty($user['admin']) && (int)$user['admin'] === 1) $isAdmin = true;
}

if (!$isAdmin) {
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// pagination
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

$stmt = $db->prepare("SELECT r.*, u1.username AS reporter_username, u1.profile_picture AS reporter_picture, u2.username AS reported_username, p.content AS post_content, p.image_url AS post_image, p.user_id AS post_user_id FROM report r LEFT JOIN users u1 ON r.reporter_user_id = u1.id LEFT JOIN users u2 ON r.reported_user_id = u2.id LEFT JOIN posts p ON r.post_id = p.id ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reports' => $rows]);

?>
