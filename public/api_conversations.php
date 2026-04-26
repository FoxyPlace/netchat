<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/MessageRequest.php';

try {
    $db = Database::getInstance()->getConnection();
    $model = new Message($db);
    new MessageRequest($db); // ensure table exists
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $items = $model->getConversationList((int)$_SESSION['user_id'], $limit, $offset);

    // Ajouter les follow mutuels (même sans messages) en conversation "vide"
    $uid = (int)$_SESSION['user_id'];
    $mutualStmt = $db->prepare("
        SELECT u.id AS other_user_id, u.username, u.profile_picture, NULL AS last_message, NULL AS last_at, 0 AS unread_count
        FROM follows f1
        JOIN follows f2 ON f2.follower_id = f1.following_id AND f2.following_id = f1.follower_id
        JOIN users u ON u.id = f1.following_id
        WHERE f1.follower_id = ?
    ");
    $mutualStmt->execute([$uid]);
    $mutual = $mutualStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fusion sans doublons (priorité aux conversations avec messages)
    $seen = [];
    foreach ($items as $it) $seen[(int)$it['other_user_id']] = true;
    foreach ($mutual as $m) {
        $oid = (int)$m['other_user_id'];
        if (!isset($seen[$oid])) {
            // "last_at" pour tri: prendre created_at du follow si possible
            $m['last_at'] = date('Y-m-d H:i:s');
            $items[] = $m;
        }
    }

    // Ajouter les demandes de message en attente (entrantes + sortantes)
    $pendingIn = $db->prepare("
        SELECT u.id AS other_user_id, u.username, u.profile_picture,
               CONCAT('Demande de message de @', u.username) AS last_message,
               mr.created_at AS last_at,
               1 AS unread_count
        FROM message_requests mr
        JOIN users u ON u.id = mr.requester_id
        WHERE mr.target_id = ? AND mr.status = 'pending'
    ");
    $pendingIn->execute([$uid]);
    foreach ($pendingIn->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $oid = (int)$row['other_user_id'];
        if (!isset($seen[$oid])) {
            $items[] = $row;
            $seen[$oid] = true;
        }
    }

    $pendingOut = $db->prepare("
        SELECT u.id AS other_user_id, u.username, u.profile_picture,
               CONCAT('Demande envoyée à @', u.username) AS last_message,
               mr.created_at AS last_at,
               0 AS unread_count
        FROM message_requests mr
        JOIN users u ON u.id = mr.target_id
        WHERE mr.requester_id = ? AND mr.status = 'pending'
    ");
    $pendingOut->execute([$uid]);
    foreach ($pendingOut->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $oid = (int)$row['other_user_id'];
        if (!isset($seen[$oid])) {
            $items[] = $row;
            $seen[$oid] = true;
        }
    }

    echo json_encode(['conversations' => $items]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

