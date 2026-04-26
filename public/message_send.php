<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/Notification.php';
require_once __DIR__ . '/../app/models/MessageRequest.php';

try {
    $db = Database::getInstance()->getConnection();
    $model = new Message($db);
    $notif = new Notification($db);
    $mr = new MessageRequest($db);

    $senderId = (int)$_SESSION['user_id'];
    $receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $content = trim((string)($_POST['content'] ?? ''));

    if ($receiverId <= 0 || $receiverId === $senderId) {
        echo json_encode(['error' => 'receiver_id invalide']);
        exit;
    }
    if ($content === '') {
        echo json_encode(['error' => 'Message vide']);
        exit;
    }
    if (mb_strlen($content) > 2000) {
        echo json_encode(['error' => 'Message trop long']);
        exit;
    }

    // Règle produit: tu dois suivre la personne pour initier une discussion / demande
    $f = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1");
    $f->execute([$senderId, $receiverId]);
    if (!$f->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['error' => 'Tu dois suivre cette personne pour lui écrire']);
        exit;
    }

    // Si pas follow mutuel et pas de demande acceptée → créer une demande de message (TikTok)
    if (!$mr->canChat($senderId, $receiverId)) {
        $prev = $mr->getStatus($senderId, $receiverId);
        $mr->ensurePending($senderId, $receiverId);
        if ($prev !== 'pending') {
            $notif->create($receiverId, 'message_request', $senderId, null, ['requester_id' => $senderId, 'excerpt' => mb_substr($content, 0, 120)]);
        }
        echo json_encode(['success' => true, 'requested' => true, 'status' => 'pending']);
        exit;
    }

    $id = $model->send($senderId, $receiverId, $content);
    if (!$id) {
        echo json_encode(['error' => 'Envoi impossible']);
        exit;
    }

    // Notification "message"
    $excerpt = mb_substr($content, 0, 120);
    $notif->create($receiverId, 'message', $senderId, null, ['message_id' => (int)$id, 'excerpt' => $excerpt]);

    echo json_encode(['success' => true, 'id' => (int)$id]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

