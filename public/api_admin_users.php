<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/User.php';

try {
    $db = Database::getInstance()->getConnection();
    $userModel = new User($db);
    $currentUser = $userModel->findById($_SESSION['user_id']);

    // Vérifier que l'utilisateur est admin ou modérateur
    $isAdmin = false;
    if ($currentUser) {
        if (!empty($currentUser['account_type']) && in_array($currentUser['account_type'], ['administrator', 'moderator'], true)) {
            $isAdmin = true;
        } elseif (!empty($currentUser['admin']) && (int)$currentUser['admin'] === 1) {
            $isAdmin = true;
        }
    }

    if (!$isAdmin) {
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($limit < 1) $limit = 1;
    if ($limit > 100) $limit = 100;
    if ($offset < 0) $offset = 0;

    $users = $userModel->findAll($offset, $limit);
    $total = $userModel->countAll();

    // Formater les données pour l'affichage
    foreach ($users as &$user) {
        $user['is_banned'] = strpos($user['username'], 'Utilisateur Banni') === 0;
        $user['account_type_display'] = match($user['account_type']) {
            'administrator' => 'Administrateur',
            'moderator' => 'Modérateur',
            'user' => 'Utilisateur',
            default => 'Utilisateur'
        };
        $user['status_display'] = match($user['status']) {
            'online' => 'En ligne',
            'away' => 'Absent',
            'busy' => 'Occupé',
            'offline' => 'Hors ligne',
            default => 'Hors ligne'
        };
    }

    echo json_encode([
        'users' => $users,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>