<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/MessageRequest.php';

try {
    $db = Database::getInstance()->getConnection();
    $mr = new MessageRequest($db);

    $me = (int)$_SESSION['user_id'];
    $other = isset($_GET['user']) ? (int)$_GET['user'] : 0;
    if ($other <= 0 || $other === $me) {
        echo json_encode(['error' => 'user invalide']);
        exit;
    }

    $stmtFollow = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1");
    $stmtFollow->execute([$me, $other]);
    $youFollow = (bool)$stmtFollow->fetch(PDO::FETCH_ASSOC);

    $stmtFollow2 = $db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1");
    $stmtFollow2->execute([$other, $me]);
    $followsYou = (bool)$stmtFollow2->fetch(PDO::FETCH_ASSOC);

    $mutual = $youFollow && $followsYou;

    $outStatus = $mr->getStatus($me, $other); // me -> other
    $inStatus = $mr->getStatus($other, $me); // other -> me

    $canChat = $mr->canChat($me, $other);

    echo json_encode([
        'can_chat' => $canChat,
        'you_follow' => $youFollow,
        'follows_you' => $followsYou,
        'mutual_follow' => $mutual,
        'request_out_status' => $outStatus,
        'request_in_status' => $inStatus
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
