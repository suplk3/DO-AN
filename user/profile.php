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
<link rel="stylesheet" href="../assets/css/social.css">
</head>
<body class="user-index">

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="index.php" class="nav-link"><span class="icon">🎬</span><span class="text">PHIM</span></a>
        <a href="social.php" class="nav-link"><span class="icon">👥</span><span class="text">CỘNG ĐỒNG</span></a>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span><span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <a href="profile.php?id=<?= $me ?>" class="hello">
          <span class="icon">👤</span>
          <span class="text"><?= htmlspecialchars($_SESSION['ten'] ?? 'Tôi') ?></span>
        </a>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline" onclick="return confirm('Đăng xuất?')">
          <span class="icon">🚪</span>
        </a>
      </div>
    </nav>
  </div>
</header>

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
      <article class="post-card" id="post-<?= $post['id'] ?>">
        <div class="post-header">
          <div class="post-user-link" style="cursor:default;">
            <?php if ($post['avatar']): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" class="avatar-sm" alt="">
            <?php else: ?>
              <div class="avatar-placeholder-sm"><?= mb_substr($post['ten_user'],0,1) ?></div>
            <?php endif; ?>
            <div>
              <div class="post-username"><?= htmlspecialchars($post['ten_user']) ?></div>
              <div class="post-time"><?= time_ago($post['created_at']) ?></div>
            </div>
          </div>
          <?php if ($post['user_id'] == $me): ?>
          <div class="post-menu">
            <button class="post-menu-btn" onclick="toggleMenu(<?= $post['id'] ?>)">⋯</button>
            <div class="post-menu-dropdown" id="menu-<?= $post['id'] ?>" style="display:none">
              <a href="post_action.php?action=delete&id=<?= $post['id'] ?>"
                 onclick="return confirm('Xoá?')" class="menu-item danger">🗑️ Xoá</a>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <?php if ($post['phim_id']): ?>
        <a href="chi_tiet_phim.php?id=<?= $post['phim_id'] ?>" class="post-movie-tag">
          <img src="../assets/images/<?= htmlspecialchars($post['phim_poster']) ?>" alt="">
          <span>🎬 <?= htmlspecialchars($post['ten_phim']) ?></span>
        </a>
        <?php endif; ?>

        <p class="post-content"><?= nl2br(htmlspecialchars($post['noi_dung'])) ?></p>

        <?php if ($post['hinh_anh']): ?>
        <div class="post-image-wrap">
          <img src="../assets/images/posts/<?= htmlspecialchars($post['hinh_anh']) ?>"
               class="post-image" alt="" loading="lazy">
        </div>
        <?php endif; ?>

        <div class="post-stats">
          <span id="stat-react-<?= $post['id'] ?>">
            <?= $post['tong_reaction'] > 0 ? '👍 ' . $post['tong_reaction'] : '' ?>
          </span>
          <span id="stat-cmt-<?= $post['id'] ?>" onclick="toggleComments(<?= $post['id'] ?>)"
                style="cursor:pointer;"><?= $post['tong_comment'] ?> bình luận</span>
        </div>

        <div class="post-actions">
          <div class="reaction-wrap" id="rw-<?= $post['id'] ?>">
            <button class="action-btn <?= $post['my_reaction'] ? 'reacted' : '' ?>"
                    id="rbtn-<?= $post['id'] ?>"
                    onclick="quickReact(<?= $post['id'] ?>, 'post', 'like')"
                    onmouseenter="showReactions(<?= $post['id'] ?>)">
              <?= $post['my_reaction'] ? ($REACTIONS[$post['my_reaction']] . ' ' . ucfirst($post['my_reaction'])) : '👍 Thích' ?>
            </button>
            <div class="reaction-picker" id="rpicker-<?= $post['id'] ?>" style="display:none"
                 onmouseleave="hideReactions(<?= $post['id'] ?>)">
              <?php foreach ($REACTIONS as $key => $emoji): ?>
              <button class="reaction-emoji" onclick="doReact(<?= $post['id'] ?>, 'post', '<?= $key ?>')"
                      title="<?= ucfirst($key) ?>"><?= $emoji ?></button>
              <?php endforeach; ?>
            </div>
          </div>
          <button class="action-btn" onclick="toggleComments(<?= $post['id'] ?>)">💬 Bình luận</button>
        </div>

        <div class="comment-section" id="comments-<?= $post['id'] ?>" style="display:none">
          <div class="comment-list" id="clist-<?= $post['id'] ?>"></div>
          <div class="comment-compose">
            <div class="avatar-placeholder-xs"><?= mb_substr($_SESSION['ten'] ?? 'U',0,1) ?></div>
            <div class="comment-input-wrap">
              <input type="text" class="comment-input" id="ci-<?= $post['id'] ?>"
                     placeholder="Viết bình luận..."
                     onkeydown="if(event.key==='Enter')submitComment(<?= $post['id'] ?>,'post')">
              <button class="comment-send" onclick="submitComment(<?= $post['id'] ?>,'post')">➤</button>
            </div>
          </div>
        </div>
      </article>
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
    if(stat)stat.textContent=data.total>0?'👍 '+data.total:'';
}
const loadedComments={};
async function toggleComments(id){const sec=document.getElementById('comments-'+id);if(sec.style.display==='none'){sec.style.display='block';if(!loadedComments[id])await loadComments(id,'post');}else{sec.style.display='none';}}
async function loadComments(id,type){
    const list=document.getElementById('clist-'+id);
    list.innerHTML='<div style="text-align:center;padding:12px;color:#64748b;font-size:12px;">Đang tải...</div>';
    const res=await fetch(`comment_api.php?target_type=${type}&target_id=${id}`);
    const data=await res.json();
    loadedComments[id]=true;
    if(!data.comments.length){list.innerHTML='<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận</div>';return;}
    list.innerHTML=data.comments.map(c=>`<div class="comment-item"><div class="comment-avatar">${c.avatar?`<img src="../assets/images/avatars/${c.avatar}" class="avatar-xs" alt="">`:`<div class="avatar-placeholder-xs">${c.ten.charAt(0)}</div>`}</div><div class="comment-bubble"><a href="profile.php?id=${c.user_id}" class="comment-name">${escHtml(c.ten)}</a><span class="comment-text">${escHtml(c.noi_dung)}</span><div class="comment-meta">${c.time_ago}</div></div></div>`).join('');
}
async function submitComment(id,type){const inp=document.getElementById('ci-'+id);const text=inp.value.trim();if(!text)return;inp.value='';const res=await fetch('comment_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({target_type:type,target_id:id,noi_dung:text})});const data=await res.json();if(data.ok){loadedComments[id]=false;await loadComments(id,type);const stat=document.getElementById('stat-cmt-'+id);if(stat){const cur=parseInt(stat.textContent)||0;stat.textContent=(cur+1)+' bình luận';}}}
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
