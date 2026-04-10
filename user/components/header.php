<?php
// Tự xác định trang đang active, mặc định là rỗng
if (!isset($active_page)) {
    $active_page = '';
}
?>
<!-- Theme Scripts -->
<link rel="stylesheet" href="../assets/css/theme-toggle.css">
<script>
(function(){
  var stored = localStorage.getItem('theme') || 'dark';
  document.documentElement.setAttribute('data-theme', stored);
})();
document.addEventListener('DOMContentLoaded', function() {
  var b = document.body;
  if (!b) return;
  var stored = localStorage.getItem('theme') || 'dark';
  b.setAttribute('data-theme', stored);
  
  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', function(){
      var cur = b.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      b.setAttribute('data-theme', cur);
      document.documentElement.setAttribute('data-theme', cur);
      localStorage.setItem('theme', cur);
    });
  }
});
</script>
<style>
/* ==== MOBILE-ONLY HIDES (inline to bypass cache) ==== */
@media (max-width: 768px) {
    .header-nav-left       { display: none !important; }
    .pc-only               { display: none !important; }
    
    /* Completely hide ALL PC elements (Search, Theme, Notif, User Menu) on mobile */
    .header-nav-right > * { 
        display: none !important; 
    }

    /* Bottom nav always visible on mobile */
    .mobile-nav-bar {
        display: flex !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 9999 !important;
        background: rgba(10,11,18,0.95) !important;
        flex-direction: row !important;
        border-top: 1px solid rgba(255,255,255,0.1) !important;
        height: 64px !important;
        padding-bottom: env(safe-area-inset-bottom, 0) !important;
    }
}
/* Bottom nav hidden on desktop */
@media (min-width: 769px) {
    .mobile-nav-bar          { display: none !important; }
}
</style>
<!-- ── Header ── -->
<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">TTVH</a>
        <nav class="header-nav">
            <div class="header-nav-left">
                <a href="index.php" class="nav-link <?= $active_page === 'phim' ? 'active' : '' ?>">
                    <span class="icon">&#127916;</span>
                    <span class="text">PHIM</span>
                </a>
                <a href="sap_chieu.php" class="nav-link <?= $active_page === 'sap_chieu' ? 'active' : '' ?>">
                    <span class="icon">&#128197;</span>
                    <span class="text">SẮP CHIẾU</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="social.php" class="nav-link <?= $active_page === 'social' ? 'active' : '' ?>">
                    <span class="icon">&#128101;</span>
                    <span class="text">CỘNG ĐỒNG</span>
                </a>
                <?php endif; ?>
            </div>
            <div class="search-wrap" id="searchWrap">
                <input type="text" id="searchInput" class="search-bar"
                       placeholder="Tìm phim, thể loại..." autocomplete="off">
                <span class="search-icon">&#128269;</span>
                <span class="search-spinner"></span>
                <div class="search-dropdown" id="searchDropdown"></div>
            </div>
            <div class="header-nav-right">
                <button class="header-search-trigger" id="mobileSearchTrigger">&#128269;</button>
                <button class="theme-toggle-btn" id="themeToggle" title="Đổi giao diện">&#127916;</button>
                <?php if (isset($_SESSION['user_id'])):
                    $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
                    $ten = htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'Tôi'));
                    
                    // Fixed unsafe fetching
                    $avatar_res = mysqli_query($conn, "SELECT avatar FROM users WHERE id=".(int)$_SESSION['user_id']);
                    $avatar_sql = $avatar_res ? mysqli_fetch_assoc($avatar_res) : null;
                    $avatar = $avatar_sql['avatar'] ?? null;
                    
                    if (!isset($notif_unread)) {
                        $notif_unread = 0;
                        if (table_exists($conn, 'notifications')) {
                            $uid = (int)$_SESSION['user_id'];
                            $nr_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0");
                            $nr = $nr_res ? mysqli_fetch_assoc($nr_res) : null;
                            $notif_unread = (int)($nr['c'] ?? 0);
                        }
                    }
                ?>
                <a href="notifications.php" class="notif-link pc-only">&#128276;
                    <?php if ($notif_unread > 0): ?><span class="notif-badge"><?= $notif_unread ?></span><?php endif; ?>
                </a>
                <div class="user-menu-wrap">
                    <button class="user-menu-btn" id="userMenuBtn">
                        <?php if ($avatar): ?>
                            <img src="../assets/images/avatars/<?= htmlspecialchars($avatar) ?>" class="user-menu-avatar" alt="">
                        <?php else: ?>
                            <div class="user-menu-initial"><?= mb_substr($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'U'), 0, 1) ?></div>
                        <?php endif; ?>
                        <span class="user-menu-name pc-only"><?= $ten ?></span>
                        <span class="user-menu-arrow pc-only">&#9662;</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <span><?= $ten ?></span>
                            <?php if ($is_admin): ?><span class="user-badge-admin">Admin</span><?php endif; ?>
                        </div>
                        <a href="profile.php?id=<?= (int)$_SESSION['user_id'] ?>" class="user-dropdown-item">&#128100; Trang cá nhân</a>
                        <a href="../user/ve_cua_toi.php" class="user-dropdown-item">&#127903; Vé của tôi</a>
                        <?php if ($is_admin): ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="../admin/dashboard.php" class="user-dropdown-item">&#128202; Dashboard</a>
                        <a href="../admin/phim.php" class="user-dropdown-item">&#127916; Quản lý phim</a>
                        <a href="../admin/suat_chieu.php" class="user-dropdown-item">&#128197; Quản lý suất chiếu</a>
                        <a href="../admin/quan_ly_user.php" class="user-dropdown-item">&#128101; Quản lý user</a>
                        <a href="../admin/quan_ly_tin_nhan.php" class="user-dropdown-item">&#128172; Quản lý tin nhắn</a>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="../auth/logout.php" class="user-dropdown-item danger"
                           onclick="return confirm('Đăng xuất?')">&#128682; Đăng xuất</a>
                    </div>
                </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-sm open-login-modal">
                        <span class="icon">&#128272;</span>
                        <span class="text">ĐĂNG NHẬP</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<!-- ── Mobile Bottom Navigation ── -->
