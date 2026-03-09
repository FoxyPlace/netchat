<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $userId = (int)$_SESSION['user_id'];
    $targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;

    if ($targetId <= 0 || $targetId === $userId) {
        echo json_encode(['error' => 'Target invalide']);
        exit;
    }

    // Vérifier si déjà suivi
    $stmt = $db->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'accepted'");
    $stmt->execute([$userId, $targetId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // unfollow
        $db->beginTransaction();
        $del = $db->prepare("DELETE FROM friendships WHERE id = ?");
        $del->execute([$exists['id']]);
        $upd = $db->prepare("UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?");
        $upd->execute([$targetId]);
        $db->commit();
        $following = false;
    } else {
        // follow - insert accepted
        $db->beginTransaction();
        $ins = $db->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
        $ins->execute([$userId, $targetId]);
        $upd = $db->prepare("UPDATE users SET followers_count = followers_count + 1 WHERE id = ?");
        $upd->execute([$targetId]);
        $db->commit();
        $following = true;
    }

    // current followers count
    $c = $db->prepare("SELECT followers_count FROM users WHERE id = ?");
    $c->execute([$targetId]);
    $count = (int)$c->fetch(PDO::FETCH_ASSOC)['followers_count'];

    echo json_encode(['following' => $following, 'followers_count' => $count]);
} catch (Exception $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
