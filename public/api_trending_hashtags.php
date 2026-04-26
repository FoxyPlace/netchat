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
    $tags = $postModel->trendingHashtagsFromRecentPosts(220, 48);
    echo json_encode(['tags' => $tags]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
