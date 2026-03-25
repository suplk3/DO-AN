<?php
$css_path = 'c:/xampp/htdocs/doan/DO-AN/assets/css/theme-toggle.css';

$append_css = <<<EOT

/* Extended Generic Text Rules for All Pages */
body[data-theme="light"] .md-title, 
body[data-theme="light"] .back, 
body[data-theme="light"] .chip, 
body[data-theme="light"] .md-short, 
body[data-theme="light"] .desc-short, 
body[data-theme="light"] .desc-full, 
body[data-theme="light"] .desc-toggle-text, 
body[data-theme="light"] .desc-toggle-icon, 
body[data-theme="light"] .md-detail-label, 
body[data-theme="light"] .md-detail-value, 
body[data-theme="light"] .trailer-btn, 
body[data-theme="light"] .rating-helper, 
body[data-theme="light"] .my-rating, 
body[data-theme="light"] .rating-summary div, 
body[data-theme="light"] .reaction-wrap, 
body[data-theme="light"] .action-btn, 
body[data-theme="light"] .spoiler-toggle-btn, 
body[data-theme="light"] #spoilerBtnText, 
body[data-theme="light"] .film-react-summary, 
body[data-theme="light"] .payment-title, 
body[data-theme="light"] .ticket-info, 
body[data-theme="light"] .summary-row, 
body[data-theme="light"] .total-row, 
body[data-theme="light"] .ticket-card, 
body[data-theme="light"] .ticket-title, 
body[data-theme="light"] .ticket-detail, 
body[data-theme="light"] .qr-text, 
body[data-theme="light"] .payment-method label, 
body[data-theme="light"] .showtime-card, 
body[data-theme="light"] .showtime-info, 
body[data-theme="light"] .st-time, 
body[data-theme="light"] .st-lang, 
body[data-theme="light"] .st-date-btn, 
body[data-theme="light"] .st-date-btn span, 
body[data-theme="light"] .date-nav, 
body[data-theme="light"] .date-item, 
body[data-theme="light"] .date-item span, 
body[data-theme="light"] .theater-group, 
body[data-theme="light"] .theater-name, 
body[data-theme="light"] .seat-grid .row-label, 
body[data-theme="light"] .legend-item span, 
body[data-theme="light"] .screen, 
body[data-theme="light"] .ticket-header, 
body[data-theme="light"] .profile-box, 
body[data-theme="light"] .form-group label, 
body[data-theme="light"] .my-tickets-title, 
body[data-theme="light"] .notif-item { 
  color: var(--light-text) !important; 
}

body[data-theme="light"] .react-detail-row span,
body[data-theme="light"] .react-total-text {
  color: var(--light-text) !important;
}

body[data-theme="light"] textarea, 
body[data-theme="light"] input,
body[data-theme="light"] .comment-input,
body[data-theme="light"] .seat.selected,
body[data-theme="light"] .checkout-box {
  background: var(--light-surface) !important;
  color: var(--light-text) !important;
  border-color: var(--light-border) !important;
}

body[data-theme="light"] .seat {
  background-color: #64748b; /* Better contrast for default seat on light background */
}

body[data-theme="light"] .seat.available {
  background-color: #cbd5e1;
}

body[data-theme="light"] .seat.booked {
  background-color: #ef4444; 
}

/* Make backgrounds bright white */
body[data-theme="light"] .trailer-box,
body[data-theme="light"] .payment-container,
body[data-theme="light"] .ticket-container,
body[data-theme="light"] .checkout-bar,
body[data-theme="light"] .profile-container,
body[data-theme="light"] .notif-dropdown {
  background: var(--light-surface) !important;
  border-color: var(--light-border) !important;
  color: var(--light-text) !important;
}

body[data-theme="light"] .form-control {
  background: var(--light-bg) !important;
  color: var(--light-text) !important;
  border: 1px solid var(--light-border) !important;
}
EOT;

file_put_contents($css_path, $append_css, FILE_APPEND);
echo "Successfully appended extended theme instructions to theme-toggle.css\n";
