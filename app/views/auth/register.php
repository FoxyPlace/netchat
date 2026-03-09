<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'NetChat - Inscription';
$extra_css = ['style.css'];
include __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-10">
            <div class="netchat-card p-5">
                <div class="text-center mb-5">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <img src="<?= $basePath ?? '/netchat/public' ?>/assets/logo.png" alt="NetChat" width="60" height="60" class="me-3" style="border-radius: 16px;">
                        <h1 class="netchat-title display-4 fw-bold mb-0">NetChat</h1>
                    </div>
                    <p class="text-muted lead">Créer ton compte et rejoins la conversation</p>
                </div>

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

                <form method="POST" action="/netchat/register" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="register">
                    
                    <div class="input-group-wrapper mb-4">
                        <div class="floating-icon"><i class="fas fa-user"></i></div>
                        <input type="text" name="username" id="pseudo" class="form-control form-control-lg py-3" 
                               placeholder="Pseudo unique" required maxlength="20" minlength="3"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <div class="invalid-feedback">3-20 caractères (lettres/chiffres/_)</div>
                    </div>

                    <div class="input-group-wrapper mb-4">
                        <div class="floating-icon"><i class="fas fa-envelope"></i></div>
                        <input type="email" name="email" id="email" class="form-control form-control-lg py-3" 
                               placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <div class="invalid-feedback">Email valide requis</div>
                    </div>

                    <div class="input-group-wrapper mb-4">
                        <div class="floating-icon"><i class="fas fa-phone"></i></div>
                        <input type="tel" name="phone" id="phone" class="form-control form-control-lg py-3" 
                               placeholder="06 12 34 56 78 (optionnel)">
                        <div class="invalid-feedback">Format invalide</div>
                    </div>

                    <div class="input-group-wrapper mb-4">
                        <div class="floating-icon"><i class="fas fa-calendar"></i></div>
                        <input type="date" name="birthdate" id="birthdate" class="form-control form-control-lg py-3" 
                               max="<?= date('Y-m-d', strtotime('-13 years')) ?>" required>
                        <div class="invalid-feedback">Minimum 13 ans</div>
                    </div>

                    <div class="input-group-wrapper mb-4">
                        <div class="floating-icon"><i class="fas fa-lock"></i></div>
                        <input type="password" name="password" id="password" class="form-control form-control-lg py-3" 
                               placeholder="Mot de passe" required minlength="8">
                        <button type="button" class="password-toggle" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">8 caractères minimum</div>
                    </div>

                    <div class="input-group-wrapper mb-5">
                        <div class="floating-icon"><i class="fas fa-lock-open"></i></div>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg py-3" 
                               placeholder="Confirmer" required>
                        <button type="button" class="password-toggle" data-target="confirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">Ne correspondent pas</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-4">
                        <i class="fas fa-rocket me-2"></i>S'inscrire
                    </button>

                    <div class="text-center my-4">
                        <span class="text-muted small px-3">ou</span>
                        <hr class="my-2">
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn" disabled>
                                <i class="fab fa-google me-2"></i>Google
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn" disabled>
                                <i class="fab fa-github me-2"></i>GitHub
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0">
                        Déjà un compte ? <a href="/netchat/public/login" class="link-netchat fw-semibold">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
