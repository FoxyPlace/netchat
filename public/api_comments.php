<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Comment.php';

try {
    $db = Database::getInstance()->getConnection();
    $commentModel = new Comment($db);
    $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    if ($postId <= 0) { echo json_encode(['error' => 'post_id requis']); exit; }
    $comments = $commentModel->findByPost($postId);
    echo json_encode(['comments' => $comments]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>
