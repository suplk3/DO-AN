<?php
// Tá»± xĂ¡c Ä‘á»‹nh trang Ä‘ang active, máº·c Ä‘á»‹nh lĂ  rá»—ng
if (!isset($active_page)) {
    $active_page = '';
}
?>
<style>
/* FOOLPROOF MOBILE HIDES */
@media (max-width: 991px) {
    /* Force hide redundant PC elements on mobile */
    .header-nav-right #themeToggle { display: none !important; }
    .header-nav-right .notif-link { display: none !important; }
    .user-menu-name { display: none !important; }
    .user-menu-arrow { display: none !important; }
    .header-nav-left { display: none !important; }
    .pc-only { display: none !important; }
    
    /* Ensure the mobile bar sticks to bottom if external CSS fails */
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
        padding-bottom: env(safe-area-inset-bottom, 8px) !important;
    }
}
</style>
<!-- â”€â”€ Header â”€â”€ -->
<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">TTVH</a>
        <nav class="header-nav">
            <div class="header-nav-left">
                <a href="index.php" class="nav-link <?= $active_page === 'phim' ? 'active' : '' ?>">
                    <span class="icon">đŸ¬</span>
                    <span class="text">PHIM</span>
                </a>
                <a href="sap_chieu.php" class="nav-link <?= $active_page === 'sap_chieu' ? 'active' : '' ?>">
                    <span class="icon">đŸ—“ï¸</span>
                    <span class="text">Sáº®P CHIáº¾U</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="social.php" class="nav-link <?= $active_page === 'social' ? 'active' : '' ?>">
                    <span class="icon">đŸ‘¥</span>
                    <span class="text">Cá»˜NG Äá»’NG</span>
                </a>
                <?php endif; ?>
            </div>
            <div class="search-wrap" id="searchWrap">
                <input type="text" id="searchInput" class="search-bar"
                       placeholder="TĂ¬m phim, thá»ƒ loáº¡i..." autocomplete="off">
                <span class="search-icon">đŸ”</span>
                <span class="search-spinner"></span>
                <div class="search-dropdown" id="searchDropdown"></div>
            </div>
            <div class="header-nav-right">
                <button class="header-search-trigger" id="mobileSearchTrigger">đŸ”</button>
                <button class="theme-toggle-btn" id="themeToggle">đŸŒ“ Giao diá»‡n</button>
                <?php if (isset($_SESSION['user_id'])):
                    $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
                    $ten = htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'TĂ´i'));
                    $avatar_sql = mysqli_fetch_assoc(mysqli_query($conn, "SELECT avatar FROM users WHERE id=".(int)$_SESSION['user_id']));
                    $avatar = $avatar_sql['avatar'] ?? null;
                    
                    // Láº¥y sá»‘ lÆ°á»£ng thĂ´ng bĂ¡o náº¿u chÆ°a cĂ³
                    if (!isset($notif_unread)) {
                        $notif_unread = 0;
                        if (table_exists($conn, 'notifications')) {
                            $uid = (int)$_SESSION['user_id'];
                            $nr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0"));
                            $notif_unread = (int)($nr['c'] ?? 0);
                        }
                    }
                ?>
                <a href="notifications.php" class="notif-link pc-only">đŸ””
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
                        <span class="user-menu-arrow pc-only">â–¾</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <span><?= $ten ?></span>
                            <?php if ($is_admin): ?><span class="user-badge-admin">Admin</span><?php endif; ?>
                        </div>
                        <a href="profile.php?id=<?= (int)$_SESSION['user_id'] ?>" class="user-dropdown-item">đŸ‘¤ Trang cĂ¡ nhĂ¢n</a>
                        <a href="../user/ve_cua_toi.php" class="user-dropdown-item">đŸŸï¸ VĂ© cá»§a tĂ´i</a>
                        <?php if ($is_admin): ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="../admin/dashboard.php" class="user-dropdown-item">đŸ“ Dashboard</a>
                        <a href="../admin/phim.php" class="user-dropdown-item">đŸ¬ Quáº£n lĂ½ phim</a>
                        <a href="../admin/suat_chieu.php" class="user-dropdown-item">đŸ—“ï¸ Quáº£n lĂ½ suáº¥t chiáº¿u</a>
                        <a href="../admin/quan_ly_user.php" class="user-dropdown-item">đŸ‘¥ Quáº£n lĂ½ user</a>
                        <a href="../admin/quan_ly_chat.php" class="user-dropdown-item">đŸ’¬ Quáº£n lĂ½ tin nháº¯n</a>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="../auth/logout.php" class="user-dropdown-item danger"
                           onclick="return confirm('ÄÄƒng xuáº¥t?')">đŸª ÄÄƒng xuáº¥t</a>
                    </div>
                </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-sm open-login-modal">
                        <span class="icon">đŸ”</span>
                        <span class="text">ÄÄ‚NG NHáº¬P</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<nav class="mobile-nav-bar">
    <a href="index.php" class="mobile-nav-item <?= $active_page === 'phim' ? 'active' : '' ?>">
        <span class="m-icon">đŸ¬</span>
        <span class="m-text">Phim</span>
    </a>
    <a href="sap_chieu.php" class="mobile-nav-item <?= $active_page === 'sap_chieu' ? 'active' : '' ?>">
        <span class="m-icon">đŸ—“ï¸</span>
        <span class="m-text">Sáº¯p chiáº¿u</span>
    </a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="social.php" class="mobile-nav-item <?= $active_page === 'social' ? 'active' : '' ?>">
        <span class="m-icon">đŸ‘¥</span>
        <span class="m-text">XĂ£ há»™i</span>
    </a>
    <a href="notifications.php" class="mobile-nav-item <?= $active_page === 'notifications' ? 'active' : '' ?>">
        <span class="m-icon">đŸ””<?php if (($notif_unread ?? 0) > 0): ?><em class="m-badge"></em><?php endif; ?></span>
        <span class="m-text">ThĂ´ng bĂ¡o</span>
    </a>
    <?php else: ?>
    <a href="../auth/login.php" class="mobile-nav-item open-login-modal">
        <span class="m-icon">đŸ”</span>
        <span class="m-text">ÄÄƒng nháº­p</span>
    </a>
    <?php endif; ?>
</nav>

