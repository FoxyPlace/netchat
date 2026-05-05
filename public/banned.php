<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/User.php';

$userModel = new User(Database::getInstance()->getConnection());

// Vérifier si c'est un utilisateur banni tentant de se connecter
if (isset($_SESSION['banned_user_id'])) {
    $userId = $_SESSION['banned_user_id'];
    unset($_SESSION['banned_user_id']);
    $user = $userModel->findById($userId);
    if (!$user || strpos($user['username'], 'Utilisateur Banni') !== 0) {
        header('Location: /netchat/public/login');
        exit;
    }
} elseif (isset($_SESSION['user_id'])) {
    // Si déjà connecté et banni, vérifier
    $user = $userModel->findById($_SESSION['user_id']);
    if (!$user || strpos($user['username'], 'Utilisateur Banni') !== 0) {
        header('Location: /netchat/public/');
        exit;
    }
} else {
    header('Location: /netchat/public/login');
    exit;
}

// Déconnecter l'utilisateur
session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte banni - NetChat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="card shadow-lg" style="max-width: 500px;">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                </div>
                <h1 class="h3 mb-3 text-danger">Compte suspendu</h1>
                <p class="text-muted mb-4">
                    Votre compte a été suspendu par un administrateur.
                    Vous ne pouvez plus accéder à NetChat.
                </p>
                <?php if ($user['bio']): ?>
                <div class="alert alert-warning">
                    <strong>Motif :</strong> <?= htmlspecialchars($user['bio']) ?>
                </div>
                <?php endif; ?>
                <p class="text-muted small">
                    Si vous pensez que c'est une erreur, contactez le support.
                </p>
                <a href="/netchat/public/login" class="btn btn-primary">Retour à la connexion</a>
            </div>
        </div>
    </div>
</body>
</html>