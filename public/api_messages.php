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
    $mr = new MessageRequest($db);

    $otherUserId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
    if ($otherUserId <= 0 || $otherUserId === (int)$_SESSION['user_id']) {
        echo json_encode(['error' => 'user invalide']);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : null;
    if ($beforeId !== null && $beforeId <= 0) $beforeId = null;

    $me = (int)$_SESSION['user_id'];
    if (!$mr->canChat($me, $otherUserId)) {
        echo json_encode(['messages' => [], 'locked' => true]);
        exit;
    }

    $msgs = $model->getMessagesBetween($me, $otherUserId, $limit, $beforeId);
    echo json_encode(['messages' => $msgs, 'locked' => false]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

