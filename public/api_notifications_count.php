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
    $model = new Notification($db);
    $count = $model->countUnread((int)$_SESSION['user_id']);
    echo json_encode(['unread' => $count]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
