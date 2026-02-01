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

// Xử lý tìm kiếm
$search_query = '';
$search_condition = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $search_condition = " AND (p.fname LIKE :search OR p.lname LIKE :search OR pr.disease LIKE :search)";
}

// Pagination
$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$records_per_page = 10;
$offset = ($page_num - 1) * $records_per_page;

// Đếm tổng số đơn thuốc
$count_sql = "SELECT COUNT(*) FROM prestb pr 
              INNER JOIN patreg p ON pr.pid = p.pid 
              WHERE pr.doctor = :doctor $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':doctor', $doctor_fullname);
if ($search_query) {
    $count_stmt->bindValue(':search', "%$search_query%");
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách đơn thuốc
$sql = "SELECT pr.*, p.fname, p.lname, p.contact, p.email
        FROM prestb pr
        INNER JOIN patreg p ON pr.pid = p.pid
        WHERE pr.doctor = :doctor $search_condition
        ORDER BY pr.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':doctor', $doctor_fullname);
if ($search_query) {
    $stmt->bindValue(':search', "%$search_query%");
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn thuốc - Bệnh viện Global</title>
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
            background: linear-gradient(135deg, #f43f5e 0%, #fb923c 100%);
            padding: 30px;
            border-radius: 16px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(244, 63, 94, 0.15);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .prescriptions-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
        }

        .prescription-item {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            background: #fafafa;
        }

        .prescription-item:hover {
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.1);
            transform: translateY(-2px);
        }

        .patient-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .prescription-detail {
            background: #fff7ed;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #fb923c;
        }

        .btn-medical {
            background: linear-gradient(135deg, #f43f5e, #fb923c);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-medical:hover {
            background: linear-gradient(135deg, #e11d48, #f43f5e);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #f43f5e;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #e11d48;
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
            <h1><i class="fas fa-file-prescription mr-3"></i>Quản lý Đơn thuốc</h1>
            <p class="mb-0 mt-2">Theo dõi và quản lý đơn thuốc của bệnh nhân</p>
        </div>

        <?php displayMessage(); ?>

        <!-- Prescriptions List -->
        <div class="prescriptions-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="fas fa-prescription mr-2" style="color: #f43f5e;"></i>Danh sách Đơn thuốc</h5>
                <div class="d-flex">
                    <form method="GET" class="form-inline mr-3">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Tìm theo tên bệnh nhân hoặc bệnh..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 280px;">
                        <button type="submit" class="btn btn-medical btn-sm"><i class="fas fa-search"></i></button>
                        <?php if ($search_query): ?>
                            <a href="prescriptions.php" class="btn btn-secondary btn-sm ml-2"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="prescribe.php" class="btn btn-success">
                        <i class="fas fa-plus mr-2"></i>Kê đơn mới
                    </a>
                </div>
            </div>

            <?php if (empty($prescriptions)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php echo $search_query ? 'Không tìm thấy đơn thuốc nào.' : 'Chưa có đơn thuốc nào được kê.'; ?>
                </div>
            <?php else: ?>
                <?php foreach ($prescriptions as $pres): ?>
                    <div class="prescription-item">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="patient-info">
                                    <h6 class="mb-2">
                                        <i class="fas fa-user-injured mr-1" style="color: #f43f5e;"></i>
                                        <strong><?php echo htmlspecialchars($pres['fname'] . ' ' . $pres['lname']); ?></strong>
                                    </h6>
                                    <p class="mb-1">
                                        <small>
                                            <i class="fas fa-phone text-success mr-1"></i>
                                            <?php echo htmlspecialchars($pres['contact']); ?>
                                        </small>
                                    </p>
                                    <p class="mb-0">
                                        <small>
                                            <i class="fas fa-envelope text-info mr-1"></i>
                                            <?php echo htmlspecialchars($pres['email']); ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="prescription-detail">
                                    <p class="mb-2">
                                        <i class="fas fa-diagnoses mr-1" style="color: #dc2626;"></i>
                                        <strong>Bệnh:</strong>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars($pres['disease']); ?></span>
                                    </p>
                                    <?php if ($pres['allergy']): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-allergies mr-1" style="color: #ea580c;"></i>
                                            <strong>Dị ứng:</strong> <?php echo htmlspecialchars($pres['allergy']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="mb-2">
                                        <i class="fas fa-pills mr-1" style="color: #d2302c;"></i>
                                        <strong>Thuốc:</strong> <?php echo htmlspecialchars(substr($pres['prescription'], 0, 60)); ?>...
                                    </p>
                                    <?php if ($pres['treatment_duration']): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-check mr-1" style="color: #ffd700;"></i>
                                            <strong>Thời gian điều trị:</strong> <?php echo htmlspecialchars($pres['treatment_duration']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-right">
                                <p class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($pres['created_at'])); ?>
                                    </small>
                                </p>
                                <p class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('H:i', strtotime($pres['created_at'])); ?>
                                    </small>
                                </p>
                                <a href="view_prescription.php?id=<?php echo $pres['ID']; ?>" class="btn btn-info btn-sm mb-2 d-block">
                                    <i class="fas fa-eye mr-1"></i>Xem chi tiết
                                </a>
                                <a href="export_prescription_pdf.php?id=<?php echo $pres['ID']; ?>" class="btn btn-medical btn-sm d-block">
                                    <i class="fas fa-file-pdf mr-1"></i>Xuất PDF
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