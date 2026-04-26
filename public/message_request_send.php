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

    $requesterId = (int)$_SESSION['user_id'];
    $targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
    if ($targetId <= 0 || $targetId === $requesterId) {
        echo json_encode(['error' => 'target_id invalide']);
        exit;
    }

    // Si follow mutuel ou déjà accepté, inutile
    if ($mr->canChat($requesterId, $targetId)) {
        echo json_encode(['success' => true, 'can_chat' => true]);
        exit;
    }

    $f = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1");
    $f->execute([$requesterId, $targetId]);
    if (!$f->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['error' => 'Tu dois suivre cette personne pour lui envoyer une demande']);
        exit;
    }

    $prev = $mr->getStatus($requesterId, $targetId);
    $mr->ensurePending($requesterId, $targetId);
    if ($prev !== 'pending') {
        $notif->create($targetId, 'message_request', $requesterId, null, ['requester_id' => $requesterId]);
    }

    echo json_encode(['success' => true, 'can_chat' => false, 'status' => 'pending']);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

