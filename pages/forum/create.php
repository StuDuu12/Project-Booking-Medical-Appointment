<?php
session_start();
require_once '../../config.php';
require_once '../../includes/forum_functions.php';

// Check if user is logged in
if (!isset($_SESSION['patientSession']) && !isset($_SESSION['doctorSession']) && !isset($_SESSION['adminSession'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Determine user type and ID
if (isset($_SESSION['patientSession'])) {
    $user_type = 'patient';
    $user_id = $_SESSION['patientSession'];
} elseif (isset($_SESSION['doctorSession'])) {
    $user_type = 'doctor';
    $user_id = $_SESSION['doctorSession'];
} else {
    $user_type = 'admin';
    $user_id = 1;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $privacy = $_POST['privacy'] ?? 'public';
    
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề';
    } elseif (empty($content)) {
        $error = 'Vui lòng nhập nội dung';
    } else {
        $data = [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
            'category' => $category,
            'privacy' => $privacy
        ];
        
        if (createForumPost($pdo, $data)) {
            $post_id = $pdo->lastInsertId();
            header('Location: post.php?id=' . $post_id);
            exit;
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài viết - Diễn đàn</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: #f0fdfa;
            font-family: 'Inter', sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0e7490, #0891b2);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0c6481, #067a8f);
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-edit"></i> Tạo bài viết mới</h1>
        </div>
    </div>

    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
            <ol class="breadcrumb" style="background: white; padding: 1rem; border-radius: 8px;">
                <li class="breadcrumb-item"><a href="../../index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="index.php">Diễn đàn</a></li>
                <li class="breadcrumb-item active">Tạo bài viết</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= h($success) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="title">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255" placeholder="Nhập tiêu đề bài viết...">
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Nội dung <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="10" required placeholder="Nhập nội dung bài viết..."></textarea>
                            <small class="form-text text-muted">Hãy mô tả chi tiết vấn đề hoặc nội dung bạn muốn chia sẻ</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category">Danh mục</label>
                                    <select class="form-control" id="category" name="category">
                                        <option value="general">Tổng quát</option>
                                        <option value="question">Câu hỏi</option>
                                        <option value="discussion">Thảo luận</option>
                                        <option value="announcement">Thông báo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="privacy">Quyền riêng tư</label>
                                    <select class="form-control" id="privacy" name="privacy">
                                        <option value="public">Công khai</option>
                                        <option value="private">Riêng tư</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">Thẻ (Tags)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Ví dụ: #tim-mạch, #câu-hỏi, #kinh-nghiệm">
                            <small class="form-text text-muted">Phân tách các tag bằng dấu  phẩy. Ví dụ: #tag1, #tag2</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Đăng bài
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
