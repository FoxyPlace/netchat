<?php
if (!isset($basePath)) {
    $basePath = '/netchat/public';
}
$title = 'Chat - NetChat';
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
                    <a href="<?= $basePath ?>/chat" class="nav-link active">
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
            <h5>Chat</h5>
        </div>

        <div class="px-3 py-3">
            <div class="netchat-card p-0 overflow-hidden">
                <div class="nc-chat-grid">
                    <section class="nc-chat-left">
                        <div class="nc-chat-left-header">
                            <div class="fw-bold">Messages</div>
                            <button id="refreshConvos" class="btn btn-sm btn-outline-primary">Rafraîchir</button>
                        </div>
                        <div id="convoList" class="nc-chat-convos">
                            <div class="p-4 text-muted">Chargement…</div>
                        </div>
                    </section>

                    <section class="nc-chat-right">
                        <div id="chatHeader" class="nc-chat-header">
                            <div class="text-muted">Sélectionne une conversation</div>
                        </div>

                        <div id="chatGate" class="nc-chat-gate d-none"></div>

                        <div id="chatMessages" class="nc-chat-messages"></div>

                        <form id="chatSendForm" class="nc-chat-send" autocomplete="off">
                            <input type="hidden" id="chatTargetId" value="<?= (int)($open_user_id ?? 0) ?>">
                            <input id="chatInput" class="form-control" placeholder="Écrire un message…" />
                            <button id="chatSendBtn" class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const basePath = <?= json_encode($basePath) ?>;
