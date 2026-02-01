<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$doctor = $_SESSION['dname'] ?? null;

if (!$doctor) {
    header("Location: ../auth/login.php");
    exit();
}

// Get doctor info
$stmt = $pdo->prepare("SELECT id, fullname FROM doctb WHERE username = :doctor");
$stmt->execute([':doctor' => $doctor]);
$doc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doc_info['id'] ?? 0;
$doctor_fullname = $doc_info['fullname'] ?? $doctor;

// Xử lý upload tài liệu
if (isset($_POST['upload_document'])) {
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $pid = $_POST['pid'];
        $appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
        $description = trim($_POST['description'] ?? '');

        $file = $_FILES['document_file'];
        $fileName = basename($file['name']);
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];

        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($fileType, $allowedTypes)) {
            redirectWithMessage('documents.php', 'error', 'Loại file không được phép. Chỉ chấp nhận PDF, JPG, PNG, GIF, DOC, DOCX.');
            exit();
        }

        if ($fileSize > 10 * 1024 * 1024) {
            redirectWithMessage('documents.php', 'error', 'Kích thước file quá lớn. Tối đa 10MB.');
            exit();
        }

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
        if (!in_array($fileExt, $allowedExts)) {
            redirectWithMessage('documents.php', 'error', 'Phần mở rộng file không hợp lệ.');
            exit();
        }

        $newFileName = uniqid('med_doc_') . '.' . $fileExt;
        $uploadPath = '../../uploads/medical_documents/' . $newFileName;

        if (!is_dir('../../uploads/medical_documents/')) {
            mkdir('../../uploads/medical_documents/', 0755, true);
        }

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO medical_documents (pid, doctor, appointment_id, document_name, file_path, file_type, file_size, description, uploaded_at) VALUES (:pid, :doctor, :appointment_id, :document_name, :file_path, :file_type, :file_size, :description, NOW())");
                $stmt->execute([
                    ':pid' => $pid,
                    ':doctor' => $doctor,
                    ':appointment_id' => $appointment_id,
                    ':document_name' => $fileName,
                    ':file_path' => $newFileName,
                    ':file_type' => $fileType,
                    ':file_size' => $fileSize,
                    ':description' => $description
                ]);
                redirectWithMessage('documents.php', 'success', 'Tài liệu đã được upload thành công!');
            } catch (PDOException $e) {
                @unlink($uploadPath);
                redirectWithMessage('documents.php', 'error', 'Lỗi khi lưu thông tin tài liệu: ' . $e->getMessage());
            }
        } else {
            redirectWithMessage('documents.php', 'error', 'Lỗi khi upload file.');
        }
    } else {
        redirectWithMessage('documents.php', 'error', 'Vui lòng chọn file để upload.');
    }
}

// Xử lý xóa tài liệu
if (isset($_GET['delete_id'])) {
    $doc_id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM medical_documents WHERE id = :id AND doctor = :doctor");
        $stmt->execute([':id' => $doc_id, ':doctor' => $doctor]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($document) {
            $filePath = '../../uploads/medical_documents/' . $document['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $stmt = $pdo->prepare("DELETE FROM medical_documents WHERE id = :id AND doctor = :doctor");
            $stmt->execute([':id' => $doc_id, ':doctor' => $doctor]);
            redirectWithMessage('documents.php', 'success', 'Xóa tài liệu thành công!');
        } else {
            redirectWithMessage('documents.php', 'error', 'Không tìm thấy tài liệu.');
        }
    } catch (PDOException $e) {
        redirectWithMessage('documents.php', 'error', 'Lỗi khi xóa tài liệu: ' . $e->getMessage());
    }
}

// Tìm kiếm và phân trang
$search_query = '';
$search_condition = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $search_condition = " AND (CONCAT(p.fname, ' ', p.lname) LIKE :search OR md.document_name LIKE :search)";
}

$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$records_per_page = 10;
$offset = ($page_num - 1) * $records_per_page;

// Đếm tổng số tài liệu
$count_sql = "SELECT COUNT(*) FROM medical_documents md 
              INNER JOIN patreg p ON md.pid = p.pid 
              WHERE md.doctor = :doctor $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':doctor', $doctor);
if ($search_query) {
    $count_stmt->bindValue(':search', "%$search_query%");
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách tài liệu
$sql = "SELECT md.*, 
               p.fname, p.lname, p.contact
        FROM medical_documents md
        INNER JOIN patreg p ON md.pid = p.pid
        WHERE md.doctor = :doctor $search_condition
        ORDER BY md.uploaded_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':doctor', $doctor);
