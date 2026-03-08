<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Infos de l'utilisateur courant (pour la sidebar et le mini-profil)
    $stmt = $pdo->prepare("SELECT username, followers_count, profile_picture, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $user_profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
    if (!file_exists($user_profile_picture)) {
        $user_profile_picture = 'assets/user_icon.png';
    }
} catch(PDOException $e) {
    $error = $e->getMessage();
    $user = ['username' => ($_SESSION['username'] ?? 'User'), 'followers_count' => 0];
    $user_profile_picture = 'assets/user_icon.png';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - NetChat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
</head>
<body>
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
                            <a href="dashboard.php" class="nav-link active">
                                <i class="fas fa-home"></i><span>Accueil</span>
                            </a>
                            <a href="#" class="nav-link">
                                <i class="fas fa-bell"></i><span>Notifications</span>
                            </a>
                            <a href="#" class="nav-link">
                                <i class="fas fa-comments"></i><span>Chat</span>
                            </a>
                            <a href="profil.php?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link">
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
                                <div class="fw-semibold"><?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                                <div class="text-muted small">@<?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                            </div>
                        </a>
                    </div>
                </div>
            </aside>

        <!-- Colonne centrale : fil d'actualité -->
        <main class="nc-main-column">
                <div class="nc-main-header">
                    <h5>Accueil</h5>
                </div>

                <!-- Nouveau Post FORM -->
                <div class="netchat-card p-4 mb-3 rounded-0 rounded-top-3">
                    <form id="postForm" enctype="multipart/form-data">
                        <textarea class="form-control form-control-lg mb-3" name="content" id="postContent" placeholder="Quoi de neuf ?" rows="3" required></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <input type="file" class="form-control w-50" name="image" id="postImage" accept="image/*">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-paper-plane me-1"></i>Poster
                            </button>
                        </div>
                        <div id="postMessage" class="mt-2"></div>
                    </form>
                </div>

                <!-- Posts AJAX -->
                <div id="postsContainer" class="px-2 py-3">
                    <p class="text-muted text-center mb-0">Chargement des posts...</p>
                </div>
            </main>

        <!-- Colonne droite : recherche, hashtags, suggestions -->
        <aside class="nc-right-sidebar d-none d-xl-block">
            <div class="nc-right-sidebar-inner">
                <!-- Barre de recherche -->
                <div class="nc-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher" />
                </div>

                <!-- Tendances / Hashtags -->
                <div class="nc-trend-card">
                    <h6>Ce qui se passe</h6>
                    <div class="nc-trend-item">
                        <div class="nc-trend-category">Tendances en France</div>
                        <div class="nc-trend-tag">#NetChat</div>
                    </div>
                    <div class="nc-trend-item">
                        <div class="nc-trend-category">Tendances en France</div>
                        <div class="nc-trend-tag">#SocialMedia</div>
                    </div>
                    <div class="nc-trend-item">
                        <div class="nc-trend-category">Tendances en France</div>
                        <div class="nc-trend-tag">#Tech</div>
                    </div>
                    <div class="nc-trend-item">
                        <div class="nc-trend-category">Tendances en France</div>
                        <div class="nc-trend-tag">#Community</div>
                    </div>
                    <div class="nc-trend-item">
                        <div class="nc-trend-category">Tendances en France</div>
                        <div class="nc-trend-tag">#Connect</div>
                    </div>
                </div>

                <!-- Comptes suggérés -->
                <div class="nc-suggest-card">
                    <h6>Suggestions</h6>
                    <div class="nc-suggest-user">
                        <img src="assets/user_icon.png" alt="User">
                        <div class="nc-suggest-user-info">
                            <div class="nc-suggest-user-name">Utilisateur 1</div>
                            <div class="nc-suggest-user-handle">@user1</div>
                        </div>
                        <button class="nc-suggest-btn">Suivre</button>
                    </div>
                    <div class="nc-suggest-user">
                        <img src="assets/user_icon.png" alt="User">
                        <div class="nc-suggest-user-info">
                            <div class="nc-suggest-user-name">Utilisateur 2</div>
                            <div class="nc-suggest-user-handle">@user2</div>
                        </div>
                        <button class="nc-suggest-btn">Suivre</button>
                    </div>
                    <div class="nc-suggest-user">
                        <img src="assets/user_icon.png" alt="User">
                        <div class="nc-suggest-user-info">
                            <div class="nc-suggest-user-name">Utilisateur 3</div>
                            <div class="nc-suggest-user-handle">@user3</div>
                        </div>
                        <button class="nc-suggest-btn">Suivre</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let offset = 0;
        
        function loadPosts() {
        fetch(`api_posts.php?offset=${offset}&limit=10`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error('API Error:', data.error);
                document.getElementById('postsContainer').innerHTML = `<p class="text-danger">${data.error}</p>`;
                return;
            }
            
            if (!data.posts || data.posts.length === 0) {
                document.getElementById('postsContainer').innerHTML = '<p class="text-muted text-center">Aucun post</p>';
                return;
            }
            
            let html = '';
            data.posts.forEach(post => {
                html += `
                <div class="col-12 mb-4">
                    <div class="netchat-card p-4">
                        <div class="d-flex align-items-start mb-3">
                            <a href="profil.php?id=${post.user_id}">
                                <img src="${post.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="Profil">
                            </a>
                            <div>
                                <a href="profil.php?id=${post.user_id}" class="text-decoration-none">
                                    <h6 class="mb-1 fw-bold link-netchat">${post.username}</h6>
                                </a>
                                <small class="text-muted">${new Date(post.created_at).toLocaleDateString('fr-FR')}</small>
                            </div>
                        </div>
                        <p class="fs-5 mb-3">${post.content
.replace(/#\w+/g, m => `<a href="hashtag.php?tag=${m.slice(1)}" class="text-primary">${m}</a>`)
.replace(/@\w+/g, m => `<a href="profil.php?id=${post.user_id}" class="text-primary">${m}</a>`)
}</p>
                        ${post.image_url ? `<img src="${post.image_url}" class="img-fluid rounded-3 mb-3" style="max-height:400px;">` : ''}
                        <div class="d-flex gap-3">
                            <button class="btn btn-outline-primary btn-sm like-btn" data-post="${post.id}"><i class="fas fa-thumbs-up"></i> ${post.likes || 0}</button>
                            <button class="btn btn-outline-primary btn-sm"><i class="fas fa-comment"></i> Commenter</button>
                        </div>
                    </div>
                </div>`;
            });
            document.getElementById('postsContainer').innerHTML = html;
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            document.getElementById('postsContainer').innerHTML = `<p class="text-danger">Erreur chargement posts</p>`;
        });
}

        
        document.getElementById('postForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const res = await fetch('post.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('postContent').value = '';
                document.getElementById('postMessage').innerHTML = '<div class="alert alert-success">Post créé !</div>';
                offset = 0;
                loadPosts();
                setTimeout(() => document.getElementById('postMessage').innerHTML = '', 3000);
            }
        });
        
        loadPosts();
    </script>
</body>
</html>
