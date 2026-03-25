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
function fmt_rating_value($n){
    $n = (float)$n;
    if ($n <= 0) return '0';
    $rounded = round($n * 2) / 2;
    if (abs($rounded - round($rounded)) < 0.001) return number_format($rounded, 0, '.', '');
    return number_format($rounded, 1, '.', '');
}
function rating_fill_percent($current, $starIndex){
    $fill = max(0, min(1, (float)$current - ((int)$starIndex - 1)));
    return (int)round($fill * 100);
}

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

// ── Rating (1-5) ──
$rating_avg = 0;
$rating_total = 0;
$my_rating = 0.0;
$can_rate_movie = false;
$rating_permission_message = 'Đăng nhập để chấm điểm và lưu đánh giá của bạn.';
$is_rating_admin = (($_SESSION['vai_tro'] ?? '') === 'admin');
$has_ticket_for_rating = false;

if ($me_id) {
    if ($is_rating_admin) {
        $can_rate_movie = true;
        $rating_permission_message = 'Admin có thể đánh giá mà không cần mua vé.';
    } else {
        $ticket_check = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT 1 AS ok
             FROM ve v
             INNER JOIN suat_chieu sc ON sc.id = v.suat_chieu_id
             WHERE v.user_id = $me_id AND sc.phim_id = $id
             LIMIT 1"
        ));
        $has_ticket_for_rating = (bool)$ticket_check;
        $can_rate_movie = $has_ticket_for_rating;
        $rating_permission_message = $can_rate_movie
            ? 'Bạn đã mua vé cho phim này và có thể đánh giá.'
            : 'Bạn cần mua vé phim này trước khi đánh giá.';
    }
}

if (table_exists($conn, 'ratings')) {
    $rating_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE phim_id=$id"
    ));
    if ($rating_row) {
        $rating_avg = $rating_row['avg_rating'] !== null ? round((float)$rating_row['avg_rating'], 1) : 0;
        $rating_total = (int)$rating_row['total'];
    }
    if ($me_id) {
        $mr = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT rating FROM ratings WHERE user_id=$me_id AND phim_id=$id LIMIT 1"
        ));
        if ($mr) $my_rating = (float)$mr['rating'];
    }
}

// ── Notifications count ──
$notif_unread = 0;
if ($me_id) {
    if (table_exists($conn, 'notifications')) {
        $nr = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$me_id AND is_read=0"
        ));
        $notif_unread = (int)($nr['c'] ?? 0);
    }
}
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
<link rel="stylesheet" href="../assets/css/theme-toggle.css">

<link rel="stylesheet" href="../assets/css/mobile-premium.css?v=<?php echo time(); ?>">
</head>
<body class="movie-detail-page">

