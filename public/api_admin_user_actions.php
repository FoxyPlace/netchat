<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Notification.php';
require_once __DIR__ . '/../app/models/Message.php';

try {
    $db = Database::getInstance()->getConnection();
    $userModel = new User($db);
    $notificationModel = new Notification($db);
    $messageModel = new Message($db);

    $currentUser = $userModel->findById($_SESSION['user_id']);

    // Vérifier que l'utilisateur est admin ou modérateur
    $isAdmin = false;
    $isAdministrator = false;
    if ($currentUser) {
        if ($currentUser['account_type'] === 'administrator') {
            $isAdministrator = true;
            $isAdmin = true;
        } elseif ($currentUser['account_type'] === 'moderator') {
            $isAdmin = true;
        } elseif (!empty($currentUser['admin']) && (int)$currentUser['admin'] === 1) {
            $isAdministrator = true;
            $isAdmin = true;
        }
    }

    if (!$isAdmin) {
        echo json_encode(['error' => 'Accès non autorisé']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if (!$targetUserId) {
        echo json_encode(['error' => 'ID utilisateur manquant']);
        exit;
    }

    $targetUser = $userModel->findById($targetUserId);
    if (!$targetUser) {
        echo json_encode(['error' => 'Utilisateur introuvable']);
        exit;
    }

    switch ($action) {
        case 'get_details':
            $details = $userModel->findForAdmin($targetUserId);
            echo json_encode(['user' => $details]);
            break;

        case 'update_account_type':
            if (!$isAdministrator) {
                echo json_encode(['error' => 'Seuls les administrateurs peuvent modifier les grades']);
                exit;
            }

            $newAccountType = $_POST['account_type'] ?? '';
            $validTypes = ['user', 'moderator', 'administrator'];

            if (!in_array($newAccountType, $validTypes)) {
                echo json_encode(['error' => 'Type de compte invalide']);
                exit;
            }

            if ($userModel->updateAccountType($targetUserId, $newAccountType)) {
                // Créer une notification système
                $notificationModel->create($targetUserId, 'system', null, null, [
                    'title' => 'Changement de grade',
                    'message' => 'Votre grade a été changé en ' . match($newAccountType) {
                        'administrator' => 'Administrateur',
                        'moderator' => 'Modérateur',
                        'user' => 'Utilisateur'
                    }
                ]);

                echo json_encode(['success' => true, 'message' => 'Grade mis à jour']);
            } else {
                echo json_encode(['error' => 'Erreur lors de la mise à jour']);
            }
            break;

        case 'send_notification':
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($title) || empty($message)) {
                echo json_encode(['error' => 'Titre et message requis']);
                exit;
            }

            $notificationModel->create($targetUserId, 'system', null, null, [
                'title' => $title,
                'message' => $message
            ]);

            echo json_encode(['success' => true, 'message' => 'Notification envoyée']);
            break;

        case 'send_message':
            $message = trim($_POST['message'] ?? '');

            if (empty($message)) {
                echo json_encode(['error' => 'Message requis']);
                exit;
            }

            // Créer un message système (de NETCHAT)
            $messageModel->create([
                'sender_id' => 0, // 0 pour les messages système
                'receiver_id' => $targetUserId,
                'content' => $message,
                'is_read' => 0
            ]);

            echo json_encode(['success' => true, 'message' => 'Message envoyé']);
            break;

        case 'ban_user':
            $banReason = trim($_POST['ban_reason'] ?? '');

            if (empty($banReason)) {
                echo json_encode(['error' => 'Motif de bannissement requis']);
                exit;
            }

            if ($userModel->banUser($targetUserId, $banReason)) {
                // Créer une notification système
                $notificationModel->create($targetUserId, 'system', null, null, [
                    'title' => 'Compte banni',
                    'message' => 'Votre compte a été banni. Motif: ' . $banReason
                ]);

                echo json_encode(['success' => true, 'message' => 'Utilisateur banni']);
            } else {
                echo json_encode(['error' => 'Erreur lors du bannissement']);
            }
            break;

        default:
            echo json_encode(['error' => 'Action inconnue']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>