<?php
session_start();
require_once '../../config.php';
require_once '../../includes/forum_functions.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['patientSession']) || isset($_SESSION['doctorSession']) || isset($_SESSION['adminSession']);

// Determine user type and ID
$user_id = null;
$user_type = null;
$user_name = '';

if (isset($_SESSION['patientSession'])) {
    $user_type = 'patient';
    $user_id = $_SESSION['patientSession'];
    $stmt = $pdo->prepare("SELECT CONCAT(fname, ' ', lname) as name FROM patreg WHERE pid = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_name = $user['name'] ?? 'Patient';
} elseif (isset($_SESSION['doctorSession'])) {
    $user_type = 'doctor';
    $user_id = $_SESSION['doctorSession'];
    $stmt = $pdo->prepare("SELECT fullname as name FROM doctb WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_name = $user['name'] ?? 'Doctor';
} elseif (isset($_SESSION['adminSession'])) {
    $user_type = 'admin';
    $user_id = 1; // Admin ID
    $user_name = 'Admin';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $isLoggedIn) {
    if ($_POST['action'] === 'toggle_like' && isset($_POST['post_id'])) {
        toggleForumLike($pdo, $user_id, $user_type, $_POST['post_id'], 'post');
        header('Location: index.php' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit;
    }
}

// Get filters
$filters = [];
if (isset($_GET['search'])) $filters['search'] = trim($_GET['search']);
if (isset($_GET['category'])) $filters['category'] = $_GET['category'];
if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
if (isset($_GET['sort'])) $filters['sort'] = $_GET['sort'];

// Add approval filter for non-admin users
// Commented out until is_approved column is added to database
// if ($user_type !== 'admin') {
//     $filters['approved_only'] = true;
// }

// Get posts
$posts = getForumPosts($pdo, $filters);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diễn đàn - Global Hospitals</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/forum.css">
    <style>
        :root {
            --primary-color: #0891b2;
            --primary-dark: #0e7490;
            --secondary-color: #14b8a6;
        }
        
        body {
            background: #f0fdfa;
            font-family: 'Inter', sans-serif;
        }
        
        .forum-header {
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .forum-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .search-filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .post-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .post-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .author-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .badge-doctor {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-patient {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .post-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .post-excerpt {
            color: #64748b;
            margin-bottom: 1rem;
        }
        
        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .tag {
            background: #e0f2fe;
            color: var(--primary-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        
        .post-footer {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-action {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .btn-action:hover {
            color: var(--primary-color);
        }
        
        .btn-action.liked {
            color: var(--primary-color);
        }
        
        .btn-create-post {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0e7490, #0891b2);
            color: white;
            border: none;
            box-shadow: 0 4px 16px rgba(8, 145, 178, 0.3);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .btn-create-post:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 24px rgba(8, 145, 178, 0.4);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-open { background: #dbeafe; color: #1e40af; }
        .status-closed { background: #fee2e2; color: #991b1b; }
        .status-solved { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <!-- Forum Header -->
    <div class="forum-header">
        <div class="container">
            <h1><i class="fas fa-comments"></i> Diễn đàn</h1>
            <p>Trao đổi, chia sẻ kinh nghiệm và đánh giá dịch vụ y tế</p>
        </div>
    </div>

    <div class="container">
        <!-- Navigation -->
        <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
            <ol class="breadcrumb" style="background: white; padding: 1rem; border-radius: 8px;">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Diễn đàn</li>
            </ol>
        </nav>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <form method="GET" class="row align-items-end">
                <div class="col-md-5">
                    <label>Tìm kiếm</label>
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm bài viết..." value="<?= h($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label>Danh mục</label>
                    <select name="category" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="general" <?= (($_GET['category'] ?? '') === 'general') ? 'selected' : '' ?>>Tổng quát</option>
                        <option value="question" <?= (($_GET['category'] ?? '') === 'question') ? 'selected' : '' ?>>Câu hỏi</option>
                        <option value="discussion" <?= (($_GET['category'] ?? '') === 'discussion') ? 'selected' : '' ?>>Thảo luận</option>
                        <option value="announcement" <?= (($_GET['category'] ?? '') === 'announcement') ? 'selected' : '' ?>>Thông báo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="open" <?= (($_GET['status'] ?? '') === 'open') ? 'selected' : '' ?>>Đang mở</option>
                        <option value="solved" <?= (($_GET['status'] ?? '') === 'solved') ? 'selected' : '' ?>>Đã giải quyết</option>
                        <option value="closed" <?= (($_GET['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Đã đóng</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Sắp xếp</label>
                    <select name="sort" class="form-control">
                        <option value="">Mới nhất</option>
                        <option value="oldest" <?= (($_GET['sort'] ?? '') === 'oldest') ? 'selected' : '' ?>>Cũ nhất</option>
                        <option value="most_viewed" <?= (($_GET['sort'] ?? '') === 'most_viewed') ? 'selected' : '' ?>>Nhiều lượt xem</option>
                        <option value="most_liked" <?= (($_GET['sort'] ?? '') === 'most_liked') ? 'selected' : '' ?>>Nhiều lượt thích</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <!-- Posts List -->
        <div class="row">
            <div class="col-md-12">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox" style="font-size: 4rem; color: #cbd5e1;"></i>
                        <p class="mt-3 text-muted">Chưa có bài viết nào. <?php if ($isLoggedIn): ?><a href="create.php">Tạo bài viết đầu tiên!</a><?php endif; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="author-info">
                                    <div class="author-avatar">
                                        <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?= h($post['author_name']) ?>
                                            <?php if ($post['user_type'] === 'doctor'): ?>
                                                <span class="badge-doctor"><i class="fas fa-user-md"></i> Bác sĩ</span>
                                            <?php elseif ($post['user_type'] === 'patient'): ?>
                                                <span class="badge-patient"><i class="fas fa-user"></i> Bệnh nhân</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= timeAgo($post['created_at']) ?></small>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $post['status'] ?>">
                                    <?= $post['status'] === 'solved' ? '✓ Đã giải quyết' : ($post['status'] === 'closed' ? 'Đã đóng' : 'Đang mở') ?>
                                </span>
                            </div>
                            
                            <a href="post.php?id=<?= $post['id'] ?>" style="text-decoration: none; color: inherit;">
                                <h3 class="post-title"><?= h($post['title']) ?></h3>
                                <p class="post-excerpt"><?= h(mb_substr(strip_tags($post['content']), 0, 200)) ?>...</p>
                                
                                <?php if ($post['tags']): ?>
                                    <div class="post-tags">
                                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                            <span class="tag"><?= h(trim($tag)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <div class="post-footer">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_like">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn-action <?= ($isLoggedIn && hasForumLiked($pdo, $user_id, $user_type, $post['id'], 'post')) ? 'liked' : '' ?>" <?= !$isLoggedIn ? 'disabled' : '' ?>>
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?= $post['like_count'] ?></span>
                                    </button>
                                </form>
                                <a href="post.php?id=<?= $post['id'] ?>#comments" class="btn-action" style="text-decoration: none;">
                                    <i class="fas fa-comment"></i>
                                    <span><?= $post['comment_count'] ?></span>
                                </a>
                                <span class="btn-action">
                                    <i class="fas fa-eye"></i>
                                    <span><?= $post['views'] ?></span>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Post Button -->
    <?php if ($isLoggedIn): ?>
        <a href="create.php" class="btn-create-post" title="Tạo bài viết mới">
            <i class="fas fa-plus"></i>
        </a>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
