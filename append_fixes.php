<?php
$css_path = 'c:/xampp/htdocs/doan/DO-AN/assets/css/theme-toggle.css';

$append_css = <<<EOT

/* --- RATINGS & COMMENTS SPECIFIC FIXES --- */
body[data-theme="light"] .rating-overview-value,
body[data-theme="light"] .rating-overview-meta strong,
body[data-theme="light"] .rating-action-title,
body[data-theme="light"] .rating-scale-note strong,
body[data-theme="light"] .film-social-title,
body[data-theme="light"] .film-reactions strong,
body[data-theme="light"] .post-username,
body[data-theme="light"] .comment-text,
body[data-theme="light"] #avgRating,
body[data-theme="light"] #ratingCount,
body[data-theme="light"] #ratingHelper,
body[data-theme="light"] #myRatingText,
body[data-theme="light"] .react-detail-row span {
  color: var(--light-text) !important;
}

body[data-theme="light"] .rating-overview-label,
body[data-theme="light"] .rating-action-subtitle,
body[data-theme="light"] .rating-user-note,
body[data-theme="light"] .rating-scale-note,
body[data-theme="light"] .rating-login-hint,
body[data-theme="light"] .film-reactions span,
body[data-theme="light"] .post-time,
body[data-theme="light"] .comment-meta,
body[data-theme="light"] .reply-btn,
body[data-theme="light"] .delete-comment-btn,
body[data-theme="light"] .action-btn {
  color: var(--light-muted) !important;
}

body[data-theme="light"] .comment-name {
  color: var(--light-accent) !important;
}

body[data-theme="light"] .comment-bubble {
  background: #f1f5f9 !important;
  color: var(--light-text) !important;
}

body[data-theme="light"] .reaction-picker {
  background: var(--light-surface) !important;
  border: 1px solid var(--light-border) !important;
}

body[data-theme="light"] .reaction-emoji:hover,
body[data-theme="light"] .action-btn:hover {
  background: #f1f5f9 !important;
}

body[data-theme="light"] .rating-action-card,
body[data-theme="light"] .rating-overview-badge,
body[data-theme="light"] .rating-stars .rating-star.active,
body[data-theme="light"] .rating-stars .rating-star:hover {
  background: var(--light-surface) !important;
  border-color: var(--light-border) !important;
}

body[data-theme="light"] .rating-stars .rating-star {
  background: #f8fafc !important;
  border-color: var(--light-border) !important;
}

body[data-theme="light"] .btn-follow {
  color: var(--light-accent) !important;
  border-color: var(--light-accent) !important;
  background: transparent !important;
}

body[data-theme="light"] .btn-follow.following {
  color: var(--light-muted) !important;
  border-color: var(--light-border) !important;
  background: #f1f5f9 !important;
}
EOT;

file_put_contents($css_path, $append_css, FILE_APPEND);
echo "Successfully appended fixes for ratings and comments to theme-toggle.css\n";
