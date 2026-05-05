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
                    <a href="<?= $basePath ?? '/netchat/public' ?>/notifications" class="nav-link">
                        <i class="fas fa-bell"></i><span>Notifications</span>
                    </a>
                    <a href="<?= $basePath ?? '/netchat/public' ?>/chat" class="nav-link">
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
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="nc-upload">
                        <input type="file" name="image" id="postImage" accept="image/*,video/mp4,video/webm,video/quicktime,.mov,.m4v" class="nc-upload-input">
                        <label for="postImage" class="nc-upload-btn" title="Ajouter une photo ou une vidéo">
                            <i class="fas fa-photo-video"></i>
                            <span class="d-none d-sm-inline">Média</span>
                        </label>

                        <div id="postImageMeta" class="nc-upload-meta d-none">
                            <img id="postImagePreview" class="nc-upload-preview" alt="Aperçu">
                            <video id="postVideoPreview" class="nc-upload-preview d-none" muted playsinline></video>
                            <div class="nc-upload-text">
                                <div id="postImageName" class="nc-upload-name"></div>
                                <button type="button" id="postImageRemove" class="nc-upload-remove">Retirer</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-paper-plane me-1"></i>Poster
                    </button>
                </div>
                <div id="postMessage" class="mt-2"></div>
            </form>
        </div>

        <div id="ncImageCropModal" class="nc-crop-modal" role="dialog" aria-modal="true" aria-labelledby="ncCropTitle">
            <div class="nc-crop-modal-backdrop" role="presentation"></div>
            <div class="nc-crop-modal-panel">
                <h5 id="ncCropTitle" class="mb-2">Ajuster la photo</h5>
                <p class="text-muted small mb-3 mb-md-4">Glisse pour recentrer, utilise le curseur pour zoomer. Le cadre garde les proportions de ta photo.</p>
                <div class="nc-crop-viewport" id="ncCropViewport">
                    <img id="ncCropImg" alt="" draggable="false">
                </div>
                <div class="mt-3">
                    <label for="ncCropZoom" class="form-label small fw-semibold mb-1">Zoom</label>
                    <input type="range" id="ncCropZoom" class="form-range" min="1" max="3" step="0.02" value="1">
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-secondary" id="ncCropCancel">Annuler</button>
                    <button type="button" class="btn btn-primary" id="ncCropApply">Valider</button>
                </div>
            </div>
        </div>

        <!-- Posts AJAX -->
        <div id="postsContainer" class="px-2 py-3">
            <p class="text-muted text-center mb-0">Chargement des posts...</p>
        </div>
    </main>

    <!-- Colonne droite : recherche, hashtags, suggestions -->
    <aside class="nc-right-sidebar d-none d-lg-block">
        <div class="nc-right-sidebar-inner">
            <!-- Barre de recherche -->
            <div class="nc-search-wrap">
                <div class="nc-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="rightSearchInput" placeholder="Rechercher" autocomplete="off" />
                    <button type="button" class="nc-search-clear d-none" id="rightSearchClear" title="Effacer" aria-label="Effacer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="rightSearchDropdown" class="nc-search-dropdown d-none" role="listbox" aria-label="Suggestions"></div>
            </div>

            <!-- Tendances / Hashtags -->
            <div class="nc-trend-card">
                <h6>Ce qui se passe</h6>
                <div id="trendsContainer"></div>
            </div>

            <!-- Comptes suggérés -->
            <div class="nc-suggest-card">
                <h6>Suggestions</h6>
                <div id="suggestionsContainer"></div>
            </div>
        </div>
    </aside>
</div>

<!-- Report Modal (global) -->
<div id="ncReportModal" class="nc-modal-backdrop" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;z-index:2000">
    <div class="nc-modal-box" style="background:#fff;padding:18px;border-radius:8px;max-width:600px;width:90%;">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0">Signaler un post</h5>
            <button type="button" id="ncReportClose" class="btn btn-sm btn-outline-secondary">✕</button>
        </div>
        <p class="text-muted small">Expliquez brièvement pourquoi vous signalez ce contenu. Votre signalement sera examiné par l'équipe de modération.</p>
        <div>
            <textarea id="ncReportReason" class="form-control mb-2" rows="4" placeholder="Motif du signalement..." required></textarea>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" id="ncReportCancel" class="btn btn-secondary">Annuler</button>
                <button type="button" id="ncReportSubmit" class="btn btn-warning">Signaler</button>
            </div>
            <div id="ncReportMessage" class="mt-2"></div>
        </div>
    </div>
</div>

