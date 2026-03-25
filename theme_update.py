import os

repo_dir = "c:/xampp/htdocs/doan/DO-AN/"

# 1. Clean duplicated toggles
files = [
    "user/social.php",
    "user/sap_chieu.php",
    "user/payment.php",
    "user/index.php",
    "user/chi_tiet_phim.php"
]

to_replace = """<script>
// Theme toggle
(function(){
  var body = document.body;
  var btn = document.getElementById('themeToggle');
  var stored = localStorage.getItem('theme') || 'dark';
  body.setAttribute('data-theme', stored);
  if (!btn) return;
  btn.addEventListener('click', function(){
    var cur = body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    body.setAttribute('data-theme', cur);
    localStorage.setItem('theme', cur);
  });
})();
</script>"""

for f in files:
    path = os.path.join(repo_dir, f)
    if os.path.exists(path):
        with open(path, "r", encoding="utf-8") as file:
            content = file.read()
        if to_replace in content:
            content = content.replace(to_replace, "")
            with open(path, "w", encoding="utf-8") as file:
                file.write(content)
            print(f"Removed toggle from {f}")
        elif to_replace.replace('\r\n', '\n') in content.replace('\r\n', '\n'):
            content = content.replace('\r\n', '\n').replace(to_replace.replace('\r\n', '\n'), "")
            with open(path, "w", encoding="utf-8") as file:
                file.write(content)
            print(f"Removed toggle (LF) from {f}")
        else:
            print(f"NOT FOUND in {f}")

# 2. Add global script to header.php
header_path = os.path.join(repo_dir, "user/components/header.php")
with open(header_path, "r", encoding="utf-8") as f:
    header_content = f.read()

new_global_theme = """
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
"""

if "<!-- Theme Scripts -->" not in header_content:
    header_content = header_content.replace("<style>\n", new_global_theme)
    header_content = header_content.replace("<style>\r\n", new_global_theme)
    with open(header_path, "w", encoding="utf-8") as f:
        f.write(header_content)
    print("Added global theme script to header.php")

# 3. Modify theme-toggle.css completely
css_path = os.path.join(repo_dir, "assets/css/theme-toggle.css")
new_css = """
:root {
  --light-bg: #f6f7fb;
  --light-surface: #ffffff;
  --light-text: #0f172a;
  --light-muted: #475569;
  --light-border: #e2e8f0;
  --light-accent: #2563eb;
}

.theme-toggle-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(255,255,255,0.06);
  color: #e2e8f0;
  font-size: 16px;
  cursor: pointer;
}
@media (max-width: 768px) {
  .theme-toggle-btn {
    border-radius: 12px;
  }
}

html[data-theme="light"],
body[data-theme="light"] {
  background: var(--light-bg) !important;
  color: var(--light-text) !important;
}

/* Base Structural Overrides */
body[data-theme="light"] h1,
body[data-theme="light"] h2,
body[data-theme="light"] h3,
body[data-theme="light"] h4,
body[data-theme="light"] h5,
body[data-theme="light"] h6,
body[data-theme="light"] p,
body[data-theme="light"] .movie-title,
body[data-theme="light"] .banner-title,
body[data-theme="light"] .genre,
body[data-theme="light"] .duration,
body[data-theme="light"] .section-title,
body[data-theme="light"] .page-title,
body[data-theme="light"] .text-white,
body[data-theme="light"] .post-author-name,
body[data-theme="light"] .post-text,
body[data-theme="light"] .post-content,
body[data-theme="light"] .comment-name,
body[data-theme="light"] .comment-text,
body[data-theme="light"] .profile-mini-name,
body[data-theme="light"] .suggest-user div,
body[data-theme="light"] .hot-movie-item div {
  color: var(--light-text) !important;
}

/* Secondary Texts */
body[data-theme="light"] .post-time,
body[data-theme="light"] .comment-meta,
body[data-theme="light"] .profile-mini-bio,
body[data-theme="light"] .profile-mini-stats span,
body[data-theme="light"] .footer {
  color: var(--light-muted) !important;
}

/* Backgrounds and Cards */
body[data-theme="light"] .header,
body[data-theme="light"] .movie-card,
body[data-theme="light"] .md-card,
body[data-theme="light"] .showtime-card,
body[data-theme="light"] .left,
body[data-theme="light"] .right,
body[data-theme="light"] .movie-summary,
body[data-theme="light"] .post-card,
body[data-theme="light"] .compose-box,
body[data-theme="light"] .suggest-box,
body[data-theme="light"] .profile-mini-card,
body[data-theme="light"] .community-chat-shell,
body[data-theme="light"] .community-chat-sidebar,
body[data-theme="light"] .community-chat-main,
body[data-theme="light"] .social-right,
body[data-theme="light"] .user-dropdown {
  background: var(--light-surface) !important;
  border-color: var(--light-border) !important;
  color: var(--light-text) !important;
}

/* Navigation & Inputs */
body[data-theme="light"] .nav-link,
body[data-theme="light"] .header-nav-left .nav-link,
body[data-theme="light"] .user-dropdown-header,
body[data-theme="light"] .user-dropdown-item {
  color: var(--light-text) !important;
}

body[data-theme="light"] .search-bar,
body[data-theme="light"] .compose-textarea,
body[data-theme="light"] select,
body[data-theme="light"] input[type="text"],
body[data-theme="light"] input[type="search"],
body[data-theme="light"] #communityChatInput {
  background: var(--light-bg) !important;
  color: var(--light-text) !important;
  border: 1px solid var(--light-border) !important;
}

body[data-theme="light"] .mobile-nav-bar {
  background: var(--light-surface) !important;
  border-top-color: var(--light-border) !important;
}
body[data-theme="light"] .mobile-nav-item .m-text,
body[data-theme="light"] .mobile-nav-item .m-icon {
  color: var(--light-muted) !important;
}
body[data-theme="light"] .mobile-nav-item.active .m-text,
body[data-theme="light"] .mobile-nav-item.active .m-icon {
  color: var(--light-accent) !important;
}
body[data-theme="light"] .mobile-nav-item.active .m-badge {
  background: red;
}

/* Buttons */
body[data-theme="light"] .btn,
body[data-theme="light"] .btn-sm,
body[data-theme="light"] .btn-outline {
  color: var(--light-text) !important;
}
body[data-theme="light"] .btn-primary {
  color: #fff !important; /* Emphasize primary button */
}
body[data-theme="light"] .theme-toggle-btn {
  background: #f1f5f9;
  color: var(--light-text);
  border-color: var(--light-border);
}
"""
with open(css_path, "w", encoding="utf-8") as f:
    f.write(new_css.strip())
print("Updated theme-toggle.css")
