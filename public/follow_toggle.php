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

    // Vérifier si déjà suivi (table follows)
    $stmt = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$userId, $targetId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // unfollow
        $db->beginTransaction();
        $del = $db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $del->execute([$userId, $targetId]);
        $upd = $db->prepare("UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?");
        $upd->execute([$targetId]);
        $db->commit();
        $following = false;
    } else {
        // follow - insert into follows
        $db->beginTransaction();
        $ins = $db->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
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
