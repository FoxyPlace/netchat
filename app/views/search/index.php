<?php
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Recherche - NetChat';
$extra_css = ['dashboard.css'];
include __DIR__ . '/../layouts/header.php';
$initial_query = $initial_query ?? '';
?>

<div class="nc-layout">
    <aside class="nc-sidebar d-none d-lg-block">
        <div class="nc-sidebar-inner">
            <div>
                <a href="<?= $basePath ?>/" class="nc-sidebar-logo">
                    <img src="<?= $basePath ?>/assets/logo.png" alt="NetChat">
                    <span class="netchat-title fs-3 fw-bold">NetChat</span>
                </a>

                <nav class="nc-sidebar-nav nav flex-column">
                    <a href="<?= $basePath ?>/" class="nav-link">
                        <i class="fas fa-home"></i><span>Accueil</span>
                    </a>
                    <a href="<?= $basePath ?>/notifications" class="nav-link">
                        <i class="fas fa-bell"></i><span>Notifications</span>
                    </a>
                    <a href="<?= $basePath ?>/chat" class="nav-link">
                        <i class="fas fa-comments"></i><span>Chat</span>
                    </a>
                    <a href="<?= $basePath ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link">
                        <i class="fas fa-user"></i><span>Profil</span>
                    </a>
                    <a href="<?= $basePath ?>/settings" class="nav-link">
                        <i class="fas fa-cog"></i><span>Paramètres</span>
                    </a>
                </nav>
            </div>

            <div>
                <a href="<?= $basePath ?>/profile?id=<?= (int)$_SESSION['user_id'] ?>" class="nc-sidebar-profile">
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($user_profile_picture) ?>" alt="Profil">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                        <div class="text-muted small">@<?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                    </div>
                </a>
            </div>
        </div>
    </aside>

    <main class="nc-main-column">
        <div class="nc-main-header">
            <h5>Recherche</h5>
        </div>

        <div class="px-3 py-3 border-bottom bg-white">
            <form id="searchPageForm" class="d-flex gap-2">
                <div class="flex-grow-1 nc-search-box nc-search-box-page mb-0">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="searchPageInput" placeholder="Rechercher un mot, #hashtag ou @pseudo" value="<?= htmlspecialchars($initial_query, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
                </div>
                <button type="submit" class="btn btn-primary px-4">Rechercher</button>
            </form>
        </div>

        <div id="postsContainer" class="px-2 py-3">
            <p class="text-muted text-center mb-0">Chargement…</p>
        </div>
    </main>

    <aside class="nc-right-sidebar d-none d-lg-block">
        <div class="nc-right-sidebar-inner">
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

            <div class="nc-trend-card">
                <h6>Ce qui se passe</h6>
                <div id="trendsContainer"></div>
            </div>

            <div class="nc-suggest-card">
                <h6>Suggestions</h6>
                <div id="suggestionsContainer"></div>
            </div>
        </div>
    </aside>
</div>

