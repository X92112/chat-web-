<?php
// Post Card Component — Hand-made Retro-Ink Zine Style
if (!isset($post)) {
    return;
}

$post_id = (int)$post['id'];
$author_id = (int)$post['user_id'];
$author_username = $post['username'] ?? 'anonymous';
$author_display = !empty($post['display_name']) ? $post['display_name'] : $author_username;
$author_verified = !empty($post['is_verified']);
$created_time = time_ago($post['created_at']);

$is_admin = ($current_user && !empty($current_user['is_admin']));
$is_own_post = ($current_user && (int)$current_user['id'] === $author_id);
$can_delete_post = ($is_own_post || $is_admin);
$is_following_author = ($current_user && !$is_own_post) ? is_following($current_user['id'], $author_id) : false;

$like_count = (int)($post['like_count'] ?? 0);
$is_liked = !empty($post['is_liked']);
$comment_count = (int)($post['comment_count'] ?? 0);
$is_bookmarked = isset($post['is_bookmarked'])
    ? !empty($post['is_bookmarked'])
    : ($current_user ? is_post_bookmarked((int)$current_user['id'], $post_id) : false);

// Image URLs
$img_src = UPLOAD_URL . '/' . rawurlencode($post['thumb'] ?: $post['filename']);
$full_img_src = UPLOAD_URL . '/' . rawurlencode($post['filename']);

// Tags
$tags = parse_tags($post['tags'] ?? '');

// Preview comments with verified badge
$db = get_db();
$comm_stmt = $db->prepare("
    SELECT c.id, c.body, c.created_at, c.user_id, u.username, u.is_verified
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = :post_id
    ORDER BY c.created_at ASC
");
$comm_stmt->execute(['post_id' => $post_id]);
$all_comments = $comm_stmt->fetchAll();
?>
<article class="card post-card" id="post-<?= $post_id ?>" data-post-id="<?= $post_id ?>">
  <!-- Header -->
  <header class="post-header">
    <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($author_username) ?>">
      <?= render_avatar($author_username, 40) ?>
    </a>
    <div class="post-user-info">
      <div style="display:flex; align-items:center; gap:4px; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($author_username) ?>" class="post-username">
          <?= e($author_username) ?>
        </a>
        <?= verified_badge($author_verified) ?>
        <?php if ($author_display !== $author_username): ?>
          <span style="font-size:12.5px; color:var(--muted);">· <?= e($author_display) ?></span>
        <?php endif; ?>
      </div>
      <span class="post-time"><?= e($created_time) ?></span>
    </div>

    <div class="post-header-action">
      <?php if ($can_delete_post): ?>
        <button type="button" class="btn-delete-post" onclick="deletePost(<?= $post_id ?>)" title="<?= $is_admin && !$is_own_post ? 'Admin delete' : 'Delete post' ?>">
          delete<?= ($is_admin && !$is_own_post) ? ' (admin)' : '' ?>
        </button>
      <?php elseif ($current_user): ?>
        <button type="button" class="btn btn-sm btn-follow <?= $is_following_author ? 'following' : '' ?>" onclick="toggleFollow(<?= $author_id ?>, this)">
          <?= $is_following_author ? 'Following' : 'Follow' ?>
        </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- Image between two ink lines -->
  <div class="post-image-container" ondblclick="doubleTapLike(<?= $post_id ?>, this)">
    <a href="<?= BASE_URL ?>/post.php?id=<?= $post_id ?>" style="display:block; width:100%;" onclick="if(event.detail > 1) event.preventDefault();">
      <img src="<?= e($img_src) ?>" alt="Photo by <?= e($author_username) ?>" class="post-image" loading="lazy">
    </a>
    <div class="like-burst-heart">liked!</div>
    <button type="button" class="post-zoom-btn" onclick="openFeedLightbox('<?= e($full_img_src) ?>', '<?= e(addslashes($post['caption'] ?? '')) ?>')" title="Open full size">
      full size
    </button>
  </div>

  <!-- Content & Actions -->
  <div class="post-body">
    <?php if (!empty($post['caption'])): ?>
      <p class="post-caption"><?= e($post['caption']) ?></p>
    <?php endif; ?>

    <?php if (!empty($tags)): ?>
      <div class="post-tags">
        <?php foreach ($tags as $tag): ?>
          <a href="<?= BASE_URL ?>/explore.php?q=<?= urlencode($tag) ?>" class="tag-badge">#<?= e($tag) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Action Row (Text buttons only, no emoji or icon libraries) -->
    <div class="post-actions">
      <button type="button" class="action-btn <?= $is_liked ? 'liked' : '' ?>" id="like-btn-<?= $post_id ?>" onclick="toggleLike(<?= $post_id ?>)">
        <span class="like-label"><?= $is_liked ? 'Liked' : 'Like' ?></span>
        <span class="like-num" id="like-count-<?= $post_id ?>"><?= $like_count ?></span>
      </button>

      <button type="button" class="action-btn" onclick="toggleCommentsSection(<?= $post_id ?>)">
        <span>Comment</span>
        <span class="comment-num" id="comment-count-<?= $post_id ?>"><?= $comment_count ?></span>
      </button>

      <button type="button" class="action-btn" onclick="sharePost(<?= $post_id ?>)">
        Share
      </button>

      <?php if ($current_user): ?>
        <button type="button" class="action-btn action-btn-save <?= $is_bookmarked ? 'bookmarked' : '' ?>" id="bookmark-btn-<?= $post_id ?>" onclick="toggleBookmark(<?= $post_id ?>, this)" style="margin-left:auto;">
          <?= $is_bookmarked ? 'Saved' : 'Save' ?>
        </button>
      <?php endif; ?>
    </div>

    <!-- Comments preview and list -->
    <div class="post-comments-container" id="comments-box-<?= $post_id ?>">
      <?php foreach ($all_comments as $c): ?>
        <div class="comment-row" id="comment-<?= (int)$c['id'] ?>">
          <a href="<?= BASE_URL ?>/profile.php?u=<?= urlencode($c['username']) ?>" class="comment-user">
            <?= e($c['username']) ?>
          </a>
          <?= verified_badge($c['is_verified'] ?? 0) ?>
          <span class="comment-body"><?= e($c['body']) ?></span>
          <?php if ($current_user && ((int)$current_user['id'] === (int)$c['user_id'] || $can_delete_post)): ?>
            <button type="button" class="comment-del" onclick="deleteComment(<?= (int)$c['id'] ?>, <?= $post_id ?>)" title="Delete comment">delete</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Inline Comment Form -->
    <?php if ($current_user): ?>
      <form class="comment-form" onsubmit="submitComment(event, <?= $post_id ?>)">
        <input type="text" class="comment-input" name="comment_body" placeholder="write a comment..." maxlength="500" required autocomplete="off">
        <button type="submit" class="btn btn-sm comment-submit">Post</button>
      </form>
    <?php endif; ?>
  </div>
</article>
