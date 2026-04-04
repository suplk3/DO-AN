<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}
$me = (int)$_SESSION['user_id'];

// ── Ranked feed dùng FeedRanker (4-step algorithm) ──
require_once __DIR__ . '/feed_ranker.php';
$ranker    = new FeedRanker($conn, $me);
$feed_rows = $ranker->getFeed(limit: 30);

// Đếm người đang follow / follower
$fl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM follows WHERE follower_id=$me) AS following,
    (SELECT COUNT(*) FROM follows WHERE following_id=$me) AS followers
"));
$me_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$me"));
$show_community_chat = (($_SESSION['vai_tro'] ?? '') !== 'admin');

// Lấy danh sách bạn bè
$following_list_data = [];
$q_following = mysqli_query($conn, "SELECT u.id, u.ten, u.avatar, u.bio FROM users u INNER JOIN follows f ON u.id = f.following_id WHERE f.follower_id = $me ORDER BY u.ten ASC");
while($r = mysqli_fetch_assoc($q_following)) $following_list_data[] = $r;

$followers_list_data = [];
$q_followers = mysqli_query($conn, "SELECT u.id, u.ten, u.avatar, u.bio, (SELECT 1 FROM follows WHERE follower_id=$me AND following_id=u.id) AS is_following FROM users u INNER JOIN follows f ON u.id = f.follower_id WHERE f.following_id = $me ORDER BY u.ten ASC");
while($r = mysqli_fetch_assoc($q_followers)) $followers_list_data[] = $r;


