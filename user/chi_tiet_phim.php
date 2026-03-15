<?php
session_start();
include "../config/db.php";

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = mysqli_query($conn, "SELECT * FROM phim WHERE id = $id");
$phim   = mysqli_fetch_assoc($result);

if (!$phim) {
    http_response_code(404);
    echo "Không tìm thấy phim"; exit;
}

$suat_sql = "SELECT s.*, pc.ten_phong, r.ten_rap
             FROM suat_chieu s
             LEFT JOIN phong_chieu pc ON s.phong_id = pc.id
             LEFT JOIN rap r ON pc.rap_id = r.id
             WHERE s.phim_id = $id ORDER BY s.ngay, s.gio";
$suat_result = mysqli_query($conn, $suat_sql);
$suats = [];
while ($r = mysqli_fetch_assoc($suat_result)) $suats[] = $r;

function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fmt_time($t){ return $t ? date('H:i', strtotime($t)) : ''; }
function fmt_money($n){ return $n !== null ? number_format($n,0,',','.') . '₫' : '—'; }

// ── Social: reactions + comments cho phim ──
$me_id = (int)($_SESSION['user_id'] ?? 0);
$REACTIONS = ['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];

$react_counts = [];
$rc = mysqli_query($conn, "SELECT loai, COUNT(*) AS c FROM reactions WHERE target_type='phim' AND target_id=$id GROUP BY loai");
if ($rc) while ($r = mysqli_fetch_assoc($rc)) $react_counts[$r['loai']] = (int)$r['c'];
$tong_react = array_sum($react_counts);

$my_react = null;
if ($me_id) {
    $mr = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT loai FROM reactions WHERE target_type='phim' AND target_id=$id AND user_id=$me_id LIMIT 1"
    ));
    $my_react = $mr['loai'] ?? null;
}

