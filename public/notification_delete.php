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
    $userId = (int)$_SESSION['user_id'];

    if (isset($_POST['all']) && (string)$_POST['all'] === '1') {
        $model->deleteAllForUser($userId);
        echo json_encode(['success' => true]);
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'id requis']);
        exit;
    }

    $model->deleteById($userId, $id);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