<!-- ── Header ── -->
<?php $active_page = ''; include 'components/header.php'; ?>
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
        <?php if (!empty($phim['trailer_url'])): ?>
          <button class="btn-sm trailer-btn" type="button" data-trailer="<?= htmlspecialchars($phim['trailer_url']) ?>">▶ Trailer</button>
        <?php endif; ?>
      </div>

      <div class="rating-block">
        <div class="rating-overview">
          <div class="rating-overview-badge">
            <span class="rating-overview-icon">★</span>
            <div class="rating-overview-copy">
              <div class="rating-overview-value" id="avgRating"><?= $rating_total > 0 ? fmt_rating_value($rating_avg) : 'Chưa có' ?></div>
              <div class="rating-overview-label">Điểm cộng đồng</div>
            </div>
          </div>
          <div class="rating-overview-meta">
            <strong id="ratingCount"><?= $rating_total ?></strong>
            <span>lượt đánh giá</span>
          </div>
        </div>

        <div class="rating-action-card">
          <div class="rating-action-head">
            <div>
              <div class="rating-action-title">Bạn thấy phim này thế nào?</div>
              <div class="rating-action-subtitle" id="ratingHelper">
                <?php if ($me_id): ?>
                  <?= htmlspecialchars($rating_permission_message) ?>
                <?php else: ?>
                  Đăng nhập để chấm điểm và lưu đánh giá của bạn.
                <?php endif; ?>
              </div>
            </div>
            <div class="rating-user-note" id="myRatingText">
              <?= $my_rating > 0 ? ('Bạn đã chấm ' . fmt_rating_value($my_rating) . '/5 sao') : 'Bạn chưa chấm phim này' ?>
            </div>
          </div>

          <div class="rating-stars <?= (!$me_id || !$can_rate_movie) ? 'is-disabled' : '' ?>" id="ratingStars" data-current="<?= number_format((float)$my_rating, 1, '.', '') ?>" data-film-id="<?= (int)$id ?>" data-can-rate="<?= ($me_id && $can_rate_movie) ? '1' : '0' ?>">
            <?php for ($i=1; $i<=5; $i++): ?>
              <button
                type="button"
                class="rating-star <?= rating_fill_percent($my_rating, $i) > 0 ? 'active' : '' ?>"
                data-value="<?= $i ?>"
                aria-label="Chấm <?= $i ?> sao"
                aria-pressed="<?= rating_fill_percent($my_rating, $i) > 0 ? 'true' : 'false' ?>"
                style="--fill-percent: <?= rating_fill_percent($my_rating, $i) ?>%;"
              ><span class="rating-star-icon">★</span></button>
            <?php endfor; ?>
          </div>

          <div class="rating-scale-note">
            Bấm nửa trái để chấm <strong>x.5</strong>, bấm nửa phải để chấm <strong>x.0</strong>.
          </div>

          <?php if (!$me_id): ?>
          <div class="rating-login-hint">
            <a href="../auth/login.php">Đăng nhập</a> để chấm điểm và đồng bộ đánh giá của bạn.
          </div>
          <?php elseif (!$can_rate_movie): ?>
          <div class="rating-login-hint">
            Chỉ user đã mua vé phim này mới được đánh giá.
          </div>
          <?php endif; ?>
        </div>
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
              <span class="badge <?= $badge_class ?>" data-suat-id="<?= $suat_id ?>" id="badge-<?= $suat_id ?>">
                <?php if (is_null($total) || $total == 0): ?>
                  Không xác định
                <?php elseif ($avail === 0): ?>
                  🔴 Hết ghế
                <?php else: ?>
                  <?= $badge_class === 'few' ? '🟡' : '🟢' ?> <?= $avail ?> / <?= $total ?> ghế trống
                <?php endif; ?>
              </span>
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
const CURRENT_USER_ID = <?= json_encode($me_id) ?>;
const IS_ADMIN        = <?= json_encode((bool)(isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin')) ?>;
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
                    ${(CURRENT_USER_ID && (parseInt(c.user_id) === parseInt(CURRENT_USER_ID) || IS_ADMIN))
                        ? `<button class="delete-comment-btn" onclick="deleteComment(${c.id}, ${c.parent_id || 0})">Xoá</button>`
                        : ''}
                </div>
                ${replies}
                ${replyBox}
            </div>
        </div>`;
}

async function deleteComment(cmtId, parentId) {
    if (!confirm('Xoá bình luận này?')) return;
    const res = await fetch('comment_api.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({comment_id: cmtId})
    });
    const data = await res.json();
    if (data.ok) {
        const el = document.getElementById('cmt-' + cmtId);
        if (el) {
            // Animation fade out rồi xoá
            el.style.transition = 'opacity .25s, transform .25s';
            el.style.opacity = '0';
            el.style.transform = 'translateX(-10px)';
            setTimeout(() => el.remove(), 250);
        }
        // Cập nhật số comment trên spoiler button
        const btn = document.getElementById('spoilerBtnText');
        if (btn) {
            const match = btn.textContent.match(/\d+/);
            if (match) {
                const cur = parseInt(match[0]);
                if (cur > 0) {
                    btn.textContent = btn.textContent.replace(/\d+/, cur - 1);
                }
            }
        }
    } else {
        alert(data.msg || 'Không thể xoá');
    }
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

<!-- Trailer modal -->
<div class="trailer-modal" id="trailerModal">
  <div class="trailer-box">
    <button class="trailer-close" id="trailerClose">×</button>
    <iframe class="trailer-iframe" id="trailerIframe" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
  </div>
</div>

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

  // ── SSE Real-time suất ghế ──
  const phimId = <?= json_encode($id) ?>;
  const sseSuat = new EventSource(`suat_updates.php?phim_id=${phimId}`);
  sseSuat.addEventListener('suats_update', e => {
    const suats = JSON.parse(e.data);
    suats.forEach(s => {
      const badge = document.getElementById(`badge-${s.id}`);
      if (!badge) return;
      const total = s.total_ghe || 0;
      const avail = s.avail;
      if (total === 0) {
        badge.innerHTML = 'Không xác định';
        badge.className = 'badge';
      } else if (avail === 0) {
        badge.innerHTML = '🔴 Hết ghế';
        badge.className = 'badge full';
      } else {
        const perc = avail / total;
        const cls = perc < 0.25 ? 'few' : 'available';
        badge.innerHTML = (cls === 'few' ? '🟡' : '🟢') + ` ${avail} / ${total} ghế trống`;
        badge.className = `badge ${cls}`;
      }
    });
    console.log('Suất ghế updated:', suats);
  });
  sseSuat.onerror = () => console.log('Suất SSE error, fallback polling');

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

<script>
(function() {
  // ── SSE Realtime: reactions + comments cho phim ──
  var phimId  = <?= json_encode($id) ?>;
  var sseFilm = null;

  function connectFilmSSE() {
    if (sseFilm) sseFilm.close();
    sseFilm = new EventSource('film_events.php?phim_id=' + phimId);

    // Snapshot ban đầu
    sseFilm.addEventListener('init', function(e) {
      var data = JSON.parse(e.data);
      if (data.reactions) updateReactSummary(phimId, data.reactions.breakdown, data.reactions.total);
      if (data.comments !== undefined) updateSpoilerCount(data.comments);
    });

    // Ai thả/hủy reaction
    sseFilm.addEventListener('reactions_update', function(e) {
      var data = JSON.parse(e.data);
      updateReactSummary(phimId, data.breakdown, data.total);
    });

    // Ai comment mới
    sseFilm.addEventListener('comments_update', function(e) {
      var data = JSON.parse(e.data);
      updateSpoilerCount(data.total);

      // Nếu spoiler đang mở → reload danh sách comment để thấy comment mới
      if (spoilerOpen) {
        var filmId = phimId;
        // Delay 300ms để DB chắc chắn đã commit trước khi fetch
        setTimeout(function() {
          fetch('comment_api.php?target_type=phim&target_id=' + filmId + '&_=' + Date.now())
            .then(function(r){ return r.json(); })
            .then(function(d){
              filmCommentsLoaded = true;
              var list = document.getElementById('fclist-' + filmId);
              if (!list) return;
              if (!d.comments || !d.comments.length) {
                list.innerHTML = '<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận nào</div>';
                return;
              }
              list.innerHTML = d.comments.map(function(cm){ return renderComment(cm); }).join('');
            })
            .catch(function(){});
        }, 300);
      }
    });

    // Server đóng sau 55s → reconnect
    sseFilm.addEventListener('reconnect', function() {
      sseFilm.close();
      setTimeout(connectFilmSSE, 500);
    });

    sseFilm.onerror = function() {
      if (sseFilm.readyState === EventSource.CLOSED) {
        setTimeout(connectFilmSSE, 3000);
      }
    };
  }

  function updateSpoilerCount(total) {
    var btn = document.getElementById('spoilerBtnText');
    if (btn && btn.textContent.indexOf('Ẩn') === -1) {
      btn.textContent = 'Xem bình luận (' + total + ') — Có thể chứa spoiler!';
    }
  }

  function updateReactSummary(id, breakdown, total) {
    var stack   = document.getElementById('reactStack-'   + id);
    var totalEl = document.getElementById('reactTotal-'   + id);
    var tooltip = document.getElementById('reactTooltip-' + id);

    // Tạo elements nếu chưa có
    if (!stack || !totalEl) {
      var bar = document.getElementById('reactBar-' + id);
      if (!bar) {
        // Tìm film-reactions div cũ và tạo bar mới
        var oldReact = document.querySelector('.film-reactions');
        if (!oldReact) return;
        bar = document.createElement('div');
        bar.id = 'reactBar-' + id;
        bar.className = 'react-bar';
        var summary = document.createElement('div');
        summary.className = 'react-summary';
        summary.id = 'reactSummary-' + id;
        stack = document.createElement('div');
        stack.className = 'react-emoji-stack';
        stack.id = 'reactStack-' + id;
        totalEl = document.createElement('span');
        totalEl.className = 'react-total-text';
        totalEl.id = 'reactTotal-' + id;
        tooltip = document.createElement('div');
        tooltip.className = 'react-detail-tooltip';
        tooltip.id = 'reactTooltip-' + id;
        summary.appendChild(stack);
        summary.appendChild(totalEl);
        summary.appendChild(tooltip);
        bar.appendChild(summary);
        oldReact.parentNode.replaceChild(bar, oldReact);
      }
    }

    var EMOJI = {like:'👍',love:'❤️',haha:'😂',wow:'😮',sad:'😢',angry:'😡'};
    var sorted = Object.entries(breakdown).sort(function(a,b){return b[1]-a[1];});

    if (stack) {
      stack.innerHTML = sorted.slice(0,3).map(function(e){
        return '<span class="react-emoji-bubble">' + (EMOJI[e[0]]||'👍') + '</span>';
      }).join('');
    }
    if (totalEl) {
      totalEl.textContent = total > 0 ? total + ' lượt cảm xúc' : 'Chưa có cảm xúc nào';
    }
    if (tooltip) {
      tooltip.innerHTML = total === 0 ? '' : sorted.map(function(e){
        var name = e[0].charAt(0).toUpperCase() + e[0].slice(1);
        return '<div class="react-detail-row"><span>'+(EMOJI[e[0]]||'?')+'</span><span>'+name+'</span><span class="react-detail-count">'+e[1]+'</span></div>';
      }).join('');
    }
  }

  connectFilmSSE();
})();
</script>
<script>
// User dropdown
(function(){
  var btn = document.getElementById('userMenuBtn');
  var dd  = document.getElementById('userDropdown');
  if (!btn || !dd) return;
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    dd.classList.toggle('open');
  });
  document.addEventListener('click', function() {
    dd.classList.remove('open');
  });
})();
</script>
<script>
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

// Trailer modal
(function(){
  var modal = document.getElementById('trailerModal');
  var iframe = document.getElementById('trailerIframe');
  var closeBtn = document.getElementById('trailerClose');
  var btns = document.querySelectorAll('.trailer-btn');
  if (!modal || !iframe || !btns.length) return;

  function toEmbed(url) {
    if (!url) return '';
    if (url.includes('youtube.com/watch')) {
      var id = new URL(url).searchParams.get('v');
      return id ? ('https://www.youtube.com/embed/' + id + '?autoplay=1') : url;
    }
    if (url.includes('youtu.be/')) {
      var id2 = url.split('youtu.be/')[1].split(/[?&]/)[0];
      return 'https://www.youtube.com/embed/' + id2 + '?autoplay=1';
    }
    return url;
  }

  function open(url) {
    iframe.src = toEmbed(url);
    modal.classList.add('open');
  }
  function close() {
    modal.classList.remove('open');
    iframe.src = '';
  }

  btns.forEach(function(b){
    b.addEventListener('click', function(){
      open(b.getAttribute('data-trailer'));
    });
  });
  modal.addEventListener('click', function(e){
    if (e.target === modal) close();
  });
  if (closeBtn) closeBtn.addEventListener('click', close);
})();

// Rating
(function(){
  var starsWrap = document.getElementById('ratingStars');
  if (!starsWrap) return;
  var stars = Array.prototype.slice.call(starsWrap.querySelectorAll('.rating-star'));
  var current = normalizeRating(parseFloat(starsWrap.dataset.current || '0'));
  var filmId = parseInt(starsWrap.dataset.filmId || '0');
  var canRate = starsWrap.dataset.canRate === '1';
  var avgEl = document.getElementById('avgRating');
  var countEl = document.getElementById('ratingCount');
  var helperEl = document.getElementById('ratingHelper');
  var myRatingEl = document.getElementById('myRatingText');
  var isLogged = <?= $me_id ? 'true' : 'false' ?>;
  var isSaving = false;
  var permissionMessage = helperEl ? helperEl.textContent.trim() : '';

  function normalizeRating(value) {
    var num = parseFloat(value || '0');
    if (!Number.isFinite(num)) return 0;
    num = Math.max(0, Math.min(5, num));
    return Math.round(num * 2) / 2;
  }

  function formatRatingValue(value) {
    var num = normalizeRating(value);
    if (num <= 0) return '0';
    return Number.isInteger(num) ? String(num) : num.toFixed(1);
  }

  function formatAvg(avg, total) {
    if (!total || !avg) return 'Chưa có';
    return Number(avg).toFixed(1);
  }

  function setHelper(message) {
    if (helperEl) helperEl.textContent = message;
  }

  function getRestingHelperMessage() {
    if (!isLogged) return 'Đăng nhập để chấm điểm và lưu đánh giá của bạn.';
    if (!canRate) return permissionMessage || 'Bạn cần mua vé phim này trước khi đánh giá.';
    if (current > 0) return 'Bấm nửa trái hoặc phải để đổi đánh giá của bạn.';
    return 'Bấm nửa trái hoặc phải của ngôi sao để chấm 0.5 sao.';
  }

  function setMyRatingText(val) {
    if (!myRatingEl) return;
    myRatingEl.textContent = val > 0
      ? ('Bạn đã chấm ' + formatRatingValue(val) + '/5 sao')
      : 'Bạn chưa chấm phim này';
  }

  function setSummary(avg, total) {
    if (avgEl) avgEl.textContent = formatAvg(Number(avg || 0), Number(total || 0));
    if (countEl) countEl.textContent = Number(total || 0);
  }

  function setSavingState(saving) {
    isSaving = saving;
    starsWrap.classList.toggle('is-loading', saving);
  }

  function setCanRate(value) {
    canRate = !!value;
    starsWrap.dataset.canRate = canRate ? '1' : '0';
    starsWrap.classList.toggle('is-disabled', !canRate);
  }

  function render(val) {
    var normalized = normalizeRating(val);
    stars.forEach(function(s){
      var v = parseInt(s.dataset.value || '0');
      var fill = Math.max(0, Math.min(1, normalized - (v - 1)));
      var fillPercent = Math.round(fill * 100);
      s.classList.toggle('active', fillPercent > 0);
      s.style.setProperty('--fill-percent', fillPercent + '%');
      s.setAttribute('aria-pressed', fillPercent > 0 ? 'true' : 'false');
    });
  }

  function getValueFromPointer(star, event) {
    var starValue = parseInt(star.dataset.value || '0');
    if (!starValue) return 0;
    var rect = star.getBoundingClientRect();
    var offset = event.clientX - rect.left;
    var value = offset <= rect.width / 2 ? starValue - 0.5 : starValue;
    return normalizeRating(value);
  }

  render(current);
  setMyRatingText(current);
  setHelper(getRestingHelperMessage());

  async function syncRatingSummary() {
    if (!filmId) return;
    try {
      const res = await fetch('rating_api.php?phim_id=' + filmId);
      const data = await res.json();
      if (!data.ok) return;
      current = normalizeRating(parseFloat(data.my || 0));
      permissionMessage = typeof data.permission_message === 'string' ? data.permission_message : permissionMessage;
      setCanRate(!!data.can_rate);
      starsWrap.dataset.current = current.toFixed(1);
      render(current);
      setSummary(data.avg, data.total);
      setMyRatingText(current);
      setHelper(getRestingHelperMessage());
    } catch (err) {
      console.error('Rating sync failed', err);
    }
  }

  syncRatingSummary();

  stars.forEach(function(s){
    s.addEventListener('mousemove', function(event){
      if (isSaving || !canRate) return;
      var preview = getValueFromPointer(s, event);
      render(preview);
      if (isLogged) setHelper('Chấm ' + formatRatingValue(preview) + '/5 sao');
    });
    s.addEventListener('mouseleave', function(){
      if (isSaving || !canRate) return;
      render(current);
      setHelper(getRestingHelperMessage());
    });
    s.addEventListener('click', async function(e){
      e.preventDefault();
      if (isSaving) return;
      var val = getValueFromPointer(s, e);
      if (!isLogged) {
        alert('Bạn cần đăng nhập để đánh giá.');
        return;
      }
      if (!canRate) {
        alert('Bạn cần mua vé phim này trước khi đánh giá.');
        return;
      }
      try {
        setSavingState(true);
        setHelper('Đang lưu đánh giá của bạn...');
        const res = await fetch('rating_api.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({phim_id: <?= (int)$id ?>, rating: val})
        });
        const data = await res.json();
        if (data.ok) {
          current = normalizeRating(parseFloat(data.my || val));
          setCanRate(true);
          starsWrap.dataset.current = current.toFixed(1);
          render(current);
          setSummary(data.avg, data.total);
          setMyRatingText(current);
          setHelper('Đã ghi nhận ' + formatRatingValue(current) + '/5 sao của bạn.');
        } else {
          permissionMessage = data.message || permissionMessage;
          setHelper(data.message || 'Không thể lưu đánh giá lúc này.');
          alert(data.message || 'Không thể đánh giá.');
        }
      } catch (err) {
        setHelper('Có lỗi xảy ra khi gửi đánh giá.');
        alert('Có lỗi khi gửi đánh giá. Vui lòng thử lại.');
      } finally {
        setSavingState(false);
        render(current);
      }
    });
  });
})();
</script>
<script>
/* Trailer Modal Logic */
document.addEventListener('DOMContentLoaded', () => {
  const trailerBtn = document.querySelector('.trailer-btn');
  const trailerModal = document.getElementById('trailerModal');
  const trailerClose = document.getElementById('trailerClose');
  const trailerIframe = document.getElementById('trailerIframe');

  if (trailerBtn && trailerModal && trailerIframe) {
    trailerBtn.addEventListener('click', () => {
      let url = trailerBtn.dataset.trailer;
      if (url) {
          let videoId = null;
          let match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([^&?]+)/);
          if (match && match[1]) {
              videoId = match[1];
          }
          if (videoId) {
              trailerIframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
              trailerModal.classList.add('open');
          } else {
              alert("Lỗi: Link Trailer không hợp lệ. Vui lòng cập nhật lại link Youtube chuẩn trong trang quản trị.");
          }
      }
    });

    const closeTrailer = () => {
      trailerModal.classList.remove('open');
      trailerIframe.src = ''; // Stop video
    };

    if (trailerClose) trailerClose.addEventListener('click', closeTrailer);
    trailerModal.addEventListener('click', (e) => {
      if (e.target === trailerModal || e.target.classList.contains('trailer-box')) {
          closeTrailer();
      }
    });
  }
});
</script>
</body>
</html>