$tong_cmt = 0;
$tc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM comments WHERE target_type='phim' AND target_id=$id"
));
if ($tc) $tong_cmt = (int)$tc['c'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($phim['ten_phim']) ?> — TTVH Cinemas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/movie-detail.css">
<link rel="stylesheet" href="../assets/css/login-modal.css">
<link rel="stylesheet" href="../assets/css/search.css">
    <link rel="stylesheet" href="../assets/css/user-menu.css">
<link rel="stylesheet" href="../assets/css/social.css">

</head>
<body class="movie-detail-page">

<!-- ── Header ── -->
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">TTVH</a>
    <nav class="header-nav">
      <div class="header-nav-left">
        <a href="index.php" class="nav-link"><span class="icon">🎬</span><span class="text">PHIM</span></a>
        <a href="sap_chieu.php" class="nav-link"><span class="icon">🗓️</span><span class="text">SẮP CHIẾU</span></a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="social.php" class="nav-link"><span class="icon">👥</span><span class="text">CỘNG ĐỒNG</span></a>
        <?php endif; ?>
      </div>
      <div class="search-wrap" id="searchWrap">
        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm phim..." autocomplete="off">
        <span class="search-icon">🔍</span><span class="search-spinner"></span>
        <div class="search-dropdown" id="searchDropdown"></div>
      </div>
      <div class="header-nav-right">
        <?php if (isset($_SESSION['user_id'])):
          $is_admin = (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin');
          $ten = htmlspecialchars($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'Tôi'));
          $av  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT avatar FROM users WHERE id=".(int)$_SESSION['user_id']));
          $avatar = $av['avatar'] ?? null;
        ?>
        <div class="user-menu-wrap">
          <button class="user-menu-btn" id="userMenuBtn">
            <?php if ($avatar): ?>
              <img src="../assets/images/avatars/<?= htmlspecialchars($avatar) ?>" class="user-menu-avatar" alt="">
            <?php else: ?>
              <div class="user-menu-initial"><?= mb_substr($_SESSION['ten_nguoi_dung'] ?? ($_SESSION['ten'] ?? 'U'),0,1) ?></div>
            <?php endif; ?>
            <span class="user-menu-name"><?= $ten ?></span>
            <span class="user-menu-arrow">▾</span>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-header">
              <span><?= $ten ?></span>
              <?php if ($is_admin): ?><span class="user-badge-admin">Admin</span><?php endif; ?>
            </div>
            <a href="profile.php?id=<?= (int)$_SESSION['user_id'] ?>" class="user-dropdown-item">👤 Trang cá nhân</a>
            <a href="../user/ve_cua_toi.php" class="user-dropdown-item">🎟️ Vé của tôi</a>
            <?php if ($is_admin): ?>
            <div class="user-dropdown-divider"></div>
            <a href="../admin/phim.php" class="user-dropdown-item">🎬 Quản lý phim</a>
            <a href="../admin/suat_chieu.php" class="user-dropdown-item">🗓️ Quản lý suất chiếu</a>
            <a href="../admin/quan_ly_user.php" class="user-dropdown-item">👥 Quản lý user</a>
            <?php endif; ?>
            <div class="user-dropdown-divider"></div>
            <a href="../auth/logout.php" class="user-dropdown-item danger"
               onclick="return confirm('Đăng xuất?')">🚪 Đăng xuất</a>
          </div>
        </div>
        <?php else: ?>
          <a href="../auth/login.php" class="btn btn-sm open-login-modal">
            <span class="icon">🔐</span><span class="text">ĐĂNG NHẬP</span>
          </a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<!-- ── Main ── -->
<main class="md-container">
  <a class="back" href="index.php">← Quay lại trang chủ</a>

  <!-- Hero card -->
  <section class="md-card">
    <div class="md-poster">
      <img src="../assets/images/<?= htmlspecialchars($phim['poster']) ?>"
           alt="<?= htmlspecialchars($phim['ten_phim']) ?>" loading="eager">
    </div>

    <div class="md-info">
      <h1 class="md-title"><?= htmlspecialchars($phim['ten_phim']) ?></h1>

      <div class="md-meta">
        <?php if (!empty($phim['the_loai'])): ?>
        <span class="chip genre-chip">🎭 <?= htmlspecialchars($phim['the_loai']) ?></span>
        <?php endif; ?>
        <?php
          $dur = $phim['thoi_luong'] ?? ($phim['thoi_gian'] ?? '');
          if ($dur):
        ?>
        <span class="chip time-chip">⏱ <?= htmlspecialchars($dur) ?> phút</span>
        <?php endif; ?>
        <?php if (!empty($phim['dao_dien'])): ?>
        <span class="chip">🎬 <?= htmlspecialchars($phim['dao_dien']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <?php if (!empty($phim['mo_ta'])): ?>
      <?php
        $mo_ta_full  = htmlspecialchars($phim['mo_ta']);
        $need_expand = mb_strlen($phim['mo_ta']) > 280;
        $mo_ta_short = htmlspecialchars(mb_strimwidth($phim['mo_ta'], 0, 280, ''));
      ?>
      <div class="md-desc-wrap">
        <p class="md-short" id="descText">
          <?php if ($need_expand): ?>
            <span class="desc-short"><?= nl2br($mo_ta_short) ?>…</span>
            <span class="desc-full" style="display:none"><?= nl2br($mo_ta_full) ?></span>
          <?php else: ?>
            <?= nl2br($mo_ta_full) ?>
          <?php endif; ?>
        </p>
        <?php if ($need_expand): ?>
        <button class="desc-toggle" id="descToggle" type="button">
          <span class="desc-toggle-text">Xem thêm</span>
          <span class="desc-toggle-icon">▾</span>
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="md-divider"></div>

      <!-- Extra details -->
      <div class="md-details">
        <?php if (!empty($phim['ngay_khoi_chieu'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">📅 Khởi chiếu</span>
          <span class="md-detail-value"><?= date('d/m/Y', strtotime($phim['ngay_khoi_chieu'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($phim['quoc_gia'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">🌍 Quốc gia</span>
          <span class="md-detail-value"><?= htmlspecialchars($phim['quoc_gia']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($phim['dien_vien'])): ?>
        <div class="md-detail-row">
          <span class="md-detail-label">🌟 Diễn viên</span>
          <span class="md-detail-value"><?= htmlspecialchars($phim['dien_vien']) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Actions -->
      <div class="md-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="chon_suat.php?phim_id=<?= (int)$phim['id'] ?>" class="btn-primary">🎟️ Đặt vé ngay</a>
        <?php else: ?>
          <a href="../auth/login.php" class="btn-outline open-login-modal">🔐 Đăng nhập để đặt vé</a>
        <?php endif; ?>
        <a href="#showtimes" class="btn-sm">🗓️ Xem suất chiếu</a>
      </div>
    </div>
  </section>

  <!-- Showtimes -->
  <section id="showtimes" class="showtimes-block">
    <h2 class="section-title">🗓️ Suất chiếu</h2>

    <?php if (empty($suats)): ?>
      <p class="muted">Hiện chưa có suất chiếu cho phim này.</p>
    <?php else: ?>
      <div class="showtimes-grid">
        <?php foreach ($suats as $s):
          $suat_id  = (int)$s['id'];
          $phong_id = (int)$s['phong_id'];
          $total    = null;
          if ($phong_id > 0) {
            $q = mysqli_query($conn,"SELECT COUNT(*) AS t FROM ghe WHERE phong_id = $phong_id");
            $total = (int)mysqli_fetch_assoc($q)['t'];
          }
          $bq = mysqli_query($conn,"SELECT COUNT(*) AS b FROM ve WHERE suat_chieu_id = $suat_id");
          $booked = (int)mysqli_fetch_assoc($bq)['b'];
          $avail  = is_null($total) ? null : max(0, $total - $booked);

          // Badge class
          $badge_class = 'available';
          if (!is_null($avail)) {
            if ($avail === 0) $badge_class = 'full';
            elseif ($total > 0 && $avail / $total < 0.25) $badge_class = 'few';
          }
        ?>
        <article class="showtime-card">
          <div class="st-top">
            <div class="st-datetime">
              <div class="st-date"><?= fmt_date($s['ngay']) ?></div>
              <div class="st-time"><?= fmt_time($s['gio']) ?></div>
            </div>
            <div class="st-price"><?= fmt_money($s['gia']) ?></div>
          </div>

          <div class="st-body">
            <div class="st-room">
              <?= htmlspecialchars($s['ten_rap'] ?: 'Rạp chưa đặt') ?>
              <?php if (!empty($s['ten_phong'])): ?> — <?= htmlspecialchars($s['ten_phong']) ?><?php endif; ?>
            </div>
            <div class="st-seats">
              <?php if (is_null($total) || $total == 0): ?>
                <span class="badge">Không xác định</span>
              <?php elseif ($avail === 0): ?>
                <span class="badge full">🔴 Hết ghế</span>
              <?php else: ?>
                <span class="badge <?= $badge_class ?>">
                  <?= $badge_class === 'few' ? '🟡' : '🟢' ?>
                  <?= $avail ?> / <?= $total ?> ghế trống
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="st-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
              <?php if (!is_null($avail) && $avail === 0): ?>
                <button class="btn-outline" disabled style="opacity:.5;cursor:not-allowed;width:100%;justify-content:center;">Hết ghế</button>
              <?php else: ?>
                <a href="chon_ghe.php?suat_id=<?= $suat_id ?>" class="btn-primary">Đặt ngay →</a>
              <?php endif; ?>
            <?php else: ?>
              <a href="../auth/login.php" class="btn-outline open-login-modal">🔐 Đăng nhập để đặt</a>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  
  
  <section class="film-social-block">
  <div class="film-social-title">🎭 Cảm xúc & Bình luận</div>
 
  <!-- Tổng reaction summary -->
  <div class="film-reactions">
    <?php foreach ($REACTIONS as $key => $emoji):
      $cnt = $react_counts[$key] ?? 0;
      if ($cnt > 0):
    ?>
    <span style="font-size:14px;color:#94a3b8;"><?= $emoji ?> <strong style="color:#f1f5f9;"><?= $cnt ?></strong></span>
    <?php endif; endforeach; ?>
    <?php if ($tong_react > 0): ?>
    <span class="film-react-summary">— <?= $tong_react ?> lượt cảm xúc</span>
    <?php endif; ?>
  </div>
 
  <!-- Reaction buttons -->
  <?php if ($me_id): ?>
  <div class="post-actions" style="margin-bottom:14px;padding:0;">
    <div class="reaction-wrap" id="rw-film-<?= $id ?>"
         onmouseenter="scheduleShowReactions(<?= $id ?>)"
         onmouseleave="cancelAndHideReactions(<?= $id ?>)">
      <button class="action-btn <?= $my_react ? 'reacted' : '' ?>"
              id="rbtn-film-<?= $id ?>"
              data-current="<?= $my_react ?? '' ?>"
              onclick="toggleReactFilm(<?= $id ?>)">
        <?php if ($my_react): ?>
          <?= $REACTIONS[$my_react] ?> <span id="rbtn-label-<?= $id ?>"><?= ucfirst($my_react) ?></span>
        <?php else: ?>
          👍 <span id="rbtn-label-<?= $id ?>">Thả cảm xúc</span>
        <?php endif; ?>
      </button>
      <div class="reaction-picker" id="rpicker-film-<?= $id ?>" style="display:none">
        <?php foreach ($REACTIONS as $key => $emoji): ?>
        <button class="reaction-emoji"
                onclick="doReactFilm(<?= $id ?>, '<?= $key ?>')"
                title="<?= ucfirst($key) ?>"><?= $emoji ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <p style="font-size:13px;color:#64748b;margin-bottom:14px;">
    <a href="../auth/login.php" style="color:#a78bfa;font-weight:700;">Đăng nhập</a> để thả cảm xúc và bình luận
  </p>
  <?php endif; ?>
 
  <!-- ⚠️ Spoiler warning — user phải bấm để xem comment -->
  <button class="spoiler-toggle-btn" id="spoilerBtn" onclick="toggleSpoilerComments()">
    <span>⚠️</span>
    <span id="spoilerBtnText">Xem bình luận (<?= $tong_cmt ?>) — Có thể chứa spoiler!</span>
    <span style="margin-left:auto;">▾</span>
  </button>
 
  <div id="filmComments" style="display:none">
    <div class="comment-list" id="fclist-<?= $id ?>"></div>
 
    <?php if ($me_id):
      $me_info_c = mysqli_fetch_assoc(mysqli_query($conn,"SELECT ten,avatar FROM users WHERE id=$me_id"));
    ?>
    <div class="comment-compose" style="margin-top:10px;">
      <?php if (!empty($me_info_c['avatar'])): ?>
        <img src="../assets/images/avatars/<?= htmlspecialchars($me_info_c['avatar']) ?>" class="avatar-xs" alt="">
      <?php else: ?>
        <div class="avatar-placeholder-xs"><?= mb_substr($me_info_c['ten'],0,1) ?></div>
      <?php endif; ?>
      <div class="comment-input-wrap">
        <input type="text" class="comment-input" id="fci-<?= $id ?>"
               placeholder="Bình luận về phim (cẩn thận spoiler!)..."
               onkeydown="if(event.key==='Enter')submitFilmComment(<?= $id ?>)">
        <button class="comment-send" onclick="submitFilmComment(<?= $id ?>)">➤</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
 
<!-- ── Script cần thêm vào cuối trang (trước </body>) ── -->
<script>
const FILM_REACTIONS = <?= json_encode($REACTIONS) ?>;
let filmReactionTimer = {};
let filmCommentsLoaded = false;
let spoilerOpen = false;
 
// Hover vào wrapper → delay 600ms rồi hiện picker
function scheduleShowReactions(id) {
    clearTimeout(filmReactionTimer[id]);
    filmReactionTimer[id] = setTimeout(() => {
        const el = document.getElementById('rpicker-film-' + id);
        if (el) el.style.display = 'flex';
    }, 600);
}
function cancelAndHideReactions(id) {
    clearTimeout(filmReactionTimer[id]);
    filmReactionTimer[id] = setTimeout(() => {
        const el = document.getElementById('rpicker-film-' + id);
        if (el) el.style.display = 'none';
    }, 200);
}
function showReactionsFilm(id){clearTimeout(filmReactionTimer[id]);document.getElementById('rpicker-film-'+id).style.display='flex';}
function hideReactionsFilm(id){clearTimeout(filmReactionTimer[id]);const el=document.getElementById('rpicker-film-'+id);if(el)el.style.display='none';}

// Click nút = toggle (hủy nếu đã react, like nếu chưa)
function toggleReactFilm(id) {
    cancelAndHideReactions(id);
    const btn = document.getElementById('rbtn-film-' + id);
    const currentLoai = btn ? btn.dataset.current : '';
    if (currentLoai) {
        doReactFilm(id, currentLoai); // gửi cùng loại → server xoá
    } else {
        doReactFilm(id, 'like');
    }
}
function quickReactFilm(id,loai){cancelAndHideReactions(id);doReactFilm(id,loai);}
async function doReactFilm(id, loai) {
    cancelAndHideReactions(id);
    const res = await fetch('reaction_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({target_type: 'phim', target_id: id, loai})
    });
    const data = await res.json();
    const btn  = document.getElementById('rbtn-film-' + id);
    if (btn) {
        if (data.action === 'removed') {
            btn.innerHTML = '👍 <span id="rbtn-label-'+id+'">Thả cảm xúc</span>';
            btn.classList.remove('reacted');
            btn.dataset.current = '';
        } else {
            const emoji = FILM_REACTIONS[data.current_loai] || '👍';
            const name  = data.current_loai ? data.current_loai.charAt(0).toUpperCase()+data.current_loai.slice(1) : 'Thả cảm xúc';
            btn.innerHTML = emoji + ' <span id="rbtn-label-'+id+'">' + name + '</span>';
            btn.classList.add('reacted');
            btn.dataset.current = data.current_loai || '';
        }
    }
    // Cập nhật summary emoji + tổng
    if (data.breakdown !== undefined) updateReactSummary(id, data.breakdown, data.total);
}
function updateReactSummary(id, breakdown, total) {
    const stack   = document.getElementById('reactStack-'   + id);
    const totalEl = document.getElementById('reactTotal-'   + id);
    const tooltip = document.getElementById('reactTooltip-' + id);
    const sorted  = Object.entries(breakdown).sort((a,b) => b[1]-a[1]);
    if (stack)   stack.innerHTML   = sorted.slice(0,3).map(([k])=>`<span class="react-emoji-bubble">${FILM_REACTIONS[k]||'👍'}</span>`).join('');
    if (totalEl) totalEl.textContent = total > 0 ? total+' lượt cảm xúc' : 'Chưa có cảm xúc nào';
    if (tooltip) tooltip.innerHTML = total === 0 ? '' : sorted.map(([k,cnt])=>`<div class="react-detail-row"><span>${FILM_REACTIONS[k]||'?'}</span><span>${k.charAt(0).toUpperCase()+k.slice(1)}</span><span class="react-detail-count">${cnt}</span></div>`).join('');
}
// ── Helper: render 1 comment (có replies) ──
function renderComment(c, isReply=false) {
    const avatar = c.avatar
        ? `<img src="../assets/images/avatars/${c.avatar}" class="avatar-xs" alt="">`
        : `<div class="avatar-placeholder-xs">${escHtml(c.ten.charAt(0))}</div>`;

    const replyBtn = !isReply
        ? `<button class="reply-btn" onclick="showReplyBox(${c.id})">Trả lời</button>`
        : '';

    const replies = (!isReply && c.replies && c.replies.length)
        ? `<div class="reply-list" id="replies-${c.id}">
               ${c.replies.map(r => renderComment(r, true)).join('')}
           </div>`
        : `<div class="reply-list" id="replies-${c.id}"></div>`;

    const replyBox = !isReply ? `
        <div class="reply-compose" id="rbox-${c.id}" style="display:none">
            <div class="comment-input-wrap" style="margin-left:8px;">
                <input type="text" class="comment-input" id="ri-${c.id}"
                       placeholder="Trả lời ${escHtml(c.ten)}..."
                       onkeydown="if(event.key==='Enter')submitReply(${c.id})">
                <button class="comment-send" onclick="submitReply(${c.id})">➤</button>
            </div>
        </div>` : '';

    const wrapClass = isReply ? 'comment-item reply-item' : 'comment-item';
    return `
        <div class="${wrapClass}" id="cmt-${c.id}">
            <div class="comment-avatar">${avatar}</div>
            <div class="comment-body">
                <div class="comment-bubble">
                    <a href="profile.php?id=${c.user_id}" class="comment-name">${escHtml(c.ten)}</a>
                    <span class="comment-text">${escHtml(c.noi_dung)}</span>
                </div>
                <div class="comment-meta">
                    ${c.time_ago}
                    ${replyBtn}
                </div>
                ${replies}
                ${replyBox}
            </div>
        </div>`;
}

function showReplyBox(parentId) {
    // Ẩn tất cả reply box khác trước
    document.querySelectorAll('.reply-compose').forEach(el => el.style.display='none');
    const box = document.getElementById('rbox-' + parentId);
    if (box) {
        box.style.display = 'block';
        const inp = document.getElementById('ri-' + parentId);
        if (inp) inp.focus();
    }
}

async function submitReply(parentId) {
    const inp  = document.getElementById('ri-' + parentId);
    const text = inp ? inp.value.trim() : '';
    if (!text) return;
    inp.value = '';

    const filmId = <?= $id ?>;
    const res  = await fetch('comment_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            target_type: 'phim',
            target_id:   filmId,
            parent_id:   parentId,
            noi_dung:    text
        })
    });
    const data = await res.json();
    if (data.ok && data.comment) {
        const replyList = document.getElementById('replies-' + parentId);
        if (replyList) {
            replyList.insertAdjacentHTML('beforeend', renderComment(data.comment, true));
        }
        // Ẩn reply box
        const box = document.getElementById('rbox-' + parentId);
        if (box) box.style.display = 'none';
        // Cập nhật đếm spoiler button
        const spoilerBtn = document.getElementById('spoilerBtnText');
        if (spoilerBtn) {
            const cur = parseInt(spoilerBtn.textContent.match(/\d+/)?.[0] || 0);
            spoilerBtn.textContent = `Xem bình luận (${cur+1}) — Có thể chứa spoiler!`;
        }
    }
}

async function toggleSpoilerComments() {
    spoilerOpen = !spoilerOpen;
    const sec = document.getElementById('filmComments');
    const btn = document.getElementById('spoilerBtnText');
    sec.style.display = spoilerOpen ? 'block' : 'none';
    if (spoilerOpen) {
        btn.textContent = 'Ẩn bình luận ▴';
        if (!filmCommentsLoaded) {
            const list = document.getElementById('fclist-<?= $id ?>');
            list.innerHTML = '<div style="text-align:center;padding:12px;color:#64748b;font-size:12px;">Đang tải...</div>';
            const r = await fetch(`comment_api.php?target_type=phim&target_id=<?= $id ?>`);
            const d = await r.json();
            filmCommentsLoaded = true;
            if (!d.comments.length) {
                list.innerHTML = '<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận nào</div>';
                return;
            }
            list.innerHTML = d.comments.map(c => renderComment(c)).join('');
        }
    } else {
        btn.textContent = `Xem bình luận — Có thể chứa spoiler!`;
    }
}

async function submitFilmComment(id) {
    const inp  = document.getElementById('fci-' + id);
    const text = inp ? inp.value.trim() : '';
    if (!text) return;
    inp.value = '';
    const res  = await fetch('comment_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({target_type:'phim', target_id:id, noi_dung:text})
    });
    const data = await res.json();
    if (data.ok && data.comment) {
        const list = document.getElementById('fclist-' + id);
        if (!filmCommentsLoaded || list.innerHTML.includes('Chưa có')) {
            list.innerHTML = '';
            filmCommentsLoaded = true;
        }
        list.insertAdjacentHTML('beforeend', renderComment(data.comment));
        // Cập nhật spoiler button count
        const spoilerBtn = document.getElementById('spoilerBtnText');
        if (spoilerBtn && spoilerBtn.textContent.includes('Ẩn')) {
            // đang mở — count sẽ tự cập nhật
        } else if (spoilerBtn) {
            const cur = parseInt(spoilerBtn.textContent.match(/\d+/)?.[0] || 0);
            spoilerBtn.textContent = `Xem bình luận (${cur+1}) — Có thể chứa spoiler!`;
        }
    }
}
function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>
 

</main>

<footer class="footer">
  <div>© <?= date('Y') ?> TTVH Cinemas — Thiết kế gọn, responsive.</div>
</footer>

<script>
(function () {
  /* Header shrink */
  const header = document.querySelector('.header');
  const body   = document.body;
  if (header) {
    const onScroll = () => {
      header.classList.toggle('shrink', window.scrollY > 50);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Desc expand/collapse */
  const toggle = document.getElementById('descToggle');
  if (toggle) {
    const short  = document.querySelector('.desc-short');
    const full   = document.querySelector('.desc-full');
    const label  = toggle.querySelector('.desc-toggle-text');
    const icon   = toggle.querySelector('.desc-toggle-icon');
    let expanded = false;
    toggle.addEventListener('click', () => {
      expanded = !expanded;
      short.style.display = expanded ? 'none'  : 'inline';
      full.style.display  = expanded ? 'inline': 'none';
      label.textContent   = expanded ? 'Thu gọn' : 'Xem thêm';
      icon.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
    });
  }

  /* Showtime card stagger fade-up */
  const cards = document.querySelectorAll('.showtime-card');
  if (!cards.length) return;
  const STAGGER = 70;
  let batch = 0, bTimer = null;
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const card = e.target;
      const delay = card.dataset.init === '1'
        ? Math.min(parseInt(card.dataset.idx) * STAGGER, 350)
        : batch * 60;
      if (card.dataset.init !== '1') {
        batch++;
        clearTimeout(bTimer);
        bTimer = setTimeout(() => { batch = 0; }, 300);
      }
      setTimeout(() => card.classList.add('is-visible'), delay);
      io.unobserve(card);
    });
  }, { threshold: 0, rootMargin: '0px 0px 250px 0px' });

  cards.forEach((c, i) => {
    c.dataset.idx = i;
    if (c.getBoundingClientRect().top < window.innerHeight + 250) c.dataset.init = '1';
    io.observe(c);
  });
})();
</script>

<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/login-modal.js"></script>
</body>
</html>