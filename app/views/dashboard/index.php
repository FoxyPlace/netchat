<?php
// S'assurer que $basePath est défini
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Accueil - NetChat';
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
                    <a href="<?= $basePath ?? '/netchat/public' ?>/" class="nav-link active">
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
                    <img src="<?= $basePath ?? '/netchat/public' ?>/<?= htmlspecialchars($user_profile_picture) ?>" alt="Profil">
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
                    <img src="<?= $basePath ?? '/netchat/public' ?>/assets/user_icon.png" alt="User">
                    <div class="nc-suggest-user-info">
                        <div class="nc-suggest-user-name">Utilisateur 1</div>
                        <div class="nc-suggest-user-handle">@user1</div>
                    </div>
                    <button class="nc-suggest-btn" data-user="2">Suivre</button>
                </div>
                <div class="nc-suggest-user">
                    <img src="<?= $basePath ?? '/netchat/public' ?>/assets/user_icon.png" alt="User">
                    <div class="nc-suggest-user-info">
                        <div class="nc-suggest-user-name">Utilisateur 2</div>
                        <div class="nc-suggest-user-handle">@user2</div>
                    </div>
                    <button class="nc-suggest-btn" data-user="3">Suivre</button>
                </div>
                <div class="nc-suggest-user">
                    <img src="<?= $basePath ?? '/netchat/public' ?>/assets/user_icon.png" alt="User">
                    <div class="nc-suggest-user-info">
                        <div class="nc-suggest-user-name">Utilisateur 3</div>
                        <div class="nc-suggest-user-handle">@user3</div>
                    </div>
                    <button class="nc-suggest-btn" data-user="4">Suivre</button>
                </div>
            </div>
        </div>
    </aside>
</div>

<script>
    let offset = 0;
    const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    
    function relativeTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) return "à l'instant";
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 604800) return Math.floor(diff / 86400) + 'j';
        if (diff < 2592000) return Math.floor(diff / 604800) + ' sem';
        if (diff < 31536000) return Math.floor(diff / 2592000) + ' mois';
        const years = Math.floor(diff / 31536000);
        return years + ' an' + (years > 1 ? 's' : '');
    }
    
    function loadPosts() {
        fetch(`<?= $basePath ?? '/netchat/public' ?>/api_posts.php?offset=${offset}&limit=10`)
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
                // Construire le contenu en linkifiant uniquement les mentions validées côté serveur
                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }
                function escapeRegExp(str) {
                    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }

                let content = escapeHtml(post.content || '');
                // Linkifier les mentions fournies par l'API
                if (post.mentions && post.mentions.length) {
                    post.mentions.forEach(m => {
                        const username = m.username;
                        const uid = m.mentioned_user_id;
                        const re = new RegExp('@' + escapeRegExp(username) + '\\b', 'g');
                        content = content.replace(re, `<a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${uid}" class="text-primary">@${username}</a>`);
                    });
                }
                // Hashtags
                content = content.replace(/#\w+/g, m => `<a href="<?= $basePath ?? '/netchat/public' ?>/hashtag?tag=${m.slice(1)}" class="text-primary">${m}</a>`);

                html += `
                <div class="col-12 mb-4">
                    <div class="netchat-card p-4">
                        <div class="d-flex align-items-start mb-3">
                            <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${post.user_id}">
                                <img src="<?= $basePath ?? '/netchat/public' ?>/${post.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="Profil">
                            </a>
                            <div>
                                <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${post.user_id}" class="text-decoration-none">
                                    <h6 class="mb-1 fw-bold link-netchat">${post.username}</h6>
                                </a>
                                <small class="text-muted"><i class="fas fa-clock me-1"></i>${relativeTime(post.created_at)}</small>
                            </div>
                        </div>
                        <p class="fs-5 mb-3">${content}</p>
                        ${post.image_url ? `<img src="<?= $basePath ?? '/netchat/public' ?>/${post.image_url}" class="img-fluid rounded-3 mb-3" style="max-height:400px;">` : ''}
                        <div class="d-flex gap-3">
                            <button class="btn btn-outline-primary btn-sm like-btn" data-post="${post.id}"><i class="fas fa-thumbs-up"></i> ${post.likes || 0}</button>
                            <button class="btn btn-outline-primary btn-sm"><i class="fas fa-comment"></i> Commenter</button>
                            ${post.user_id === currentUserId ? `<button class="btn btn-outline-danger btn-sm delete-btn" data-post="${post.id}"><i class="fas fa-trash"></i> Supprimer</button>` : ''}
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
        
            const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/post.php', { method: 'POST', body: formData });
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

    // Follow buttons in suggestion card (if data-user attribute present)
    document.addEventListener('click', async function(e) {
        // Follow buttons in suggestion card
        const suggestBtn = e.target.closest('.nc-suggest-btn');
        if (suggestBtn) {
            const targetId = suggestBtn.getAttribute('data-user');
            if (!targetId) return;
            try {
                const form = new FormData();
                form.append('target_id', targetId);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/follow_toggle.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                // Toggle button text
                suggestBtn.textContent = data.following ? 'Se désabonner' : 'Suivre';
            } catch (err) {
                console.error(err);
            }
            return;
        }

        // Delete buttons
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const postId = deleteBtn.getAttribute('data-post');
            if (!postId) return;
            if (!confirm('Voulez-vous vraiment supprimer ce post ?')) return;
            try {
                const form = new FormData();
                form.append('post_id', postId);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/post_delete.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                // Supprimer l'élément du DOM
                const root = deleteBtn.closest('.col-12');
                if (root) root.remove();
            } catch (err) {
                console.error(err);
            }
            return;
        }

        // Like buttons
        const likeBtn = e.target.closest('.like-btn');
        if (likeBtn) {
            const postId = likeBtn.getAttribute('data-post');
            if (!postId) return;
            try {
                const form = new FormData();
                form.append('post_id', postId);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/post_like.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                // Update UI: count and active state
                likeBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.count}`;
                if (data.liked) {
                    likeBtn.classList.remove('btn-outline-primary');
                    likeBtn.classList.add('btn-primary');
                } else {
                    likeBtn.classList.remove('btn-primary');
                    likeBtn.classList.add('btn-outline-primary');
                }
            } catch (err) {
                console.error(err);
            }
            return;
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
