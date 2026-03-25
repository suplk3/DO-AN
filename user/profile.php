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
        <label class="avatar-edit-btn" title="Đổi ảnh đại diện">
          📷
          <input type="file" accept="image/*" style="display:none" id="avatarUpload">
        </label>
        <?php endif; ?>
      </div>

      <div class="profile-header-info">
        <h1 class="profile-name"><?= htmlspecialchars($user['ten']) ?></h1>
        <?php if ($user['bio']): ?>
          <p class="profile-bio"><?= htmlspecialchars($user['bio']) ?></p>
        <?php elseif ($is_me): ?>
          <p class="profile-bio" style="color:#475569;font-style:italic;">Thêm tiểu sử...</p>
        <?php endif; ?>

        <div class="profile-stats-row">
          <div class="pstat"><strong><?= $stats['bai_dang'] ?></strong><span>Bài đăng</span></div>
          <div class="pstat"><strong><?= $stats['followers'] ?></strong><span>Người theo dõi</span></div>
          <div class="pstat"><strong><?= $stats['following'] ?></strong><span>Đang theo dõi</span></div>
          <div class="pstat"><strong><?= $stats['ve_dat'] ?></strong><span>Vé đã đặt</span></div>
        </div>
      </div>

      <div class="profile-header-actions">
        <?php if ($is_me): ?>
          <button class="btn-edit-profile" id="editProfileBtn">✏️ Chỉnh sửa</button>
        <?php else: ?>
          <button class="btn-follow <?= $is_following ? 'following' : '' ?>"
                  id="followBtn" onclick="doFollow(<?= $uid ?>, this)">
            <?= $is_following ? '✓ Đang theo dõi' : '+ Theo dõi' ?>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Edit profile panel (only for self) -->
  <?php if ($is_me): ?>
  <div class="edit-profile-panel" id="editPanel" style="display:none">
    <form action="profile_action.php" method="POST" enctype="multipart/form-data">
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
          <label style="font-size:12px;color:#64748b;font-weight:700;display:block;margin-bottom:4px;">TÊN</label>
          <input type="text" name="ten" value="<?= htmlspecialchars($user['ten']) ?>"
                 class="edit-input" required>
        </div>
        <div style="flex:2;min-width:260px;">
          <label style="font-size:12px;color:#64748b;font-weight:700;display:block;margin-bottom:4px;">TIỂU SỬ</label>
          <input type="text" name="bio" value="<?= htmlspecialchars($user['bio'] ?? '') ?>"
                 class="edit-input" placeholder="Viết gì đó về bản thân...">
        </div>
        <div>
          <label style="font-size:12px;color:#64748b;font-weight:700;display:block;margin-bottom:4px;">ẢNH ĐẠI DIỆN</label>
          <input type="file" name="avatar" accept="image/*" class="edit-input" style="padding:6px;">
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn-post">Lưu</button>
          <button type="button" class="btn-cancel-compose" onclick="document.getElementById('editPanel').style.display='none'">Hủy</button>
        </div>
      </div>
    </form>
  </div>
  <?php endif; ?>

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
document.getElementById('editProfileBtn').addEventListener('click', () => {
    const p = document.getElementById('editPanel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
});
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
function toggleMenu(id){const el=document.getElementById('menu-'+id);el.style.display=el.style.display==='none'?'block':'none';}
document.addEventListener('click',e=>{if(!e.target.classList.contains('post-menu-btn'))document.querySelectorAll('.post-menu-dropdown').forEach(el=>el.style.display='none');});
function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
if(h&&b){const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}
</script>
<script src="../assets/js/search.js"></script>
</body>
</html>
