<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Post.php';

try {
    $db = Database::getInstance()->getConnection();
    $postModel = new Post($db);

    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($q === '') {
        echo json_encode(['posts' => []]);
        exit;
    }

    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit < 1) {
        $limit = 10;
    }
    if ($limit > 30) {
        $limit = 30;
    }

    $posts = $postModel->searchPosts($q, $offset, $limit);

    $stmtFollow = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $userId = (int)$_SESSION['user_id'];
    foreach ($posts as &$p) {
        $stmtFollow->execute([$userId, (int)$p['user_id']]);
        $p['is_following'] = (bool)$stmtFollow->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode(['posts' => $posts]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
