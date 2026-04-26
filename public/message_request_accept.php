<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/MessageRequest.php';
require_once __DIR__ . '/../app/models/Notification.php';

try {
    $db = Database::getInstance()->getConnection();
    $mr = new MessageRequest($db);
    $notif = new Notification($db);

    $targetId = (int)$_SESSION['user_id']; // celui qui accepte
    $requesterId = isset($_POST['requester_id']) ? (int)$_POST['requester_id'] : 0;
    if ($requesterId <= 0 || $requesterId === $targetId) {
        echo json_encode(['error' => 'requester_id invalide']);
        exit;
    }

    $mr->accept($requesterId, $targetId);
    $notif->create($requesterId, 'message', $targetId, null, ['excerpt' => 'Demande de message acceptée']);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

