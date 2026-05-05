<?php
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Panel administrateur - NetChat';
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
        </div>
    </aside>

    <main class="nc-main-column">
        <div class="nc-main-header">
            <h5>Panel administrateur</h5>
        </div>

        <div class="px-4 py-4">
            <!-- Admin Tabs -->
            <ul class="nav nav-tabs admin-tabs mb-4" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="true">
                        <i class="fas fa-flag me-2"></i>Signalements
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                        <i class="fas fa-users-cog me-2"></i>Gestion utilisateurs
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="adminTabContent">
                <!-- Reports Tab -->
                <div class="tab-pane fade show active" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                    <div class="admin-tab-content">
                        <div class="row gx-4">
                            <div class="col-lg-4">
                                <div class="netchat-card p-3" style="max-height:72vh;overflow:auto;">
                                    <h6>Signalements récents</h6>
                                    <div id="reportsList" class="mt-3">
                                        <p class="text-muted">Chargement...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="netchat-card p-3" id="reportDetail">
                                    <h6>Détails</h6>
                                    <div id="reportDetailContent" class="mt-3 text-muted">Sélectionnez un signalement pour voir les détails.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Tab -->
                <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                    <div class="admin-tab-content">
                        <div class="row gx-4">
                            <div class="col-lg-4">
                                <div class="netchat-card p-3" style="max-height:72vh;overflow:auto;">
                                    <h6>Utilisateurs</h6>
                                    <div id="usersList" class="mt-3">
                                        <p class="text-muted">Chargement...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="netchat-card p-3" id="userDetail">
                                    <h6>Détails de l'utilisateur</h6>
                                    <div id="userDetailContent" class="mt-3 text-muted">Sélectionnez un utilisateur pour voir les détails.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const basePathAdmin = '<?= $basePath ?>';
let reportsCache = [];
let selectedReport = null;
let usersCache = [];
let selectedUser = null;
let currentUserRole = '<?= $user['account_type'] ?? 'user' ?>';

// Fonctions pour les signalements (existantes)
function statusBadge(status) {
    if (status === 'accepted') return '<span class="badge bg-success">Accepté</span>';
    if (status === 'rejected') return '<span class="badge bg-danger">Rejeté</span>';
    return '<span class="badge bg-warning text-dark">En attente</span>';
}

// Fonctions pour les signalements existantes
async function loadReports() {
    const el = document.getElementById('reportsList');
    el.innerHTML = '<p class="text-muted">Chargement...</p>';
    try {
        const res = await fetch(`${basePathAdmin}/api_reports.php?limit=200`);
        const j = await res.json();
        if (j.error) { el.innerHTML = `<div class="text-danger">${escapeHtml(j.error)}</div>`; return; }
        reportsCache = j.reports || [];
        if (!reportsCache.length) { el.innerHTML = '<p class="text-muted">Aucun signalement</p>'; return; }

        el.innerHTML = reportsCache.map(r => {
            const badge = (r.status ? statusBadge(r.status) : statusBadge('pending'));
            const title = r.post_content ? (r.post_content.substring(0,80) + (r.post_content.length > 80 ? '...' : '')) : 'Post supprimé ou non disponible';
            return `
                <div class="admin-report-item p-2 mb-2" data-id="${r.id}" style="border:1px solid #eee;border-radius:6px;cursor:pointer;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="small fw-semibold">${escapeHtml(title)}</div>
                        <div>${badge}</div>
                    </div>
                    <div class="small text-muted">Signalé par ${escapeHtml(r.reporter_username || 'Inconnu')} • ${escapeHtml(r.created_at)}</div>
                </div>
            `;
        }).join('');

        document.querySelectorAll('.admin-report-item').forEach(it => it.addEventListener('click', () => {
            const id = it.getAttribute('data-id');
            selectReport(id);
        }));
    } catch (e) {
        console.error(e);
        el.innerHTML = '<div class="text-danger">Erreur chargement</div>';
    }
}

// Fonctions pour la gestion des utilisateurs
function accountTypeBadge(accountType, isBanned) {
    if (isBanned) return '<span class="badge bg-danger">Banni</span>';
    if (accountType === 'administrator') return '<span class="badge bg-danger">Administrateur</span>';
    if (accountType === 'moderator') return '<span class="badge bg-warning text-dark">Modérateur</span>';
    return '<span class="badge bg-secondary">Utilisateur</span>';
}

