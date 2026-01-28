<?php
session_start();
require_once '../../config.php';
require_once '../../includes/forum_functions.php';

// Check if admin
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle post approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_post' && isset($_POST['post_id'])) {
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$_POST['post_id']]);
        header('Location: moderate_posts.php?msg=approved');
        exit;
    } elseif ($_POST['action'] === 'reject_post' && isset($_POST['post_id'])) {
        $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
        $stmt->execute([$_POST['post_id']]);
        header('Location: moderate_posts.php?msg=rejected');
        exit;
    }
}

// Get pending posts
$stmt = $pdo->query("
    SELECT fp.*,
        CASE 
            WHEN fp.user_type = 'patient' THEN CONCAT(p.fname, ' ', p.lname)
            WHEN fp.user_type = 'doctor' THEN d.fullname
            WHEN fp.user_type = 'admin' THEN 'Admin'
        END as author_name
    FROM forum_posts fp
    LEFT JOIN patreg p ON (fp.user_id = p.pid AND fp.user_type = 'patient')
    LEFT JOIN doctb d ON (fp.user_id = d.id AND fp.user_type = 'doctor')
    WHERE fp.is_approved = 0
    ORDER BY fp.created_at DESC
");
$pending_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt bài viết - Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="sidebar-title">Bệnh viện Global</h1>
                    <div class="sidebar-subtitle">Cổng Quản trị</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="dashboard.php" class="sidebar-menu-link">
                        <i class="fas fa-th-large sidebar-menu-icon"></i>
                        <span>Bảng điều khiển</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="moderate_posts.php" class="sidebar-menu-link active">
                        <i class="fas fa-check-circle sidebar-menu-icon"></i>
                        <span>Duyệt bài viết</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="../forum/index.php" class="sidebar-menu-link">
                        <i class="fas fa-comments sidebar-menu-icon"></i>
                        <span>Diễn đàn</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="btn btn-danger btn-block">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Duyệt bài viết diễn đàn</h1>
                </div>
            </nav>

            <section class="content-section">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success">
                        <?= $_GET['msg'] === 'approved' ? 'Đã duyệt bài viết!' : 'Đã từ chối bài viết!' ?>
                    </div>
                <?php endif; ?>

                <div class="data-table-container">
                    <div class="data-table-header">
                        <h3 class="data-table-title">
                            <i class="fas fa-list"></i> Bài viết chờ duyệt (<?= count($pending_posts) ?>)
                        </h3>
                    </div>

                    <?php if (empty($pending_posts)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                            <p class="mt-3">Không có bài viết nào chờ duyệt</p>
                        </div>
                    <?php else: ?>
                        <div class="p-4">
                            <?php foreach ($pending_posts as $post): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                                <p class="text-muted small">
                                                    <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?>
                                                    <span class="ml-3"><i class="fas fa-clock"></i> <?= timeAgo($post['created_at']) ?></span>
                                                    <span class="ml-3"><i class="fas fa-tag"></i> <?= ucfirst($post['category']) ?></span>
                                                </p>
                                                <p class="card-text"><?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>...</p>
                                                <?php if ($post['tags']): ?>
                                                    <div class="mb-2">
                                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                                            <span class="badge badge-info"><?= htmlspecialchars(trim($tag)) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve_post">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline ml-2" onsubmit="return confirm('Bạn có chắc muốn từ chối bài viết này?')">
                                                <input type="hidden" name="action" value="reject_post">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Từ chối
                                                </button>
                                            </form>
                                            <a href="../forum/post.php?id=<?= $post['id'] ?>" class="btn btn-info ml-2" target="_blank">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
