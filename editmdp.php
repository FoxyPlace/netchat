<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Connexion DB
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

$errorMessages = [];
$successMessage = '';

// Récupérer quelques infos de l'utilisateur (pour affichage éventuel / règles mdp)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation basique inspirée de register.php
    if ($new_password !== $confirm_password) {
        $errorMessages[] = "Les mots de passe ne correspondent pas";
    }
    if (strlen($new_password) < 8) {
        $errorMessages[] = "Mot de passe trop court (8 caractères minimum)";
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        $errorMessages[] = "Le mot de passe doit contenir au moins une majuscule";
    }
    if (!preg_match('/[a-z]/', $new_password)) {
        $errorMessages[] = "Le mot de passe doit contenir au moins une minuscule";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $errorMessages[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    if (!preg_match('/[\W_]/', $new_password)) {
        $errorMessages[] = "Le mot de passe doit contenir au moins un caractère spécial";
    }
    if (stripos($new_password, $user['username']) !== false) {
        $errorMessages[] = "Le mot de passe ne doit pas contenir votre pseudo";
    }

    if (empty($errorMessages)) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($stmt->execute([$hash, $_SESSION['user_id']])) {
            $successMessage = "Mot de passe mis à jour avec succès";
        } else {
            $errorMessages[] = "Erreur lors de la mise à jour du mot de passe";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le mot de passe - NetChat</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icon.png">
    <link rel="apple-touch-icon" href="assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'assets/navbar.html'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="netchat-card p-4 p-md-5">
                    <h3 class="mb-4 text-center">
                        <i class="fas fa-key me-2"></i>Changer le mot de passe
                    </h3>

                    <?php if (!empty($errorMessages)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0 text-start">
                                <?php foreach ($errorMessages as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Nouveau mot de passe -->
                        <div class="input-group-wrapper mb-4">
                            <div class="floating-icon"><i class="fas fa-lock"></i></div>
                            <input type="password" name="new_password" id="new_password" class="form-control form-control-lg py-3" 
                                   placeholder="Nouveau mot de passe" required minlength="8">
                            <button type="button" class="password-toggle" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">8 caractères minimum</div>
                        </div>

                        <!-- Confirmation -->
                        <div class="input-group-wrapper mb-4">
                            <div class="floating-icon"><i class="fas fa-lock-open"></i></div>
                            <input type="password" name="confirm_password" id="confirm_new_password" class="form-control form-control-lg py-3" 
                                   placeholder="Confirmer le mot de passe" required>
                            <button type="button" class="password-toggle" data-target="confirm_new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Les mots de passe doivent correspondre</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