async function loadUsers() {
    const el = document.getElementById('usersList');
    el.innerHTML = '<p class="text-muted">Chargement...</p>';
    try {
        const res = await fetch(`${basePathAdmin}/api_admin_users.php?limit=200`);
        const j = await res.json();
        if (j.error) { el.innerHTML = `<div class="text-danger">${j.error}</div>`; return; }
        usersCache = j.users || [];
        if (!usersCache.length) { el.innerHTML = '<p class="text-muted">Aucun utilisateur</p>'; return; }

        el.innerHTML = usersCache.map(u => {
            const badge = accountTypeBadge(u.account_type, u.is_banned);
            return `
                <div class="admin-user-item p-2 mb-2" data-id="${u.id}" style="border:1px solid #eee;border-radius:6px;cursor:pointer;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="small fw-semibold">${escapeHtml(u.username)}</div>
                        <div>${badge}</div>
                    </div>
                    <div class="small text-muted">${escapeHtml(u.email)} • ${u.status_display}</div>
                </div>
            `;
        }).join('');

        // attach click
        document.querySelectorAll('.admin-user-item').forEach(it => it.addEventListener('click', () => {
            const id = it.getAttribute('data-id');
            selectUser(id);
        }));
    } catch (e) {
        console.error(e);
        el.innerHTML = '<div class="text-danger">Erreur chargement</div>';
    }
}

function findUserById(id) {
    id = parseInt(id,10);
    return usersCache.find(u => parseInt(u.id,10) === id) || null;
}

function renderUserDetail(u) {
    const container = document.getElementById('userDetailContent');
    if (!u) { container.innerHTML = '<p class="text-muted">Sélectionnez un utilisateur.</p>'; return; }

    const isBanned = u.is_banned;
    const canModifyRoles = currentUserRole === 'administrator';
    const accountTypeOptions = canModifyRoles ? `
        <option value="user" ${u.account_type === 'user' ? 'selected' : ''}>Utilisateur</option>
        <option value="moderator" ${u.account_type === 'moderator' ? 'selected' : ''}>Modérateur</option>
        <option value="administrator" ${u.account_type === 'administrator' ? 'selected' : ''}>Administrateur</option>
    ` : `<option value="${u.account_type}" selected>${u.account_type_display}</option>`;

    container.innerHTML = `
        <div>
            <div class="d-flex align-items-center mb-3">
                <img src="${basePathAdmin}/${escapeHtml(u.profile_picture)}" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                <div>
                    <h5 class="mb-1">${escapeHtml(u.username)}</h5>
                    <p class="text-muted mb-0">${escapeHtml(u.email)}</p>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Statut :</strong> ${u.status_display}
                </div>
                <div class="col-md-6">
                    <strong>Grade actuel :</strong> ${accountTypeBadge(u.account_type, isBanned)}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong>Posts :</strong> ${u.posts_count || 0}
                </div>
                <div class="col-md-4">
                    <strong>Abonnés :</strong> ${u.followers_count || 0}
                </div>
                <div class="col-md-4">
                    <strong>Abonnements :</strong> ${u.following_count || 0}
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Inscrit le :</strong> ${new Date(u.created_at).toLocaleDateString('fr-FR')}
                </div>
                <div class="col-md-6">
                    <strong>Dernière connexion :</strong> ${u.last_login ? new Date(u.last_login).toLocaleDateString('fr-FR') : 'Jamais'}
                </div>
            </div>

            ${u.bio ? `<div class="mb-3"><strong>Bio :</strong><div class="text-muted small mt-1">${escapeHtml(u.bio)}</div></div>` : ''}

            <hr>

            <!-- Modifier le grade -->
            ${canModifyRoles ? `
            <div class="mb-4">
                <h6>Modifier le grade</h6>
                <div class="row">
                    <div class="col-md-6">
                        <select id="userAccountType" class="form-select">
                            ${accountTypeOptions}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button id="updateAccountTypeBtn" class="btn btn-outline-primary w-100">Mettre à jour</button>
                    </div>
                </div>
            </div>
            ` : ''}

            <!-- Envoyer une notification -->
            <div class="mb-4">
                <h6>Envoyer une notification</h6>
                <div class="mb-2">
                    <input type="text" id="notificationTitle" class="form-control" placeholder="Titre de la notification">
                </div>
                <div class="mb-2">
                    <textarea id="notificationMessage" class="form-control" rows="3" placeholder="Message de la notification"></textarea>
                </div>
                <button id="sendNotificationBtn" class="btn btn-outline-info w-100">Envoyer la notification</button>
            </div>

            <!-- Envoyer un message privé -->
            <div class="mb-4">
                <h6>Envoyer un message privé (de NETCHAT)</h6>
                <div class="mb-2">
                    <textarea id="privateMessage" class="form-control" rows="3" placeholder="Message privé"></textarea>
                </div>
                <button id="sendMessageBtn" class="btn btn-outline-success w-100">Envoyer le message</button>
            </div>

            <!-- Bannir l'utilisateur -->
            ${!isBanned ? `
            <div class="mb-4">
                <h6 class="text-danger">Bannir l'utilisateur</h6>
                <div class="mb-2">
                    <textarea id="banReason" class="form-control" rows="3" placeholder="Motif du bannissement (obligatoire)"></textarea>
                </div>
                <button id="banUserBtn" class="btn btn-outline-danger w-100">Bannir l'utilisateur</button>
            </div>
            ` : '<div class="alert alert-danger">Cet utilisateur est déjà banni.</div>'}

            <div id="userActionMessage" class="mt-3"></div>
        </div>
    `;

    // Attacher les événements
    if (canModifyRoles) {
        document.getElementById('updateAccountTypeBtn')?.addEventListener('click', () => updateUserAccountType(u.id));
    }
    document.getElementById('sendNotificationBtn')?.addEventListener('click', () => sendUserNotification(u.id));
    document.getElementById('sendMessageBtn')?.addEventListener('click', () => sendUserMessage(u.id));
    if (!isBanned) {
        document.getElementById('banUserBtn')?.addEventListener('click', () => banUser(u.id));
    }
}

