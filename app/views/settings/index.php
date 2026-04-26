<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Paramètres - NetChat';
$extra_css = ['dashboard.css', 'settingsprofile.css'];
include __DIR__ . '/../layouts/header.php';
?>

<div class="nc-layout">
    <!-- Sidebar gauche type X -->
    <aside class="nc-sidebar d-none d-md-block">
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
                    <a href="<?= $basePath ?? '/netchat/public' ?>/notifications" class="nav-link">
                        <i class="fas fa-bell"></i><span>Notifications</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/chat" class="nav-link">
                        <i class="fas fa-comments"></i><span>Chat</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link">
                        <i class="fas fa-user"></i><span>Profil</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/settings" class="nav-link active">
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

    <!-- Colonne centrale : paramètres -->
    <main class="nc-main-column">
        <div class="nc-main-header">
            <h5>Paramètres</h5>
        </div>
        
        <div class="px-4 py-3">
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="netchat-card p-3">
                        <h5 class="mb-4 fw-bold">Paramètres</h5>
                        <nav class="nav flex-column settings-nav">
                            <a class="nav-link active" data-section="profile" href="#profile">
                                <i class="fas fa-user me-2"></i>Profil
                            </a>
                            <a class="nav-link" data-section="account" href="#account">
                                <i class="fas fa-cog me-2"></i>Compte
                            </a>
                        </nav>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="netchat-card p-4">
                        <!-- Messages d'erreur/succès -->
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
                        
                        <!-- Section Profil -->
                        <div id="profile-section" class="settings-content">
                            <h4 class="mb-4">
                                <i class="fas fa-user me-2"></i>Profil
                            </h4>
                            
                            <form method="POST" action="/netchat/public/settings" enctype="multipart/form-data" class="needs-validation" novalidate id="profileForm">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <!-- Photo de profil -->
                                <div class="text-center mb-4">
                                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($profile_picture) ?>" 
                                         alt="Photo de profil" 
                                         class="rounded-circle mb-3" 
                                         width="120" 
                                         height="120" 
                                         style="object-fit: cover; border: 4px solid var(--primary-blue); box-shadow: 0 8px 24px rgba(0, 212, 255, 0.3);"
                                         id="profile-preview">
                                    <div>
                                        <label for="profile_picture" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-camera me-1"></i>Changer la photo
                                        </label>
                                        <input type="file" 
                                               name="profile_picture" 
                                               id="profile_picture" 
                                               accept="image/*" 
                                               class="d-none">
                                    </div>
                                </div>
                                
                                <!-- Nom d'utilisateur -->
                                <div class="input-group-wrapper mb-4">
                                    <div class="floating-icon"><i class="fas fa-user"></i></div>
                                    <input type="text" 
                                           name="username" 
                                           id="username" 
                                           class="form-control form-control-lg py-3" 
                                           placeholder="Nom d'utilisateur" 
                                           required 
                                           maxlength="20" 
                                           minlength="3"
                                           value="<?= htmlspecialchars($user['username']) ?>">
                                    <div class="invalid-feedback">3-20 caractères (lettres/chiffres/_)</div>
                                </div>
                                
                                <!-- Bio -->
                                <div class="mb-4">
                                    <label for="bio" class="form-label fw-semibold mb-2">
                                        <i class="fas fa-pencil-alt me-1"></i>Bio
                                    </label>
                                    <textarea name="bio" 
                                              id="bio" 
                                              class="form-control" 
                                              rows="4" 
                                              maxlength="500"
                                              placeholder="Parlez-nous de vous..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                    <small class="text-muted">
                                        <span id="bio-count"><?= strlen($user['bio'] ?? '') ?></span>/500 caractères
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </form>
                            
                            <!-- Section déconnexion -->
                            <hr class="my-4">      
                            <div class="d-flex justify-content-end">
                                <!-- Appel MVC : route /logout gérée par LogoutController -->
                                <a href="<?= $basePath ?>/logout" class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Se déconnecter
                                </a>
                            </div>
                        </div>
                        
                        <!-- Section Compte -->
                        <div id="account-section" class="settings-content" style="display: none;">
                            <h4 class="mb-4">
                                <i class="fas fa-cog me-2"></i>Compte
                            </h4>
                            
                            <form method="POST" action="/netchat/public/settings" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_account">
                                
                                <!-- Email -->
                                <div class="input-group-wrapper mb-4">
                                    <div class="floating-icon"><i class="fas fa-envelope"></i></div>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-control form-control-lg py-3" 
                                           placeholder="Email" 
                                           required
                                           value="<?= htmlspecialchars($user['email']) ?>">
                                    <div class="invalid-feedback">Email valide requis</div>
                                </div>

                                <!-- Changer le mot de passe -->
                                <div class="input-group-wrapper mb-4">
                                    <div class="floating-icon"><i class="fas fa-key"></i></div>
                                    <button type="button"
                                            class="form-control form-control-lg py-3 btn btn-outline-primary d-flex align-items-center justify-content-between"
                                            style="border-radius: 16px;"
                                            onclick="window.location.href='<?= $basePath ?? '/netchat/public' ?>/password/edit'">
                                        <span class="fw-semibold">Changer le mot de passe</span>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </button>
                                </div>
                                
                                <!-- Téléphone -->
                                <div class="input-group-wrapper mb-4">
                                    <div class="floating-icon"><i class="fas fa-phone"></i></div>
                                    <input type="tel" 
                                           name="phone" 
                                           id="phone" 
                                           class="form-control form-control-lg py-3" 
                                           placeholder="Téléphone (optionnel)"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    <div class="invalid-feedback">Format invalide</div>
                                </div>
                                
                                <!-- Date de naissance -->
                                <div class="input-group-wrapper mb-4">
                                    <div class="floating-icon"><i class="fas fa-calendar"></i></div>
                                    <input type="date" 
                                           name="birthdate" 
                                           id="birthdate" 
                                           class="form-control form-control-lg py-3" 
                                           max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                                           value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
                                    <div class="invalid-feedback">Minimum 13 ans</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg">
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

<script>
    // Navigation entre les sections
    document.querySelectorAll('.settings-nav .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            
            // Mettre à jour les liens actifs
            document.querySelectorAll('.settings-nav .nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Afficher/masquer les sections
            document.getElementById('profile-section').style.display = section === 'profile' ? 'block' : 'none';
            document.getElementById('account-section').style.display = section === 'account' ? 'block' : 'none';
        });
    });
    
    // Prévisualisation de l'image sélectionnée
    document.addEventListener('DOMContentLoaded', function() {
        const profilePictureInput = document.getElementById('profile_picture');
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('profile-preview').src = event.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        // Compteur de bio
        const bioTextarea = document.getElementById('bio');
        const bioCount = document.getElementById('bio-count');
        if (bioTextarea && bioCount) {
            bioTextarea.addEventListener('input', function() {
                bioCount.textContent = this.value.length;
            });
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
