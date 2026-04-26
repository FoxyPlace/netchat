<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $currentUserId = (int)$_SESSION['user_id'];

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
    if ($limit < 1) $limit = 1;
    if ($limit > 10) $limit = 10;

    // Random users excluding current user + is_following
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.profile_picture,
            CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS is_following
        FROM users u
        LEFT JOIN follows f 
            ON f.following_id = u.id AND f.follower_id = ?
        WHERE u.id != ?
        ORDER BY RAND()
        LIMIT " . $limit . "
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$currentUserId, $currentUserId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as &$u) {
        $u['id'] = (int)$u['id'];
        $u['is_following'] = (bool)$u['is_following'];
    }

    echo json_encode(['users' => $users]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

