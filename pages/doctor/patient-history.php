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

// Xem chi tiết bệnh án
$view_record_id = isset($_GET['view']) ? intval($_GET['view']) : 0;
$record_detail = null;

if ($view_record_id > 0) {
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               p.fname, p.lname, p.contact, p.email, p.gender, p.date_of_birth, p.blood_group,
               d.fullname as doctor_name
        FROM medical_records mr
        INNER JOIN patreg p ON mr.patient_id = p.pid
        LEFT JOIN doctb d ON mr.doctor_id = d.id
        WHERE mr.id = :id AND mr.doctor_id = :doctor_id
    ");
    $stmt->execute([':id' => $view_record_id, ':doctor_id' => $doctor_id]);
    $record_detail = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tìm kiếm và phân trang
$search_query = '';
$search_condition = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $search_condition = " AND (p.fname LIKE :search OR p.lname LIKE :search OR mr.diagnosis LIKE :search)";
}

$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$records_per_page = 10;
$offset = ($page_num - 1) * $records_per_page;

// Đếm tổng số bệnh án
$count_sql = "SELECT COUNT(*) FROM medical_records mr 
              INNER JOIN patreg p ON mr.patient_id = p.pid 
              WHERE mr.doctor_id = :doctor_id $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
if ($search_query) {
    $count_stmt->bindValue(':search', "%$search_query%");
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách bệnh án
$sql = "SELECT mr.*, 
               p.fname, p.lname, p.contact, p.gender, p.date_of_birth
        FROM medical_records mr
        INNER JOIN patreg p ON mr.patient_id = p.pid
        WHERE mr.doctor_id = :doctor_id $search_condition
        ORDER BY mr.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
if ($search_query) {
    $stmt->bindValue(':search', "%$search_query%");
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tuổi
function calculateAge($dob)
{
    if (!$dob) return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age . ' tuổi';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Bệnh án - Bệnh viện Global</title>
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
            background: linear-gradient(135deg, #d2302c 0%, #ff4d4d 100%);
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

        .records-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
        }

        .record-item {
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            background: #fafafa;
        }

        .record-item:hover {
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1);
            transform: translateY(-2px);
        }

        .vital-signs {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .vital-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .btn-medical {
            background: linear-gradient(135deg, #d2302c, #ff4d4d);
            color: white;
            border: none;
            padding: 8px 20px;
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

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }

        .modal-overlay.active {
            display: block;
        }

        .modal-content-detail {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1001;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .patient-info-box {
            background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
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
            <h1><i class="fas fa-notes-medical mr-3"></i>Lịch sử Bệnh án</h1>
            <p class="mb-0 mt-2">Quản lý và theo dõi hồ sơ bệnh án của bệnh nhân</p>
        </div>

        <?php displayMessage(); ?>

        <!-- Records List -->
        <div class="records-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="fas fa-folder-open mr-2" style="color: #d2302c;"></i>Danh sách Bệnh án</h5>
                <div class="d-flex">
                    <form method="GET" class="form-inline mr-3">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Tìm theo tên hoặc chẩn đoán..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 280px;">
                        <button type="submit" class="btn btn-medical btn-sm"><i class="fas fa-search"></i></button>
                        <?php if ($search_query): ?>
                            <a href="patient-history.php" class="btn btn-secondary btn-sm ml-2"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                    <a href="medical-records.php?page=add" class="btn btn-success">
                        <i class="fas fa-plus mr-2"></i>Thêm bệnh án mới
                    </a>
                </div>
            </div>

            <?php if (empty($medical_records)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php echo $search_query ? 'Không tìm thấy bệnh án nào.' : 'Chưa có bệnh án nào.'; ?>
                </div>
            <?php else: ?>
                <?php foreach ($medical_records as $record): ?>
                    <div class="record-item">
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="mb-2">
                                    <i class="fas fa-user-injured text-primary mr-1"></i>
                                    <strong><?php echo htmlspecialchars($record['fname'] . ' ' . $record['lname']); ?></strong>
                                </h6>
                                <p class="mb-1 text-muted">
                                    <small>
                                        <i class="fas fa-<?php echo ($record['gender'] == 'Nam') ? 'mars text-info' : 'venus text-danger'; ?> mr-1"></i>
                                        <?php echo $record['gender']; ?> - <?php echo calculateAge($record['date_of_birth']); ?>
                                    </small>
                                </p>
                                <p class="mb-0 text-muted">
                                    <small>
                                        <i class="fas fa-phone text-success mr-1"></i>
                                        <?php echo htmlspecialchars($record['contact']); ?>
                                    </small>
                                </p>
                            </div>
                            <div class="col-md-5">
                                <div class="vital-signs">
                                    <?php if ($record['blood_pressure']): ?>
                                        <span class="vital-item">
                                            <i class="fas fa-heartbeat text-danger mr-1"></i>
                                            <strong>HA:</strong> <?php echo $record['blood_pressure']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($record['heart_rate']): ?>
                                        <span class="vital-item">
                                            <i class="fas fa-heart text-danger mr-1"></i>
                                            <strong>NT:</strong> <?php echo $record['heart_rate']; ?> bpm
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($record['temperature']): ?>
                                        <span class="vital-item">
                                            <i class="fas fa-thermometer-half text-warning mr-1"></i>
                                            <strong>T°:</strong> <?php echo $record['temperature']; ?>°C
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-1"><strong>Triệu chứng:</strong> <?php echo htmlspecialchars(substr($record['symptoms'], 0, 80)); ?>...</p>
                                <p class="mb-0">
                                    <strong>Chẩn đoán:</strong>
                                    <span class="badge badge-warning"><?php echo htmlspecialchars($record['diagnosis']); ?></span>
                                </p>
                            </div>
                            <div class="col-md-3 text-right">
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($record['record_date'])); ?>
                                    </small>
                                </p>
                                <p class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('H:i', strtotime($record['created_at'])); ?>
                                    </small>
                                </p>
                                <a href="?view=<?php echo $record['id']; ?>" class="btn btn-medical btn-sm">
                                    <i class="fas fa-eye mr-1"></i>Xem chi tiết
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

    <!-- Modal chi tiết -->
    <?php if ($record_detail): ?>
        <div class="modal-overlay active" onclick="window.location.href='patient-history.php'"></div>
        <div class="modal-content-detail">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-file-medical text-primary mr-2"></i>Chi tiết Bệnh án</h4>
                <a href="patient-history.php" class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i>Đóng
                </a>
            </div>

            <div class="patient-info-box">
                <h5 class="mb-3">Thông tin Bệnh nhân</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($record_detail['fname'] . ' ' . $record_detail['lname']); ?></p>
                        <p><strong>Giới tính:</strong> <?php echo $record_detail['gender']; ?></p>
                        <p><strong>Tuổi:</strong> <?php echo calculateAge($record_detail['date_of_birth']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($record_detail['contact']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($record_detail['email']); ?></p>
                        <p><strong>Nhóm máu:</strong> <?php echo $record_detail['blood_group'] ?: 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <div class="vital-signs">
                <h6 class="mb-3">Chỉ số Sức khỏe</h6>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Chiều cao:</strong> <?php echo $record_detail['height'] ? $record_detail['height'] . ' cm' : 'N/A'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Cân nặng:</strong> <?php echo $record_detail['weight'] ? $record_detail['weight'] . ' kg' : 'N/A'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Huyết áp:</strong> <?php echo $record_detail['blood_pressure'] ?: 'N/A'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Nhịp tim:</strong> <?php echo $record_detail['heart_rate'] ? $record_detail['heart_rate'] . ' bpm' : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <hr>
            <h6>Thông tin Khám bệnh</h6>
            <p><strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($record_detail['record_date'])); ?></p>
            <p><strong>Bác sĩ khám:</strong> <?php echo htmlspecialchars($record_detail['doctor_name']); ?></p>

            <div class="mt-3">
                <h6 class="text-primary">Triệu chứng</h6>
                <p><?php echo nl2br(htmlspecialchars($record_detail['symptoms'])); ?></p>
            </div>

            <div class="mt-3">
                <h6 class="text-danger">Chẩn đoán</h6>
                <p class="font-weight-bold"><?php echo nl2br(htmlspecialchars($record_detail['diagnosis'])); ?></p>
            </div>

            <div class="mt-3">
                <h6 class="text-success">Phương án Điều trị</h6>
                <p><?php echo nl2br(htmlspecialchars($record_detail['treatment_plan'])); ?></p>
            </div>

            <?php if ($record_detail['notes']): ?>
                <div class="mt-3">
                    <h6 class="text-info">Ghi chú</h6>
                    <p><?php echo nl2br(htmlspecialchars($record_detail['notes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>