const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function relativeTime(dateStr) {
    if (!dateStr) return '';
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

let activeUserId = parseInt(document.getElementById('chatTargetId').value || '0', 10) || 0;
let pollTimer = null;
let chatState = null;

function setActiveConversation(userId, username, picture) {
    activeUserId = userId;
    document.getElementById('chatTargetId').value = String(userId);
    const header = document.getElementById('chatHeader');
    header.innerHTML = `
        <a class="d-flex align-items-center gap-2 text-decoration-none" href="${basePath}/profile?id=${encodeURIComponent(userId)}">
            <img class="nc-chat-avatar" src="${basePath}/${picture || 'assets/user_icon.png'}" alt="">
            <div class="fw-bold">${escapeHtml(username)}</div>
        </a>
        <div class="text-muted small">En ligne</div>
    `;
    refreshChatGate().then(() => loadMessages(true));
    startPolling();
}

async function refreshChatGate() {
    const gate = document.getElementById('chatGate');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    if (!gate || !input || !sendBtn) return;

    if (!activeUserId) {
        gate.classList.add('d-none');
        gate.innerHTML = '';
        input.disabled = true;
        sendBtn.disabled = true;
        chatState = null;
        return;
    }

    const res = await fetch(`${basePath}/api_chat_status.php?user=${encodeURIComponent(activeUserId)}`);
    const st = await res.json();
    if (st.error) {
        gate.classList.remove('d-none');
        gate.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(st.error)}</div>`;
        input.disabled = true;
        sendBtn.disabled = true;
        chatState = null;
        return;
    }

    chatState = st;

    if (st.can_chat) {
        gate.classList.add('d-none');
        gate.innerHTML = '';
        input.placeholder = 'Écrire un message…';
        input.disabled = false;
        sendBtn.disabled = false;
        return;
    }

    // Pas encore autorisé à voir l'historique tant que pas follow mutuel / acceptation
    gate.classList.remove('d-none');

    if (st.request_in_status === 'pending') {
        gate.innerHTML = `
            <div class="nc-chat-gate-inner">
                <div class="fw-bold">Demande de message</div>
                <div class="text-muted small">Cette personne veut te parler. Accepte pour débloquer la discussion.</div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-primary" id="acceptIncomingRequestBtn">Accepter</button>
                    <a class="btn btn-sm btn-outline-primary" href="${basePath}/notifications">Voir les notifications</a>
                </div>
            </div>
        `;
        input.disabled = true;
        sendBtn.disabled = true;
        return;
    }

    if (st.request_out_status === 'pending') {
        gate.innerHTML = `
            <div class="nc-chat-gate-inner">
                <div class="fw-bold">Demande envoyée</div>
                <div class="text-muted small">Tu pourras envoyer des messages une fois que la personne aura accepté (ou si vous vous suivez mutuellement).</div>
            </div>
        `;
        input.disabled = true;
        sendBtn.disabled = true;
        return;
    }

    if (!st.you_follow) {
        gate.innerHTML = `
            <div class="nc-chat-gate-inner">
                <div class="fw-bold">Abonnement requis</div>
                <div class="text-muted small">Tu dois suivre cette personne pour lui écrire.</div>
                <div class="d-flex gap-2 mt-2">
                    <a class="btn btn-sm btn-primary" href="${basePath}/profile?id=${encodeURIComponent(activeUserId)}">Voir le profil</a>
                </div>
            </div>
        `;
        input.disabled = true;
        sendBtn.disabled = true;
        return;
    }

    gate.innerHTML = `
        <div class="nc-chat-gate-inner">
            <div class="fw-bold">Discussion verrouillée</div>
            <div class="text-muted small">Écris un premier message : une <strong>demande d’acceptation</strong> sera envoyée.</div>
        </div>
    `;
    input.disabled = false;
    sendBtn.disabled = false;
    input.placeholder = 'Premier message (demande)…';
}

async function loadConversations() {
    const list = document.getElementById('convoList');
    const res = await fetch(`${basePath}/api_conversations.php?limit=50`);
    const data = await res.json();
    if (data.error) {
        list.innerHTML = `<div class="p-4 text-danger">${escapeHtml(data.error)}</div>`;
        return;
    }

    const items = data.conversations || [];
    if (!items.length) {
        list.innerHTML = `<div class="p-5 text-center text-muted">Aucune conversation</div>`;
        return;
    }

    list.innerHTML = items.map(c => {
        const isActive = activeUserId && (parseInt(c.other_user_id, 10) === activeUserId);
        const pic = c.profile_picture || 'assets/user_icon.png';
        const unread = parseInt(c.unread_count || '0', 10) || 0;
        return `
            <button class="nc-convo ${isActive ? 'active' : ''}" data-user="${c.other_user_id}" data-username="${escapeHtml(c.username)}" data-pic="${escapeHtml(pic)}" type="button">
                <img class="nc-convo-avatar" src="${basePath}/${pic}" alt="">
                <div class="nc-convo-body">
                    <div class="nc-convo-top">
                        <div class="nc-convo-name">${escapeHtml(c.username)}</div>
                        <div class="nc-convo-time">${relativeTime(c.last_at)}</div>
                    </div>
                    <div class="nc-convo-last">${escapeHtml(c.last_message || '')}</div>
                </div>
                ${unread ? `<span class="nc-convo-badge">${unread}</span>` : ''}
            </button>
        `;
    }).join('');
}

async function loadMessages(scrollBottom = false) {
    const box = document.getElementById('chatMessages');
    if (!activeUserId) {
        box.innerHTML = `<div class="p-5 text-center text-muted">Sélectionne une conversation</div>`;
        return;
    }

    await refreshChatGate();

    const res = await fetch(`${basePath}/api_messages.php?user=${encodeURIComponent(activeUserId)}&limit=80`);
    const data = await res.json();
    if (data.error) {
        box.innerHTML = `<div class="p-4 text-danger">${escapeHtml(data.error)}</div>`;
        return;
    }

    if (data.locked) {
        box.innerHTML = `<div class="p-5 text-center text-muted">Les messages apparaîtront ici une fois la discussion débloquée.</div>`;
        return;
    }

    const items = data.messages || [];
    if (!items.length) {
        box.innerHTML = `<div class="p-5 text-center text-muted">Aucun message</div>`;
        return;
    }

    box.innerHTML = items.map(m => {
        const mine = parseInt(m.sender_id, 10) === currentUserId;
        const senderName = mine ? 'Vous' : (m.sender_username || 'Inconnu');
        const senderPic = mine ? '' : (m.sender_profile_picture ? `${basePath}/${m.sender_profile_picture}` : `${basePath}/assets/user_icon.png`);
        const showSender = !mine;
        return `
            <div class="nc-msg-row ${mine ? 'mine' : ''}">
                ${showSender ? `
                <div class="nc-msg-sender">
                    <img class="nc-msg-avatar" src="${senderPic}" alt="">
                    <span class="nc-msg-sender-name">${escapeHtml(senderName)}</span>
                </div>
                ` : ''}
                <div class="nc-msg">
                    <div class="nc-msg-text">${escapeHtml(m.content || '')}</div>
                    <div class="nc-msg-time">${relativeTime(m.created_at)}</div>
                </div>
            </div>
        `;
    }).join('');

    if (scrollBottom) {
        box.scrollTop = box.scrollHeight;
    }

    // marquer lu
    try {
        if (chatState && chatState.can_chat) {
            const form = new FormData();
            form.append('user', String(activeUserId));
            await fetch(`${basePath}/message_read.php`, { method: 'POST', body: form });
        }
    } catch (e) {}
}

async function sendMessage(text) {
    const form = new FormData();
    form.append('receiver_id', String(activeUserId));
    form.append('content', text);
    const res = await fetch(`${basePath}/message_send.php`, { method: 'POST', body: form });
    return await res.json();
}

function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => {
        loadMessages(false);
        loadConversations();
    }, 3000);
}

document.getElementById('refreshConvos')?.addEventListener('click', () => loadConversations());

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.nc-convo');
    if (!btn) return;
    const uid = parseInt(btn.dataset.user || '0', 10) || 0;
    const username = btn.dataset.username || '';
    const pic = btn.dataset.pic || 'assets/user_icon.png';
    if (uid) setActiveConversation(uid, username, pic);
});

document.getElementById('chatSendForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('chatInput');
    const text = (input.value || '').trim();
    if (!activeUserId || !text) return;
    input.value = '';
    const data = await sendMessage(text);
    if (data.error) {
        console.error(data.error);
        return;
    }
    if (data.requested) {
        await refreshChatGate();
        await loadMessages(true);
        await loadConversations();
        return;
    }
    await loadMessages(true);
    await loadConversations();
});

document.addEventListener('click', async (e) => {
    const sendReq = e.target.closest('#sendRequestBtn');
    if (sendReq) {
        e.preventDefault();
        if (!activeUserId) return;
        try {
            const form = new FormData();
            form.append('target_id', String(activeUserId));
            const res = await fetch(`${basePath}/message_request_send.php`, { method: 'POST', body: form });
            const j = await res.json();
            if (j.error) { alert(j.error); return; }
            await refreshChatGate();
            await loadConversations();
        } catch (err) { console.error(err); }
        return;
    }

    const acceptIncoming = e.target.closest('#acceptIncomingRequestBtn');
    if (acceptIncoming) {
        e.preventDefault();
        if (!activeUserId) return;
        try {
            const form = new FormData();
            form.append('requester_id', String(activeUserId));
            const res = await fetch(`${basePath}/message_request_accept.php`, { method: 'POST', body: form });
            const j = await res.json();
            if (j.error) { alert(j.error); return; }
            await refreshChatGate();
            await loadMessages(true);
            await loadConversations();
        } catch (err) { console.error(err); }
    }
});

// init
loadConversations().then(async () => {
    <?php if (!empty($open_user) && !empty($open_user_id)): ?>
        setActiveConversation(
            <?= (int)$open_user_id ?>,
            <?= json_encode($open_user['username'] ?? '') ?>,
            <?= json_encode($open_user['profile_picture'] ?? 'assets/user_icon.png') ?>
        );
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

