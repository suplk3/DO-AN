<?php
// Yêu cầu các biến: $post, $me, $me_info, $REACTIONS
if (!isset($REACTIONS)) {
    $REACTIONS = ['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];
}
if (!function_exists('time_ago_post_card')) {
    function time_ago_post_card($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Vừa xong';
        if ($diff < 3600) return floor($diff/60) . ' phút trước';
        if ($diff < 86400) return floor($diff/3600) . ' giờ trước';
        if ($diff < 604800) return floor($diff/86400) . ' ngày trước';
        return date('d/m/Y', strtotime($datetime));
    }
}
?>
<article class="post-card" id="post-<?= $post['id'] ?>" data-post-id="<?= $post['id'] ?>" data-time="<?= strtotime($post['created_at']) ?>">
  <!-- Post header -->
  <div class="post-header">
    <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-user-link">
      <?php if ($post['avatar']): ?>
        <img src="../assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" class="avatar-sm" alt="">
      <?php else: ?>
        <div class="avatar-placeholder-sm"><?= mb_substr($post['ten_user'] ?? '',0,1) ?></div>
      <?php endif; ?>
      <div>
        <div class="post-username"><?= htmlspecialchars($post['ten_user'] ?? '') ?></div>
        <div class="post-time">
          <?= time_ago_post_card($post['created_at']) ?>
          <?php
            $badge = ['following'=>'','interest'=>'✨ Đề xuất','trending'=>'🔥 Trending','self'=>''];
            $src   = $post['source'] ?? '';
            if (!empty($badge[$src])): ?>
            <span class="source-badge"><?= $badge[$src] ?></span>
          <?php endif; ?>
        </div>
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
  <?php if (!empty($post['phim_id'])): ?>
  <a href="chi_tiet_phim.php?id=<?= $post['phim_id'] ?>" class="post-movie-tag">
    <img src="../assets/images/<?= htmlspecialchars($post['phim_poster'] ?? '') ?>" alt="">
    <span>🎬 <?= htmlspecialchars($post['ten_phim'] ?? '') ?></span>
  </a>
  <?php endif; ?>

  <!-- Content -->
  <p class="post-content"><?= nl2br(htmlspecialchars($post['noi_dung'] ?? '')) ?></p>

  <!-- Image -->
  <?php if (!empty($post['hinh_anh'])): ?>
  <div class="post-image-wrap">
    <img src="../assets/images/posts/<?= htmlspecialchars($post['hinh_anh']) ?>"
         class="post-image" alt="" loading="lazy">
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="post-stats">
    <div class="react-summary" id="react-summary-<?= $post['id'] ?>" onmouseenter="loadReactionBreakdown(<?= $post['id'] ?>, 'post')">
      <span class="post-stat-reactions" id="stat-react-<?= $post['id'] ?>">
        <?php if (($post['tong_reaction'] ?? 0) > 0): ?>
          👍❤️ <?= $post['tong_reaction'] ?>
        <?php endif; ?>
      </span>
      <div class="react-detail-tooltip" id="react-tooltip-<?= $post['id'] ?>">
        <div style="color:#94a3b8; font-size:12px; padding:4px 8px;">Đang tải...</div>
      </div>
    </div>
    <span class="post-stat-comments" id="stat-cmt-<?= $post['id'] ?>"
          onclick="toggleComments(<?= $post['id'] ?>)" style="cursor:pointer;">
      <?= $post['tong_comment'] ?? 0 ?> bình luận
    </span>
  </div>

  <!-- Actions -->
  <div class="post-actions">
    <!-- Reaction button -->
    <div class="reaction-wrap" id="rw-<?= $post['id'] ?>">
      <button class="action-btn <?= empty($post['my_reaction']) ? '' : 'reacted' ?>"
              id="rbtn-<?= $post['id'] ?>"
              onclick="quickReact(<?= $post['id'] ?>, 'post', 'like')"
              onmouseenter="showReactions(<?= $post['id'] ?>)">
        <?= !empty($post['my_reaction']) ? ($REACTIONS[$post['my_reaction']] . ' ' . ucfirst($post['my_reaction'])) : '👍 Thích' ?>
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
      <?php if (!empty($me_info['avatar'])): ?>
        <img src="../assets/images/avatars/<?= htmlspecialchars($me_info['avatar']) ?>" class="avatar-xs" alt="">
      <?php else: ?>
        <div class="avatar-placeholder-xs"><?= mb_substr($me_info['ten'] ?? '',0,1) ?></div>
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
