<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Profil - ' . htmlspecialchars($profile_user['username']) . ' - NetChat';
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
                    <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link active">
                        <i class="fas fa-user"></i><span>Profil</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/settings" class="nav-link">
                        <i class="fas fa-cog"></i><span>Paramètres</span>
                    </a>
                </nav>
            </div>
            
            <div>
                <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nc-sidebar-profile">
                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($user_profile_picture) ?>" alt="Profil">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($current_user['username'] ?? $_SESSION['username']) ?></div>
                        <div class="text-muted small">@<?= htmlspecialchars($current_user['username'] ?? $_SESSION['username']) ?></div>
                    </div>
                </a>
            </div>
        </div>
    </aside>

    <!-- Colonne centrale : profil -->
    <main class="nc-main-column">
        <div class="nc-main-header">
            <h5>Profil</h5>
        </div>
        
        <div class="px-4 py-3">
            <div class="container mt-5 mb-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <!-- Carte de profil -->
                        <div class="netchat-card p-5 text-center mb-4">
                            <!-- Photo de profil -->
                            <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($profile_picture) ?>" 
                                 alt="Photo de profil" 
                                 class="rounded-circle mb-4" 
                                 width="150" 
                                 height="150" 
                                 style="object-fit: cover; border: 4px solid var(--primary-blue); box-shadow: 0 8px 24px rgba(0, 212, 255, 0.3);">
                            
                            <!-- Nom d'utilisateur -->
                            <h2 class="netchat-title mb-2"><?= htmlspecialchars($profile_user['username']) ?></h2>

                            <!-- Stats discrètes type X : abonnés + date d'arrivée -->
                            <div class="d-flex justify-content-center align-items-center gap-3 mb-3 text-muted small">
                                <span>
                                    <strong id="followersCount"><?= (int)($profile_user['followers_count'] ?? 0) ?></strong>
                                    abonné<?= (($profile_user['followers_count'] ?? 0) > 1 ? 's' : '') ?>
                                </span>
                                <?php if (!empty($profile_user['created_at'])): ?>
                                    <span>•</span>
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        A rejoint NetChat le <?= date('d/m/Y', strtotime($profile_user['created_at'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bio -->
                            <?php if (!empty($profile_user['bio'])): ?>
                                <p class="text-muted fs-5 mb-3" style="max-width: 600px; margin: 0 auto;">
                                    <?= nl2br(htmlspecialchars($profile_user['bio'])) ?>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-3">Aucune bio pour le moment</p>
                            <?php endif; ?>
                            
                            <!-- Bouton Modifier le profil si c'est le profil de l'utilisateur connecté -->
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id): ?>
                                <a href="<?= $basePath ?? '/netchat/public' ?>/settings" class="btn btn-primary btn-lg mt-3">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </a>
                            <?php else: ?>
                                <!-- Bouton s'abonner / se désabonner et demande d'amis -->
                                    <div class="d-flex justify-content-center gap-2 mt-3">
                                        <button id="followBtn" data-user="<?= $profile_user_id ?>" class="btn <?= (!empty($isFollowing) ? 'btn-outline-secondary' : 'btn-primary') ?> btn-lg">
                                            <i class="fas fa-user-plus me-2"></i>
                                            <span id="followBtnText"><?= (!empty($isFollowing) ? 'Se désabonner' : "S'abonner") ?></span>
                                        </button>

                                        <?php if (!empty($isFriend)): ?>
                                            <button id="friendBtn" class="btn btn-secondary btn-lg">Amis</button>
                                        <?php else: ?>
                                            <?php if (!empty($incomingRequest)): ?>
                                                <button id="friendAcceptBtn" data-user="<?= $profile_user_id ?>" class="btn btn-success btn-lg">Accepter</button>
                                            <?php else: ?>
                                                <button id="friendBtn" data-user="<?= $profile_user_id ?>" class="btn <?= (!empty($outgoingRequest) ? 'btn-outline-secondary' : 'btn-outline-primary') ?> btn-lg">
                                                    <span id="friendBtnText"><?= (!empty($outgoingRequest) ? 'Demande envoyée' : 'Demande d\'amis') ?></span>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Section Posts -->
                        <div class="netchat-card p-4">
                            <h4 class="mb-4">
                                <i class="fas fa-paper-plane me-2"></i>Publications
                            </h4>
                            
                            <?php if (empty($posts)): ?>
                                <!-- Message si aucun post -->
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3" style="opacity: 0.5;"></i>
                                    <p class="text-muted fs-5 mb-0">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id): ?>
                                            Vous n'avez encore rien posté.
                                        <?php else: ?>
                                            Cet utilisateur n'a encore rien posté.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Liste des posts -->
                                <div class="row">
                                    <?php foreach ($posts as $post): ?>
                                        <div class="col-12 mb-4">
                                            <div class="netchat-card p-4">
                                                <div class="d-flex align-items-start mb-3">
                                                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($profile_picture) ?>" 
                                                         class="rounded-circle me-3" 
                                                         width="50" 
                                                         height="50" 
                                                         style="object-fit:cover;" 
                                                         alt="Profil">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 fw-bold link-netchat"><?= htmlspecialchars($profile_user['username']) ?></h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?= relativeTime($post['created_at']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <p class="fs-5 mb-3">
                                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                                </p>
                                                
                                                <?php if (!empty($post['image_url'])): ?>
                                                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($post['image_url']) ?>" 
                                                         class="img-fluid rounded-3 mb-3" 
                                                         style="max-height: 400px; width: 100%; object-fit: cover;" 
                                                         alt="Image du post">
                                                <?php endif; ?>
                                                
                                                <div class="d-flex gap-3 align-items-center">
                                                    <button class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-thumbs-up me-1"></i> <?= (int)$post['likes'] ?>
                                                    </button>
                                                    <button class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-comment me-1"></i> Commenter
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const followBtn = document.getElementById('followBtn');
    if (!followBtn) return;
    followBtn.addEventListener('click', async function() {
        const target = this.getAttribute('data-user');
        if (!target) return;
        try {
            const form = new FormData();
            form.append('target_id', target);
            const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/follow_toggle.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.error) {
                alert(data.error);
                return;
            }
            // Update button text and style
            const textEl = document.getElementById('followBtnText');
            if (data.following) {
                followBtn.classList.remove('btn-primary');
                followBtn.classList.add('btn-outline-secondary');
                if (textEl) textEl.textContent = 'Se désabonner';
            } else {
                followBtn.classList.remove('btn-outline-secondary');
                followBtn.classList.add('btn-primary');
                if (textEl) textEl.textContent = "S'abonner";
            }
            // Update followers count
            const cntEl = document.getElementById('followersCount');
            if (cntEl && typeof data.followers_count !== 'undefined') {
                cntEl.textContent = data.followers_count;
            }
        } catch (err) {
            console.error(err);
        }
    });
    // Friend request toggle
    const friendBtn = document.getElementById('friendBtn');
    if (friendBtn) {
        friendBtn.addEventListener('click', async function() {
            const target = this.getAttribute('data-user');
            if (!target) return;
            try {
                const form = new FormData();
                form.append('target_id', target);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/friend_toggle.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                const textEl = document.getElementById('friendBtnText');
                if (data.requested) {
                    if (textEl) textEl.textContent = 'Demande envoyée';
                    friendBtn.classList.remove('btn-outline-primary');
                    friendBtn.classList.add('btn-outline-secondary');
                } else {
                    if (textEl) textEl.textContent = 'Demande d\'amis';
                    friendBtn.classList.remove('btn-outline-secondary');
                    friendBtn.classList.add('btn-outline-primary');
                }
            } catch (err) { console.error(err); }
        });
    }

    // Accept friend request (incoming)
    const friendAcceptBtn = document.getElementById('friendAcceptBtn');
    if (friendAcceptBtn) {
        friendAcceptBtn.addEventListener('click', async function() {
            const requester = this.getAttribute('data-user');
            if (!requester) return;
            try {
                const form = new FormData();
                form.append('requester_id', requester);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/friend_accept.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                if (data.accepted) {
                    // replace buttons
                    this.textContent = 'Amis';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-secondary');
                    const followBtn = document.getElementById('followBtn');
                    if (followBtn) {
                        // no automatic action on follow
                    }
                }
            } catch (err) { console.error(err); }
        });
    }
});
</script>