function selectUser(id) {
    const u = findUserById(id);
    selectedUser = u;
    renderUserDetail(u);
}

async function updateUserAccountType(userId) {
    const msgEl = document.getElementById('userActionMessage');
    msgEl.innerHTML = '';
    const accountType = document.getElementById('userAccountType')?.value;

    if (!accountType) {
        msgEl.innerHTML = '<div class="alert alert-danger">Type de compte requis.</div>';
        return;
    }

    const form = new FormData();
    form.append('action', 'update_account_type');
    form.append('user_id', userId);
    form.append('account_type', accountType);

    try {
        const res = await fetch(`${basePathAdmin}/api_admin_user_actions.php`, { method: 'POST', body: form });
        const j = await res.json();
        if (j.error) { msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`; return; }
        msgEl.innerHTML = `<div class="alert alert-success">${escapeHtml(j.message)}</div>`;
        await loadUsers(); // Recharger la liste
    } catch (e) {
        console.error(e);
        msgEl.innerHTML = '<div class="alert alert-danger">Erreur serveur</div>';
    }
}

async function sendUserNotification(userId) {
    const msgEl = document.getElementById('userActionMessage');
    msgEl.innerHTML = '';
    const title = document.getElementById('notificationTitle')?.value.trim();
    const message = document.getElementById('notificationMessage')?.value.trim();

    if (!title || !message) {
        msgEl.innerHTML = '<div class="alert alert-danger">Titre et message requis.</div>';
        return;
    }

    const form = new FormData();
    form.append('action', 'send_notification');
    form.append('user_id', userId);
    form.append('title', title);
    form.append('message', message);

    try {
        const res = await fetch(`${basePathAdmin}/api_admin_user_actions.php`, { method: 'POST', body: form });
        const j = await res.json();
        if (j.error) { msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`; return; }
        msgEl.innerHTML = `<div class="alert alert-success">${escapeHtml(j.message)}</div>`;
        // Vider les champs
        document.getElementById('notificationTitle').value = '';
        document.getElementById('notificationMessage').value = '';
    } catch (e) {
        console.error(e);
        msgEl.innerHTML = '<div class="alert alert-danger">Erreur serveur</div>';
    }
}

async function sendUserMessage(userId) {
    const msgEl = document.getElementById('userActionMessage');
    msgEl.innerHTML = '<div class="alert alert-info">Envoi en cours...</div>';
    const message = document.getElementById('privateMessage')?.value.trim();

    if (!message) {
        msgEl.innerHTML = '<div class="alert alert-danger">Message requis.</div>';
        return;
    }

    const form = new FormData();
    form.append('action', 'send_message');
    form.append('user_id', userId);
    form.append('message', message);

    try {
        const res = await fetch(`${basePathAdmin}/api_admin_user_actions.php`, { method: 'POST', body: form });
        const j = await res.json();
        if (j.error) { msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`; return; }
        msgEl.innerHTML = `<div class="alert alert-success">${escapeHtml(j.message)}</div>`;
        document.getElementById('privateMessage').value = '';
    } catch (e) {
        console.error(e);
        msgEl.innerHTML = '<div class="alert alert-danger">Erreur serveur</div>';
    }
}

async function banUser(userId) {
    const msgEl = document.getElementById('userActionMessage');
    msgEl.innerHTML = '';
    const banReason = document.getElementById('banReason')?.value.trim();

    if (!banReason) {
        msgEl.innerHTML = '<div class="alert alert-danger">Motif de bannissement requis.</div>';
        return;
    }

    if (!confirm('Êtes-vous sûr de vouloir bannir cet utilisateur ? Cette action est irréversible.')) {
        return;
    }

    const form = new FormData();
    form.append('action', 'ban_user');
    form.append('user_id', userId);
    form.append('ban_reason', banReason);

    try {
        const res = await fetch(`${basePathAdmin}/api_admin_user_actions.php`, { method: 'POST', body: form });
        const j = await res.json();
        if (j.error) { msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`; return; }
        msgEl.innerHTML = `<div class="alert alert-success">${escapeHtml(j.message)}</div>`;
        await loadUsers(); // Recharger la liste
        renderUserDetail(findUserById(userId)); // Mettre à jour les détails
    } catch (e) {
        console.error(e);
        msgEl.innerHTML = '<div class="alert alert-danger">Erreur serveur</div>';
    }
}

