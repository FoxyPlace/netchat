<?php
session_start();

// Redir si connecté vers l'app publique
if (isset($_SESSION['user_id'])) {
    header("Location: public/#");
    exit();
}

try {
    $dsn = 'mysql:host=netchat-netchat.i.aivencloud.com;port=13911;dbname=netchat;charset=utf8mb4;ssl_mode=REQUIRED';
    $pdo = new PDO($dsn, 'avnadmin', 'AVNS_-mk_dITiGa0x6UxHo_G', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch(PDOException $e) {
    die("Erreur DB");
}

$error = '';
$identifier_value = '';

if ($_POST) {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];
    $identifier_value = $identifier;
    
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: public/#");
        exit();
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetChat - Connexion</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="netchat-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <img src="assets/logo.png" alt="NetChat" width="50" height="50" class="me-3 title-logo">
                            <h1 class="netchat-title h2 fw-bold mb-0">NetChat</h1>
                        </div>
                        <p class="text-muted">Connecte-toi</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm" class="needs-validation" novalidate>
    <div class="input-group-wrapper mb-4">
        <div class="floating-icon"><i class="fas fa-user"></i></div>
        <input type="text" name="identifier" class="form-control form-control-lg py-3" 
               placeholder="Pseudo ou Email" value="<?= htmlspecialchars($identifier_value) ?>" required>
        <div class="invalid-feedback">Requis</div>
    </div>

    <div class="input-group-wrapper mb-4">
        <div class="floating-icon"><i class="fas fa-lock"></i></div>
        <input type="password" name="password" class="form-control form-control-lg py-3" 
               placeholder="Mot de passe" required>
        <div class="invalid-feedback">Requis</div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">
        Se connecter
    </button>
</form>
 <!-- Séparateur -->
                        <div class="text-center my-4">
                            <span class="text-muted small px-3">ou</span>
                            <hr class="my-2">
                        </div>

                        <!-- Boutons OAuth -->
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn">
                                    <i class="fab fa-google me-2"></i>Google
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn">
                                    <i class="fab fa-github me-2"></i>GitHub
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">
                            Pas encore inscrit ? <a href="register.php" class="link-netchat fw-semibold">S'inscrire</a>
                        </p>
                    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
