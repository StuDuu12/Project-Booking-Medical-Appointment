<?php
session_start();
require_once '../../config.php';
require_once '../../includes/forum_functions.php';

$post_id = intval($_GET['id'] ?? 0);
if (!$post_id) {
    header('Location: index.php');
    exit;
}

$isLoggedIn = isset($_SESSION['patientSession']) || isset($_SESSION['doctorSession']) || isset($_SESSION['adminSession']);
$user_id = null;
$user_type = null;

if (isset($_SESSION['patientSession'])) {
    $user_type = 'patient';
    $user_id = $_SESSION['patientSession'];
} elseif (isset($_SESSION['doctorSession'])) {
    $user_type = 'doctor';
    $user_id = $_SESSION['doctorSession'];
} elseif (isset($_SESSION['adminSession'])) {
    $user_type = 'admin';
    $user_id = 1;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    if ($_POST['action'] === 'add_comment') {
        addForumComment($pdo, [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'content' => $_POST['comment_content'],
            'parent_id' => $_POST['parent_id'] ?? null
        ]);
        header('Location: post.php?id=' . $post_id . '#comments');
        exit;
    } elseif ($_POST['action'] === 'toggle_like') {
        toggleForumLike($pdo, $user_id, $user_type, $_POST['target_id'], $_POST['target_type']);
        header('Location: post.php?id=' . $post_id);
        exit;
    }
}

// Get post
$post = getForumPost($pdo, $post_id);
if (!$post) {
    header('Location: index.php');
    exit;
}

// Increment views
incrementPostViews($pdo, $post_id);

// Get comments
$comments = getForumComments($pdo, $post_id);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($post['title']) ?> - Diễn đàn</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f0fdfa; font-family: 'Inter', sans-serif; }
        .post-detail { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .comments-section { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .comment-card { padding: 1rem; border-left: 3px solid #0891b2; margin-bottom: 1rem; background: #f8fafc; border-radius: 8px; }
        .badge-doctor { background: #10b981; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; }
        .badge-patient { background: #3b82f6; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="background: white; padding: 1rem; border-radius: 8px;">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="index.php">Diễn đàn</a></li>
                <li class="breadcrumb-item active"><?= h($post['title']) ?></li>
            </ol>
        </nav>

        <!-- Post Detail -->
        <div class="post-detail">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="h3"><?= h($post['title']) ?></h1>
                    <div class="mt-2">
                        <strong><?= h($post['author_name']) ?></strong>
                        <?php if ($post['user_type'] === 'doctor'): ?>
                            <span class="badge-doctor"><i class="fas fa-user-md"></i> Bác sĩ</span>
                        <?php elseif ($post['user_type'] === 'patient'): ?>
                            <span class="badge-patient"><i class="fas fa-user"></i> Bệnh nhân</span>
                        <?php endif; ?>
                        <span class="text-muted ml-2"><?= timeAgo($post['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <div class="post-content mb-4" style="line-height: 1.8;">
                <?= nl2br(h($post['content'])) ?>
            </div>

            <?php if ($post['tags']): ?>
                <div class="mb-3">
                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                        <span class="badge badge-info mr-1"><?= h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-3">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="toggle_like">
                    <input type="hidden" name="target_id" value="<?= $post_id ?>">
                    <input type="hidden" name="target_type" value="post">
                    <button type="submit" class="btn btn-sm <?= ($isLoggedIn && hasForumLiked($pdo, $user_id, $user_type, $post_id, 'post')) ? 'btn-primary' : 'btn-outline-primary' ?>" <?= !$isLoggedIn ? 'disabled' : '' ?>>
                        <i class="fas fa-thumbs-up"></i> <?= $post['like_count'] ?>
                    </button>
                </form>
                <span class="btn btn-sm btn-outline-secondary"><i class="fas fa-comment"></i> <?= $post['comment_count'] ?></span>
                <span class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i> <?= $post['views'] ?></span>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section" id="comments">
            <h4 class="mb-4"><i class="fas fa-comments"></i> Bình luận (<?= count($comments) ?>)</h4>

            <?php if ($isLoggedIn): ?>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="add_comment">
                    <div class="form-group">
                        <textarea name="comment_content" class="form-control" rows="3" placeholder="Viết bình luận..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi bình luận</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">Vui lòng <a href="../auth/login.php">đăng nhập</a> để bình luận.</div>
            <?php endif; ?>

            <?php foreach ($comments as $comment): ?>
                <div class="comment-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= h($comment['author_name']) ?></strong>
                            <span class="text-muted ml-2"><?= timeAgo($comment['created_at']) ?></span>
                        </div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_like">
                            <input type="hidden" name="target_id" value="<?= $comment['id'] ?>">
                            <input type="hidden" name="target_type" value="comment">
                            <button type="submit" class="btn btn-sm btn-link" <?= !$isLoggedIn ? 'disabled' : '' ?>>
                                <i class="fas fa-thumbs-up"></i> <?= $comment['like_count'] ?>
                            </button>
                        </form>
                    </div>
                    <p class="mt-2 mb-0"><?= nl2br(h($comment['content'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
