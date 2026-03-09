<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'NetChat - Connexion';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="netchat-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <img src="<?= $basePath ?? '/netchat/public' ?>/assets/logo.png" alt="NetChat" width="50" height="50" class="me-3 title-logo">
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
                               placeholder="Pseudo ou Email" value="<?= htmlspecialchars($identifier_value ?? '') ?>" required>
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

                <div class="text-center mt-4">
                    <p class="text-muted mb-0">
                        Pas encore inscrit ? <a href="/netchat/public/register" class="link-netchat fw-semibold">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
