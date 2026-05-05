<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Notification.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$user = $userModel->findById($_SESSION['user_id']);

$isAdmin = false;
if ($user) {
    if (!empty($user['account_type']) && in_array($user['account_type'], ['administrator', 'moderator'], true)) $isAdmin = true;
    if (!empty($user['admin']) && (int)$user['admin'] === 1) $isAdmin = true;
}

if (!$isAdmin) {
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

$reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$decision_reason = isset($_POST['decision_reason']) ? trim((string)$_POST['decision_reason']) : null;

if ($reportId <= 0) { echo json_encode(['error' => 'Report invalide']); exit; }

// récupérer le report
$q = $db->prepare("SELECT * FROM report WHERE id = ?");
$q->execute([$reportId]);
$rep = $q->fetch(PDO::FETCH_ASSOC);
if (!$rep) { echo json_encode(['error' => 'Signalement introuvable']); exit; }

// Préparer informations complémentaires (post excerpt, reported username)
$postData = null;
if (!empty($rep['post_id'])) {
    $pstmt = $db->prepare("SELECT id, user_id, content FROM posts WHERE id = ?");
    $pstmt->execute([(int)$rep['post_id']]);
    $postData = $pstmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$reportedUsername = null;
if (!empty($rep['reported_user_id'])) {
    $u = $db->prepare("SELECT username FROM users WHERE id = ?");
    $u->execute([(int)$rep['reported_user_id']]);
    $ur = $u->fetch(PDO::FETCH_ASSOC);
    $reportedUsername = $ur ? $ur['username'] : null;
}

// traiter actions : accept, reject, update
if ($action === 'accept' || $action === 'reject') {
    if (empty($decision_reason)) { echo json_encode(['error' => 'Motif requis']); exit; }
    $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
    $upd = $db->prepare("UPDATE report SET status = ?, decision_reason = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
    $upd->execute([$newStatus, $decision_reason, $_SESSION['user_id'], $reportId]);

    // notifications
    $notif = new Notification($db);
    // construire un libellé pour le post (priorité : reported username, sinon extrait du contenu)
    $targetLabel = null;
    if ($reportedUsername) {
        $targetLabel = '@' . $reportedUsername;
    } elseif ($postData && !empty($postData['content'])) {
        $excerpt = mb_substr(trim($postData['content']), 0, 80);
        $targetLabel = '« ' . $excerpt . (mb_strlen($postData['content']) > 80 ? '...' : '') . ' »';
    } else {
        $targetLabel = 'le contenu ciblé';
    }

    // informer le reporter (message en français)
    if ($newStatus === 'accepted') {
        $reporterMessage = "Votre signalement concernant {$targetLabel} a été accepté. Motif : " . $decision_reason;
    } else {
        $reporterMessage = "Votre signalement concernant {$targetLabel} a été rejeté. Motif : " . $decision_reason;
    }
    $notif->create((int)$rep['reporter_user_id'], 'report', $_SESSION['user_id'], (int)$rep['post_id'], ['report_id' => $reportId, 'message' => $reporterMessage, 'decision_reason' => $decision_reason]);

    // si accepted => supprimer le post et notifier le propriétaire
    if ($newStatus === 'accepted') {
        // récupérer post owner
        $p = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $p->execute([(int)$rep['post_id']]);
        $post = $p->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            $ownerId = (int)$post['user_id'];
            // supprimer le post
            $del = $db->prepare("DELETE FROM posts WHERE id = ?");
            $del->execute([(int)$rep['post_id']]);
            // notification au propriétaire (message FR + info)
            $ownerMessage = "Votre contenu (id: " . (int)$rep['post_id'] . ") a été supprimé suite à un signalement. Motif : " . $decision_reason;
            $notif->create($ownerId, 'report', $_SESSION['user_id'], (int)$rep['post_id'], ['report_id' => $reportId, 'message' => $ownerMessage, 'decision_reason' => $decision_reason]);
        }
    }

    echo json_encode(['success' => true, 'status' => $newStatus]);
    exit;
}

// update motif sans changer status
if ($action === 'update') {
    if ($decision_reason === null) { echo json_encode(['error' => 'Motif requis']); exit; }
    $upd = $db->prepare("UPDATE report SET decision_reason = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
    $upd->execute([$decision_reason, $_SESSION['user_id'], $reportId]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Action inconnue']);

?>