<script>
    const basePath = <?= json_encode($basePath ?? '/netchat/public') ?>;
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

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeRegExp(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    const NC_VIDEO_EXT = ['mp4', 'webm', 'mov', 'm4v'];

    function postMediaMarkup(url) {
        if (!url) return '';
        const esc = escapeHtml(url);
        const ext = String(url).split('.').pop().toLowerCase();
        const base = '<?= $basePath ?? '/netchat/public' ?>/';
        if (NC_VIDEO_EXT.includes(ext)) {
            return `<div class="nc-post-media nc-post-media--video"><video src="${base}${esc}" controls playsinline preload="metadata"></video></div>`;
        }
        return `<div class="nc-post-media"><img src="${base}${esc}" alt=""></div>`;
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
                content = content.replace(/#\w+/g, m => {
                    const enc = encodeURIComponent(m.slice(1));
                    return `<a href="<?= $basePath ?? '/netchat/public' ?>/search?q=%23${enc}" class="text-primary">${m}</a>`;
                });

                const isSelfPost = (parseInt(post.user_id, 10) === currentUserId);
                const subscribeBtnHtml = isSelfPost ? '' : `
                    <button type="button" class="btn btn-sm btn-outline-primary subscribe-btn" data-user="${post.user_id}">${post.is_following ? 'Se désabonner' : "S'abonner"}</button>
                `;

                html += `
                <div class="col-12 mb-4">
                    <div class="netchat-card p-4">
                        <div class="d-flex align-items-start mb-3">
                            <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${post.user_id}">
                                <img src="<?= $basePath ?? '/netchat/public' ?>/${post.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="Profil">
                            </a>
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${post.user_id}" class="text-decoration-none">
                                        <h6 class="mb-0 fw-bold link-netchat">${post.username}</h6>
                                    </a>
                                    ${subscribeBtnHtml}
                                </div>
                                <small class="text-muted d-block mt-1"><i class="fas fa-clock me-1"></i>${relativeTime(post.created_at)}</small>
                            </div>
                        </div>
                        <p class="fs-5 mb-3">${content}</p>
                        ${post.image_url ? postMediaMarkup(post.image_url) : ''}
                        <div class="d-flex gap-3">
                            <button class="btn btn-outline-primary btn-sm like-btn" data-post="${post.id}"><i class="fas fa-thumbs-up"></i> ${post.likes || 0}</button>
                            <button class="btn btn-outline-primary btn-sm comment-btn" data-post="${post.id}"><i class="fas fa-comment"></i> Commenter (${post.comments_count || 0})</button>
                            ${post.user_id === currentUserId ? `<button class="btn btn-outline-danger btn-sm delete-btn" data-post="${post.id}"><i class="fas fa-trash"></i> Supprimer</button>` : ''}
                            <!-- Report button -->
                            <button class="btn btn-outline-warning btn-sm report-btn ms-2" data-post="${post.id}" data-post-user="${post.user_id}" title="Signaler ce post">
                                <i class="fas fa-flag"></i>
                            </button>
                        </div>

                        <div class="mt-3 post-comments" data-post="${post.id}">
                            ${post.comments && post.comments.length ? post.comments.map(c => `
                                <div class="d-flex align-items-start mb-2 comment-item" data-comment="${c.id}">
                                    <img src="<?= $basePath ?? '/netchat/public' ?>/${c.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small mb-1">${escapeHtml(c.username)} <span class="text-muted small">• ${relativeTime(c.created_at)}</span></div>
                                        <div class="small">${escapeHtml(c.content)}</div>
                                    </div>
                                    ${c.user_id == currentUserId ? `<button class="btn btn-sm btn-outline-danger ms-2 comment-inline-delete" data-comment="${c.id}">Supprimer</button>` : ''}
                                </div>
                            `).join('') : '<div class="text-muted small">Pas de commentaires</div>'}

                            <div class="d-flex gap-2 mt-2">
                                <input type="text" class="form-control form-control-sm comment-input" data-post="${post.id}" placeholder="Répondre...">
                                <button class="btn btn-sm btn-primary comment-send" data-post="${post.id}">OK</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            document.getElementById('postsContainer').innerHTML = html;

            // Mettre à jour les hashtags (à droite) depuis les posts chargés
            updateTrendsFromPosts(data.posts || []);
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            document.getElementById('postsContainer').innerHTML = `<p class="text-danger">Erreur chargement posts</p>`;
        });
    }

    function shuffleInPlace(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function extractHashtags(text) {
        const tags = [];
        if (!text) return tags;
        const re = /#([A-Za-z0-9_]{1,50})\b/g;
        let m;
        while ((m = re.exec(text)) !== null) {
            tags.push('#' + m[1]);
        }
        return tags;
    }

    function updateTrendsFromPosts(posts) {
        const container = document.getElementById('trendsContainer');
        if (!container) return;

        const map = new Map(); // key lower -> original
        (posts || []).forEach(p => {
            extractHashtags(p.content || '').forEach(tag => {
                const key = tag.toLowerCase();
                if (!map.has(key)) map.set(key, tag);
            });
        });

        const all = Array.from(map.values());
        shuffleInPlace(all);
        const picked = all.slice(0, 5);

        // Garder 5 emplacements, vides si pas assez de hashtags
        while (picked.length < 5) picked.push('');

        container.innerHTML = picked.map(tag => {
            if (tag) {
                const href = `<?= $basePath ?? '/netchat/public' ?>/search?q=%23${encodeURIComponent(tag.slice(1))}`;
                return `
                <a href="${href}" class="nc-trend-item">
                    <div class="nc-trend-category">Tendances</div>
                    <div class="nc-trend-tag">${tag}</div>
                </a>`;
            }
            return `
                <div class="nc-trend-item nc-trend-item-empty">
                    <div class="nc-trend-category">Tendances</div>
                    <div class="nc-trend-tag">&nbsp;</div>
                </div>`;
        }).join('');
    }

    async function loadSuggestions() {
        const container = document.getElementById('suggestionsContainer');
        if (!container) return;

        try {
            const res = await fetch(`<?= $basePath ?? '/netchat/public' ?>/api_users.php?limit=3`);
            const data = await res.json();
            const users = data.users || [];

            const items = users.slice(0, 3);
            while (items.length < 3) items.push(null);

            container.innerHTML = items.map(u => {
                if (!u) {
                    return `
                        <div class="nc-suggest-user" style="visibility:hidden">
                            <span class="nc-suggest-user-link" tabindex="-1" aria-hidden="true">
                                <img src="<?= $basePath ?? '/netchat/public' ?>/assets/user_icon.png" alt="">
                                <div class="nc-suggest-user-info">
                                    <div class="nc-suggest-user-name">&nbsp;</div>
                                    <div class="nc-suggest-user-handle">&nbsp;</div>
                                </div>
                            </span>
                            <button type="button" class="nc-suggest-btn" disabled>Suivre</button>
                        </div>
                    `;
                }

                const pic = u.profile_picture || 'assets/user_icon.png';
                const following = !!u.is_following;
                const uname = String(u.username || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                return `
                    <div class="nc-suggest-user">
                        <a href="<?= $basePath ?? '/netchat/public' ?>/profile?id=${u.id}" class="nc-suggest-user-link">
                            <img src="<?= $basePath ?? '/netchat/public' ?>/${pic}" alt="">
                            <div class="nc-suggest-user-info">
                                <div class="nc-suggest-user-name">${uname}</div>
                                <div class="nc-suggest-user-handle">@${uname}</div>
                            </div>
                        </a>
                        <button type="button" class="nc-suggest-btn" data-user="${u.id}">${following ? 'Se désabonner' : 'Suivre'}</button>
                    </div>
                `;
            }).join('');
        } catch (e) {
            console.error(e);
            container.innerHTML = '';
        }
    }
    
    document.getElementById('postForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
            const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/post.php', { method: 'POST', body: formData });
        const data = await res.json();
        const msgEl = document.getElementById('postMessage');

        if (data.success) {
            document.getElementById('postContent').value = '';
            clearPostImage();
            msgEl.innerHTML = '<div class="alert alert-success">Post créé !</div>';
            offset = 0;
            loadPosts();
            setTimeout(() => { msgEl.innerHTML = ''; }, 3000);
        } else if (data.error) {
            msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.error)}</div>`;
        }
    });

    async function openCommentsModal(postId) {
        // fetch comments
        const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/api_comments.php?post_id=' + encodeURIComponent(postId));
        const data = await res.json();
        if (data.error) { alert(data.error); return; }

        // build modal HTML
        const modalId = 'nc-comments-modal';
        let modal = document.getElementById(modalId);
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'nc-modal-backdrop';
        modal.style.position = 'fixed'; modal.style.left = 0; modal.style.top = 0; modal.style.right = 0; modal.style.bottom = 0;
        modal.style.background = 'rgba(0,0,0,0.4)'; modal.style.display = 'flex'; modal.style.alignItems = 'center'; modal.style.justifyContent = 'center';

        const box = document.createElement('div');
        box.className = 'nc-modal-box';
        box.style.background = '#fff'; box.style.padding = '16px'; box.style.width = '600px'; box.style.maxHeight = '80vh'; box.style.overflow = 'auto'; box.style.borderRadius = '8px';

        box.innerHTML = `<h5>Commentaires</h5><div id="nc-comments-list"></div><hr><div><textarea id="nc-comment-input" class="form-control" rows="3" placeholder="Écrire un commentaire..."></textarea><div class="d-flex justify-content-end mt-2"><button id="nc-comment-send" class="btn btn-primary">Envoyer</button> <button id="nc-comment-close" class="btn btn-secondary ms-2">Fermer</button></div></div>`;
        modal.appendChild(box);
        document.body.appendChild(modal);

        const listEl = box.querySelector('#nc-comments-list');

        function renderComments(items) {
            listEl.innerHTML = '';
            if (!items || !items.length) { listEl.innerHTML = '<p class="text-muted">Aucun commentaire</p>'; return; }
            items.forEach(c => {
                const el = document.createElement('div');
                el.className = 'd-flex align-items-start gap-3 p-2';
                el.innerHTML = `
                    <img src="<?= $basePath ?? '/netchat/public' ?>/${c.profile_picture || 'assets/user_icon.png'}" width="40" height="40" style="object-fit:cover;border-radius:50%">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${escapeHtml(c.username)} <small class="text-muted">${relativeTime(c.created_at)}</small></div>
                        <div>${escapeHtml(c.content)}</div>
                    </div>
                `;
                if (c.user_id == currentUserId) {
                    const del = document.createElement('button');
                    del.className = 'btn btn-sm btn-outline-danger ms-2';
                    del.textContent = 'Supprimer';
                    del.addEventListener('click', async () => {
                        if (!confirm('Supprimer ce commentaire ?')) return;
                        const form = new FormData(); form.append('comment_id', c.id);
                        const r = await fetch('<?= $basePath ?? '/netchat/public' ?>/comment/delete', { method: 'POST', body: form });
                        const j = await r.json();
                        if (j.error) { alert(j.error); return; }
                        // refresh list
                        const newRes = await fetch('<?= $basePath ?? '/netchat/public' ?>/api_comments.php?post_id=' + encodeURIComponent(postId));
                        const newData = await newRes.json();
                        renderComments(newData.comments);
                    });
                    el.appendChild(del);
                }
                listEl.appendChild(el);
            });
        }

        renderComments(data.comments || []);

        box.querySelector('#nc-comment-close').addEventListener('click', () => modal.remove());
        box.querySelector('#nc-comment-send').addEventListener('click', async () => {
            const txt = box.querySelector('#nc-comment-input').value.trim();
            if (!txt) return;
            const form = new FormData();
            form.append('post_id', postId);
            form.append('content', txt);
            const r = await fetch('<?= $basePath ?? '/netchat/public' ?>/comment/create', { method: 'POST', body: form });
            const j = await r.json();
            if (j.error) { alert(j.error); return; }
            box.querySelector('#nc-comment-input').value = '';
            // re-render
            const newRes = await fetch('<?= $basePath ?? '/netchat/public' ?>/api_comments.php?post_id=' + encodeURIComponent(postId));
            const newData = await newRes.json();
            renderComments(newData.comments);
            // also refresh posts (to update comments_count)
            loadPosts();
        });
    }
    
    loadPosts();
    loadSuggestions();

    (function initRightSearchSuggest() {
        const input = document.getElementById('rightSearchInput');
        const dd = document.getElementById('rightSearchDropdown');
        const clearBtn = document.getElementById('rightSearchClear');
        if (!input || !dd) return;

        let t = null;
        let seq = 0;

        function hideDd() {
            dd.classList.add('d-none');
            dd.innerHTML = '';
        }

        function showDd() {
            dd.classList.remove('d-none');
        }

        function rowHtml(iconClass, titleHtml, subtitleHtml, metaLabel, dataAttrs = '') {
            return `
                <button type="button" class="nc-search-dd-item" role="option" ${dataAttrs}>
                    <div class="nc-search-dd-icon"><i class="${iconClass}"></i></div>
                    <div class="nc-search-dd-main">
                        <div class="nc-search-dd-title">${titleHtml}</div>
                        ${subtitleHtml ? `<div class="nc-search-dd-sub">${subtitleHtml}</div>` : ''}
                    </div>
                    <div class="nc-search-dd-meta">${escapeHtml(metaLabel)}</div>
                </button>
            `;
        }

        async function runSuggest() {
            const q = input.value.trim();
            const mySeq = ++seq;
            clearTimeout(t);
            if (q.length < 1) {
                hideDd();
                clearBtn.classList.add('d-none');
                return;
            }
            clearBtn.classList.remove('d-none');
            t = setTimeout(async () => {
                try {
                    const res = await fetch(`${basePath}/api_search_suggest.php?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    if (mySeq !== seq) return;
                    if (data.error) { hideDd(); return; }

                    const parts = [];
                    if ((data.hashtags || []).length) {
                        parts.push(`<div class="nc-search-dd-section">Hashtags</div>`);
                        data.hashtags.forEach(h => {
                            const enc = encodeURIComponent(h.tag);
                            parts.push(rowHtml('fas fa-hashtag', escapeHtml(h.display), '', h.label, `data-kind="hashtag" data-tag="${enc}"`));
                        });
                    }
                    if ((data.accounts || []).length) {
                        parts.push(`<div class="nc-search-dd-section">Comptes</div>`);
                        data.accounts.forEach(a => {
                            const pic = a.profile_picture || 'assets/user_icon.png';
                            parts.push(`
                                <button type="button" class="nc-search-dd-item nc-search-dd-account" role="option" data-kind="account" data-id="${a.id}">
                                    <img class="nc-search-dd-avatar" src="${basePath}/${escapeHtml(pic)}" alt="">
                                    <div class="nc-search-dd-main">
                                        <div class="nc-search-dd-title">${escapeHtml(a.username)}</div>
                                        <div class="nc-search-dd-sub">@${escapeHtml(a.username)}</div>
                                    </div>
                                    <div class="nc-search-dd-meta">${escapeHtml(a.label)}</div>
                                </button>
                            `);
                        });
                    }

                    if (!parts.length) {
                        hideDd();
                        return;
                    }
                    dd.innerHTML = parts.join('');
                    showDd();
                } catch (e) {
                    hideDd();
                }
            }, 180);
        }

        input.addEventListener('input', runSuggest);
        input.addEventListener('focus', runSuggest);

        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.classList.add('d-none');
            hideDd();
            input.focus();
        });

        dd.addEventListener('click', (e) => {
            const btn = e.target.closest('button.nc-search-dd-item');
            if (!btn) return;
            const kind = btn.getAttribute('data-kind');
            if (kind === 'account') {
                const id = btn.getAttribute('data-id');
                window.location.href = `${basePath}/profile?id=${encodeURIComponent(id)}`;
                return;
            }
            if (kind === 'hashtag') {
                const encTag = btn.getAttribute('data-tag') || '';
                window.location.href = `${basePath}/search?q=%23${encTag}`;
                return;
            }
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dd.contains(e.target) && !clearBtn.contains(e.target)) {
                hideDd();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.location.href = `${basePath}/search?q=${encodeURIComponent(input.value.trim())}`;
            }
        });
    })();

    function clearPostImage() {
        const postImageInput = document.getElementById('postImage');
        const postImageMeta = document.getElementById('postImageMeta');
        const postImagePreview = document.getElementById('postImagePreview');
        const postVideoPreview = document.getElementById('postVideoPreview');
        const postImageName = document.getElementById('postImageName');
        if (postImageInput) postImageInput.value = '';
        if (postImagePreview) {
            postImagePreview.src = '';
            postImagePreview.classList.remove('d-none');
        }
        if (postVideoPreview) {
            postVideoPreview.pause();
            postVideoPreview.removeAttribute('src');
            postVideoPreview.load();
            postVideoPreview.classList.add('d-none');
        }
        if (postImageName) postImageName.textContent = '';
        if (postImageMeta) postImageMeta.classList.add('d-none');
        if (window._postImagePreviewUrl) {
            URL.revokeObjectURL(window._postImagePreviewUrl);
            window._postImagePreviewUrl = null;
        }
        if (window._postVideoPreviewUrl) {
            URL.revokeObjectURL(window._postVideoPreviewUrl);
            window._postVideoPreviewUrl = null;
        }
    }

    // --- Report modal handlers ---
    let _ncReportState = { postId: null, reportedUserId: null };
    document.addEventListener('click', (e) => {
        const btn = e.target.closest && e.target.closest('.report-btn');
        if (!btn) return;
        // ouvrir modal
        _ncReportState.postId = btn.getAttribute('data-post');
        _ncReportState.reportedUserId = btn.getAttribute('data-post-user');
        const modal = document.getElementById('ncReportModal');
        const ta = document.getElementById('ncReportReason');
        const msg = document.getElementById('ncReportMessage');
        if (ta) ta.value = '';
        if (msg) msg.innerHTML = '';
        if (modal) modal.style.display = 'flex';
    });

    document.getElementById('ncReportClose').addEventListener('click', () => {
        document.getElementById('ncReportModal').style.display = 'none';
    });
    document.getElementById('ncReportCancel').addEventListener('click', () => {
        document.getElementById('ncReportModal').style.display = 'none';
    });

    document.getElementById('ncReportSubmit').addEventListener('click', async () => {
        const reasonEl = document.getElementById('ncReportReason');
        const msgEl = document.getElementById('ncReportMessage');
        if (!reasonEl) return;
        const reason = reasonEl.value.trim();
        if (!reason) {
            msgEl.innerHTML = '<div class="alert alert-danger">Veuillez saisir un motif.</div>';
            return;
        }

        const form = new FormData();
        form.append('post_id', _ncReportState.postId);
        if (_ncReportState.reportedUserId) form.append('reported_user_id', _ncReportState.reportedUserId);
        form.append('reason', reason);

        try {
            const res = await fetch(`${basePath}/report_submit.php`, { method: 'POST', body: form });
            const j = await res.json();
            if (j.error) {
                msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`;
                return;
            }
            if (j.success) {
                msgEl.innerHTML = `<div class="alert alert-success">${escapeHtml(j.message || 'Signalement envoyé.')}</div>`;
                // fermer après un court délai
                setTimeout(() => { document.getElementById('ncReportModal').style.display = 'none'; }, 900);
                return;
            }
            msgEl.innerHTML = '<div class="alert alert-danger">Réponse inattendue du serveur.</div>';
        } catch (err) {
            console.error(err);
            msgEl.innerHTML = '<div class="alert alert-danger">Erreur réseau.</div>';
        }
    });

    (function initPostImageCropModal() {
        const modal = document.getElementById('ncImageCropModal');
        const viewport = document.getElementById('ncCropViewport');
        const imgEl = document.getElementById('ncCropImg');
        const zoomEl = document.getElementById('ncCropZoom');
        const btnCancel = document.getElementById('ncCropCancel');
        const btnApply = document.getElementById('ncCropApply');
        const backdrop = modal?.querySelector('.nc-crop-modal-backdrop');
        if (!modal || !viewport || !imgEl || !zoomEl || !btnCancel || !btnApply) return;

        let modalObjectUrl = null;
        let pendingFileName = '';
        const st = {
            iw: 0, ih: 0, Wd: 0, Hd: 0, coverScale: 1, zoomMult: 1,
            panX: 0, panY: 0, dragging: false,
            dragStartX: 0, dragStartY: 0, panStartX: 0, panStartY: 0
        };

        function syncViewportSize() {
            const r = viewport.getBoundingClientRect();
            st.Wd = r.width;
            st.Hd = r.height;
        }

        function sizeCropViewportBox() {
            if (!st.iw || !st.ih) return;
            const panel = viewport.closest('.nc-crop-modal-panel');
            const maxW = Math.min(480, (panel ? panel.clientWidth : 480) - 8);
            const maxH = Math.min(window.innerHeight * 0.52, 520);
            let w = maxW;
            let h = (w * st.ih) / st.iw;
            if (h > maxH) {
                h = maxH;
                w = (h * st.iw) / st.ih;
            }
            viewport.style.width = Math.round(w) + 'px';
            viewport.style.height = Math.round(h) + 'px';
        }

        function clearCropViewportBox() {
            viewport.style.width = '';
            viewport.style.height = '';
        }

        function recomputeCoverScale() {
            if (!st.iw || !st.ih || !st.Wd || !st.Hd) return;
            st.coverScale = Math.max(st.Wd / st.iw, st.Hd / st.ih);
        }

        function dispDims() {
            const s = st.coverScale * st.zoomMult;
            return { w: st.iw * s, h: st.ih * s, s };
        }

        function clampPan() {
            const { w, h } = dispDims();
            st.panX = Math.min(0, Math.max(st.Wd - w, st.panX));
            st.panY = Math.min(0, Math.max(st.Hd - h, st.panY));
        }

        function renderCropImg() {
            const { w, h } = dispDims();
            imgEl.style.width = w + 'px';
            imgEl.style.height = h + 'px';
            imgEl.style.left = st.panX + 'px';
            imgEl.style.top = st.panY + 'px';
        }

        function resetCropLayout() {
            sizeCropViewportBox();
            syncViewportSize();
            if (st.Wd < 2 || st.Hd < 2) return;
            recomputeCoverScale();
            st.zoomMult = parseFloat(zoomEl.value) || 1;
            const { w, h } = dispDims();
            st.panX = (st.Wd - w) / 2;
            st.panY = (st.Hd - h) / 2;
            clampPan();
            renderCropImg();
        }

        function onZoomInput() {
            const prevS = st.coverScale * st.zoomMult;
            st.zoomMult = parseFloat(zoomEl.value) || 1;
            const ns = st.coverScale * st.zoomMult;
            const cx = st.Wd / 2;
            const cy = st.Hd / 2;
            const natX = (cx - st.panX) / prevS;
            const natY = (cy - st.panY) / prevS;
            st.panX = cx - natX * ns;
            st.panY = cy - natY * ns;
            clampPan();
            renderCropImg();
        }

        function closeModalFromCancel() {
            modal.classList.remove('is-open');
            clearCropViewportBox();
            if (modalObjectUrl) {
                URL.revokeObjectURL(modalObjectUrl);
                modalObjectUrl = null;
            }
            imgEl.src = '';
            imgEl.onload = null;
            st.dragging = false;
            const input = document.getElementById('postImage');
            if (input) input.value = '';
        }

        function closeModalAfterApply() {
            modal.classList.remove('is-open');
            clearCropViewportBox();
            if (modalObjectUrl) {
                URL.revokeObjectURL(modalObjectUrl);
                modalObjectUrl = null;
            }
            imgEl.src = '';
            imgEl.onload = null;
            st.dragging = false;
        }

        function applyCrop() {
            syncViewportSize();
            recomputeCoverScale();
            const { s } = dispDims();
            const { Wd, Hd, panX, panY, iw, ih } = st;
            if (!iw || !Wd || s <= 0) {
                closeModalFromCancel();
                return;
            }

            let sx = (-panX) / s;
            let sy = (-panY) / s;
            let sw = Wd / s;
            let sh = Hd / s;
            if (sx < 0) { sw += sx; sx = 0; }
            if (sy < 0) { sh += sy; sy = 0; }
            if (sx + sw > iw) sw = iw - sx;
            if (sy + sh > ih) sh = ih - sy;
            if (sw <= 0 || sh <= 0) {
                closeModalFromCancel();
                return;
            }
            let outW = Math.round(sw);
            let outH = Math.round(sh);
            const cap = 1920;
            const longest = Math.max(outW, outH);
            if (longest > cap) {
                const f = cap / longest;
                outW = Math.max(1, Math.round(outW * f));
                outH = Math.max(1, Math.round(outH * f));
            }
            const canvas = document.createElement('canvas');
            canvas.width = outW;
            canvas.height = outH;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(imgEl, sx, sy, sw, sh, 0, 0, outW, outH);

            canvas.toBlob((blob) => {
                if (!blob) {
                    alert('Impossible de traiter l’image.');
                    return;
                }
                const outFile = new File([blob], 'post.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(outFile);
                const input = document.getElementById('postImage');
                input.files = dt.files;

                const meta = document.getElementById('postImageMeta');
                const preview = document.getElementById('postImagePreview');
                const previewVid = document.getElementById('postVideoPreview');
                const nameEl = document.getElementById('postImageName');
                if (previewVid) {
                    previewVid.pause();
                    previewVid.removeAttribute('src');
                    previewVid.load();
                    previewVid.classList.add('d-none');
                }
                if (window._postVideoPreviewUrl) {
                    URL.revokeObjectURL(window._postVideoPreviewUrl);
                    window._postVideoPreviewUrl = null;
                }
                if (window._postImagePreviewUrl) {
                    URL.revokeObjectURL(window._postImagePreviewUrl);
                }
                window._postImagePreviewUrl = URL.createObjectURL(blob);
                preview.src = window._postImagePreviewUrl;
                preview.classList.remove('d-none');
                const baseName = pendingFileName.replace(/\.[^.]+$/, '');
                nameEl.textContent = (baseName || 'photo') + '.jpg';
                meta.classList.remove('d-none');

                closeModalAfterApply();
            }, 'image/jpeg', 0.92);
        }

        window._ncOpenPostImageCrop = function(file) {
            if (modalObjectUrl) URL.revokeObjectURL(modalObjectUrl);
            modalObjectUrl = URL.createObjectURL(file);
            pendingFileName = file.name || 'photo';
            zoomEl.value = '1';
            st.zoomMult = 1;
            st.dragging = false;
            imgEl.onload = function() {
                imgEl.onload = null;
                st.iw = imgEl.naturalWidth;
                st.ih = imgEl.naturalHeight;
                requestAnimationFrame(() => {
                    requestAnimationFrame(resetCropLayout);
                });
            };
            imgEl.src = modalObjectUrl;
            modal.classList.add('is-open');
        };

        zoomEl.addEventListener('input', onZoomInput);

        viewport.addEventListener('pointerdown', (e) => {
            if (e.button !== 0) return;
            st.dragging = true;
            st.dragStartX = e.clientX;
            st.dragStartY = e.clientY;
            st.panStartX = st.panX;
            st.panStartY = st.panY;
            try { viewport.setPointerCapture(e.pointerId); } catch (err) {}
        });
        viewport.addEventListener('pointermove', (e) => {
            if (!st.dragging) return;
            st.panX = st.panStartX + (e.clientX - st.dragStartX);
            st.panY = st.panStartY + (e.clientY - st.dragStartY);
            clampPan();
            renderCropImg();
        });
        function endDrag(e) {
            if (st.dragging) {
                st.dragging = false;
                try { viewport.releasePointerCapture(e.pointerId); } catch (err) {}
            }
        }
        viewport.addEventListener('pointerup', endDrag);
        viewport.addEventListener('pointercancel', endDrag);

        btnCancel.addEventListener('click', closeModalFromCancel);
        if (backdrop) backdrop.addEventListener('click', closeModalFromCancel);
        btnApply.addEventListener('click', applyCrop);

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape' || !modal.classList.contains('is-open')) return;
            closeModalFromCancel();
        });
    })();

    (function bindPostImageUI() {
        const input = document.getElementById('postImage');
        const removeBtn = document.getElementById('postImageRemove');
        const meta = document.getElementById('postImageMeta');
        const nameEl = document.getElementById('postImageName');
        const imgPrev = document.getElementById('postImagePreview');
        const vidPrev = document.getElementById('postVideoPreview');
        if (!input || !removeBtn || !meta || !nameEl || !imgPrev || !vidPrev) return;

        const maxVideoBytes = 100 * 1024 * 1024;
        const msgVideoTooBig = 'Désolé, votre vidéo est trop volumineuse. Merci d’en choisir une plus légère.';

        input.addEventListener('change', function() {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                clearPostImage();
                return;
            }

            if (file.type.startsWith('video/')) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!NC_VIDEO_EXT.includes(ext)) {
                    alert('Vidéo : formats acceptés MP4, WebM, MOV, M4V.');
                    clearPostImage();
                    return;
                }
                if (file.size > maxVideoBytes) {
                    alert(msgVideoTooBig);
                    clearPostImage();
                    return;
                }
                if (window._postVideoPreviewUrl) {
                    URL.revokeObjectURL(window._postVideoPreviewUrl);
                    window._postVideoPreviewUrl = null;
                }
                if (window._postImagePreviewUrl) {
                    URL.revokeObjectURL(window._postImagePreviewUrl);
                    window._postImagePreviewUrl = null;
                }
                imgPrev.classList.add('d-none');
                vidPrev.classList.remove('d-none');
                window._postVideoPreviewUrl = URL.createObjectURL(file);
                vidPrev.src = window._postVideoPreviewUrl;
                nameEl.textContent = file.name;
                meta.classList.remove('d-none');
                return;
            }

            if (file.type.startsWith('image/')) {
                const imgExt = (file.name.split('.').pop() || '').toLowerCase();
                const isGif = imgExt === 'gif' || file.type === 'image/gif';
                if (isGif) {
                    if (window._postVideoPreviewUrl) {
                        URL.revokeObjectURL(window._postVideoPreviewUrl);
                        window._postVideoPreviewUrl = null;
                    }
                    if (window._postImagePreviewUrl) {
                        URL.revokeObjectURL(window._postImagePreviewUrl);
                    }
                    imgPrev.classList.remove('d-none');
                    vidPrev.classList.add('d-none');
                    vidPrev.pause();
                    vidPrev.removeAttribute('src');
                    vidPrev.load();
                    window._postImagePreviewUrl = URL.createObjectURL(file);
                    imgPrev.src = window._postImagePreviewUrl;
                    nameEl.textContent = file.name;
                    meta.classList.remove('d-none');
                    return;
                }
                imgPrev.classList.remove('d-none');
                vidPrev.classList.add('d-none');
                if (typeof window._ncOpenPostImageCrop === 'function') {
                    window._ncOpenPostImageCrop(file);
                }
                return;
            }

            alert('Fichier non supporté (image ou vidéo MP4 / WebM / MOV).');
            clearPostImage();
        });

        removeBtn.addEventListener('click', function() {
            clearPostImage();
        });
    })();

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

        // Inline comment send
        const commentSend = e.target.closest('.comment-send');
        if (commentSend) {
            const postId = commentSend.getAttribute('data-post');
            const input = document.querySelector(`.comment-input[data-post="${postId}"]`);
            if (!input) return;
            const val = input.value.trim();
            if (!val) return;
            try {
                const form = new FormData();
                form.append('post_id', postId);
                form.append('content', val);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/comment/create', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                // refresh comments for this post by reloading posts (simpler)
                loadPosts();
            } catch (err) { console.error(err); }
            return;
        }

        // Inline comment delete
        const commentInlineDel = e.target.closest('.comment-inline-delete');
        if (commentInlineDel) {
            const commentId = commentInlineDel.getAttribute('data-comment');
            if (!commentId) return;
            if (!confirm('Supprimer ce commentaire ?')) return;
            try {
                const form = new FormData();
                form.append('comment_id', commentId);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/comment/delete', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                loadPosts();
            } catch (err) { console.error(err); }
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
                // Update UI: uniquement le compteur (pas de changement de style "rempli")
                likeBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.count}`;
            } catch (err) {
                console.error(err);
            }
            return;
        }

        // Comment buttons - open modal listing comments and add new
        const commentBtn = e.target.closest('.comment-btn');
        if (commentBtn) {
            const postId = commentBtn.getAttribute('data-post');
            if (!postId) return;
            try {
                await openCommentsModal(postId);
            } catch (err) { console.error(err); }
            return;
        }

        // Subscribe buttons in post header
        const subBtn = e.target.closest('.subscribe-btn');
        if (subBtn) {
            const targetId = subBtn.getAttribute('data-user');
            if (!targetId) return;
            try {
                const form = new FormData();
                form.append('target_id', targetId);
                const res = await fetch('<?= $basePath ?? '/netchat/public' ?>/follow_toggle.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                subBtn.textContent = data.following ? 'Se désabonner' : "S'abonner";
                // If on profile of target, update followBtn text if present
                const followBtn = document.getElementById('followBtn');
                const textEl = document.getElementById('followBtnText');
                if (followBtn && textEl && followBtn.getAttribute('data-user') == targetId) {
                    if (data.following) {
                        followBtn.classList.remove('btn-primary');
                        followBtn.classList.add('btn-outline-secondary');
                        textEl.textContent = 'Se désabonner';
                    } else {
                        followBtn.classList.remove('btn-outline-secondary');
                        followBtn.classList.add('btn-primary');
                        textEl.textContent = "S'abonner";
                    }
                }
            } catch (err) { console.error(err); }
            return;
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
