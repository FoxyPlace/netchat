<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Friend.php';

try {
    $db = Database::getInstance()->getConnection();
    $friendModel = new Friend($db);
    $userId = (int)$_SESSION['user_id'];
    $targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;

    if ($targetId <= 0 || $userId === $targetId) {
        echo json_encode(['error' => 'Target invalide']);
        exit;
    }

    $res = $friendModel->toggleRequest($userId, $targetId);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>