<script>
    const basePath = <?= json_encode($basePath) ?>;
    let offset = 0;
    const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    let activeQuery = <?= json_encode($initial_query) ?> || '';
    let searchLoading = false;
    let searchHasMore = true;

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
        if (NC_VIDEO_EXT.includes(ext)) {
            return `<div class="nc-post-media nc-post-media--video"><video src="${basePath}/${esc}" controls playsinline preload="metadata"></video></div>`;
        }
        return `<div class="nc-post-media"><img src="${basePath}/${esc}" alt=""></div>`;
    }

    function postContentHtml(post) {
        let content = escapeHtml(post.content || '');
        if (post.mentions && post.mentions.length) {
            post.mentions.forEach(m => {
                const username = m.username;
                const uid = m.mentioned_user_id;
                const re = new RegExp('@' + escapeRegExp(username) + '\\b', 'g');
                content = content.replace(re, `<a href="${basePath}/profile?id=${uid}" class="text-primary">@${username}</a>`);
            });
        }
        content = content.replace(/#\w+/g, m => {
            const tag = encodeURIComponent(m.slice(1));
            return `<a href="${basePath}/search?q=%23${tag}" class="text-primary">${m}</a>`;
        });
        return content;
    }

    function buildPostCard(post) {
        const content = postContentHtml(post);
        const isSelfPost = (parseInt(post.user_id, 10) === currentUserId);
        const subscribeBtnHtml = isSelfPost ? '' : `
            <button type="button" class="btn btn-sm btn-outline-primary subscribe-btn" data-user="${post.user_id}">${post.is_following ? 'Se désabonner' : "S'abonner"}</button>
        `;
        return `
        <div class="col-12 mb-4">
            <div class="netchat-card p-4">
                <div class="d-flex align-items-start mb-3">
                    <a href="${basePath}/profile?id=${post.user_id}">
                        <img src="${basePath}/${post.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;" alt="Profil">
                    </a>
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="${basePath}/profile?id=${post.user_id}" class="text-decoration-none">
                                <h6 class="mb-0 fw-bold link-netchat">${escapeHtml(post.username)}</h6>
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
                </div>

                <div class="mt-3 post-comments" data-post="${post.id}">
                    ${post.comments && post.comments.length ? post.comments.map(c => `
                        <div class="d-flex align-items-start mb-2 comment-item" data-comment="${c.id}">
                            <img src="${basePath}/${c.profile_picture || 'assets/user_icon.png'}" class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
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
    }

    function loadSearch(reset) {
        const q = (activeQuery || '').trim();
        const container = document.getElementById('postsContainer');
        if (!container) return;

        if (!q) {
            container.innerHTML = '<p class="text-muted text-center mb-0">Saisis un terme dans la barre ci-dessus.</p>';
            searchHasMore = false;
            return;
        }

        if (reset) {
            offset = 0;
            searchHasMore = true;
            container.innerHTML = '<p class="text-muted text-center mb-0">Chargement…</p>';
        }

        if (!searchHasMore || searchLoading) return;
        searchLoading = true;

        fetch(`${basePath}/api_search_posts.php?q=${encodeURIComponent(q)}&offset=${offset}&limit=10`)
            .then(r => r.json())
            .then(data => {
                searchLoading = false;
                if (data.error) {
                    container.innerHTML = `<p class="text-danger text-center mb-0">${escapeHtml(data.error)}</p>`;
                    return;
                }
                const posts = data.posts || [];
                if (reset && posts.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center mb-0">Aucun résultat pour cette recherche.</p>';
                    searchHasMore = false;
                    return;
                }
                if (posts.length < 10) searchHasMore = false;
                offset += posts.length;

                const chunk = posts.map(buildPostCard).join('');
                if (reset) {
                    container.innerHTML = '<div class="row">' + chunk + '</div>';
                } else {
                    const row = container.querySelector('.row');
                    if (row) row.insertAdjacentHTML('beforeend', chunk);
                    else container.innerHTML = '<div class="row">' + chunk + '</div>';
                }
            })
            .catch(err => {
                searchLoading = false;
                console.error(err);
                container.innerHTML = '<p class="text-danger text-center mb-0">Erreur chargement</p>';
            });
    }

    function shuffleInPlace(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function renderTrendsSidebarFromTags(tagList) {
        const container = document.getElementById('trendsContainer');
        if (!container) return;

        let tags = (tagList || []).filter(Boolean);
        const q = (activeQuery || '').trim();
        if (q.startsWith('#') && q.length > 1) {
            const skip = q.slice(1).toLowerCase();
            tags = tags.filter(t => String(t).replace(/^#/, '').toLowerCase() !== skip);
        }

        const all = tags.slice();
        shuffleInPlace(all);
        const picked = all.slice(0, 5);
        while (picked.length < 5) picked.push('');

        container.innerHTML = picked.map(tag => {
            if (tag) {
                const href = `${basePath}/search?q=%23${encodeURIComponent(String(tag).replace(/^#/, ''))}`;
                return `
                <a href="${href}" class="nc-trend-item">
                    <div class="nc-trend-category">Tendances</div>
                    <div class="nc-trend-tag">${escapeHtml(tag)}</div>
                </a>`;
            }
            return `
                <div class="nc-trend-item nc-trend-item-empty">
                    <div class="nc-trend-category">Tendances</div>
                    <div class="nc-trend-tag">&nbsp;</div>
                </div>`;
        }).join('');
    }

    async function loadTrendingSidebar() {
        const container = document.getElementById('trendsContainer');
        if (!container) return;
        try {
            const res = await fetch(`${basePath}/api_trending_hashtags.php`);
            const data = await res.json();
            if (data.error) {
                renderTrendsSidebarFromTags([]);
                return;
            }
            renderTrendsSidebarFromTags(data.tags || []);
        } catch (e) {
            console.error(e);
            renderTrendsSidebarFromTags([]);
        }
    }

    async function loadSuggestions() {
        const container = document.getElementById('suggestionsContainer');
        if (!container) return;

        try {
            const res = await fetch(`${basePath}/api_users.php?limit=3`);
            const data = await res.json();
            const users = data.users || [];

            const items = users.slice(0, 3);
            while (items.length < 3) items.push(null);

            container.innerHTML = items.map(u => {
                if (!u) {
                    return `
                        <div class="nc-suggest-user" style="visibility:hidden">
                            <span class="nc-suggest-user-link" tabindex="-1" aria-hidden="true">
                                <img src="${basePath}/assets/user_icon.png" alt="">
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
                return `
                    <div class="nc-suggest-user">
                        <a href="${basePath}/profile?id=${u.id}" class="nc-suggest-user-link">
                            <img src="${basePath}/${pic}" alt="">
                            <div class="nc-suggest-user-info">
                                <div class="nc-suggest-user-name">${escapeHtml(u.username)}</div>
                                <div class="nc-suggest-user-handle">@${escapeHtml(u.username)}</div>
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

    async function openCommentsModal(postId) {
        const res = await fetch(`${basePath}/api_comments.php?post_id=` + encodeURIComponent(postId));
        const data = await res.json();
        if (data.error) { alert(data.error); return; }

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
                    <img src="${basePath}/${c.profile_picture || 'assets/user_icon.png'}" width="40" height="40" style="object-fit:cover;border-radius:50%">
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
                        const r = await fetch(`${basePath}/comment/delete`, { method: 'POST', body: form });
                        const j = await r.json();
                        if (j.error) { alert(j.error); return; }
                        const newRes = await fetch(`${basePath}/api_comments.php?post_id=` + encodeURIComponent(postId));
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
            const r = await fetch(`${basePath}/comment/create`, { method: 'POST', body: form });
            const j = await r.json();
            if (j.error) { alert(j.error); return; }
            box.querySelector('#nc-comment-input').value = '';
            const newRes = await fetch(`${basePath}/api_comments.php?post_id=` + encodeURIComponent(postId));
            const newData = await newRes.json();
            renderComments(newData.comments);
            loadSearch(true);
        });
    }

    document.getElementById('searchPageForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const v = document.getElementById('searchPageInput').value.trim();
        window.location.href = `${basePath}/search?q=${encodeURIComponent(v)}`;
    });

    window.addEventListener('scroll', () => {
        if (!searchHasMore || searchLoading) return;
        const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 400;
        if (nearBottom) loadSearch(false);
    });

    /** Suggestions colonne droite (même logique que le dashboard) */
    (function initRightSearchSuggest() {
        const input = document.getElementById('rightSearchInput');
        const dd = document.getElementById('rightSearchDropdown');
        const clearBtn = document.getElementById('rightSearchClear');
        if (!input || !dd || !clearBtn) return;

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
            const dataBtn = btn.getAttribute('data-kind');
            if (dataBtn === 'account') {
                const id = btn.getAttribute('data-id');
                window.location.href = `${basePath}/profile?id=${encodeURIComponent(id)}`;
                return;
            }
            if (dataBtn === 'hashtag') {
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

    loadSuggestions();
    loadTrendingSidebar();
    loadSearch(true);

    document.addEventListener('click', async function(e) {
        const suggestBtn = e.target.closest('.nc-suggest-btn');
        if (suggestBtn) {
            const targetId = suggestBtn.getAttribute('data-user');
            if (!targetId) return;
            try {
                const form = new FormData();
                form.append('target_id', targetId);
                const res = await fetch(`${basePath}/follow_toggle.php`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                suggestBtn.textContent = data.following ? 'Se désabonner' : 'Suivre';
            } catch (err) {
                console.error(err);
            }
            return;
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const postId = deleteBtn.getAttribute('data-post');
            if (!postId) return;
            if (!confirm('Voulez-vous vraiment supprimer ce post ?')) return;
            try {
                const form = new FormData();
                form.append('post_id', postId);
                const res = await fetch(`${basePath}/post_delete.php`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const root = deleteBtn.closest('.col-12');
                if (root) root.remove();
            } catch (err) {
                console.error(err);
            }
            return;
        }

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
                const res = await fetch(`${basePath}/comment/create`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                loadSearch(true);
            } catch (err) { console.error(err); }
            return;
        }

        const commentInlineDel = e.target.closest('.comment-inline-delete');
        if (commentInlineDel) {
            const commentId = commentInlineDel.getAttribute('data-comment');
            if (!commentId) return;
            if (!confirm('Supprimer ce commentaire ?')) return;
            try {
                const form = new FormData();
                form.append('comment_id', commentId);
                const res = await fetch(`${basePath}/comment/delete`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                loadSearch(true);
            } catch (err) { console.error(err); }
            return;
        }

        const likeBtn = e.target.closest('.like-btn');
        if (likeBtn) {
            const postId = likeBtn.getAttribute('data-post');
            if (!postId) return;
            try {
                const form = new FormData();
                form.append('post_id', postId);
                const res = await fetch(`${basePath}/post_like.php`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                likeBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.count}`;
            } catch (err) {
                console.error(err);
            }
            return;
        }

        const commentBtn = e.target.closest('.comment-btn');
        if (commentBtn) {
            const postId = commentBtn.getAttribute('data-post');
            if (!postId) return;
            try {
                await openCommentsModal(postId);
            } catch (err) { console.error(err); }
            return;
        }

        const subBtn = e.target.closest('.subscribe-btn');
        if (subBtn) {
            const targetId = subBtn.getAttribute('data-user');
            if (!targetId) return;
            try {
                const form = new FormData();
                form.append('target_id', targetId);
                const res = await fetch(`${basePath}/follow_toggle.php`, { method: 'POST', body: form });
                const data = await res.json();
                if (data.error) { alert(data.error); return; }
                subBtn.textContent = data.following ? 'Se désabonner' : "S'abonner";
            } catch (err) { console.error(err); }
            return;
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
