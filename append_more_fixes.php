<?php
$css_path = 'c:/xampp/htdocs/doan/DO-AN/assets/css/theme-toggle.css';

$append_css = <<<EOT

/* --- SIDEBAR, HEADER & PLACEHOLDER FIXES --- */
/* Sidebar Nav */
body[data-theme="light"] .snav-item {
  color: var(--light-muted) !important;
}
body[data-theme="light"] .snav-item.active {
  color: var(--light-accent) !important;
  background: rgba(37, 99, 235, 0.1) !important;
}
body[data-theme="light"] .snav-item:hover {
  background: var(--light-bg) !important;
}

/* Sidebar Profile Stats & Headings */
body[data-theme="light"] .profile-mini-stats strong,
body[data-theme="light"] .suggest-title,
body[data-theme="light"] .community-chat-kicker {
  color: var(--light-text) !important;
}

/* Header User Name & Icons */
body[data-theme="light"] .user-menu-name {
  color: var(--light-text) !important;
}
body[data-theme="light"] .notif-link,
body[data-theme="light"] .header-search-trigger {
  color: var(--light-text) !important;
  border-color: var(--light-border) !important;
  background: var(--light-bg) !important;
}

/* Compose Box Trigger */
body[data-theme="light"] .compose-trigger {
  color: var(--light-muted) !important;
  background: var(--light-bg) !important;
  border-color: var(--light-border) !important;
}
body[data-theme="light"] .compose-trigger:hover {
  color: var(--light-text) !important;
}
body[data-theme="light"] .compose-attach {
  color: var(--light-muted) !important;
  background: var(--light-bg) !important;
  border-color: var(--light-border) !important;
}

/* Placeholders (must be separate blocks) */
body[data-theme="light"] .comment-input::placeholder {
  color: #94a3b8 !important;
}
body[data-theme="light"] .compose-textarea::placeholder {
  color: #94a3b8 !important;
}
body[data-theme="light"] .search-bar::placeholder {
  color: #94a3b8 !important;
}
body[data-theme="light"] #searchInput::placeholder {
  color: #94a3b8 !important;
}
body[data-theme="light"] .community-chat-search-box input::placeholder {
  color: #94a3b8 !important;
}
EOT;

file_put_contents($css_path, $append_css, FILE_APPEND);
echo "Successfully appended more fixes for sidebar, header, and placeholders to theme-toggle.css\n";
