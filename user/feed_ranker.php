<?php
/**
 * feed_ranker.php — TTVH Social Ranking Engine
 *
 * Implement thuật toán 4 bước theo mô hình X/Twitter:
 *   Step 1: Inventory    — Thu thập candidates từ 3 nguồn
 *   Step 2: Signals      — Tính Relationship, Content, Recency
 *   Step 3: Predictions  — Ước tính P(like), P(reply), P(dwell), P(share)
 *   Step 4: Score        — Tổng hợp weighted score + diversity boost
 *
 * Usage:
 *   require_once 'feed_ranker.php';
 *   $ranker = new FeedRanker($conn, $user_id);
 *   $feed   = $ranker->getFeed(limit: 30);
 */

class FeedRanker {

    private $conn;
    private int $me;
    private array $userProfile = [];   // cache profile + interests

    // ── Weights cho Relevance Score (tunable) ──────────────
    private array $W = [
        'like'         => 0.30,
        'reply'        => 0.50,   // reply = high-cost action → weight cao nhất
        'dwell'        => 1.00,   // watch-time driver
        'share'        => 0.80,
        'relationship' => 0.40,   // bonus khi author là người follow
        'interest'     => 0.35,   // bonus khi match interest
    ];

    // ── Penalties ──────────────────────────────────────────
    private array $PENALTY = [
        'same_author_repeat' => 0.60,  // nhân hệ số nếu author xuất hiện >1 lần
        'already_reacted'    => 0.50,  // hạ điểm bài đã tương tác (ít cần xem lại)
        'old_post_hours'     => 72,    // post > 72h → score giảm mạnh
    ];

    public function __construct($conn, int $user_id) {
        $this->conn = $conn;
        $this->me   = $user_id;
        $this->loadUserProfile();
    }

