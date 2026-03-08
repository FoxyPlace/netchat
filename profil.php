<?php
session_start();

// Connexion DB
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

// Récupérer l'ID de l'utilisateur à afficher (soit depuis GET, soit l'utilisateur connecté)
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

if (!$profile_user_id) {
    header('Location: login.php');
    exit;
}

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT id, username, profile_picture, bio, created_at, followers_count FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les posts de l'utilisateur
$stmt = $pdo->prepare("
    SELECT p.id, p.content, p.image_url, p.created_at,
           COUNT(DISTINCT r.id) as likes
    FROM posts p
    LEFT JOIN reactions r ON p.id = r.post_id AND r.reaction_type = 'like'
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$profile_user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Photo de profil par défaut
$profile_picture = $profile_user['profile_picture'] ?? 'assets/user_icon.png';
if (!file_exists($profile_picture)) {
    $profile_picture = 'assets/user_icon.png';
}

// Infos de l'utilisateur connecté (pour la sidebar)
if (isset($_SESSION['user_id'])) {
    $stmt_nav = $pdo->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
    $stmt_nav->execute([$_SESSION['user_id']]);
    $nav_user = $stmt_nav->fetch(PDO::FETCH_ASSOC);
    $user_profile_picture = $nav_user['profile_picture'] ?? 'assets/user_icon.png';
    if (!file_exists($user_profile_picture)) {
        $user_profile_picture = 'assets/user_icon.png';
    }
    $current_user = $nav_user;
} else {
    $user_profile_picture = 'assets/user_icon.png';
    $current_user = ['username' => 'User'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars($profile_user['username']) ?> - NetChat</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icon.png">
    <link rel="apple-touch-icon" href="assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="nc-layout">
        <!-- Sidebar gauche type X -->
        <aside class="nc-sidebar d-none d-lg-block">
            <div class="nc-sidebar-inner">
                <div>
                    <a href="dashboard.php" class="nc-sidebar-logo">
                        <img src="assets/logo.png" alt="NetChat">
                        <span class="netchat-title fs-3 fw-bold">NetChat</span>
                    </a>
                    
                    <nav class="nc-sidebar-nav nav flex-column">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i><span>Accueil</span>
                        </a>
                        <a href="#" class="nav-link">
                            <i class="fas fa-bell"></i><span>Notifications</span>
                        </a>
                        <a href="#" class="nav-link">
                            <i class="fas fa-comments"></i><span>Chat</span>
                        </a>
                        <a href="profil.php?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link active">
                            <i class="fas fa-user"></i><span>Profil</span>
                        </a>
                        <a href="settingsprofile.php" class="nav-link">
                            <i class="fas fa-cog"></i><span>Paramètres</span>
                        </a>
                    </nav>
                </div>
                
                <div>
                    <a href="profil.php?id=<?= (int)$_SESSION['user_id'] ?>" class="nc-sidebar-profile">
                        <img src="<?= htmlspecialchars($user_profile_picture) ?>" alt="Profil">
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
    <?php endif; ?>
    
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- Carte de profil -->
                <div class="netchat-card p-5 text-center mb-4">
                    <!-- Photo de profil -->
                    <img src="<?= htmlspecialchars($profile_picture) ?>" 
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
                            <strong><?= (int)($profile_user['followers_count'] ?? 0) ?></strong>
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
                        <a href="settingsprofile.php" class="btn btn-primary btn-lg mt-3">
                            <i class="fas fa-edit me-2"></i>Modifier le profil
                        </a>
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
                                            <img src="<?= htmlspecialchars($profile_picture) ?>" 
                                                 class="rounded-circle me-3" 
                                                 width="50" 
                                                 height="50" 
                                                 style="object-fit:cover;" 
                                                 alt="Profil">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold link-netchat"><?= htmlspecialchars($profile_user['username']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('d/m/Y à H:i', strtotime($post['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <p class="fs-5 mb-3">
                                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                                        </p>
                                        
                                        <?php if (!empty($post['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($post['image_url']) ?>" 
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
    <?php if (isset($_SESSION['user_id'])): ?>
            </div>
        </main>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
