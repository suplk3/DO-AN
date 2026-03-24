<?php
// Tự xác định trang đang active, mặc định là rỗng
if (!isset($active_page)) {
    $active_page = '';
}
?>
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
            <div class="header-nav-right">
                <button class="header-search-trigger" id="mobileSearchTrigger">&#128269;</button>
                <div class="search-wrap pc-only" id="searchWrap">
                    <input type="text" id="searchInput" class="search-bar"
                           placeholder="Tìm phim, thể loại..." autocomplete="off">
                    <span class="search-icon">&#128269;</span>
                    <span class="search-spinner"></span>
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
                
                <a href="../user/ve_cua_toi.php" class="header-btn pc-only" title="Vé của tôi">&#127916;</a>
                <a href="notifications.php" class="header-btn pc-only" title="Thông báo">
                    &#128276;
                    <?php 
                    $notif_unread = 0;
                    if (isset($_SESSION['user_id']) && table_exists($conn, 'notifications')) {
                        $uid = (int)$_SESSION['user_id'];
                        $nr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0"));
                        $notif_unread = (int)($nr['c'] ?? 0);
                    }
                    if ($notif_unread > 0): ?><span class="notif-badge"><?= $notif_unread ?></span><?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user_id'])):
                    $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
                    $ten = htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'Tôi'));
                    $avatar_sql = mysqli_fetch_assoc(mysqli_query($conn, "SELECT avatar FROM users WHERE id=".(int)$_SESSION['user_id']));
                    $avatar = $avatar_sql['avatar'] ?? null;
                ?>
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
                        <a href="../user/ve_cua_toi.php" class="user-dropdown-item">&#127915; Vé của tôi</a>
                        <?php if ($is_admin): ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="../admin/dashboard.php" class="user-dropdown-item">&#128202; Dashboard</a>
                        <a href="../admin/phim.php" class="user-dropdown-item">&#127916; Quản lý phim</a>
                        <a href="../admin/suat_chieu.php" class="user-dropdown-item">&#128197; Quản lý suất chiếu</a>
                        <a href="../admin/quan_ly_user.php" class="user-dropdown-item">&#128101; Quản lý user</a>
                        <a href="../admin/quan_ly_chat.php" class="user-dropdown-item">&#128172; Quản lý tin nhắn</a>
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