    // ════════════════════════════════════════════════════════
    // PUBLIC: Entry point
    // ════════════════════════════════════════════════════════
    public function getFeed(int $limit = 30): array {
        // Step 1
        $candidates = $this->collectInventory();

        if (empty($candidates)) return [];

        // Step 2 + 3 + 4
        $scored = [];
        foreach ($candidates as &$post) {
            $this->computeSignals($post);
            $this->computePredictions($post);
            $post['relevance_score'] = $this->computeScore($post);
            $scored[] = $post;
        }

        // Sort DESC by score
        usort($scored, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        // Diversity enforcement + serve
        return $this->serveFeed($scored, $limit);
    }

    // ════════════════════════════════════════════════════════
    // STEP 1: INVENTORY — Thu thập candidates từ 3 nguồn
    // ════════════════════════════════════════════════════════
    private function collectInventory(): array {
        $me    = $this->me;
        $posts = [];
        $has_impressions = $this->tableExists('post_impressions');
        $has_follows = $this->tableExists('follows');

        $impression_filter = $has_impressions
            ? "AND p.id NOT IN (
                SELECT post_id FROM post_impressions
                WHERE user_id=$me AND action IN ('hide','report')
            )"
            : "";
        $follow_source = $has_follows
            ? "WHERE p.user_id IN (
                SELECT following_id FROM follows WHERE follower_id = $me
            )"
            : "WHERE 1=0";
        $follow_exclude = $has_follows
            ? "AND p.user_id NOT IN (
                SELECT following_id FROM follows WHERE follower_id=$me
            )"
            : "";

        // ── Nguồn A: Social graph (người đang follow) ──────
        // Ưu tiên cao nhất — gắn label source để tính signal sau
        $sql_a = "
            SELECT p.*,
                   u.ten AS ten_user, u.avatar,
                   'following' AS source,
                   (SELECT COUNT(*) FROM reactions
                    WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
                   (SELECT COUNT(*) FROM comments
                    WHERE target_type='post' AND target_id=p.id) AS tong_comment,
                   (SELECT loai FROM reactions
                    WHERE target_type='post' AND target_id=p.id AND user_id=$me
                    LIMIT 1) AS my_reaction,
                   ph.ten_phim, ph.poster AS phim_poster, ph.the_loai AS phim_genre
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN phim ph ON p.phim_id = ph.id
            $follow_source
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            $impression_filter
            ORDER BY p.created_at DESC
            LIMIT 200
        ";
        $r = mysqli_query($this->conn, $sql_a);
        while ($row = mysqli_fetch_assoc($r)) $posts[$row['id']] = $row;

        // ── Nguồn B: Interest-based (phim cùng thể loại yêu thích) ──
        $interest_topics = $this->getTopInterests(5);
        if (!empty($interest_topics)) {
            $topics_in = implode(',', array_map(
                fn($t) => "'" . mysqli_real_escape_string($this->conn, $t) . "'",
                $interest_topics
            ));
            $sql_b = "
                SELECT p.*,
                       u.ten AS ten_user, u.avatar,
                       'interest' AS source,
                       (SELECT COUNT(*) FROM reactions
                        WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
                       (SELECT COUNT(*) FROM comments
                        WHERE target_type='post' AND target_id=p.id) AS tong_comment,
                       (SELECT loai FROM reactions
                        WHERE target_type='post' AND target_id=p.id AND user_id=$me
                        LIMIT 1) AS my_reaction,
                       ph.ten_phim, ph.poster AS phim_poster, ph.the_loai AS phim_genre
                FROM posts p
                JOIN users u ON p.user_id = u.id
                JOIN phim ph ON p.phim_id = ph.id
                WHERE ph.the_loai IN ($topics_in)
                AND p.user_id != $me
                $follow_exclude
                AND p.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                $impression_filter
                ORDER BY p.engagement_score DESC
                LIMIT 100
            ";
            $r = mysqli_query($this->conn, $sql_b);
            while ($row = mysqli_fetch_assoc($r)) {
                if (!isset($posts[$row['id']])) $posts[$row['id']] = $row;
            }
        }

        // ── Nguồn C: Trending (engagement cao nhất 24h) ────
        $sql_c = "
            SELECT p.*,
                   u.ten AS ten_user, u.avatar,
                   'trending' AS source,
                   (SELECT COUNT(*) FROM reactions
                    WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
                   (SELECT COUNT(*) FROM comments
                    WHERE target_type='post' AND target_id=p.id) AS tong_comment,
                   (SELECT loai FROM reactions
                    WHERE target_type='post' AND target_id=p.id AND user_id=$me
                    LIMIT 1) AS my_reaction,
                   ph.ten_phim, ph.poster AS phim_poster, ph.the_loai AS phim_genre
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN phim ph ON p.phim_id = ph.id
            WHERE p.user_id != $me
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            $impression_filter
            ORDER BY p.engagement_score DESC
            LIMIT 50
        ";
        $r = mysqli_query($this->conn, $sql_c);
        while ($row = mysqli_fetch_assoc($r)) {
            if (!isset($posts[$row['id']])) $posts[$row['id']] = $row;
        }

        // Bài của chính mình luôn xuất hiện
        $sql_me = "
            SELECT p.*,
                   u.ten AS ten_user, u.avatar,
                   'self' AS source,
                   (SELECT COUNT(*) FROM reactions
                    WHERE target_type='post' AND target_id=p.id) AS tong_reaction,
                   (SELECT COUNT(*) FROM comments
                    WHERE target_type='post' AND target_id=p.id) AS tong_comment,
                   NULL AS my_reaction,
                   ph.ten_phim, ph.poster AS phim_poster, ph.the_loai AS phim_genre
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN phim ph ON p.phim_id = ph.id
            WHERE p.user_id = $me
            ORDER BY p.created_at DESC
            LIMIT 10
        ";
        $r = mysqli_query($this->conn, $sql_me);
        while ($row = mysqli_fetch_assoc($r)) $posts[$row['id']] = $row;

        return array_values($posts);
    }

    // ════════════════════════════════════════════════════════
    // STEP 2: SIGNALS
    // ════════════════════════════════════════════════════════
    private function computeSignals(array &$post): void {
        $author = (int)$post['user_id'];
        $me     = $this->me;

        // ── Relationship signal ─────────────────────────────
        $rel = 0.0;
        if ($post['source'] === 'self') {
            $rel = 3.0;
        } elseif ($post['source'] === 'following') {
            $rel = 2.0;
            // Mutual follow bonus
            if ($this->tableExists('follows')) {
                $mutual = mysqli_fetch_assoc(mysqli_query($this->conn,
                    "SELECT 1 FROM follows WHERE follower_id=$author AND following_id=$me LIMIT 1"
                ));
                if ($mutual) $rel += 0.5;

                // Bạn chung cũng tương tác bài này
                $mutual_engaged = (int)mysqli_fetch_assoc(mysqli_query($this->conn,
                    "SELECT COUNT(*) AS c FROM reactions r
                     JOIN follows f ON f.following_id = r.user_id
                     WHERE f.follower_id=$me AND r.target_type='post' AND r.target_id={$post['id']}"
                ))['c'];
                $rel += min($mutual_engaged * 0.3, 1.0);
            }
        } elseif ($post['source'] === 'interest') {
            $rel = 0.5;
        } else {
            $rel = 0.2; // trending
        }
        $post['sig_relationship'] = $rel;

        // ── Content-type signal ─────────────────────────────
        $has_image  = !empty($post['hinh_anh']) ? 1.0 : 0.0;
        $has_movie  = !empty($post['phim_id'])  ? 0.8 : 0.0;
        $text_len   = mb_strlen($post['noi_dung'] ?? '');
        $text_score = $text_len > 50 ? 0.6 : 0.3;

        // Lấy preference từ lịch sử user
        $content_pref = $this->getContentTypePreference($post);
        $post['sig_content'] = max($has_image, $has_movie, $text_score) * $content_pref;

        // ── Recency signal — exponential decay ─────────────
        $age_hours = max(0, (time() - strtotime($post['created_at'])) / 3600);
        if ($age_hours < 1) {
            $decay = 1.0;
        } elseif ($age_hours < $this->PENALTY['old_post_hours']) {
            // Half-life ≈ 10h: exp(-0.07 * age)
            $decay = exp(-0.07 * $age_hours);
        } else {
            $decay = exp(-0.07 * $age_hours) * 0.3; // accelerated decay sau 72h
        }

        // Viral spike reset: nếu engagement tăng mạnh gần đây → reset decay
        $recent_reactions = (int)mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT COUNT(*) AS c FROM reactions
             WHERE target_type='post' AND target_id={$post['id']}
             AND created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
        ))['c'];
        if ($recent_reactions >= 5) {
            $decay = min(1.0, $decay * 1.5); // spike boost
        }
        $post['sig_recency'] = $decay;

        // ── Engagement signal ───────────────────────────────
        $reactions = (int)($post['tong_reaction'] ?? 0);
        $comments  = (int)($post['tong_comment']  ?? 0);
        $eng_raw   = $reactions * 1.0 + $comments * 2.0;
        // Normalize với log để tránh viral post dominate hoàn toàn
        $post['sig_engagement'] = $eng_raw > 0 ? log(1 + $eng_raw) : 0.0;
    }

    // ════════════════════════════════════════════════════════
    // STEP 3: PREDICTIONS — P(action) dựa trên heuristics
    // (Thay thế ML model bằng feature-weighted heuristics)
    // ════════════════════════════════════════════════════════
    private function computePredictions(array &$post): void {
        $me = $this->me;
        $author = (int)$post['user_id'];

        // ── P(like) ─────────────────────────────────────────
        // Cao khi: author quen, content type match, engagement cao
        $p_like = 0.05; // baseline
        $p_like += $post['sig_relationship'] * 0.08;
        $p_like += $post['sig_content']      * 0.06;
        $p_like += min($post['sig_engagement'] * 0.02, 0.15);
        // User này hay like content của author không?
        $past_like_rate = $this->getAuthorInteractionRate($author, 'like');
        $p_like += $past_like_rate * 0.10;
        $post['p_like'] = min($p_like, 1.0);

        // ── P(reply) ────────────────────────────────────────
        // Cao khi: controversial/discussion-worthy, mutual follow
        $p_reply = 0.02; // baseline (reply hiếm hơn like)
        $p_reply += $post['sig_relationship'] * 0.04;
        $text_len = mb_strlen($post['noi_dung'] ?? '');
        $p_reply += $text_len > 100 ? 0.03 : 0.01; // bài dài → dễ gợi reply hơn
        $p_reply += (int)($post['tong_comment'] ?? 0) * 0.005; // bài đã có discussion
        $past_reply_rate = $this->getAuthorInteractionRate($author, 'reply');
        $p_reply += $past_reply_rate * 0.05;
        $post['p_reply'] = min($p_reply, 1.0);

        // ── P(dwell) — proxy watch time ─────────────────────
        // Cao khi: có ảnh/phim tag, bài dài, source = following
        $p_dwell = 0.10;
        $p_dwell += !empty($post['hinh_anh']) ? 0.15 : 0;
        $p_dwell += !empty($post['phim_id'])  ? 0.12 : 0;
        $p_dwell += min($text_len / 500.0, 0.15); // max 0.15 khi bài dài
        $p_dwell += $post['sig_relationship'] * 0.06;
        // Interest match bonus
        if (!empty($post['phim_genre'])) {
            $genre_score = $this->getInterestScore($post['phim_genre']);
            $p_dwell += $genre_score * 0.10;
        }
        $post['p_dwell'] = min($p_dwell, 1.0);

        // ── P(share) ────────────────────────────────────────
        $p_share = 0.01;
        $p_share += !empty($post['phim_id'])  ? 0.04 : 0;
        $p_share += $post['sig_engagement']   * 0.01;
        $post['p_share'] = min($p_share, 1.0);

        // ── P(negative) — hide/report ───────────────────────
        $p_neg = 0.01;
        // Nếu user đã hide bài từ author này trước đó
        $hide_count = 0;
        if ($this->tableExists('post_impressions')) {
            $row = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT COUNT(*) AS c FROM post_impressions pi
                 JOIN posts pp ON pi.post_id = pp.id
                 WHERE pi.user_id=$me AND pp.user_id=$author
                 AND pi.action IN ('hide','report')"
            ));
            $hide_count = (int)($row['c'] ?? 0);
        }
        $p_neg += $hide_count * 0.05;
        $post['p_negative'] = min($p_neg, 1.0);
    }

    // ════════════════════════════════════════════════════════
    // STEP 4: RELEVANCE SCORE
    // ════════════════════════════════════════════════════════
    private function computeScore(array $post): float {
        $W = $this->W;

        // Base weighted score
        $score =
            $W['like']         * ($post['p_like']   ?? 0)
          + $W['reply']        * ($post['p_reply']  ?? 0)
          + $W['dwell']        * ($post['p_dwell']  ?? 0)
          + $W['share']        * ($post['p_share']  ?? 0)
          + $W['relationship'] * ($post['sig_relationship'] ?? 0) * 0.1
          + $W['interest']     * ($post['sig_content'] ?? 0) * 0.1;

        // Recency multiplier
        $score *= max(0.1, $post['sig_recency'] ?? 1.0);

        // Penalty: negative signal
        if (($post['p_negative'] ?? 0) > 0.15) {
            $score -= 5.0;
        }

        // Penalty: đã tương tác bài này rồi → hạ ưu tiên
        if (!empty($post['my_reaction'])) {
            $score *= $this->PENALTY['already_reacted'];
        }

        // Diversity boost: author nằm ngoài top-following → tăng nhẹ
        // (chống filter bubble)
        if ($post['source'] === 'interest' || $post['source'] === 'trending') {
            $score *= 1.20;
        }

        // Bài của mình: luôn xuất hiện đầu nếu mới
        if ($post['source'] === 'self') {
            $age_h = (time() - strtotime($post['created_at'])) / 3600;
            if ($age_h < 6) $score += 2.0;
        }

        return max(0.0, $score);
    }

    // ════════════════════════════════════════════════════════
    // SERVE: Diversity enforcement
    // ════════════════════════════════════════════════════════
    private function serveFeed(array $ranked, int $limit): array {
        $feed         = [];
        $author_count = [];
        $source_count = ['following'=>0,'interest'=>0,'trending'=>0,'self'=>0];

        foreach ($ranked as $post) {
            $author = (int)$post['user_id'];
            $source = $post['source'];

            // Mỗi author tối đa 2 bài trong feed
            if (($author_count[$author] ?? 0) >= 2) continue;

            // Tối đa 40% trending trong feed (diversity)
            if ($source === 'trending' && $source_count['trending'] >= (int)($limit * 0.40)) continue;

            $feed[]                 = $post;
            $author_count[$author]  = ($author_count[$author] ?? 0) + 1;
            $source_count[$source]  = ($source_count[$source] ?? 0) + 1;

            if (count($feed) >= $limit) break;
        }

        return $feed;
    }

    // ════════════════════════════════════════════════════════
    // FEEDBACK LOOP — gọi sau khi user tương tác
    // ════════════════════════════════════════════════════════
    public function logImpression(int $post_id, string $action, int $dwell_ms = 0): void {
        $me = $this->me;
        $action = mysqli_real_escape_string($this->conn, $action);
        if ($this->tableExists('post_impressions')) {
            mysqli_query($this->conn,
                "INSERT INTO post_impressions (user_id, post_id, dwell_ms, action)
                 VALUES ($me, $post_id, $dwell_ms, '$action')
                 ON DUPLICATE KEY UPDATE dwell_ms = GREATEST(dwell_ms, $dwell_ms), action='$action'"
            );
        }

        // Cập nhật interest profile
        if (in_array($action, ['like','reply','share'])) {
            $genre = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT ph.the_loai FROM posts p
                 JOIN phim ph ON p.phim_id=ph.id
                 WHERE p.id=$post_id LIMIT 1"
            ));
            if ($genre && $genre['the_loai']) {
                $this->updateInterest($genre['the_loai'], $action);
            }
        }

        // Cập nhật engagement_score của bài đăng
        $this->refreshEngagementScore($post_id);
    }

    private function refreshEngagementScore(int $post_id): void {
        // score = reactions*1 + comments*2 + views*0.1
        if ($this->tableExists('post_impressions')) {
            mysqli_query($this->conn,
                "UPDATE posts SET engagement_score = (
                    SELECT COALESCE(SUM(
                        CASE action
                            WHEN 'like'   THEN 3
                            WHEN 'reply'  THEN 5
                            WHEN 'share'  THEN 4
                            ELSE 0.1
                        END
                    ), 0)
                    FROM post_impressions WHERE post_id = $post_id
                ) + (
                    SELECT COUNT(*)*1 FROM reactions
                    WHERE target_type='post' AND target_id=$post_id
                ) + (
                    SELECT COUNT(*)*2 FROM comments
                    WHERE target_type='post' AND target_id=$post_id
                )
                WHERE id = $post_id"
            );
        } else {
            mysqli_query($this->conn,
                "UPDATE posts SET engagement_score = (
                    SELECT COUNT(*)*1 FROM reactions
                    WHERE target_type='post' AND target_id=$post_id
                ) + (
                    SELECT COUNT(*)*2 FROM comments
                    WHERE target_type='post' AND target_id=$post_id
                )
                WHERE id = $post_id"
            );
        }
    }

    // ════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════
    private function loadUserProfile(): void {
        $me = $this->me;
        $interests = [];
        if (!$this->tableExists('user_interests')) {
            $this->userProfile['interests'] = [];
            return;
        }
        $r = mysqli_query($this->conn,
            "SELECT topic, score FROM user_interests WHERE user_id=$me ORDER BY score DESC"
        );
        if ($r) while ($row = mysqli_fetch_assoc($r)) {
            $interests[$row['topic']] = (float)$row['score'];
        }
        $this->userProfile['interests'] = $interests;
    }

    private function getTopInterests(int $n): array {
        $interests = $this->userProfile['interests'] ?? [];
        arsort($interests);
        return array_slice(array_keys($interests), 0, $n);
    }

    private function getInterestScore(string $topic): float {
        return min(1.0, ($this->userProfile['interests'][$topic] ?? 0) / 10.0);
    }

    private function getAuthorInteractionRate(int $author_id, string $action): float {
        $me = $this->me;
        $action = mysqli_real_escape_string($this->conn, $action);
        if (!$this->tableExists('post_impressions')) {
            return 0.0;
        }
        $row = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT
                COUNT(*) AS interactions,
                (SELECT COUNT(*) FROM posts WHERE user_id=$author_id) AS total_posts
             FROM post_impressions pi
             JOIN posts pp ON pi.post_id=pp.id
             WHERE pi.user_id=$me AND pp.user_id=$author_id AND pi.action='$action'"
        ));
        if (!$row || !$row['total_posts']) return 0.0;
        return min(1.0, (float)$row['interactions'] / (float)$row['total_posts']);
    }

    private function getContentTypePreference(array $post): float {
        // Dựa trên lịch sử: user hay dwell trên loại content nào
        $me = $this->me;
        $has_image = !empty($post['hinh_anh']) ? 1 : 0;
        $has_movie = !empty($post['phim_id'])  ? 1 : 0;

        if (!$this->tableExists('post_impressions')) {
            return $has_movie ? 1.2 : 1.0;
        }
        if ($has_image) {
            $row = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT AVG(pi.dwell_ms) AS avg_dwell
                 FROM post_impressions pi
                 JOIN posts pp ON pi.post_id=pp.id
                 WHERE pi.user_id=$me AND pp.hinh_anh IS NOT NULL"
            ));
            $avg = (float)($row['avg_dwell'] ?? 5000);
            return min(1.5, $avg / 5000.0); // 5000ms = baseline
        }
        if ($has_movie) return 1.2;
        return 1.0;
    }

    private function updateInterest(string $topic, string $action): void {
        $me = $this->me;
        if (!$this->tableExists('user_interests')) {
            return;
        }
        $delta = ['like'=>0.5, 'reply'=>1.0, 'share'=>0.8][$action] ?? 0.1;
        $topic = mysqli_real_escape_string($this->conn, $topic);
        mysqli_query($this->conn,
            "INSERT INTO user_interests (user_id, topic, score)
             VALUES ($me, '$topic', $delta)
             ON DUPLICATE KEY UPDATE
             score = LEAST(10.0, score + $delta)"
        );
        // Decay các interest khác (chậm dần nếu không tương tác)
        mysqli_query($this->conn,
            "UPDATE user_interests SET score = GREATEST(0.1, score * 0.99)
             WHERE user_id=$me AND topic != '$topic'"
        );
        $this->loadUserProfile(); // refresh cache
    }

    private function tableExists(string $table): bool {
        $sql = "SELECT COUNT(*) AS cnt
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 's', $table);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row && (int)$row['cnt'] > 0;
    }
}
