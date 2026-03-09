<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $dsn = 'mysql:host=netchat-netchat.i.aivencloud.com;port=13911;dbname=netchat;charset=utf8mb4;ssl_mode=REQUIRED';
    $pdo = new PDO($dsn, 'avnadmin', 'AVNS_-mk_dITiGa0x6UxHo_G', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT followers_count FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NetChat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-gradient">
    <?php include 'assets/navbar.html'; ?>
    
    <div class="container mt-4">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="netchat-card p-4 text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3><?= $user['followers_count'] ?? 0 ?></h3>
                    <p class="text-muted">Abonnés</p>
                </div>
            </div>
        </div>
        
        <!-- Nouveau Post FORM -->
        <div class="netchat-card p-4 mb-4">
            <form id="postForm" enctype="multipart/form-data">
                <textarea class="form-control form-control-lg mb-3" name="content" id="postContent" placeholder="Quoi de neuf ? Partage avec tes amis..." rows="3" required></textarea>
                <div class="d-flex justify-content-between">
                    <input type="file" class="form-control w-50" name="image" id="postImage" accept="image/*">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Poster</button>
                </div>
                <div id="postMessage"></div>
            </form>
        </div>
        
        <!-- Posts AJAX -->
        <div class="row" id="postsContainer">
            <p class="text-muted text-center">Chargement des posts...</p>
        </div>
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
                            <a href="profile.php?id=${post.user_id}">
                                <img src="${post.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="Profil">
                            </a>
                            <div>
                                <a href="profile.php?id=${post.user_id}" class="text-decoration-none">
                                    <h6 class="mb-1 fw-bold link-netchat">${post.username}</h6>
                                </a>
                                <small class="text-muted">${new Date(post.created_at).toLocaleDateString('fr-FR')}</small>
                            </div>
                        </div>
                        <p class="fs-5 mb-3">${post.content// Ligne ~150 dans formatContent ou dans replace :
.replace(/#\w+/g, m => `<a href="hashtag.php?tag=${m.slice(1)}" class="text-primary">${m}</a>`)
.replace(/@\w+/g, m => `<a href="profile.php?id=${post.user_id}" class="text-primary">${m}</a>`)
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
