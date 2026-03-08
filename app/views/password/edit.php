<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Modifier le mot de passe - NetChat';
$extra_css = ['dashboard.css'];
include __DIR__ . '/../layouts/header.php';
?>

<div class="nc-layout">
    <!-- Sidebar gauche type X -->
    <aside class="nc-sidebar d-none d-lg-block">
        <div class="nc-sidebar-inner">
            <div>
                <a href="<?= $basePath ?? '/netchat/public' ?>/" class="nc-sidebar-logo">
                    <img src="<?= $basePath ?? '/netchat/public' ?>/assets/logo.png" alt="NetChat">
                    <span class="netchat-title fs-3 fw-bold">NetChat</span>
                </a>
                
                <nav class="nc-sidebar-nav nav flex-column">
                    <a href="<?= $basePath ?? '/netchat/public' ?>/" class="nav-link">
                        <i class="fas fa-home"></i><span>Accueil</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-bell"></i><span>Notifications</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-comments"></i><span>Chat</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link">
                        <i class="fas fa-user"></i><span>Profil</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/settings" class="nav-link">
                        <i class="fas fa-cog"></i><span>Paramètres</span>
                    </a>
                </nav>
            </div>
            
            <div>
                <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nc-sidebar-profile">
                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($profile_picture) ?>" alt="Profil">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                        <div class="text-muted small">@<?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                    </div>
                </a>
            </div>
        </div>
    </aside>

    <!-- Colonne centrale : changement de mot de passe -->
    <main class="nc-main-column">
        <div class="nc-main-header">
            <h5>Changer le mot de passe</h5>
        </div>
        
        <div class="px-4 py-3">
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

                            <form method="POST" action="<?= $basePath ?? '/netchat/public' ?>/password/edit" class="needs-validation" novalidate>
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
        </div>
    </main>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