// Charger les utilisateurs quand l'onglet est activé
document.getElementById('users-tab')?.addEventListener('shown.bs.tab', () => {
    loadUsers();
});

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function findReportById(id) {
    id = parseInt(id,10);
    return reportsCache.find(r => parseInt(r.id,10) === id) || null;
}

function renderDetail(r) {
    const container = document.getElementById('reportDetailContent');
    if (!r) { container.innerHTML = '<p class="text-muted">Sélectionnez un signalement.</p>'; return; }

    const reportedUser = r.reported_username ? escapeHtml(r.reported_username) : 'Inconnu';
    const reporter = r.reporter_username ? escapeHtml(r.reporter_username) : 'Inconnu';
    const postContent = r.post_content ? escapeHtml(r.post_content) : '<em>Post non disponible</em>';
    const postImage = r.post_image ? `<div class="mt-2"><img src="${basePathAdmin}/${escapeHtml(r.post_image)}" style="max-width:100%;height:auto;border-radius:6px;"></div>` : '';
    const decisionReason = r.decision_reason ? escapeHtml(r.decision_reason) : '';

    const isAccepted = (r.status === 'accepted');
    container.innerHTML = `
        <div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Post : </div>
                    <div class="text-muted small">${postContent}</div>
                </div>
                <div>${statusBadge(r.status)}</div>
            </div>
            ${postImage}
            <hr>
            <div><strong>Signalé par :</strong> ${reporter}</div>
            <div><strong>Utilisateur signalé :</strong> ${reportedUser}</div>
            <div class="mt-2"><strong>Motif du signalement :</strong><div class="text-muted small mt-1">${escapeHtml(r.reason)}</div></div>
            <hr>
            <div>
                <label class="form-label fw-semibold">Motif / justification (requis pour accepter ou rejeter)</label>
                <textarea id="adminDecisionReason" class="form-control" rows="3">${decisionReason}</textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button id="adminUpdateBtn" class="btn btn-outline-secondary" ${isAccepted ? 'disabled' : ''}>Modifier motif</button>
                <button id="adminRejectBtn" class="btn btn-danger" ${isAccepted ? 'disabled' : ''}>Rejeter</button>
                <button id="adminAcceptBtn" class="btn btn-success" ${isAccepted ? 'disabled' : ''}>Accepter</button>
            </div>
            <div id="adminActionMessage" class="mt-2"></div>
        </div>
    `;

    // attach actions (désactivés si accepté)
    if (!isAccepted) {
        document.getElementById('adminAcceptBtn').addEventListener('click', () => processReport(r.id, 'accept'));
        document.getElementById('adminRejectBtn').addEventListener('click', () => processReport(r.id, 'reject'));
        document.getElementById('adminUpdateBtn').addEventListener('click', () => processReport(r.id, 'update'));
    } else {
        // rendre textarea readonly
        const ta = document.getElementById('adminDecisionReason');
        if (ta) ta.setAttribute('readonly', 'readonly');
    }
}

function selectReport(id) {
    const r = findReportById(id);
    selectedReport = r;
    renderDetail(r);
}

async function processReport(id, action) {
    const msgEl = document.getElementById('adminActionMessage');
    msgEl.innerHTML = '';
    const reason = document.getElementById('adminDecisionReason')?.value.trim() || '';
    if ((action === 'accept' || action === 'reject' || action === 'update') && reason === '') {
        msgEl.innerHTML = '<div class="alert alert-danger">Le motif est requis.</div>';
        return;
    }

    const form = new FormData();
    form.append('report_id', id);
    form.append('action', action);
    form.append('decision_reason', reason);

    try {
        const res = await fetch(`${basePathAdmin}/report_process.php`, { method: 'POST', body: form });
        const j = await res.json();
        if (j.error) { msgEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(j.error)}</div>`; return; }
        msgEl.innerHTML = `<div class="alert alert-success">Action effectuée.</div>`;
        // reload list
        await loadReports();
        // re-select same report if still present
        const found = findReportById(id);
        selectedReport = found;
        renderDetail(found);
    } catch (e) {
        console.error(e);
        msgEl.innerHTML = '<div class="alert alert-danger">Erreur serveur</div>';
    }
}

document.addEventListener('DOMContentLoaded', () => loadReports());
</script>
    </main>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
