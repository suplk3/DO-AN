<?php
/**
 * HƯỚNG DẪN TÍCH HỢP SEARCH VÀO CÁC TRANG
 * ==========================================
 * 1. Copy search_api.php  → user/search_api.php
 * 2. Copy search.css      → assets/css/search.css
 * 3. Copy search.js       → assets/js/search.js
 * 4. Trong <head> thêm:
 *       <link rel="stylesheet" href="../assets/css/search.css">
 * 5. Trong header, thêm đoạn HTML bên dưới vào giữa
 *    header-nav-left và header-nav-right
 * 6. Trước </body> thêm:
 *       <script src="../assets/js/search.js"></script>
 */
?>

<!-- ════════════════════════════════════════
     SEARCH BAR — dán vào giữa header-nav
     (giữa </div> của nav-left và <div class="header-nav-right">)
     ════════════════════════════════════════ -->

<div class="search-wrap" id="searchWrap">
    <input
        type="text"
        id="searchInput"
        class="search-bar"
        placeholder="Tìm phim, thể loại..."
        autocomplete="off"
        spellcheck="false"
        aria-label="Tìm kiếm phim"
        aria-autocomplete="list"
        aria-controls="searchDropdown"
    >
    <span class="search-icon" aria-hidden="true">🔍</span>
    <span class="search-spinner" aria-hidden="true"></span>
    <div class="search-dropdown" id="searchDropdown" role="listbox"></div>
</div>


<!-- ════════════════════════════════════════
     VÍ DỤ: Đoạn header hoàn chỉnh sau khi tích hợp
     (chỉ để tham khảo, KHÔNG copy nguyên block này)
     ════════════════════════════════════════ -->

<!--
<?php $active_page = ''; include 'components/header.php'; ?>
-->