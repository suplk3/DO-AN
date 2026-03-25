<?php
$header_path = 'c:/xampp/htdocs/doan/DO-AN/user/components/header.php';

$append_js = <<<EOT

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pcNotif = document.querySelector('.notif-link.pc-only');
    const mobNotifIcon = document.querySelector('.mobile-nav-item[href="notifications.php"] .m-icon');
    
    if (!pcNotif && !mobNotifIcon) return;
    
    function updateNotifBadges(count) {
        if (pcNotif) {
            let badge = pcNotif.querySelector('.notif-badge');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notif-badge';
                    pcNotif.appendChild(badge);
                }
                badge.innerText = count;
            } else {
                if (badge) badge.remove();
            }
        }
        
        if (mobNotifIcon) {
            let mBadge = mobNotifIcon.querySelector('.m-badge');
            if (count > 0) {
                if (!mBadge) {
                    mBadge = document.createElement('em');
                    mBadge.className = 'm-badge';
                    mobNotifIcon.appendChild(mBadge);
                }
            } else {
                if (mBadge) mBadge.remove();
            }
        }
    }
    
    function pollNotifications() {
        fetch('../api/notif_poll_api.php?action=poll')
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    updateNotifBadges(res.unread_count);
                    window.dispatchEvent(new CustomEvent('notifications_polled', { detail: res }));
                }
            })
            .catch(e => console.error('Notif poll error:', e));
    }
    
    setInterval(pollNotifications, 10000);
});
</script>
EOT;

file_put_contents($header_path, $append_js, FILE_APPEND);
echo "Successfully appended polling JS to header.php\n";
