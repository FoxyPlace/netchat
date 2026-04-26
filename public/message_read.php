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

    $userId = (int)$_SESSION['user_id'];
    $other = isset($_POST['user']) ? (int)$_POST['user'] : 0;
    if ($other <= 0 || $other === $userId) {
        echo json_encode(['error' => 'user invalide']);
        exit;
    }

    if ($mr->canChat($userId, $other)) {
        $model->markReadFromSender($userId, $other);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

