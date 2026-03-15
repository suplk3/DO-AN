<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}
$me = (int)$_SESSION['user_id'];

// Lấy feed: bài của mình + bài của người mình follow
$feed_sql = "
    SELECT p.*, u.ten AS ten_user, u.avatar,
           (SELECT COUNT(*) FROM reactions WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
           (SELECT loai FROM reactions WHERE target_type='post' AND target_id=p.id AND user_id=$me LIMIT 1) AS my_reaction,
           (SELECT COUNT(*) FROM comments WHERE target_type='post' AND target_id=p.id) AS tong_comment,
           ph.ten_phim, ph.poster AS phim_poster
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN phim ph ON p.phim_id = ph.id
    WHERE p.user_id = $me
       OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = $me)
    ORDER BY p.created_at DESC
    LIMIT 30
";
$feed = mysqli_query($conn, $feed_sql);

// Đếm người đang follow / follower
$fl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM follows WHERE follower_id=$me) AS following,
    (SELECT COUNT(*) FROM follows WHERE following_id=$me) AS followers
"));
$me_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$me"));

$REACTIONS = ['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cộng đồng — TTVH Cinemas</title>
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
        <a href="sap_chieu.php" class="nav-link"><span class="icon">🗓️</span><span class="text">SẮP CHIẾU</span></a>
        <a href="social.php" class="nav-link active"><span class="icon">👥</span><span class="text">CỘNG ĐỒNG</span></a>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span><span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <a href="profile.php?id=<?= $me ?>" class="hello">
          <?php if ($me_info['avatar']): ?>
            <img src="../assets/images/avatars/<?= htmlspecialchars($me_info['avatar']) ?>" class="avatar-xs" alt="">
          <?php else: ?>
            <span class="icon">👤</span>
          <?php endif; ?>
          <span class="text"><?= htmlspecialchars($me_info['ten']) ?></span>
        </a>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline" onclick="return confirm('Đăng xuất?')">
          <span class="icon">🚪</span><span class="text">ĐĂNG XUẤT</span>
        </a>
      </div>
    </nav>
  </div>
</header>

<main class="container">
  <div class="social-layout">

    <!-- LEFT: Profile mini + nav -->
    <aside class="social-sidebar">
      <div class="profile-mini-card">
        <a href="profile.php?id=<?= $me ?>">
          <div class="avatar-wrap">
            <?php if ($me_info['avatar']): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($me_info['avatar']) ?>" class="avatar-lg" alt="">
            <?php else: ?>
              <div class="avatar-placeholder-lg"><?= mb_substr($me_info['ten'],0,1) ?></div>
            <?php endif; ?>
          </div>
          <div class="profile-mini-name"><?= htmlspecialchars($me_info['ten']) ?></div>
        </a>
        <?php if ($me_info['bio']): ?>
          <p class="profile-mini-bio"><?= htmlspecialchars($me_info['bio']) ?></p>
        <?php endif; ?>
        <div class="profile-mini-stats">
          <div><strong><?= $fl['following'] ?></strong><span>Đang theo dõi</span></div>
          <div><strong><?= $fl['followers'] ?></strong><span>Người theo dõi</span></div>
        </div>
      </div>
      <nav class="social-nav">
        <a href="social.php" class="snav-item active"><span>🏠</span> Bảng tin</a>
        <a href="profile.php?id=<?= $me ?>" class="snav-item"><span>👤</span> Trang cá nhân</a>
        <a href="index.php" class="snav-item"><span>🎬</span> Xem phim</a>
        <a href="ve_cua_toi.php" class="snav-item"><span>🎟️</span> Vé của tôi</a>
      </nav>
    </aside>

    <!-- CENTER: Feed -->
    <div class="social-feed">

      <!-- Compose box -->
      <div class="compose-box">
        <div class="compose-top">
          <?php if ($me_info['avatar']): ?>
            <img src="../assets/images/avatars/<?= htmlspecialchars($me_info['avatar']) ?>" class="avatar-sm" alt="">
          <?php else: ?>
            <div class="avatar-placeholder-sm"><?= mb_substr($me_info['ten'],0,1) ?></div>
          <?php endif; ?>
          <button class="compose-trigger" id="composeToggle">
            Bạn đang nghĩ gì về bộ phim vừa xem?
          </button>
        </div>
        <!-- Compose form (hidden by default) -->
        <form class="compose-form" id="composeForm" style="display:none"
              action="post_action.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create">
          <textarea name="noi_dung" class="compose-textarea" placeholder="Chia sẻ cảm nhận của bạn..." rows="3" required></textarea>
          <div class="compose-options">
            <div class="compose-left">
              <label class="compose-attach" title="Đính kèm ảnh">
                📷 Ảnh
                <input type="file" name="hinh_anh" accept="image/*" style="display:none" id="imgPicker">
              </label>
              <select name="phim_id" class="compose-tag-movie">
                <option value="">🎬 Gắn tag phim</option>
                <?php
                $phim_list = mysqli_query($conn, "SELECT id, ten_phim FROM phim ORDER BY ten_phim");
                while ($p = mysqli_fetch_assoc($phim_list)):
                ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ten_phim']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="compose-right">
              <span id="imgPreviewName" style="font-size:12px;color:#64748b;"></span>
              <button type="button" class="btn-cancel-compose" id="cancelCompose">Hủy</button>
              <button type="submit" class="btn-post">Đăng</button>
            </div>
          </div>
          <div id="imgPreviewWrap" style="display:none;margin-top:8px;">
            <img id="imgPreview" style="max-height:200px;border-radius:10px;max-width:100%;" alt="">
          </div>
        </form>
      </div>

      <!-- Feed posts -->
      <?php if (mysqli_num_rows($feed) === 0): ?>
      <div class="feed-empty">
        <div style="font-size:48px;margin-bottom:12px;">🎬</div>
        <div style="font-size:16px;font-weight:700;color:#e2e8f0;margin-bottom:6px;">Bảng tin trống</div>
        <div style="font-size:13px;color:#64748b;">Hãy theo dõi bạn bè để xem bài đăng của họ</div>
      </div>
      <?php else: ?>
      <?php while ($post = mysqli_fetch_assoc($feed)): ?>
      <article class="post-card" id="post-<?= $post['id'] ?>">
        <!-- Post header -->
        <div class="post-header">
          <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-user-link">
            <?php if ($post['avatar']): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" class="avatar-sm" alt="">
            <?php else: ?>
              <div class="avatar-placeholder-sm"><?= mb_substr($post['ten_user'],0,1) ?></div>
            <?php endif; ?>
            <div>
              <div class="post-username"><?= htmlspecialchars($post['ten_user']) ?></div>
              <div class="post-time"><?= time_ago($post['created_at']) ?></div>
            </div>
          </a>
          <?php if ($post['user_id'] == $me): ?>
          <div class="post-menu">
            <button class="post-menu-btn" onclick="toggleMenu(<?= $post['id'] ?>)">⋯</button>
            <div class="post-menu-dropdown" id="menu-<?= $post['id'] ?>" style="display:none">
              <a href="post_action.php?action=delete&id=<?= $post['id'] ?>"
                 onclick="return confirm('Xoá bài đăng này?')" class="menu-item danger">🗑️ Xoá bài</a>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Movie tag -->
        <?php if ($post['phim_id']): ?>
        <a href="chi_tiet_phim.php?id=<?= $post['phim_id'] ?>" class="post-movie-tag">
          <img src="../assets/images/<?= htmlspecialchars($post['phim_poster']) ?>" alt="">
          <span>🎬 <?= htmlspecialchars($post['ten_phim']) ?></span>
        </a>
        <?php endif; ?>

        <!-- Content -->
        <p class="post-content"><?= nl2br(htmlspecialchars($post['noi_dung'])) ?></p>

        <!-- Image -->
        <?php if ($post['hinh_anh']): ?>
        <div class="post-image-wrap">
          <img src="../assets/images/posts/<?= htmlspecialchars($post['hinh_anh']) ?>"
               class="post-image" alt="" loading="lazy">
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="post-stats">
          <?php if ($post['tong_reaction'] > 0): ?>
          <span class="post-stat-reactions" id="stat-react-<?= $post['id'] ?>">
            👍❤️ <?= $post['tong_reaction'] ?>
          </span>
          <?php else: ?>
          <span id="stat-react-<?= $post['id'] ?>"></span>
          <?php endif; ?>
          <span class="post-stat-comments" id="stat-cmt-<?= $post['id'] ?>"
                onclick="toggleComments(<?= $post['id'] ?>)" style="cursor:pointer;">
            <?= $post['tong_comment'] ?> bình luận
          </span>
        </div>

        <!-- Actions -->
        <div class="post-actions">
          <!-- Reaction button -->
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

        <!-- Comment section (hidden by default) -->
        <div class="comment-section" id="comments-<?= $post['id'] ?>" style="display:none">
          <div class="comment-list" id="clist-<?= $post['id'] ?>">
            <!-- loaded via JS -->
          </div>
          <div class="comment-compose">
            <?php if ($me_info['avatar']): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($me_info['avatar']) ?>" class="avatar-xs" alt="">
            <?php else: ?>
              <div class="avatar-placeholder-xs"><?= mb_substr($me_info['ten'],0,1) ?></div>
            <?php endif; ?>
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
      <?php endif; ?>
    </div>

    <!-- RIGHT: Suggestions -->
    <aside class="social-right">
      <div class="suggest-box">
        <div class="suggest-title">Gợi ý theo dõi</div>
        <?php
        $suggest = mysqli_query($conn, "
            SELECT u.id, u.ten, u.avatar,
                   (SELECT COUNT(*) FROM posts WHERE user_id=u.id) AS bai_dang
            FROM users u
            WHERE u.id != $me
              AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=$me)
            ORDER BY bai_dang DESC
            LIMIT 5
        ");
        while ($s = mysqli_fetch_assoc($suggest)):
        ?>
        <div class="suggest-item">
          <a href="profile.php?id=<?= $s['id'] ?>" class="suggest-user">
            <?php if ($s['avatar']): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($s['avatar']) ?>" class="avatar-sm" alt="">
            <?php else: ?>
              <div class="avatar-placeholder-sm"><?= mb_substr($s['ten'],0,1) ?></div>
            <?php endif; ?>
            <div>
              <div style="font-size:13px;font-weight:700;color:#f1f5f9;"><?= htmlspecialchars($s['ten']) ?></div>
              <div style="font-size:11px;color:#64748b;"><?= $s['bai_dang'] ?> bài đăng</div>
            </div>
          </a>
          <button class="btn-follow" onclick="doFollow(<?= $s['id'] ?>, this)">Theo dõi</button>
        </div>
        <?php endwhile; ?>
      </div>

      <!-- Phim hot -->
      <div class="suggest-box" style="margin-top:16px;">
        <div class="suggest-title">🔥 Phim đang hot</div>
        <?php
        $hot = mysqli_query($conn, "
            SELECT p.id, p.ten_phim, p.poster,
                   COUNT(r.id) AS tong_react
            FROM phim p
            LEFT JOIN reactions r ON r.target_type='phim' AND r.target_id=p.id
            GROUP BY p.id ORDER BY tong_react DESC LIMIT 4
        ");
        while ($h = mysqli_fetch_assoc($hot)):
        ?>
        <a href="chi_tiet_phim.php?id=<?= $h['id'] ?>" class="hot-movie-item">
          <img src="../assets/images/<?= htmlspecialchars($h['poster']) ?>" alt="">
          <div>
            <div style="font-size:12px;font-weight:700;color:#f1f5f9;"><?= htmlspecialchars($h['ten_phim']) ?></div>
            <div style="font-size:11px;color:#64748b;">❤️ <?= $h['tong_react'] ?> lượt cảm xúc</div>
          </div>
        </a>
        <?php endwhile; ?>
      </div>
    </aside>

  </div>
</main>

<footer class="footer"><div>© <?= date('Y') ?> TTVH Cinemas</div></footer>

<script>
<?php
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Vừa xong';
    if ($diff < 3600) return floor($diff/60) . ' phút trước';
    if ($diff < 86400) return floor($diff/3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff/86400) . ' ngày trước';
    return date('d/m/Y', strtotime($datetime));
}
?>

const REACTIONS = <?= json_encode($REACTIONS) ?>;

// ── Toggle compose box ──
document.getElementById('composeToggle').addEventListener('click', () => {
    document.getElementById('composeForm').style.display = 'block';
    document.getElementById('composeToggle').style.display = 'none';
    document.querySelector('.compose-form textarea').focus();
});
document.getElementById('cancelCompose').addEventListener('click', () => {
    document.getElementById('composeForm').style.display = 'none';
    document.getElementById('composeToggle').style.display = 'block';
});

// Image preview
document.getElementById('imgPicker').addEventListener('change', function() {
    if (this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreviewWrap').style.display = 'block';
            document.getElementById('imgPreviewName').textContent = this.files[0].name;
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// ── Reactions ──
let reactionTimer = {};

function showReactions(postId) {
    clearTimeout(reactionTimer[postId]);
    document.getElementById('rpicker-' + postId).style.display = 'flex';
}
function hideReactions(postId) {
    reactionTimer[postId] = setTimeout(() => {
        const el = document.getElementById('rpicker-' + postId);
        if (el) el.style.display = 'none';
    }, 300);
}
function quickReact(id, type, loai) {
    hideReactions(id);
    doReact(id, type, loai);
}
async function doReact(id, type, loai) {
    hideReactions(id);
    const res = await fetch('reaction_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({target_type: type, target_id: id, loai})
    });
    const data = await res.json();
    const btn = document.getElementById('rbtn-' + id);
    const stat = document.getElementById('stat-react-' + id);
    if (btn) {
        if (data.action === 'removed') {
            btn.textContent = '👍 Thích';
            btn.classList.remove('reacted');
        } else {
            btn.textContent = (REACTIONS[loai] || '👍') + ' ' + (loai.charAt(0).toUpperCase() + loai.slice(1));
            btn.classList.add('reacted');
        }
    }
    if (stat) stat.textContent = data.total > 0 ? '👍 ' + data.total : '';
}

// ── Comments ──
const loadedComments = {};
async function toggleComments(id) {
    const sec = document.getElementById('comments-' + id);
    if (sec.style.display === 'none') {
        sec.style.display = 'block';
        if (!loadedComments[id]) await loadComments(id, 'post');
    } else {
        sec.style.display = 'none';
    }
}
async function loadComments(id, type) {
    const list = document.getElementById('clist-' + id);
    list.innerHTML = '<div style="text-align:center;padding:12px;color:#64748b;font-size:12px;">Đang tải...</div>';
    const res = await fetch(`comment_api.php?target_type=${type}&target_id=${id}`);
    const data = await res.json();
    loadedComments[id] = true;
    if (!data.comments.length) {
        list.innerHTML = '<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận nào</div>';
        return;
    }
    list.innerHTML = data.comments.map(c => `
        <div class="comment-item" id="cmt-${c.id}">
            <div class="comment-avatar">
                ${c.avatar
                    ? `<img src="../assets/images/avatars/${c.avatar}" class="avatar-xs" alt="">`
                    : `<div class="avatar-placeholder-xs">${c.ten.charAt(0)}</div>`}
            </div>
            <div class="comment-bubble">
                <a href="profile.php?id=${c.user_id}" class="comment-name">${escHtml(c.ten)}</a>
                <span class="comment-text">${escHtml(c.noi_dung)}</span>
                <div class="comment-meta">${c.time_ago}</div>
            </div>
        </div>
    `).join('');
}
async function submitComment(id, type) {
    const inp = document.getElementById('ci-' + id);
    const text = inp.value.trim();
    if (!text) return;
    inp.value = '';
    const res = await fetch('comment_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({target_type: type, target_id: id, noi_dung: text})
    });
    const data = await res.json();
    if (data.ok) {
        loadedComments[id] = false;
        await loadComments(id, type);
        const stat = document.getElementById('stat-cmt-' + id);
        if (stat) {
            const cur = parseInt(stat.textContent) || 0;
            stat.textContent = (cur + 1) + ' bình luận';
        }
    }
}

// ── Follow ──
async function doFollow(userId, btn) {
    const res = await fetch('follow_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({following_id: userId})
    });
    const data = await res.json();
    btn.textContent = data.action === 'followed' ? 'Đang theo dõi' : 'Theo dõi';
    btn.classList.toggle('following', data.action === 'followed');
}

// ── Post menu ──
function toggleMenu(id) {
    const el = document.getElementById('menu-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', e => {
    if (!e.target.classList.contains('post-menu-btn')) {
        document.querySelectorAll('.post-menu-dropdown').forEach(el => el.style.display = 'none');
    }
});

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Header shrink
const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
if(h&&b){const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/login-modal.js"></script>
</body>
</html>