<nav class="mobile-nav-bar">
    <a href="index.php" class="mobile-nav-item <?= $active_page === 'phim' ? 'active' : '' ?>">
        <span class="m-icon">&#127916;</span>
        <span class="m-text">Phim</span>
    </a>
    <a href="sap_chieu.php" class="mobile-nav-item <?= $active_page === 'sap_chieu' ? 'active' : '' ?>">
        <span class="m-icon">&#128197;</span>
        <span class="m-text">Sắp chiếu</span>
    </a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="social.php" class="mobile-nav-item <?= $active_page === 'social' ? 'active' : '' ?>">
        <span class="m-icon">&#128101;</span>
        <span class="m-text">Cộng đồng</span>
    </a>
    <a href="notifications.php" class="mobile-nav-item <?= $active_page === 'notifications' ? 'active' : '' ?>">
        <span class="m-icon">&#128276;<?php if (($notif_unread ?? 0) > 0): ?><em class="m-badge"></em><?php endif; ?></span>
        <span class="m-text">Thông báo</span>
    </a>
    <a href="ve_cua_toi.php" class="mobile-nav-item <?= $active_page === 've_cua_toi' ? 'active' : '' ?>">
        <span class="m-icon">&#127903;</span>
        <span class="m-text">Vé</span>
    </a>
    <a href="profile.php?id=<?= (int)$_SESSION['user_id'] ?>" class="mobile-nav-item <?= $active_page === 'profile' ? 'active' : '' ?>">
        <span class="m-icon">&#128100;</span>
        <span class="m-text">Tôi</span>
    </a>
    <?php else: ?>
    <a href="../auth/login.php" class="mobile-nav-item open-login-modal">
        <span class="m-icon">&#128272;</span>
        <span class="m-text">Đăng nhập</span>
    </a>
    <?php endif; ?>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pcNotif = document.querySelector('.notif-link.pc-only');
    const mobNotifIcon = document.querySelector('.mobile-nav-item[href="notifications.php"] .m-icon');
    const POLL_INTERVAL_MS = 1000;
    let notifPollTimer = null;
    let notifPollInFlight = false;
    let lastProfilePollTs = Math.floor(Date.now() / 1000) - 2;
    const currentUserId = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0 ?>;
    let appliedProfiles = new Set();
    
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
    
    function updateProfileOnPage(p) {
        const cacheKey = p.id + '-' + p.ts;
        if (appliedProfiles.has(cacheKey)) return;
        appliedProfiles.add(cacheKey);

        window.dispatchEvent(new CustomEvent('profile_updated', { detail: p }));

        // Update basic links wrapping avatars and names
        document.querySelectorAll(`a[href*="profile.php?id=${p.id}"]`).forEach(link => {
            const nameEl = link.querySelector('.post-username');
            if (nameEl) {
                nameEl.textContent = p.ten;
            } else if (link.classList.contains('comment-name') || link.classList.contains('post-author-name')) {
                link.textContent = p.ten;
            }

            if (p.avatar) {
                const img = link.querySelector('img');
                if (img) {
                    img.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                } else {
                    const polder = link.querySelector('[class*="avatar-placeholder"]');
                    if (polder) {
                        const newImg = document.createElement('img');
                        newImg.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                        newImg.className = polder.className.replace('placeholder-', '');
                        if (newImg.className.includes('avatar-sm')) newImg.alt = '';
                        polder.parentNode.replaceChild(newImg, polder);
                    }
                }
            }
        });

        // Update comment elements explicitly
        document.querySelectorAll(`a.comment-name[href*="profile.php?id=${p.id}"]`).forEach(el => {
             el.textContent = p.ten;
             const item = el.closest('.comment-item');
             if (item && p.avatar) {
                 const avtWrap = item.querySelector('.comment-avatar');
                 if (avtWrap) {
                     avtWrap.innerHTML = `<img src="../assets/images/avatars/${p.avatar}?t=${p.ts}" class="avatar-xs" alt="">`;
                 }
             }
        });
        
        // Active user updates
        if (currentUserId === p.id) {
            document.querySelectorAll('.user-menu-name.pc-only, .user-dropdown-header span').forEach(el => {
                el.textContent = p.ten;
            });
            
            if (p.avatar) {
                const headerAvatar = document.querySelector('.user-menu-btn img');
                if (headerAvatar) {
                     headerAvatar.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                } else {
                     const polder = document.querySelector('.user-menu-btn .user-menu-initial');
                     if (polder) {
                         const newImg = document.createElement('img');
                         newImg.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                         newImg.className = 'user-menu-avatar';
                         polder.parentNode.replaceChild(newImg, polder);
                     }
                }
                
                // Update comment composer avatar
                const composePolder = document.querySelector('.comment-compose .avatar-placeholder-xs');
                if (composePolder) {
                    const newImg = document.createElement('img');
                    newImg.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                    newImg.className = 'avatar-xs';
                    composePolder.parentNode.replaceChild(newImg, composePolder);
                } else {
                    const composeImg = document.querySelector('.comment-compose .avatar-xs');
                    if (composeImg) composeImg.src = `../assets/images/avatars/${p.avatar}?t=${p.ts}`;
                }
            }
        }
    }

    function pollNotifications() {
        if (notifPollInFlight) return;
        notifPollInFlight = true;

        const url = '../api/notif_poll_api.php?action=poll&p_ts=' + lastProfilePollTs + '&_=' + Date.now();
        fetch(url, {
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache' }
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    updateNotifBadges(res.unread_count);
                    
                    if (res.p_ts) lastProfilePollTs = res.p_ts;
                    if (res.updated_profiles && res.updated_profiles.length > 0) {
                        res.updated_profiles.forEach(p => updateProfileOnPage(p));
                    }
                    
                    window.dispatchEvent(new CustomEvent('notifications_polled', { detail: res }));
                }
            })
            .catch(e => console.error('Notif poll error:', e))
            .finally(() => {
                notifPollInFlight = false;
            });
    }
    
    pollNotifications();
    notifPollTimer = setInterval(pollNotifications, POLL_INTERVAL_MS);

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            pollNotifications();
        }
    });

    window.addEventListener('focus', pollNotifications);
});
</script>
