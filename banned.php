<?php
session_start();

// Si l'utilisateur n'est pas connecté ou n'est pas banni, rediriger
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/User.php';

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$user = $userModel->findById($_SESSION['user_id']);

$isBanned = $user && strpos($user['username'], 'Utilisateur Banni') === 0;

if (!$isBanned) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer la raison du ban (stockée dans le bio pour l'instant)
$banReason = $user['bio'] ?? 'Aucune raison spécifiée';

$title = 'Compte banni - NetChat';
include __DIR__ . '/app/views/layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #ff4757 0%, #ff3838 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="text-center text-white">
                    <div class="mb-4">
                        <i class="fas fa-ban fa-5x text-white mb-4"></i>
                        <h1 class="display-4 fw-bold mb-3">Compte suspendu</h1>
                        <p class="lead mb-4">Votre compte NetChat a été banni par un administrateur.</p>
                    </div>

                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-4 p-4 mb-4">
                        <h5 class="text-white mb-3">Motif du bannissement :</h5>
                        <p class="text-white-50 mb-0"><?php echo htmlspecialchars($banReason); ?></p>
                    </div>

                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-4 p-4 mb-4">
                        <h6 class="text-white mb-3">Que faire maintenant ?</h6>
                        <p class="text-white-50 small mb-0">
                            Si vous pensez que cette décision est une erreur, vous pouvez contacter l'équipe de modération via email à l'adresse suivante : moderation@netchat.com
                        </p>
                    </div>

                    <div class="mt-4">
                        <a href="logout.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.backdrop-blur {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
</style>

<?php include __DIR__ . '/app/views/layouts/footer.php'; ?>