$REACTIONS = ['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cộng đồng — TTVH Cinemas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
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

<?php $active_page = 'social'; include 'components/header.php'; ?>
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
        <a href="#" class="snav-item active" id="feedNavTrigger"><span>🏠</span> Bảng tin</a>
        <?php if ($show_community_chat): ?>
        <a href="#" class="snav-item" id="chatNavTrigger"><span>💬</span> Trò chuyện</a>
        <?php endif; ?>
        <a href="#" class="snav-item" id="friendsNavTrigger"><span>👥</span> Bạn bè</a>
        <a href="profile.php?id=<?= $me ?>" class="snav-item"><span>👤</span> Trang cá nhân</a>
        <a href="index.php" class="snav-item"><span>🎬</span> Xem phim</a>
        <a href="ve_cua_toi.php" class="snav-item"><span>🎟️</span> Vé của tôi</a>
      </nav>
    </aside>

    <!-- CENTER: Feed -->
    <div class="social-feed">
      <div class="social-mobile-tabs">
        <button type="button" class="social-mobile-tab active" id="feedMobileTrigger">Bảng tin</button>
        <?php if ($show_community_chat): ?>
        <button type="button" class="social-mobile-tab" id="chatMobileTrigger">Trò chuyện</button>
        <?php endif; ?>
        <button type="button" class="social-mobile-tab" id="friendsMobileTrigger">Bạn bè</button>
      </div>

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
      <?php if (empty($feed_rows)): ?>
      <div class="feed-empty">
        <div style="font-size:48px;margin-bottom:12px;">🎬</div>
        <div style="font-size:16px;font-weight:700;color:#e2e8f0;margin-bottom:6px;">Bảng tin trống</div>
        <div style="font-size:13px;color:#64748b;">Hãy theo dõi bạn bè để xem bài đăng của họ</div>
      </div>
      <?php else: ?>
      <?php foreach ($feed_rows as $post): ?>
      <?php include 'components/post_card.php'; ?>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($show_community_chat): ?>
      <section class="community-chat-view" id="communityChatView" style="display:none;">
        <div class="community-chat-shell">
          <div class="community-chat-sidebar">
            <div class="community-chat-sidebar-head">
              <div>
                <div class="community-chat-kicker">Tin nhắn cộng đồng</div>
                <div class="community-chat-title">Trò chuyện</div>
              </div>
              <span class="community-chat-pill">Messenger</span>
            </div>
            <div class="community-chat-search">
              <div class="community-chat-search-box">
                <span class="community-chat-search-icon">⌕</span>
                <input type="search" id="communityChatSearch" placeholder="Tìm admin hoặc chiến hữu..." autocomplete="off">
                <button type="button" class="community-chat-search-clear" id="communityChatSearchClear" aria-label="Xóa tìm kiếm" hidden>×</button>
              </div>
              <div class="community-chat-search-suggestions" id="communityChatSuggestions" hidden></div>
            </div>
            <div class="community-chat-list" id="communityChatList">
              <div class="community-chat-empty-list">Đang tải danh sách trò chuyện...</div>
            </div>
          </div>
          <div class="community-chat-main">
            <div class="community-chat-main-head">
              <div id="communityChatHeaderAvatar" class="community-chat-avatar large admin">AD</div>
              <div class="community-chat-main-copy">
                <div class="community-chat-main-name" id="communityChatHeaderName">Chọn một cuộc trò chuyện</div>
                <div class="community-chat-main-meta" id="communityChatHeaderMeta">Nhắn với admin hoặc những người bạn đang theo dõi.</div>
              </div>
            </div>
            <div class="community-chat-messages" id="communityChatMessages">
              <div class="community-chat-placeholder">Chọn một cuộc trò chuyện để bắt đầu nhắn tin.</div>
            </div>
            <form class="community-chat-compose" id="communityChatForm">
              <input type="text" id="communityChatInput" placeholder="Nhập tin nhắn..." autocomplete="off" disabled>
              <button type="submit" id="communityChatSend" disabled>Gửi</button>
            </form>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <section class="friends-view" id="friendsView" style="display:none; padding-top:20px; animation:slideIn 0.3s forwards;">
        <div class="friends-tabs" style="display:flex; gap:16px; margin-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.1);">
            <button class="ftab-btn active" onclick="switchFriendsTab('following')" style="background:none; border:none; color:#f1f5f9; padding:8px 8px 12px; font-weight:700; border-bottom:2px solid #3b82f6; cursor:pointer;">Đang theo dõi (<?= $fl['following'] ?>)</button>
            <button class="ftab-btn" onclick="switchFriendsTab('followers')" style="background:none; border:none; color:#94a3b8; padding:8px 8px 12px; font-weight:700; border-bottom:2px solid transparent; cursor:pointer;">Người theo dõi (<?= $fl['followers'] ?>)</button>
        </div>
        
        <div id="ftab-following" class="ftab-content" style="display:block;">
            <?php if(empty($following_list_data)): ?>
                <div style="padding:40px 20px; text-align:center; color:#64748b; background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">Bạn chưa theo dõi ai.</div>
            <?php else: ?>
                <?php foreach($following_list_data as $fu): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:16px; margin-bottom:12px; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='none';">
                        <a href="profile.php?id=<?= $fu['id'] ?>" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
                            <?php if($fu['avatar']): ?>
                                <img src="../assets/images/avatars/<?= htmlspecialchars($fu['avatar']) ?>" class="avatar-sm" style="width:48px;height:48px;" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder-sm" style="width:48px;height:48px;font-size:18px;"><?= mb_substr($fu['ten'],0,1) ?></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:700; color:#f1f5f9; font-size:15px;"><?= htmlspecialchars($fu['ten']) ?></div>
                                <div style="font-size:13px; color:#64748b; margin-top:2px;"><?= htmlspecialchars(mb_strimwidth($fu['bio'] ?? '', 0, 40, '...')) ?></div>
                            </div>
                        </a>
                        <button class="btn-follow following" onclick="doFollow(<?= $fu['id'] ?>, this)">Đang theo dõi</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="ftab-followers" class="ftab-content" style="display:none;">
            <?php if(empty($followers_list_data)): ?>
                <div style="padding:40px 20px; text-align:center; color:#64748b; background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">Chưa có ai theo dõi bạn.</div>
            <?php else: ?>
                <?php foreach($followers_list_data as $fu): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:16px; margin-bottom:12px; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='none';">
                        <a href="profile.php?id=<?= $fu['id'] ?>" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
                            <?php if($fu['avatar']): ?>
                                <img src="../assets/images/avatars/<?= htmlspecialchars($fu['avatar']) ?>" class="avatar-sm" style="width:48px;height:48px;" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder-sm" style="width:48px;height:48px;font-size:18px;"><?= mb_substr($fu['ten'],0,1) ?></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:700; color:#f1f5f9; font-size:15px;"><?= htmlspecialchars($fu['ten']) ?></div>
                                <div style="font-size:13px; color:#64748b; margin-top:2px;"><?= htmlspecialchars(mb_strimwidth($fu['bio'] ?? '', 0, 40, '...')) ?></div>
                            </div>
                        </a>
                        <button class="btn-follow <?= $fu['is_following'] ? 'following' : '' ?>" onclick="doFollow(<?= $fu['id'] ?>, this)">
                            <?= $fu['is_following'] ? 'Đang theo dõi' : 'Theo dõi lại' ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
      </section>
      <script>
      function switchFriendsTab(tab) {
          document.getElementById('ftab-following').style.display = tab === 'following' ? 'block' : 'none';
          document.getElementById('ftab-followers').style.display = tab === 'followers' ? 'block' : 'none';
          const btns = document.querySelectorAll('.ftab-btn');
          btns[0].style.borderBottomColor = tab === 'following' ? '#3b82f6' : 'transparent';
          btns[0].style.color = tab === 'following' ? '#f1f5f9' : '#94a3b8';
          btns[1].style.borderBottomColor = tab === 'followers' ? '#3b82f6' : 'transparent';
          btns[1].style.color = tab === 'followers' ? '#f1f5f9' : '#94a3b8';
      }
      </script>

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
// ── Impression tracking (dwell time) ──────────────────
// Ghi nhận thời gian đọc từng bài → feedback loop cho ranker
const postTimers = {};
const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
        const id = e.target.dataset.postId;
        if (!id) return;
        if (e.isIntersecting) {
            postTimers[id] = Date.now();
        } else if (postTimers[id]) {
            const dwell = Date.now() - postTimers[id];
            if (dwell > 500) {  // chỉ log nếu xem > 500ms
                navigator.sendBeacon('impression_api.php',
                    JSON.stringify({ post_id: parseInt(id), action: 'view', dwell_ms: dwell })
                );
            }
            delete postTimers[id];
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.post-card[data-post-id]').forEach(el => io.observe(el));
</script>

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
const CURRENT_USER_ID = <?= json_encode($me) ?>;
const IS_ADMIN = <?= json_encode((bool)(isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin')) ?>;

let IS_BANNED = <?= !empty($me_info['is_banned']) ? 'true' : 'false' ?>;

function checkBanned(e) {
    if (IS_BANNED) {
        if (e && typeof e.preventDefault === 'function') { e.preventDefault(); e.stopPropagation(); }
        showBannedWidget();
        return true;
    }
    return false;
}

function showBannedWidget() {
    let w = document.getElementById('banned-widget');
    if (!w) {
        w = document.createElement('div');
        w.id = 'banned-widget';
        w.style.cssText = 'position:fixed; bottom:20px; right:20px; background:linear-gradient(135deg, #ef4444, #b91c1c); color:#fff; padding:15px 20px; border-radius:10px; font-weight:bold; box-shadow:0 10px 25px rgba(239,68,68,0.5); z-index:9999; animation:slideIn 0.3s forwards; pointer-events:none; font-family:"Be Vietnam Pro", sans-serif; font-size:14px;';
        w.innerHTML = '🚫 Bạn hiện tại đang bị cấm tương tác!';
        document.body.appendChild(w);
        
        if (!document.getElementById('widget-keyframes')) {
            const style = document.createElement('style');
            style.id = 'widget-keyframes';
            style.innerHTML = '@keyframes slideIn { from { transform: translateX(120%); opacity:0; } to { transform: translateX(0); opacity:1; } } @keyframes slideOut { from { transform: translateX(0); opacity:1; } to { transform: translateX(120%); opacity:0; } }';
            document.head.appendChild(style);
        }
    }
    
    w.style.animation = 'none';
    w.offsetHeight; // force reflow
    w.style.animation = 'slideIn 0.3s forwards';
    
    if (w.timeoutId) clearTimeout(w.timeoutId);
    w.timeoutId = setTimeout(() => {
        if (w) w.style.animation = 'slideOut 0.3s forwards';
    }, 4000);
}

function showUnbannedWidget() {
    let w = document.getElementById('unbanned-widget');
    if (!w) {
        w = document.createElement('div');
        w.id = 'unbanned-widget';
        w.style.cssText = 'position:fixed; bottom:20px; right:20px; background:linear-gradient(135deg, #10b981, #059669); color:#fff; padding:15px 20px; border-radius:10px; font-weight:bold; box-shadow:0 10px 25px rgba(16,185,129,0.5); z-index:9999; animation:slideIn 0.3s forwards; pointer-events:none; font-family:"Be Vietnam Pro", sans-serif; font-size:14px;';
        w.innerHTML = '✅ Bạn đã được gỡ cấm. Mọi tính năng hoạt động lại bình thường!';
        document.body.appendChild(w);
    }
    
    w.style.animation = 'none';
    w.offsetHeight; // force reflow
    w.style.animation = 'slideIn 0.3s forwards';
    
    if (w.timeoutId) clearTimeout(w.timeoutId);
    w.timeoutId = setTimeout(() => {
        if (w) w.style.animation = 'slideOut 0.3s forwards';
    }, 5000);
}

// ── Toggle compose box ──
document.getElementById('composeToggle').addEventListener('click', (e) => {
    if (checkBanned(e)) return;
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
    if (checkBanned()) return;
    hideReactions(id);
    doReact(id, type, loai);
}
async function doReact(id, type, loai) {
    if (checkBanned()) return;
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
    if (stat) stat.textContent = data.total > 0 ? '👍❤️ ' + data.total : '';
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
                html += `
                <div class="react-detail-row">
                    <span>${emoji}</span>
                    <span style="text-transform: capitalize; padding-left:8px;">${vnName}</span>
                    <span class="react-detail-count">${count}</span>
                </div>`;
            }
            tooltip.innerHTML = html;
        }
    } catch(e) {
        tooltip.innerHTML = '<div style="color:#ef4444; font-size:12px; padding:4px 8px;">Lỗi tải</div>';
    }
}

// ── Comments ──
const loadedComments = {};
const commentCountCache = {};
let isFeedPolling = false;

function getDisplayedCommentCount(id) {
    const stat = document.getElementById('stat-cmt-' + id);
    return stat ? (parseInt(stat.textContent, 10) || 0) : 0;
}

function setDisplayedCommentCount(id, count) {
    commentCountCache[id] = count;
    const stat = document.getElementById('stat-cmt-' + id);
    if (stat) stat.textContent = count + ' bình luận';
}

function isCommentsOpen(id) {
    const sec = document.getElementById('comments-' + id);
    return !!sec && sec.style.display !== 'none';
}

function countCommentsTree(comments) {
    return comments.reduce((total, comment) => {
        const replies = Array.isArray(comment.replies) ? countCommentsTree(comment.replies) : 0;
        return total + 1 + replies;
    }, 0);
}

async function toggleComments(id) {
    const sec = document.getElementById('comments-' + id);
    if (sec.style.display === 'none') {
        sec.style.display = 'block';
        if (!loadedComments[id]) await loadComments(id, 'post');
    } else {
        sec.style.display = 'none';
    }
}
function renderCommentTree(comments, postId, type, depth = 0) {
    if (!comments || !comments.length) return '';
    let html = '';
    
    comments.forEach(c => {
        let repliesHtml = '';
        if (c.replies && c.replies.length > 0) {
            repliesHtml = `<div class="comment-replies" style="margin-left: ${depth === 0 ? 34 : 0}px; border-left: 2px solid rgba(255,255,255,0.05); padding-left: 12px; margin-top: 8px;">
                ${renderCommentTree(c.replies, postId, type, depth + 1)}
            </div>`;
        }
        
        const avatarSize = depth === 0 ? 'avatar-xs' : 'avatar-xxs';
        const parentId = depth === 0 ? c.id : c.parent_id;
        
        html += `
            <div class="comment-item" id="cmt-${c.id}" style="${depth > 0 ? 'margin-top: 10px; padding:0;' : ''}">
                <div class="comment-avatar">
                    ${c.avatar
                        ? `<img src="../assets/images/avatars/${c.avatar}" class="avatar-xs" alt="" style="${depth > 0 ? 'width:24px;height:24px;' : ''}">`
                        : `<div class="avatar-placeholder-xs" style="${depth > 0 ? 'width:24px;height:24px;font-size:10px;' : ''}">${c.ten.charAt(0)}</div>`}
                </div>
                <div class="comment-bubble-wrap" style="flex:1;">
                    <div class="comment-bubble" style="${depth > 0 ? 'padding:8px 10px;' : ''}">
                        <a href="profile.php?id=${c.user_id}" class="comment-name">${escHtml(c.ten)}</a>
                        <span class="comment-text">${escHtml(c.noi_dung)}</span>
                        <div class="comment-meta">
                            ${c.time_ago}
                            <button class="btn-reply-text" style="background:none;border:none;color:#94a3b8;font-size:11px;cursor:pointer;padding:0 5px; margin-left:8px;" onclick="showReplyBox(${postId}, '${type}', ${parentId})">Trả lời</button>
                            ${(CURRENT_USER_ID && (parseInt(c.user_id) === parseInt(CURRENT_USER_ID) || IS_ADMIN))
                                ? `<button class="btn-delete-text" style="background:none;border:none;color:#ef4444;font-size:11px;cursor:pointer;padding:0 5px; margin-left:8px;" onclick="deleteCommentNode(${c.id}, ${postId}, '${type}')">Xoá</button>`
                                : ''}
                        </div>
                    </div>
                    
                    <!-- Inline Reply Box placeholder -->
                    <div id="reply-box-${postId}-${parentId}" class="reply-box-container" style="display:none; margin-top: 6px;"></div>
                    
                    ${repliesHtml}
                </div>
            </div>
        `;
    });
    
    return html;
}

function showReplyBox(postId, type, parentId) {
    document.querySelectorAll(`.reply-box-container[id^="reply-box-${postId}-"]`).forEach(el => {
        el.style.display = 'none';
        el.innerHTML = '';
    });
    
    const box = document.getElementById(`reply-box-${postId}-${parentId}`);
    if (!box) return;
    
    box.style.display = 'block';
    box.innerHTML = `
        <div class="comment-compose" style="padding: 0; background: transparent; border: none; margin-top: 8px;">
            <div class="comment-input-wrap" style="background: rgba(255,255,255,0.03);">
                <input type="text" class="comment-input" id="ci-reply-${postId}-${parentId}" placeholder="Viết phản hồi..." onfocus="if(typeof checkBanned === 'function' && checkBanned(event)) this.blur();" onkeydown="if(event.key==='Enter')submitComment(${postId}, '${type}', ${parentId})">
                <button class="comment-send" onclick="submitComment(${postId}, '${type}', ${parentId})">➤</button>
            </div>
        </div>
    `;
    if (typeof checkBanned === 'function' && !IS_BANNED) {
        setTimeout(() => document.getElementById(`ci-reply-${postId}-${parentId}`).focus(), 50);
    }
}

async function loadComments(id, type, options = {}) {
    const list = document.getElementById('clist-' + id);
    if (!options.background) {
        list.innerHTML = '<div style="text-align:center;padding:12px;color:#64748b;font-size:12px;">Đang tải...</div>';
    }
    const res = await fetch(`comment_api.php?target_type=${type}&target_id=${id}`);
    const data = await res.json();
    loadedComments[id] = true;
    if (!data.comments.length) {
        setDisplayedCommentCount(id, 0);
        list.innerHTML = '<div style="text-align:center;padding:8px;color:#64748b;font-size:12px;">Chưa có bình luận nào</div>';
        return;
    }
    setDisplayedCommentCount(id, countCommentsTree(data.comments));
    list.innerHTML = renderCommentTree(data.comments, id, type);
}

async function submitComment(id, type, parentId = null) {
    if (checkBanned()) return;
    const inpId = parentId ? `ci-reply-${id}-${parentId}` : `ci-${id}`;
    const inp = document.getElementById(inpId);
    if (!inp) return;
    const text = inp.value.trim();
    if (!text) return;
    inp.value = '';
    
    const payload = { target_type: type, target_id: id, noi_dung: text };
    if (parentId) payload.parent_id = parentId;
    
    const res = await fetch('comment_api.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.ok) {
        loadedComments[id] = false;
        await loadComments(id, type);
    }
}

async function deleteCommentNode(cmtId, postId, type) {
    if (!confirm('Bạn có chắc muốn xoá bình luận này?')) return;
    const res = await fetch('comment_api.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({comment_id: cmtId})
    });
    const data = await res.json();
    if (data.ok) {
        const el = document.getElementById('cmt-' + cmtId);
        if (el) {
            el.style.transition = 'opacity .25s, transform .25s';
            el.style.opacity = '0';
            el.style.transform = 'translateX(-10px)';
            setTimeout(() => {
                el.remove();
                loadedComments[postId] = false;
                loadComments(postId, type);
            }, 250);
        }
    } else {
        alert(data.msg || 'Không thể xoá');
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
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Header shrink
const h=document.querySelector('.header'),b=document.querySelector('body.user-index');
if(h&&b){const fn=()=>{h.classList.toggle('shrink',scrollY>50);b.classList.toggle('header-shrink',scrollY>50);};window.addEventListener('scroll',fn,{passive:true});fn();}

// ── Real-time Feed Polling ──
let pollingInterval = null;
const feedPollIntervalMs = 2000;
async function pollFeed() {
        if (isFeedPolling) return;
        isFeedPolling = true;
        const posts = Array.from(document.querySelectorAll('.post-card[data-post-id]'));
        const postIds = posts.map(p => p.dataset.postId);

        let latestTime = 0;
        posts.forEach(p => {
            const t = parseInt(p.dataset.time) || 0;
            if (t > latestTime) latestTime = t;
        });
        const latestKnownTime = latestTime || 0;

        try {
            const res = await fetch('feed_poll_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ latest_time: latestKnownTime, post_ids: postIds })
            });
            const data = await res.json();
            const commentRefreshTasks = [];

            if (typeof data.is_banned !== 'undefined') {
                if (IS_BANNED && !data.is_banned) {
                    showUnbannedWidget();
                } else if (!IS_BANNED && data.is_banned) {
                    showBannedWidget();
                }
                IS_BANNED = data.is_banned;
            }

            if (data.updates) {
                for (const [id, stats] of Object.entries(data.updates)) {
                    const reactEl = document.getElementById('stat-react-' + id);
                    if (reactEl) reactEl.textContent = stats.reactions > 0 ? '👍❤️ ' + stats.reactions : '';

                    const nextCommentCount = Number(stats.comments) || 0;
                    const prevCommentCount = commentCountCache[id] ?? getDisplayedCommentCount(id);
                    setDisplayedCommentCount(id, nextCommentCount);
                    if (prevCommentCount !== nextCommentCount) {
                        loadedComments[id] = false;
                        if (isCommentsOpen(id)) {
                            commentRefreshTasks.push(loadComments(id, 'post', { background: true }));
                        }
                    }
                }
            }
            if (commentRefreshTasks.length) await Promise.allSettled(commentRefreshTasks);

            if (data.deleted_posts && data.deleted_posts.length > 0) {
                data.deleted_posts.forEach(del_id => {
                    const postEl = document.getElementById('post-' + del_id);
                    if (postEl) {
                        postEl.style.transition = "all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1)";
                        postEl.style.opacity = "0";
                        postEl.style.transform = "scale(0.9) translateY(20px)";
                        setTimeout(() => postEl.remove(), 400);
                    }
                });
            }

            if (data.new_posts_html) {
                const feedContainer = document.querySelector('.social-feed');
                const composeBox = document.querySelector('.compose-box');
                const emptyState = document.querySelector('.feed-empty');
                const temp = document.createElement('div');
                temp.innerHTML = data.new_posts_html;

                const newPosts = Array.from(temp.children);
                if (newPosts.length && emptyState) emptyState.remove();
                newPosts.reverse().forEach(postEl => {
                    if (postEl.id && document.getElementById(postEl.id)) return;
                    feedContainer.insertBefore(postEl, composeBox.nextSibling);
                    if (postEl.dataset.postId) {
                        commentCountCache[postEl.dataset.postId] = getDisplayedCommentCount(postEl.dataset.postId);
                    }
                    if (typeof io !== 'undefined') io.observe(postEl);
                });
            }
        } catch (e) {
            console.error('Polling error', e);
        } finally {
            isFeedPolling = false;
        }
}
function startFeedPolling() {
    document.querySelectorAll('.post-card[data-post-id]').forEach(postEl => {
        if (postEl.dataset.postId) {
            commentCountCache[postEl.dataset.postId] = getDisplayedCommentCount(postEl.dataset.postId);
        }
    });
    pollFeed();
    pollingInterval = setInterval(pollFeed, feedPollIntervalMs);
}
startFeedPolling();
</script>
<script>
const ENABLE_COMMUNITY_CHAT = <?= json_encode($show_community_chat) ?>;
const communityChatState = {
    initialized: false,
    activeType: '',
    activeUserId: 0,
    contacts: [],
    searchQuery: '',
    contactsScrollTop: 0,
    pollTimer: null
};

function setCommunityView(view) {
    const showChat = ENABLE_COMMUNITY_CHAT && view === 'chat';
    const showFriends = view === 'friends';
    document.querySelectorAll('.compose-box, .post-card, .feed-empty').forEach(el => {
        el.style.display = (showChat || showFriends) ? 'none' : '';
    });

    const chatView = document.getElementById('communityChatView');
    if (chatView) chatView.style.display = showChat ? 'block' : 'none';
    
    const friendsView = document.getElementById('friendsView');
    if (friendsView) friendsView.style.display = showFriends ? 'block' : 'none';

    const feedNav = document.getElementById('feedNavTrigger');
    const chatNav = document.getElementById('chatNavTrigger');
    const friendsNav = document.getElementById('friendsNavTrigger');
    const feedMobile = document.getElementById('feedMobileTrigger');
    const chatMobile = document.getElementById('chatMobileTrigger');
    const friendsMobile = document.getElementById('friendsMobileTrigger');
    
    if (feedNav) feedNav.classList.toggle('active', view === 'feed');
    if (chatNav) chatNav.classList.toggle('active', view === 'chat');
    if (friendsNav) friendsNav.classList.toggle('active', view === 'friends');
    if (feedMobile) feedMobile.classList.toggle('active', view === 'feed');
    if (chatMobile) chatMobile.classList.toggle('active', view === 'chat');
    if (friendsMobile) friendsMobile.classList.toggle('active', view === 'friends');

    if (showChat) initCommunityChat();
}

function getCommunityChatKey(type, userId) {
    return `${type}:${userId || 0}`;
}

function getActiveCommunityContact() {
    return communityChatState.contacts.find(contact =>
        contact.type === communityChatState.activeType &&
        Number(contact.user_id || 0) === Number(communityChatState.activeUserId || 0)
    ) || null;
}

function buildCommunityAvatar(contact, large = false) {
    const sizeClass = large ? ' large' : '';
    if (contact.avatar) {
        return `<div class="community-chat-avatar${sizeClass}"><img src="../assets/images/avatars/${escHtml(contact.avatar)}" alt=""></div>`;
    }

    const initials = contact.type === 'admin'
        ? 'AD'
        : escHtml((contact.name || '?').trim().charAt(0).toUpperCase());
    const adminClass = contact.type === 'admin' ? ' admin' : '';
    return `<div class="community-chat-avatar${sizeClass}${adminClass}">${initials}</div>`;
}

function normalizeCommunitySearch(value) {
    return String(value || '')
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

function getCommunityContactSearchText(contact) {
    const keywords = [
        contact.name || '',
        contact.note || '',
        contact.last_message || '',
        contact.type === 'admin' ? 'admin ho tro ve thanh toan cong dong' : 'chien huu ban theo doi tro chuyen',
    ];

    return normalizeCommunitySearch(keywords.join(' '));
}

function getFilteredCommunityContacts() {
    const keyword = normalizeCommunitySearch(communityChatState.searchQuery);
    if (!keyword) {
        return communityChatState.contacts.slice();
    }

    return communityChatState.contacts.filter(contact => getCommunityContactSearchText(contact).includes(keyword));
}

function updateCommunitySearchClear() {
    const clearButton = document.getElementById('communityChatSearchClear');
    if (clearButton) {
        clearButton.hidden = !communityChatState.searchQuery.trim();
    }
}

function renderCommunitySearchSuggestions(forceOpen = false) {
    const panel = document.getElementById('communityChatSuggestions');
    const input = document.getElementById('communityChatSearch');
    if (!panel || !input) return;

    const hasFocus = document.activeElement === input;
    const keyword = normalizeCommunitySearch(communityChatState.searchQuery);
    const shouldOpen = forceOpen || hasFocus;

    if (!shouldOpen) {
        panel.hidden = true;
        panel.innerHTML = '';
        return;
    }

    let suggestions = communityChatState.contacts.slice();
    if (keyword) {
        suggestions = suggestions.filter(contact => getCommunityContactSearchText(contact).includes(keyword));
    }

    suggestions = suggestions.slice(0, keyword ? 6 : 5);
    if (!suggestions.length) {
        if (!keyword) {
            panel.hidden = true;
            panel.innerHTML = '';
            return;
        }

        panel.hidden = false;
        panel.innerHTML = '<div class="community-chat-search-caption">Gợi ý</div><div class="community-chat-search-empty">Không tìm thấy người dùng phù hợp.</div>';
        return;
    }

    panel.hidden = false;
    panel.innerHTML = `
        <div class="community-chat-search-caption">${keyword ? 'Kết quả gợi ý' : 'Gợi ý nhanh'}</div>
        ${suggestions.map(contact => `
            <button type="button" class="community-chat-suggestion" data-type="${contact.type}" data-user-id="${Number(contact.user_id || 0)}">
                ${buildCommunityAvatar(contact)}
                <span class="community-chat-suggestion-copy">
                    <span class="community-chat-suggestion-name">${escHtml(contact.name || 'Người dùng')}</span>
                    <span class="community-chat-suggestion-note">${escHtml(contact.note || (contact.type === 'admin' ? 'Hỗ trợ nhanh từ admin.' : 'Mở cuộc trò chuyện'))}</span>
                </span>
            </button>
        `).join('')}
    `;

    panel.querySelectorAll('.community-chat-suggestion').forEach(button => {
        button.addEventListener('click', () => {
            const inputEl = document.getElementById('communityChatSearch');
            if (inputEl) {
                inputEl.value = '';
                inputEl.blur();
            }
            communityChatState.searchQuery = '';
            communityChatState.contactsScrollTop = 0;
            updateCommunitySearchClear();
            panel.hidden = true;
            panel.innerHTML = '';
            renderCommunityContacts();
            selectCommunityConversation(button.dataset.type, Number(button.dataset.userId || 0));
        });
    });
}

function setCommunitySearchQuery(value, options = {}) {
    const nextValue = String(value || '');
    const changed = nextValue !== communityChatState.searchQuery;
    communityChatState.searchQuery = nextValue;
    if (changed) {
        communityChatState.contactsScrollTop = 0;
    }

    updateCommunitySearchClear();
    renderCommunityContacts();
    renderCommunitySearchSuggestions(!!options.keepOpen);
}

function renderCommunityContacts() {
    const list = document.getElementById('communityChatList');
    if (!list) return;

    const previousScrollTop = communityChatState.contactsScrollTop || list.scrollTop || 0;
    const visibleContacts = getFilteredCommunityContacts();

    if (!communityChatState.contacts.length) {
        list.innerHTML = '<div class="community-chat-empty-list">Chưa có cuộc trò chuyện nào.</div>';
        communityChatState.contactsScrollTop = 0;
        return;
    }

    if (!visibleContacts.length) {
        list.innerHTML = '<div class="community-chat-empty-list">Không tìm thấy chiến hữu hoặc admin phù hợp.</div>';
        communityChatState.contactsScrollTop = 0;
        return;
    }

    list.innerHTML = visibleContacts.map(contact => {
        const key = getCommunityChatKey(contact.type, contact.user_id);
        const active = key === getCommunityChatKey(communityChatState.activeType, communityChatState.activeUserId);
        const preview = contact.last_message || (contact.type === 'admin'
            ? 'Hỏi admin về vé, thanh toán hoặc cộng đồng.'
            : 'Bắt đầu cuộc trò chuyện mới.');

        return `
            <button type="button" class="community-chat-contact ${active ? 'active' : ''}" data-type="${contact.type}" data-user-id="${Number(contact.user_id || 0)}">
                ${buildCommunityAvatar(contact)}
                <span class="community-chat-contact-copy">
                    <span class="community-chat-contact-top">
                        <span class="community-chat-contact-name">${escHtml(contact.name)}</span>
                        <span class="community-chat-contact-time">${escHtml(contact.last_time || '')}</span>
                    </span>
                    <span class="community-chat-contact-note">${escHtml(contact.note || '')}</span>
                    <span class="community-chat-contact-preview">${preview}</span>
                </span>
            </button>
        `;
    }).join('');

    list.scrollTop = previousScrollTop;
    communityChatState.contactsScrollTop = list.scrollTop;

    list.querySelectorAll('.community-chat-contact').forEach(button => {
        button.addEventListener('click', () => {
            selectCommunityConversation(button.dataset.type, Number(button.dataset.userId || 0));
        });
    });

    list.onscroll = () => {
        communityChatState.contactsScrollTop = list.scrollTop;
    };
}

function updateCommunityChatHeader() {
    const headerAvatar = document.getElementById('communityChatHeaderAvatar');
    const headerName = document.getElementById('communityChatHeaderName');
    const headerMeta = document.getElementById('communityChatHeaderMeta');
    const input = document.getElementById('communityChatInput');
    const send = document.getElementById('communityChatSend');
    const activeContact = getActiveCommunityContact();

    if (!activeContact) {
        if (headerAvatar) headerAvatar.outerHTML = '<div id="communityChatHeaderAvatar" class="community-chat-avatar large admin">AD</div>';
        if (headerName) headerName.textContent = 'Chọn một cuộc trò chuyện';
        if (headerMeta) headerMeta.textContent = 'Nhắn với admin hoặc những người bạn đang theo dõi.';
        if (input) {
            input.value = '';
            input.disabled = true;
            input.placeholder = 'Nhập tin nhắn...';
        }
        if (send) send.disabled = true;
        return;
    }

    if (headerAvatar) headerAvatar.outerHTML = buildCommunityAvatar(activeContact, true).replace('class="community-chat-avatar', 'id="communityChatHeaderAvatar" class="community-chat-avatar');
    if (headerName) headerName.textContent = activeContact.name || 'Cuộc trò chuyện';
    if (headerMeta) headerMeta.textContent = activeContact.note || (activeContact.type === 'admin' ? 'Hỗ trợ nhanh từ admin.' : 'Trò chuyện riêng trong cộng đồng.');
    if (input) {
        input.disabled = false;
        input.placeholder = activeContact.type === 'admin'
            ? 'Nhập tin nhắn cho admin...'
            : `Nhắn cho ${activeContact.name}...`;
    }
    if (send) send.disabled = false;
}

function renderCommunityMessages(messages) {
    const box = document.getElementById('communityChatMessages');
    if (!box) return;

    if (!messages.length) {
        box.innerHTML = '<div class="community-chat-placeholder">Chưa có tin nhắn nào. Hãy gửi lời chào để bắt đầu.</div>';
        return;
    }

    box.innerHTML = messages.map(message => `
        <div class="community-chat-bubble ${message.is_mine ? 'mine' : 'theirs'}">
            ${message.message}
            <span class="community-chat-time">${escHtml(message.created_at || '')}</span>
        </div>
    `).join('');
    box.scrollTop = box.scrollHeight;
}

async function loadCommunityMessages(forceScroll = false) {
    if (!communityChatState.activeType) return;

    const url = communityChatState.activeType === 'admin'
        ? '../api/chat_api.php?action=get_messages'
        : `../api/chat_api.php?action=get_messages&scope=direct&user_id=${communityChatState.activeUserId}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) {
            renderCommunityMessages([]);
            return;
        }

        renderCommunityMessages(data.data || []);
        if (forceScroll) {
            const box = document.getElementById('communityChatMessages');
            if (box) box.scrollTop = box.scrollHeight;
        }
    } catch (error) {
        console.error('Community chat load error', error);
    }
}

function selectCommunityConversation(type, userId) {
    communityChatState.activeType = type;
    communityChatState.activeUserId = Number(userId || 0);
    renderCommunityContacts();
    updateCommunityChatHeader();
    loadCommunityMessages(true);
}

async function loadCommunityContacts() {
    try {
        const res = await fetch('../api/chat_api.php?action=get_chat_contacts');
        const data = await res.json();
        if (!data.success) return;

        communityChatState.contacts = Array.isArray(data.data) ? data.data : [];
        renderCommunityContacts();
        renderCommunitySearchSuggestions();

        const activeExists = getActiveCommunityContact();
        if (!activeExists && communityChatState.contacts.length) {
            const first = communityChatState.contacts[0];
            selectCommunityConversation(first.type, first.user_id || 0);
            return;
        }

        updateCommunityChatHeader();
    } catch (error) {
        console.error('Community contact load error', error);
    }
}

async function submitCommunityMessage(event) {
    event.preventDefault();

    if (!communityChatState.activeType) return;
    const input = document.getElementById('communityChatInput');
    if (!input) return;

    const message = input.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append('message', message);
    if (communityChatState.activeType === 'user') {
        formData.append('scope', 'direct');
        formData.append('user_id', String(communityChatState.activeUserId));
    }

    try {
        const res = await fetch('../api/chat_api.php?action=send', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (!data.success) {
            alert(data.error || 'Không thể gửi tin nhắn.');
            return;
        }

        input.value = '';
        await loadCommunityMessages(true);
        await loadCommunityContacts();
    } catch (error) {
        console.error('Community chat send error', error);
    }
}

function initCommunityChat() {
    if (!ENABLE_COMMUNITY_CHAT || communityChatState.initialized) return;

    communityChatState.initialized = true;
    const form = document.getElementById('communityChatForm');
    const searchInput = document.getElementById('communityChatSearch');
    const searchClear = document.getElementById('communityChatSearchClear');
    if (form) form.addEventListener('submit', submitCommunityMessage);
    if (searchInput) {
        searchInput.addEventListener('input', event => {
            setCommunitySearchQuery(event.target.value, { keepOpen: true });
        });
        searchInput.addEventListener('focus', () => {
            renderCommunitySearchSuggestions(true);
        });
        searchInput.addEventListener('blur', () => {
            setTimeout(() => renderCommunitySearchSuggestions(false), 120);
        });
        searchInput.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                searchInput.value = '';
                setCommunitySearchQuery('', { keepOpen: false });
                searchInput.blur();
            }
        });
    }
    if (searchClear) {
        searchClear.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            setCommunitySearchQuery('', { keepOpen: true });
        });
    }
    document.addEventListener('click', event => {
        if (!event.target.closest('.community-chat-search')) {
            renderCommunitySearchSuggestions(false);
        }
    });

    loadCommunityContacts();
    communityChatState.pollTimer = setInterval(() => {
        const chatView = document.getElementById('communityChatView');
        if (!chatView || chatView.style.display === 'none') return;
        loadCommunityContacts();
        loadCommunityMessages();
    }, 2500);
}

document.getElementById('feedNavTrigger')?.addEventListener('click', event => {
    event.preventDefault();
    setCommunityView('feed');
});

document.getElementById('chatNavTrigger')?.addEventListener('click', event => {
    event.preventDefault();
    setCommunityView('chat');
});

document.getElementById('friendsNavTrigger')?.addEventListener('click', event => {
    event.preventDefault();
    setCommunityView('friends');
});

document.getElementById('feedMobileTrigger')?.addEventListener('click', () => {
    setCommunityView('feed');
});

document.getElementById('chatMobileTrigger')?.addEventListener('click', () => {
    setCommunityView('chat');
});

document.getElementById('friendsMobileTrigger')?.addEventListener('click', () => {
    setCommunityView('friends');
});
</script>
<script src="../assets/js/search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/login-modal.js"></script>
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

</body>
</html>
