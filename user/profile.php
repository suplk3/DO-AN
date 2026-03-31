<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}
$me      = (int)$_SESSION['user_id'];
$uid     = isset($_GET['id']) ? (int)$_GET['id'] : $me;
$is_me   = ($uid === $me);

$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE id=$uid"
));
if (!$user) { http_response_code(404); die("Không tìm thấy người dùng"); }

// Stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        (SELECT COUNT(*) FROM posts    WHERE user_id=$uid) AS bai_dang,
        (SELECT COUNT(*) FROM follows  WHERE following_id=$uid) AS followers,
        (SELECT COUNT(*) FROM follows  WHERE follower_id=$uid)  AS following,
        (SELECT COUNT(*) FROM ve       WHERE user_id=$uid)      AS ve_dat
"));

$is_following = false;
if (!$is_me) {
    $chk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT 1 FROM follows WHERE follower_id=$me AND following_id=$uid"
    ));
    $is_following = (bool)$chk;
}

// Bài đăng của user này
$posts = mysqli_query($conn, "
    SELECT p.*, u.ten AS ten_user, u.avatar,
           (SELECT COUNT(*) FROM reactions WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
           (SELECT loai FROM reactions WHERE target_type='post' AND target_id=p.id AND user_id=$me LIMIT 1) AS my_reaction,
           (SELECT COUNT(*) FROM comments WHERE target_type='post' AND target_id=p.id) AS tong_comment,
           ph.ten_phim, ph.poster AS phim_poster
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN phim ph ON p.phim_id = ph.id
    WHERE p.user_id = $uid
    ORDER BY p.created_at DESC
");

$REACTIONS = ['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Vừa xong';
    if ($diff < 3600) return floor($diff/60) . ' phút trước';
    if ($diff < 86400) return floor($diff/3600) . ' giờ trước';
    return date('d/m/Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($user['ten']) ?> — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/user-index.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link rel="stylesheet" href="../assets/css/search.css">
<link rel="stylesheet" href="../assets/css/user-menu.css">
<link rel="stylesheet" href="../assets/css/theme-toggle.css">
<link rel="stylesheet" href="../assets/css/social.css">
<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="user-index">

<?php $active_page = ''; include 'components/header.php'; ?>
<main class="container" style="padding-top:20px;">

<style>
/* Z-INDEX FIX CHO HEADER */
.header {
    position: relative;
    z-index: 1000 !important;
}

/* PREMIUM PROFILE REDESIGN */
.profile-header-card {
    background: linear-gradient(145deg, rgba(30,41,59,0.7), rgba(15,23,42,0.9));
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 24px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    position: relative;
}
.profile-cover {
    height: 240px;
    background: linear-gradient(135deg, #1e3a8a, #7e22ce, #be185d);
    background-size: 200% 200%;
    animation: gradientMove 10s ease infinite;
    position: relative;
    overflow: hidden;
}
@keyframes gradientMove { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
.profile-cover::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 120px;
    background: linear-gradient(to top, rgba(15,23,42,1), transparent);
}
.profile-header-body {
    padding: 0 32px 32px;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -70px;
}
.profile-avatar-wrap {
    position: relative;
    margin-bottom: 16px;
    z-index: 10;
}
.profile-avatar-big {
    width: 150px; height: 150px;
    border-radius: 50%;
    border: 6px solid #0f172a;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    object-fit: cover;
    background: #0f172a;
}
.avatar-placeholder-big {
    width: 150px; height: 150px;
    border-radius: 50%;
    border: 6px solid #0f172a;
    background: linear-gradient(135deg, #7c3aed, #ec4899);
    color: white; font-size: 60px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.avatar-edit-btn {
    position: absolute;
    bottom: 8px; right: 8px;
    background: rgba(15,23,42,0.85);
    border: 2px solid rgba(255,255,255,0.15);
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    padding: 0;
    font: inherit;
    line-height: 1;
    cursor: pointer; backdrop-filter: blur(8px);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    color: #fff;
}
.avatar-edit-btn:hover { background: #3b82f6; border-color: #60a5fa; transform: scale(1.15); box-shadow: 0 8px 20px rgba(59,130,246,0.4); }
.profile-name { font-size: 32px; font-weight: 800; color: #f8fafc; margin: 0 0 8px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); text-align: center; letter-spacing: -0.5px;}
.profile-bio { font-size: 15px; color: #94a3b8; max-width: 500px; text-align: center; margin: 0 0 24px; line-height: 1.6; }

.profile-stats-glass {
    display: flex; gap: 8px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(20px);
    padding: 16px 24px;
    border-radius: 20px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}
.glass-stat {
    display: flex; flex-direction: column; align-items: center;
    padding: 0 20px;
    position: relative;
    transition: transform 0.2s;
    cursor: default;
}
.glass-stat:hover { transform: translateY(-3px); }
.glass-stat:not(:last-child)::after {
    content: ''; position: absolute;
    right: 0; top: 15%; height: 70%; width: 1px;
    background: rgba(255,255,255,0.08);
}
.glass-stat strong { font-size: 24px; font-weight: 800; color: #e2e8f0; line-height: 1.2; }
.glass-stat span { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; margin-top:2px; }

.btn-edit-profile, .btn-follow {
    padding: 12px 32px; border-radius: 99px;
    font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
    cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-edit-profile {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #e2e8f0; backdrop-filter: blur(8px);
}
.btn-edit-profile:hover { background: rgba(255,255,255,0.12); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.2); color: #fff; }

.btn-follow { background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; color: #fff; box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
.btn-follow:hover { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 12px 24px rgba(59,130,246,0.4); }
.btn-follow.following { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; box-shadow: none; }

/* Edit Panel Premium */
.edit-premium-panel {
    background: rgba(15,23,42,0.8);
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(16px);
    border-radius: 24px;
    padding: 24px;
    margin-top: 24px;
    width: 100%; max-width: 600px;
    box-shadow: inset 0 1px 1px rgba(255,255,255,0.05), 0 20px 40px rgba(0,0,0,0.5);
    animation: slideDown 0.4s ease-out;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-15px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
.ep-row { display: grid; grid-template-columns: 1fr 1.5fr; gap: 16px; margin-bottom: 16px; }
.ep-group { display: flex; flex-direction: column; gap: 6px; }
.ep-group label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.ep-input {
    background: rgba(0,0,0,0.25) !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 12px; padding: 12px 16px; color: #f8fafc;
    font-size: 14px; width: 100%; transition: all 0.3s;
}
.ep-input:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59,130,246,0.15) !important; outline: none; }
.ep-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
.btn-ep-save { background: linear-gradient(135deg, #10b981, #059669); border: none; padding: 10px 24px; border-radius: 10px; color: white; font-weight: 700; cursor: pointer; transition: 0.2s;}
.btn-ep-save:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(16,185,129,0.3); }
.btn-ep-cancel { background: transparent; border: 1px solid rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 10px; color: #94a3b8; font-weight: 700; cursor: pointer; transition: 0.2s;}
.btn-ep-cancel:hover { background: rgba(255,255,255,0.05); color: #fff; }

@media(max-width: 600px) {
    .ep-row { grid-template-columns: 1fr; }
    .profile-stats-glass { gap: 0; padding: 16px 10px; }
    .glass-stat { padding: 0 12px; }
    .profile-avatar-big, .avatar-placeholder-big { width: 120px; height: 120px; font-size: 48px; border-width: 4px; }
    .profile-name { font-size: 26px; }
    .profile-header-body { padding: 0 20px 24px; }
}
</style>

  <!-- Profile header card -->
  <div class="profile-header-card">
    <div class="profile-cover"></div>
    <div class="profile-header-body">
      <div class="profile-avatar-wrap">
        <?php if ($user['avatar']): ?>
          <img src="../assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>"
               class="profile-avatar-big" alt="" id="avatarImg">
        <?php else: ?>
          <div class="avatar-placeholder-big" id="avatarImg"><?= mb_substr($user['ten'],0,1) ?></div>
        <?php endif; ?>
        <?php if ($is_me): ?>
        <button type="button" class="avatar-edit-btn" id="avatarTrigger" title="Đổi ảnh đại diện">
          📷
        </button>
        <?php endif; ?>
      </div>

      <div class="profile-header-info" style="display:flex;flex-direction:column;align-items:center;width:100%;">
        <h1 class="profile-name"><?= htmlspecialchars($user['ten']) ?></h1>
        <?php if ($user['bio']): ?>
          <p class="profile-bio"><?= htmlspecialchars($user['bio']) ?></p>
        <?php elseif ($is_me): ?>
          <p class="profile-bio" style="font-style:italic;opacity:0.5;">Thêm tiểu sử để mọi người hiểu hơn về bạn...</p>
        <?php endif; ?>

        <div class="profile-stats-glass">
          <div class="glass-stat"><strong><?= $stats['bai_dang'] ?></strong><span>Bài viết</span></div>
          <div class="glass-stat"><strong><?= $stats['followers'] ?></strong><span>Follower</span></div>
          <div class="glass-stat"><strong><?= $stats['following'] ?></strong><span>Đang Theo</span></div>
          <div class="glass-stat"><strong style="color:#60a5fa;"><?= $stats['ve_dat'] ?></strong><span style="color:#93c5fd;">Vé đặt</span></div>
        </div>
      </div>

      <div class="profile-header-actions">
        <?php if ($is_me): ?>
          <button class="btn-edit-profile" id="editProfileBtn">✏️ Chỉnh sửa hồ sơ</button>
        <?php else: ?>
          <button class="btn-follow <?= $is_following ? 'following' : '' ?>"
                  id="followBtn" onclick="doFollow(<?= $uid ?>, this)">
            <?= $is_following ? '✓ Đang theo dõi' : '+ Theo dõi' ?>
          </button>
        <?php endif; ?>
      </div>
      <!-- Edit profile panel (only for self) -->
      <?php if ($is_me): ?>
      <div class="edit-premium-panel" id="editPanel" style="display:none">
        <form action="profile_action.php" method="POST" enctype="multipart/form-data" id="editProfileForm">
          <div class="ep-row">
            <div class="ep-group">
              <label>Tên hiển thị</label>
              <input type="text" name="ten" value="<?= htmlspecialchars($user['ten']) ?>" class="ep-input" required>
            </div>
            <div class="ep-group">
              <label>Tiểu sử (Bio)</label>
              <input type="text" name="bio" value="<?= htmlspecialchars($user['bio'] ?? '') ?>" class="ep-input" placeholder="Viết gì đó về bản thân...">
            </div>
          </div>
          <div class="ep-group">
            <label>Cập nhật ảnh đại diện (hoặc nhấn 📷 ở Avatar)</label>
            <input type="file" name="avatar" accept="image/*" class="ep-input" style="padding: 9px 16px;" id="editAvatarInput">
          </div>

          <div class="ep-actions">
            <button type="button" class="btn-ep-cancel" onclick="document.getElementById('editPanel').style.display='none'">Hủy bỏ</button>
            <button type="submit" class="btn-ep-save">💾 Lưu thay đổi</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Posts grid -->
  <div class="profile-posts-area">
    <h2 class="profile-section-title">
      <?= $is_me ? 'Bài đăng của bạn' : 'Bài đăng của ' . htmlspecialchars($user['ten']) ?>
    </h2>

    <?php if (mysqli_num_rows($posts) === 0): ?>
    <div class="feed-empty">
      <div style="font-size:40px;margin-bottom:10px;">📝</div>
      <div style="font-size:15px;font-weight:700;color:#94a3b8;">Chưa có bài đăng nào</div>
    </div>
    <?php else: ?>
    <div class="social-feed" style="max-width:680px;">
      <?php while ($post = mysqli_fetch_assoc($posts)): ?>
        <?php include 'components/post_card.php'; ?>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas</div></footer>

<script>
const REACTIONS = <?= json_encode($REACTIONS) ?>;

<?php if ($is_me): ?>
const editPanel = document.getElementById('editPanel');
const editProfileBtn = document.getElementById('editProfileBtn');
const avatarTrigger = document.getElementById('avatarTrigger');
const editAvatarInput = document.getElementById('editAvatarInput');

editProfileBtn.addEventListener('click', () => {
    editPanel.style.display = editPanel.style.display === 'none' ? 'block' : 'none';
});

if (avatarTrigger && editAvatarInput) {
    avatarTrigger.addEventListener('click', () => {
        editPanel.style.display = 'block';
        editAvatarInput.click();
    });
}
<?php endif; ?>

// Reuse same JS functions from social.php
let reactionTimer = {};
function showReactions(id){clearTimeout(reactionTimer[id]);document.getElementById('rpicker-'+id).style.display='flex';}
function hideReactions(id){reactionTimer[id]=setTimeout(()=>{const el=document.getElementById('rpicker-'+id);if(el)el.style.display='none';},300);}
function quickReact(id,type,loai){hideReactions(id);doReact(id,type,loai);}
async function doReact(id,type,loai){
    hideReactions(id);
    const res=await fetch('reaction_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({target_type:type,target_id:id,loai})});
    const data=await res.json();
    const btn=document.getElementById('rbtn-'+id);
    const stat=document.getElementById('stat-react-'+id);
    if(btn){if(data.action==='removed'){btn.textContent='👍 Thích';btn.classList.remove('reacted');}else{btn.textContent=(REACTIONS[loai]||'👍')+' '+(loai.charAt(0).toUpperCase()+loai.slice(1));btn.classList.add('reacted');}}
    if(stat)stat.textContent=data.total>0?'👍❤️ '+data.total:'';
}
async function loadReactionBreakdown(id, type) {
    const tooltip = document.getElementById('react-tooltip-' + id);
    if (!tooltip) return;
    tooltip.innerHTML = '<div style="color:#94a3b8; font-size:12px; padding:4px 8px;">Đang tải...</div>';
    try {
        const res = await fetch(`reaction_api.php?action=breakdown&target_type=${type}&target_id=${id}`);
        const data = await res.json();
        if (data.success) {
            if (data.total === 0) {
                tooltip.innerHTML = '<div style="color:#64748b; font-size:12px; padding:4px 8px;">Chưa có cảm xúc nào</div>';
                return;
            }
            let html = '';
            for (const [loai, count] of Object.entries(data.breakdown)) {
                let emoji = REACTIONS[loai] || '👍';
                const names = {like:'Thích', love:'Yêu thích', haha:'Haha', wow:'Woa', sad:'Buồn', angry:'Phẫn nộ'};
                const vnName = names[loai] || loai;
                html += `<div class="react-detail-row"><span>${emoji}</span><span style="text-transform: capitalize; padding-left:8px;">${vnName}</span><span class="react-detail-count">${count}</span></div>`;
            }
            tooltip.innerHTML = html;
        }
    } catch(e) { tooltip.innerHTML = '<div style="color:#ef4444; font-size:12px; padding:4px 8px;">Lỗi tải</div>'; }
}

const loadedComments={};
const commentCountCache = {};

function getDisplayedCommentCount(id) { const stat = document.getElementById('stat-cmt-' + id); return stat ? (parseInt(stat.textContent, 10) || 0) : 0; }
function setDisplayedCommentCount(id, count) { commentCountCache[id] = count; const stat = document.getElementById('stat-cmt-' + id);  if (stat) stat.textContent = count + ' bình luận'; }
function isCommentsOpen(id) { const sec = document.getElementById('comments-' + id); return !!sec && sec.style.display !== 'none'; }
function countCommentsTree(comments) { return comments.reduce((total, comment) => { const replies = Array.isArray(comment.replies) ? countCommentsTree(comment.replies) : 0; return total + 1 + replies; }, 0); }

async function toggleComments(id) { const sec = document.getElementById('comments-' + id); if (sec.style.display === 'none') { sec.style.display = 'block'; if (!loadedComments[id]) await loadComments(id, 'post'); } else { sec.style.display = 'none'; } }
function renderCommentTree(comments, postId, type, depth = 0) {
    if (!comments || !comments.length) return '';
    let html = '';
    comments.forEach(c => {
        let repliesHtml = '';
        if (c.replies && c.replies.length > 0) { repliesHtml = `<div class="comment-replies" style="margin-left: ${depth === 0 ? 34 : 0}px; border-left: 2px solid rgba(255,255,255,0.05); padding-left: 12px; margin-top: 8px;">${renderCommentTree(c.replies, postId, type, depth + 1)}</div>`; }
        const parentId = depth === 0 ? c.id : c.parent_id;
        html += `<div class="comment-item" id="cmt-${c.id}" style="${depth > 0 ? 'margin-top: 10px; padding:0;' : ''}">
            <div class="comment-avatar">${c.avatar ? `<img src="../assets/images/avatars/${c.avatar}" class="avatar-xs" alt="" style="${depth > 0 ? 'width:24px;height:24px;' : ''}">` : `<div class="avatar-placeholder-xs" style="${depth > 0 ? 'width:24px;height:24px;font-size:10px;' : ''}">${c.ten.charAt(0)}</div>`}</div>
            <div class="comment-bubble-wrap" style="flex:1;">
                <div class="comment-bubble" style="${depth > 0 ? 'padding:8px 10px;' : ''}"><a href="profile.php?id=${c.user_id}" class="comment-name">${escHtml(c.ten)}</a><span class="comment-text">${escHtml(c.noi_dung)}</span><div class="comment-meta">${c.time_ago}<button class="btn-reply-text" style="background:none;border:none;color:#94a3b8;font-size:11px;cursor:pointer;padding:0 5px; margin-left:8px;" onclick="showReplyBox(${postId}, '${type}', ${parentId})">Trả lời</button></div></div>
                <div id="reply-box-${postId}-${parentId}" class="reply-box-container" style="display:none; margin-top: 6px;"></div>
                ${repliesHtml}
            </div></div>`;
    });
    return html;
}
function showReplyBox(postId, type, parentId) {
    document.querySelectorAll(`.reply-box-container[id^="reply-box-${postId}-"]`).forEach(el => { el.style.display = 'none'; el.innerHTML = ''; });
    const box = document.getElementById(`reply-box-${postId}-${parentId}`);
    if (!box) return;
    box.style.display = 'block';
    box.innerHTML = `<div class="comment-compose" style="padding: 0; background: transparent; border: none; margin-top: 8px;">
        <div class="comment-input-wrap" style="background: rgba(255,255,255,0.03);">
            <input type="text" class="comment-input" id="ci-reply-${postId}-${parentId}" placeholder="Viết phản hồi..." onkeydown="if(event.key==='Enter')submitComment(${postId}, '${type}', ${parentId})">
            <button class="comment-send" onclick="submitComment(${postId}, '${type}', ${parentId})">➤</button>
        </div></div>`;
    setTimeout(() => document.getElementById(`ci-reply-${postId}-${parentId}`).focus(), 50);
}
async function loadComments(id, type, options = {}) {
    const list = document.getElementById('clist-' + id);
    if (!options.background) { list.innerHTML = '<div style="text-align:center;padding:12px;color:#64748b;font-size:12px;">Đang tải...</div>';  }
    const res = await fetch(`comment_api.php?target_type=${type}&target_id=${id}`);
    const data = await res.json();
    loadedComments[id] = true;
    if (!data.comments.length) { setDisplayedCommentCount(id, 0); list.innerHTML = '<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận nào</div>'; return; }
    setDisplayedCommentCount(id, countCommentsTree(data.comments));
    list.innerHTML = renderCommentTree(data.comments, id, type);
}
async function submitComment(id, type, parentId = null) {
    const inpId = parentId ? `ci-reply-${id}-${parentId}` : `ci-${id}`;
    const inp = document.getElementById(inpId);
    if (!inp) return;
    const text = inp.value.trim();
    if (!text) return;
    inp.value = '';
    const payload = { target_type: type, target_id: id, noi_dung: text };
    if (parentId) payload.parent_id = parentId;
    const res = await fetch('comment_api.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await res.json();
    if (data.ok) { loadedComments[id] = false; await loadComments(id, type); }
}
async function doFollow(userId,btn){const res=await fetch('follow_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({following_id:userId})});const data=await res.json();btn.textContent=data.action==='followed'?'✓ Đang theo dõi':'+ Theo dõi';btn.classList.toggle('following',data.action==='followed');}
const followBtn = document.getElementById('followBtn');
if (followBtn) {
    followBtn.textContent = followBtn.classList.contains('following') ? 'Đang theo dõi' : 'Theo dõi';
}

async function doFollow(userId, btn) {
    if (!btn) return;

    btn.disabled = true;

    try {
        const res = await fetch('follow_api.php?following_id=' + encodeURIComponent(userId), {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ following_id: userId })
        });
        const data = await res.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        const isFollowing = data.action === 'followed';
        btn.textContent = isFollowing ? 'Đang theo dõi' : 'Theo dõi';
        btn.classList.toggle('following', isFollowing);
    } catch (error) {
        console.error('Follow error:', error);
        alert('Không thể theo dõi lúc này.');
    } finally {
        btn.disabled = false;
    }
}

function toggleMenu(id){const el=document.getElementById('menu-'+id);el.style.display=el.style.display==='none'?'block':'none';}
document.addEventListener('click',e=>{if(!e.target.classList.contains('post-menu-btn'))document.querySelectorAll('.post-menu-dropdown').forEach(el=>el.style.display='none');});
function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
if(h&&b){const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}
</script>
<script src="../assets/js/search.js"></script>
<script>
// User dropdown toggle
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => {
        e.stopPropagation();
        userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
}
</script>
<script>
(function () {
    const followBtn = document.getElementById('followBtn');
    if (!followBtn) return;

    const profileUserId = <?= (int)$uid ?>;
    followBtn.textContent = followBtn.classList.contains('following') ? 'Đang theo dõi' : 'Theo dõi';
    followBtn.removeAttribute('onclick');

    async function handleProfileFollow(e) {
        e.preventDefault();

        const body = new URLSearchParams();
        body.set('following_id', String(profileUserId));

        const res = await fetch('follow_api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        });
        const data = await res.json();

        if (data.action === 'followed' || data.action === 'unfollowed') {
            const isFollowing = data.action === 'followed';
            followBtn.textContent = isFollowing ? 'Đang theo dõi' : 'Theo dõi';
            followBtn.classList.toggle('following', isFollowing);
            return;
        }

        if (data.error) {
            alert(data.error);
        }
    }

    window.doFollow = async function(userId, btn) {
        if (Number(userId) !== Number(profileUserId) || btn !== followBtn) {
            return handleProfileFollow(new Event('click'));
        }
        return handleProfileFollow(new Event('click'));
    };

    followBtn.addEventListener('click', handleProfileFollow);
})();
</script>
</body>
</html>