if ($search_query) {
    $stmt->bindValue(':search', "%$search_query%");
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bệnh nhân
$patients_stmt = $pdo->prepare("SELECT DISTINCT p.pid, p.fname, p.lname, p.contact 
                                 FROM patreg p 
                                 INNER JOIN appointmenttb a ON p.pid = a.pid 
                                 WHERE TRIM(a.doctor) = TRIM(:doctor)
                                 ORDER BY p.fname, p.lname");
$patients_stmt->execute([':doctor' => $doctor_fullname]);
$patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Tài liệu - Bệnh viện Global</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #d2302c 0%, #ff4d4d 50%, #ff6b6b 100%);
            padding: 30px;
            border-radius: 16px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(210, 48, 44, 0.15);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .upload-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
        }

        .documents-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
        }

        .doc-item {
            border: 1px solid #e0f2fe;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            background: #fafafa;
        }

        .doc-item:hover {
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.1);
            transform: translateY(-2px);
        }

        .file-icon {
            font-size: 36px;
            margin-right: 20px;
        }

        .btn-medical {
            background: linear-gradient(135deg, #d2302c, #ff4d4d);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-medical:hover {
            background: linear-gradient(135deg, #8b0000, #d2302c);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(210, 48, 44, 0.3);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #d2302c;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #8b0000;
            text-decoration: none;
            transform: translateX(-5px);
        }
    </style>
</head>

<body>
    <?php include('../../includes/navbar.php'); ?>

    <div class="page-container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại bảng điều khiển
        </a>

        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-file-upload mr-3"></i>Upload Tài liệu Y tế</h1>
            <p class="mb-0 mt-2">Quản lý tài liệu và hồ sơ bệnh nhân</p>
        </div>

        <?php displayMessage(); ?>

        <!-- Upload Form -->
        <div class="upload-card">
            <h5 class="mb-4"><i class="fas fa-cloud-upload-alt mr-2" style="color: #d2302c;"></i>Upload Tài liệu Mới</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user-injured mr-1"></i>Chọn bệnh nhân <span class="text-danger">*</span></label>
                            <select name="pid" class="form-control" required>
                                <option value="">-- Chọn bệnh nhân --</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['pid']; ?>">
                                        <?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname'] . ' - ' . $patient['contact']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-file-alt mr-1"></i>Chọn file <span class="text-danger">*</span></label>
                            <input type="file" name="document_file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                            <small class="form-text text-muted">Chấp nhận: PDF, JPG, PNG, GIF, DOC, DOCX (Tối đa 10MB)</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-1"></i>Mô tả</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả về tài liệu..."></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" name="upload_document" class="btn btn-medical">
                    <i class="fas fa-upload mr-2"></i>Upload Tài liệu
                </button>
            </form>
        </div>

        <!-- Documents List -->
        <div class="documents-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="fas fa-folder-open mr-2" style="color: #d2302c;"></i>Danh sách Tài liệu</h5>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                    <button type="submit" class="btn btn-medical btn-sm"><i class="fas fa-search"></i></button>
                    <?php if ($search_query): ?>
                        <a href="documents.php" class="btn btn-secondary btn-sm ml-2"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($documents)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php echo $search_query ? 'Không tìm thấy tài liệu nào.' : 'Chưa có tài liệu nào được upload.'; ?>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <?php
                    $ext = strtolower(pathinfo($doc['document_name'], PATHINFO_EXTENSION));
                    if ($ext == 'pdf') {
                        $icon = '<i class="fas fa-file-pdf file-icon" style="color: #ef4444;"></i>';
                    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $icon = '<i class="fas fa-file-image file-icon" style="color: #ffd700;"></i>';
                    } elseif (in_array($ext, ['doc', 'docx'])) {
                        $icon = '<i class="fas fa-file-word file-icon" style="color: #d2302c;"></i>';
                    } else {
                        $icon = '<i class="fas fa-file file-icon" style="color: #6b7280;"></i>';
                    }
                    ?>
                    <div class="doc-item">
                        <div class="d-flex align-items-center">
                            <?php echo $icon; ?>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><strong><?php echo htmlspecialchars($doc['document_name']); ?></strong></h6>
                                <p class="mb-1">
                                    <i class="fas fa-user text-primary mr-1"></i>
                                    <strong>Bệnh nhân:</strong> <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-phone text-success mr-1"></i><?php echo htmlspecialchars($doc['contact']); ?>
                                </p>
                                <?php if ($doc['description']): ?>
                                    <p class="mb-1 text-muted"><i class="fas fa-info-circle mr-1"></i><?php echo htmlspecialchars($doc['description']); ?></p>
                                <?php endif; ?>
                                <p class="mb-0 text-muted">
                                    <small>
                                        <i class="fas fa-clock mr-1"></i><?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-hdd mr-1"></i><?php echo number_format($doc['file_size'] / 1024, 1); ?> KB
                                    </small>
                                </p>
                            </div>
                            <div>
                                <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-info mr-2">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" download class="btn btn-sm btn-success mr-2">
                                    <i class="fas fa-download"></i> Tải
                                </a>
                                <a href="?delete_id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa tài liệu này?');">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page_num > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page_num=<?php echo ($page_num - 1); ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Trước
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page_num) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page_num=<?php echo $i; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page_num < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page_num=<?php echo ($page_num + 1); ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                        Sau <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>