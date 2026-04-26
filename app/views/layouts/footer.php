    <?php
    // Utiliser le même $basePath que dans header.php
    if (!isset($basePath)) {
        $basePath = '/netchat/public';
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mode "PC fenêtre étroite": compacte la sidebar (icônes) pour garder un layout proche du plein écran.
        (function () {
            function setCompactNav() {
                if (window.innerWidth < 992) {
                    document.body.classList.remove('nc-compact-nav');
                    return;
                }
                const narrow = window.matchMedia('(max-width: 1199.98px)').matches;
                document.body.classList.toggle('nc-compact-nav', narrow);
            }
            setCompactNav();
            window.addEventListener('resize', setCompactNav);
            const mq = window.matchMedia('(max-width: 1199.98px)');
            if (mq && mq.addEventListener) mq.addEventListener('change', setCompactNav);
            else if (mq && mq.addListener) mq.addListener(setCompactNav);
        })();
    </script>
    <script>
        // Badge notifications (sidebar) sur toutes les pages qui ont la nav
        (function () {
            const basePath = <?= json_encode($basePath) ?>;

            function findNotifLinks() {
                return Array.from(document.querySelectorAll('.nc-sidebar-nav a[href$="/notifications"], .nc-sidebar-nav a[href*="/notifications"]'));
            }

            function ensureBadge(link) {
                let b = link.querySelector('.nc-nav-badge');
                if (!b) {
                    b = document.createElement('span');
                    b.className = 'nc-nav-badge d-none';
                    b.setAttribute('aria-label', 'Notifications non lues');
                    link.appendChild(b);
                }
                return b;
            }

            async function refreshUnread() {
                const links = findNotifLinks();
                if (!links.length) return;

                try {
                    const res = await fetch(`${basePath}/api_notifications_count.php`, { cache: 'no-store' });
                    const data = await res.json();
                    if (!data || typeof data.unread === 'undefined') return;

                    const unread = parseInt(data.unread, 10) || 0;
                    links.forEach(link => {
                        const b = ensureBadge(link);
                        if (unread > 0) {
                            b.textContent = unread > 99 ? '99+' : String(unread);
                            b.classList.remove('d-none');
                        } else {
                            b.classList.add('d-none');
                            b.textContent = '';
                        }
                    });
                } catch (e) {
                    // ignore
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                refreshUnread();
                setInterval(refreshUnread, 30000);
            });
        })();
    </script>
    <script src="<?= $basePath ?>/script.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?= $basePath ?>/<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
