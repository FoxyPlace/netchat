<?php
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Notifications - NetChat';
$extra_css = ['dashboard.css'];
include __DIR__ . '/../layouts/header.php';
?>

<div class="nc-layout">
    <aside class="nc-sidebar d-none d-md-block">
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
                    <a href="<?= $basePath ?>/notifications" class="nav-link active">
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
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2">
                <h5 class="py-2 mb-0">Notifications</h5>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="markAllReadBtn" class="btn btn-sm btn-outline-primary">Tout marquer comme lu</button>
                    <button type="button" id="deleteAllNotifBtn" class="btn btn-sm btn-outline-danger">Tout supprimer</button>
                </div>
            </div>
        </div>

        <div class="px-3 py-3">
            <div id="notifList" class="netchat-card p-0 overflow-hidden">
                <div class="p-4 text-muted">Chargement…</div>
            </div>
        </div>
    </main>
</div>

<script>
function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function notifLabel(n) {
    const actor = n.actor_username ? '@' + n.actor_username : 'Quelqu’un';
    switch (n.type) {
        case 'friend_request': return `${actor} t’a envoyé une demande d’ami`;
        case 'friend_accept': return `${actor} a accepté ta demande d’ami`;
        case 'follow': return `${actor} s’est abonné à toi`;
        case 'mention': return `${actor} t’a mentionné`;
        case 'message': return `${actor} t’a envoyé un message`;
        case 'like': return `${actor} a aimé ton post`;
        case 'comment': return `${actor} a commenté ton post`;
        case 'message_request': return `${actor} veut t’envoyer un message`;
        case 'report':
            // Pour les signalements, afficher un message simple sans préfixe acteur
            if (n.data && n.data.message) return `${n.data.message}`;
            return 'Votre signalement est en cours de traitement.';
        default: return `${actor} — notification`;
    }
}

function notifLink(n) {
    if (n.type === 'message' && n.actor_id) return `<?= $basePath ?>/chat?user=${encodeURIComponent(n.actor_id)}`;
    if (n.type === 'message_request' && n.actor_id) return `<?= $basePath ?>/notifications`;
    if (n.type === 'mention' && n.data && (n.data.post_id || n.data.comment_id)) return `<?= $basePath ?>/`;
    if ((n.type === 'follow' || n.type === 'friend_request' || n.type === 'friend_accept') && n.actor_id) return `<?= $basePath ?>/profile?id=${encodeURIComponent(n.actor_id)}`;
    if ((n.type === 'like' || n.type === 'comment') && n.target_id) return `<?= $basePath ?>/`;
    return `<?= $basePath ?>/`;
}

async function markRead(id) {
    const form = new FormData();
    form.append('id', id);
    const res = await fetch(`<?= $basePath ?>/notification_read.php`, { method: 'POST', body: form });
    return await res.json();
}

async function markAllRead() {
    const res = await fetch(`<?= $basePath ?>/notification_read.php`, { method: 'POST', body: new URLSearchParams({ all: '1' }) });
    return await res.json();
}

async function deleteNotification(id) {
    const form = new FormData();
    form.append('id', id);
    const res = await fetch(`<?= $basePath ?>/notification_delete.php`, { method: 'POST', body: form });
    return await res.json();
}

async function deleteAllNotifications() {
    const res = await fetch(`<?= $basePath ?>/notification_delete.php`, { method: 'POST', body: new URLSearchParams({ all: '1' }) });
    return await res.json();
}

async function loadNotifications() {
    const list = document.getElementById('notifList');
    const res = await fetch(`<?= $basePath ?>/api_notifications.php?limit=40`);
    const data = await res.json();
    if (data.error) {
        list.innerHTML = `<div class="p-4 text-danger">${escapeHtml(data.error)}</div>`;
        return;
    }

    const items = data.notifications || [];
    if (!items.length) {
        list.innerHTML = `<div class="p-5 text-center text-muted">Aucune notification</div>`;
        return;
    }

    list.innerHTML = items.map(n => {
        const pic = n.actor_profile_picture || 'assets/user_icon.png';
        const cls = n.is_read ? 'nc-notif-item' : 'nc-notif-item nc-notif-unread';
        const rowCls = n.is_read ? 'nc-notif-row' : 'nc-notif-row nc-notif-row-unread';
        const href = notifLink(n);
        const excerpt = (n.data && n.data.excerpt) ? `<div class="nc-notif-excerpt">${escapeHtml(n.data.excerpt)}</div>` : '';
        const actions = (n.type === 'message_request' && !n.is_read) ? `
            <div class="nc-notif-actions">
                <button type="button" class="btn btn-sm btn-primary nc-accept-request" data-user="${n.actor_id}">Accepter</button>
            </div>
        ` : '';
        return `
            <div class="${rowCls}">
                <a class="${cls}" href="${href}" data-id="${n.id}">
                    <img class="nc-notif-avatar" src="<?= $basePath ?>/${pic}" alt="">
                    <div class="nc-notif-body">
                        <div class="nc-notif-title">${escapeHtml(notifLabel(n))}</div>
                        ${excerpt}
                        <div class="nc-notif-time text-muted small">${escapeHtml(n.created_at)}</div>
                        ${actions}
                    </div>
                    ${n.is_read ? '' : '<span class="nc-notif-dot"></span>'}
                </a>
                <button type="button" class="nc-notif-delete" data-id="${n.id}" title="Supprimer" aria-label="Supprimer cette notification">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        `;
    }).join('');
}

document.addEventListener('click', async (e) => {
    const delBtn = e.target.closest('.nc-notif-delete');
    if (delBtn) {
        e.preventDefault();
        e.stopPropagation();
        const nid = delBtn.getAttribute('data-id');
        if (!nid) return;
        try {
            const r = await deleteNotification(nid);
            if (r.error) { console.error(r.error); return; }
            await loadNotifications();
        } catch (err) { console.error(err); }
        return;
    }

    const acceptBtn = e.target.closest('.nc-accept-request');
    if (acceptBtn) {
        e.preventDefault();
        e.stopPropagation();
        const uid = acceptBtn.getAttribute('data-user');
        if (!uid) return;
        try {
            const form = new FormData();
            form.append('requester_id', uid);
            await fetch(`<?= $basePath ?>/message_request_accept.php`, { method: 'POST', body: form });
            await loadNotifications();
        } catch (err) { console.error(err); }
        return;
    }

    const item = e.target.closest('.nc-notif-item');
    if (item && item.dataset.id) {
        // Marquer lu en background, sans empêcher la navigation
        try { await markRead(item.dataset.id); } catch (err) { /* ignore */ }
    }
});

document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
    try {
        await markAllRead();
        await loadNotifications();
    } catch (e) { console.error(e); }
});

document.getElementById('deleteAllNotifBtn')?.addEventListener('click', async () => {
    if (!confirm('Supprimer toutes les notifications ? Cette action est irréversible.')) return;
    try {
        const r = await deleteAllNotifications();
        if (r.error) { console.error(r.error); return; }
        await loadNotifications();
    } catch (e) { console.error(e); }
});

loadNotifications();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

