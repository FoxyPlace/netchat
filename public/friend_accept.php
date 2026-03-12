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
    $currentUserId = (int)$_SESSION['user_id'];
    $requesterId = isset($_POST['requester_id']) ? (int)$_POST['requester_id'] : 0;

    if ($requesterId <= 0 || $currentUserId === $requesterId) {
        echo json_encode(['error' => 'Paramètres invalides']);
        exit;
    }

    $res = $friendModel->acceptRequest($requesterId, $currentUserId);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>
