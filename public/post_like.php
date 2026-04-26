<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';

try {
    $db = Database::getInstance()->getConnection();
    $notif = new Notification($db);
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $userId = (int)$_SESSION['user_id'];

    if ($postId <= 0) {
        echo json_encode(['error' => 'Post invalide']);
        exit;
    }

    // Vérifier si l'utilisateur a déjà liké
    $stmt = $db->prepare("SELECT id FROM reactions WHERE post_id = ? AND user_id = ? AND reaction_type = 'like'");
    $stmt->execute([$postId, $userId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // Supprimer le like (unlike)
        $del = $db->prepare("DELETE FROM reactions WHERE id = ?");
        $del->execute([$exists['id']]);
        $liked = false;
    } else {
        // Insérer le like
        $ins = $db->prepare("INSERT IGNORE INTO reactions (post_id, user_id, reaction_type) VALUES (?, ?, 'like')");
        $ins->execute([$postId, $userId]);
        $liked = true;

        // Notification au propriétaire du post
        $q = $db->prepare("SELECT user_id, content FROM posts WHERE id = ?");
        $q->execute([$postId]);
        $p = $q->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $ownerId = (int)$p['user_id'];
            if ($ownerId && $ownerId !== $userId) {
                $excerpt = mb_substr((string)$p['content'], 0, 120);
                $notif->create($ownerId, 'like', $userId, $postId, ['post_id' => $postId, 'excerpt' => $excerpt]);
            }
        }
    }

    // Compter les likes
    $c = $db->prepare("SELECT COUNT(*) as cnt FROM reactions WHERE post_id = ? AND reaction_type = 'like'");
    $c->execute([$postId]);
    $count = (int)$c->fetch(PDO::FETCH_ASSOC)['cnt'];

    echo json_encode(['liked' => $liked, 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
