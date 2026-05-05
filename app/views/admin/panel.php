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
    </main>
</div>

<script>
const basePathAdmin = '<?= $basePath ?>';
let reportsCache = [];
let selectedReport = null;

function statusBadge(status) {
    if (status === 'accepted') return '<span class="badge bg-success">Accepté</span>';
    if (status === 'rejected') return '<span class="badge bg-danger">Rejeté</span>';
    return '<span class="badge bg-warning text-dark">En attente</span>';
}

async function loadReports() {
    const el = document.getElementById('reportsList');
    el.innerHTML = '<p class="text-muted">Chargement...</p>';
    try {
        const res = await fetch(`${basePathAdmin}/api_reports.php?limit=200`);
        const j = await res.json();
        if (j.error) { el.innerHTML = `<div class="text-danger">${j.error}</div>`; return; }
        reportsCache = j.reports || [];
        if (!reportsCache.length) { el.innerHTML = '<p class="text-muted">Aucun signalement</p>'; return; }

        el.innerHTML = reportsCache.map(r => {
            const badge = (r.status ? statusBadge(r.status) : statusBadge('pending'));
            const title = r.post_content ? (r.post_content.substring(0,80) + (r.post_content.length>80? '...' : '')) : 'Post supprimé ou non disponible';
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

        // attach click
        document.querySelectorAll('.admin-report-item').forEach(it => it.addEventListener('click', () => {
            const id = it.getAttribute('data-id');
            selectReport(id);
        }));
    } catch (e) {
        console.error(e);
        el.innerHTML = '<div class="text-danger">Erreur chargement</div>';
    }
}

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
