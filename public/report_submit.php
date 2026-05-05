<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';

$pdo = Database::getInstance()->getConnection();

// Vérifier si utilisateur connecté
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vous devez être connecté pour signaler un contenu.']);
    exit;
}

$reporter_id = (int)$_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reported_user_id = isset($_POST['reported_user_id']) ? ((int)$_POST['reported_user_id'] ?: null) : null;
$reason = trim((string)($_POST['reason'] ?? ''));

if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Post invalide.']);
    exit;
}

if ($reason === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Veuillez préciser un motif.']);
    exit;
}

try {
    // Empêcher un doublon : même utilisateur qui a déjà signalé le même post
    $check = $pdo->prepare("SELECT id FROM report WHERE reporter_user_id = ? AND post_id = ? LIMIT 1");
    $check->execute([$reporter_id, $post_id]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);
    if ($exists) {
        echo json_encode(['error' => 'Vous avez déjà signalé ce post.']);
        exit;
    }

    // Insérer le report (colonnes nommées selon netchat.sql)
    $stmt = $pdo->prepare("INSERT INTO report (reporter_user_id, reported_user_id, post_id, reason, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $ok = $stmt->execute([$reporter_id, $reported_user_id, $post_id, $reason]);

    if (!$ok) throw new Exception('Impossible d\'enregistrer le signalement.');

    $reportId = (int)$pdo->lastInsertId();

    // Créer une notification pour l'utilisateur ayant signalé (même approche que post_like.php)
    $notif = new Notification($pdo);
    $notif->create($reporter_id, 'report', $reporter_id, $post_id, ['report_id' => $reportId, 'message' => 'Votre signalement est en cours de traitement.']);

    echo json_encode(['success' => true, 'message' => 'Signalement transmis.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>