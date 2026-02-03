<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'doctors', 'patients', 'appointments', 'prescriptions', 'queries', 'medical-records', 'manage_schedule');
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Thống kê cho dashboard
if ($page === 'dashboard') {
    // Lấy dữ liệu bệnh nhân mới 12 tháng gần nhất (đếm từ lịch hẹn đầu tiên của mỗi bệnh nhân)
    $patientsMonthlyQuery = "SELECT 
        DATE_FORMAT(first_appointment, '%Y-%m') as month,
        COUNT(*) as count
        FROM (
            SELECT pid, MIN(created_at) as first_appointment
            FROM appointmenttb
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY pid
        ) as new_patients
        GROUP BY DATE_FORMAT(first_appointment, '%Y-%m')
        ORDER BY month ASC";
    $patientsMonthlyStmt = $pdo->query($patientsMonthlyQuery);
    $patientsMonthlyData = $patientsMonthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy dữ liệu doanh thu 12 tháng gần nhất
    $revenueMonthlyQuery = "SELECT 
        DATE_FORMAT(a.appdate, '%Y-%m') as month,
        SUM(CAST(d.docFees AS DECIMAL(10,2))) as revenue
        FROM appointmenttb a
        INNER JOIN doctb d ON a.doctor = d.id
        WHERE a.appdate >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND a.userStatus = 1 AND a.doctorStatus = 1
        GROUP BY DATE_FORMAT(a.appdate, '%Y-%m')
        ORDER BY month ASC";
    $revenueMonthlyStmt = $pdo->query($revenueMonthlyQuery);
    $revenueMonthlyData = $revenueMonthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Tỷ lệ lịch khám thành công/hủy
    $appointmentStatsQuery = "SELECT 
        SUM(CASE WHEN userStatus = 1 AND doctorStatus = 1 THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN userStatus = 0 OR doctorStatus = 0 THEN 1 ELSE 0 END) as cancelled,
        COUNT(*) as total
        FROM appointmenttb";
    $appointmentStatsStmt = $pdo->query($appointmentStatsQuery);
    $appointmentStats = $appointmentStatsStmt->fetch(PDO::FETCH_ASSOC);

    // Top 5 bác sĩ có nhiều lịch khám nhất
    $topDoctorsQuery = "SELECT 
        d.fullname,
        COALESCE(s.name_vi, d.spec) as spec,
        COUNT(a.ID) as appointment_count,
        SUM(CASE WHEN a.userStatus = 1 AND a.doctorStatus = 1 THEN 1 ELSE 0 END) as success_count
        FROM doctb d
        LEFT JOIN appointmenttb a ON d.id = a.doctor
        LEFT JOIN specializations s ON d.spec_id = s.id
        GROUP BY d.id, d.fullname, d.spec, s.name_vi
        HAVING appointment_count > 0
        ORDER BY appointment_count DESC
        LIMIT 5";
    $topDoctorsStmt = $pdo->query($topDoctorsQuery);
    $topDoctors = $topDoctorsStmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- LOGIC CŨ: THÊM BÁC SĨ ---
if (isset($_POST['docsub'])) {
    try {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $dpassword = password_hash($_POST['dpassword'], PASSWORD_DEFAULT);
        $demail = $_POST['demail'];
        $spec_id = $_POST['special_id'];
        $docFees = $_POST['docFees'];

        // Get Spec Name for legacy compatibility
        $stmt_spec = $pdo->prepare("SELECT name FROM specializations WHERE id = ?");
        $stmt_spec->execute([$spec_id]);
        $spec_row = $stmt_spec->fetch();
        $spec_name = $spec_row ? $spec_row['name'] : '';

        $check = $pdo->prepare("SELECT * FROM doctb WHERE username = ? OR email = ?");
        $check->execute([$username, $demail]);
        if ($check->rowCount() > 0) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=doctors', 'error', 'Username or Email already exists!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO doctb(username, fullname, password, email, spec, spec_id, docFees) VALUES(:username, :fullname, :dpassword, :demail, :spec, :spec_id, :docFees)");
            $stmt->execute([
                ':username' => $username,
                ':fullname' => $fullname,
                ':dpassword' => $dpassword,
                ':demail' => $demail,
                ':spec' => $spec_name,
                ':spec_id' => $spec_id,
                ':docFees' => $docFees
            ]);
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=doctors', 'success', 'Doctor added successfully!');
        }
    } catch (PDOException $e) {
        error_log("Add doctor error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=doctors', 'error', 'Error adding doctor: ' . $e->getMessage());
    }
}

// --- LOGIC CŨ: XÓA BÁC SĨ ---
if (isset($_POST['docsub1'])) {
    try {
        $demail = $_POST['demail'];
        $stmt = $pdo->prepare("DELETE FROM doctb WHERE email = :demail");
        $stmt->execute([':demail' => $demail]);
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=doctors', 'success', 'Doctor removed successfully!');
    } catch (PDOException $e) {
        error_log("Delete doctor error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=doctors', 'error', 'Error removing doctor!');
    }
}

// --- LOGIC MỚI: QUẢN LÝ LỊCH ---
if (isset($_POST['assign_schedule'])) {
    $doctor_id = $_POST['doctor_id'];
    $days = isset($_POST['day_of_week']) ? $_POST['day_of_week'] : [];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    if (empty($days)) {
        echo "<script>alert('Vui lòng chọn ít nhất một ngày!');</script>";
    } else {
        $successCount = 0;
        foreach ($days as $day_num) {
            // Kiểm tra trùng
            $check = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id=? AND day_of_week=? AND start_time=?");
            $check->execute([$doctor_id, $day_num, $start]);

            if ($check->rowCount() == 0) {
                $sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$doctor_id, $day_num, $start, $end])) {
                    $successCount++;
                }
            }
        }
        if ($successCount > 0) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=manage_schedule', 'success', "Đã thêm lịch cho $successCount ngày.");
        } else {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=manage_schedule', 'warning', 'Không có lịch nào được thêm (có thể do trùng).');
        }
    }
}

if (isset($_GET['reset_schedule'])) {
    $doc_id = $_GET['reset_schedule'];
    $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$doc_id]);
    redirectWithMessage($_SERVER['PHP_SELF'] . '?page=manage_schedule', 'success', 'Đã xóa toàn bộ lịch của bác sĩ này.');
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Bảng điều khiển Quản trị - Bệnh viện Global</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        body {
            background-image:
                linear-gradient(135deg, rgba(254, 243, 199, 0.85) 0%, rgba(254, 215, 170, 0.85) 25%, rgba(253, 186, 116, 0.85) 50%, rgba(251, 146, 60, 0.85) 75%, rgba(249, 115, 22, 0.85) 100%),
                url('../../images/ngua.png');
            background-size: cover, contain;
            background-position: center, center;
            background-repeat: no-repeat, no-repeat;
            background-attachment: fixed, fixed;
            font-family: 'Inter', sans-serif;
        }

        .navbar-user.dropdown .dropdown-toggle::after {
            display: none;
        }

        .navbar-user .dropdown-menu {
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: none;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .navbar-user .dropdown-item {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .navbar-user .dropdown-item i {
            width: 20px;
            font-size: 0.9rem;
        }

        .navbar-user .dropdown-item:hover {
            background: #fff5f5;
            color: #d2302c;
            padding-left: 1.75rem;
        }

        .navbar-user .dropdown-item.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .navbar-user .dropdown-divider {
            margin: 0.5rem 0;
        }

        .navbar-user-info {
            margin-left: 1rem;
        }

        /* CSS cho nút chọn ngày (Week Selector) */
        .weekDays-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .weekDays-selector input[type=checkbox] {
            display: none !important;
        }

        .weekDays-selector input[type=checkbox]+label {
            display: inline-block;
            border-radius: 20px;
            background: #f1f5f9;
            color: #64748b;
            padding: 8px 15px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            user-select: none;
            margin-bottom: 0;
        }

        .weekDays-selector input[type=checkbox]:hover+label {
            background: #e2e8f0;
        }

        .weekDays-selector input[type=checkbox]:checked+label {
            background: #007bff;
            color: #ffffff;
            border-color: #007bff;
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.3);
            transform: translateY(-1px);
        }

        /* CSS cho bảng lịch (Matrix) */
        .table-schedule th,
        .table-schedule td {
            text-align: center;
            vertical-align: middle !important;
        }

        .check-icon {
            color: #d2302c;
            font-size: 1.2rem;
            cursor: help;
        }

        .cross-icon {
            color: #e5e7eb;
            font-size: 1.0rem;
        }

        .saturday-alert {
            border-left: 5px solid #f59e0b;
            background-color: #fffbeb;
            color: #92400e;
        }

        .search-card {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }

        .custom-search-input {
            border-radius: 50px;
            padding-left: 40px;
            border: 1px solid #d1d5db;
            transition: all 0.3s;
        }

        .custom-search-input:focus {
            box-shadow: 0 0 0 3px rgba(210, 48, 44, 0.2);
            border-color: #d2302c;
        }

        .search-icon-overlay {
            position: absolute;
            left: 6px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 10;
        }

        .custom-select-filter {
            border-radius: 50px;
            border: 1px solid #d1d5db;
            cursor: pointer;
        }

        /* ===============================
   FIX MODAL MEDICAL RECORDS
   =============================== */

        body.page-medical-records.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }

        /* ===============================
   MODAL SIDEBAR RIGHT - CENTERED
   =============================== */

        .page-medical-records .modal.modal-right .modal-dialog {
            position: fixed;
            left: 50%;
            top: 50%;
            height: auto;
            max-height: 90vh;
            width: 800px;
            max-width: 90%;
            margin: 0;
            transform: translate(-50%, -50%);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        .page-medical-records .modal.modal-right.show .modal-dialog {
            transform: translate(-50%, -50%);
            opacity: 1;
        }

        .page-medical-records .modal.modal-right .modal-content {
            height: auto;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 8px;
            border: none;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        }

        /* ===============================
   CLOSE BUTTON
   =============================== */

        .page-medical-records .modal-header .close {
            margin-left: auto;
            padding: 1.25rem;
            opacity: 0.7;
        }

        .page-medical-records .modal-header .close:hover {
            opacity: 1;
        }

        sidebar {
            position: relative;
            z-index: 1100 !important;
        }

        /* main content thấp hơn sidebar */
        .main-content {
            position: relative;
            z-index: 1000;
        }

        /* ===============================
   FIX: KHÔNG BỊ BACKDROP ĐÈ LÊN MODAL (KHÔNG CLICK ĐƯỢC)
   =============================== */

        /* Backdrop PHẢI nằm dưới modal và KHÔNG chặn click */
        .page-medical-records .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 1040 !important;
            background-color: transparent !important;
            pointer-events: none !important;
        }

        /* Modal luôn nằm trên backdrop và CHO PHÉP click */
        .page-medical-records .modal {
            position: fixed !important;
            z-index: 1050 !important;
            pointer-events: auto !important;
        }

        .page-medical-records .modal-dialog {
            position: relative !important;
            z-index: 1051 !important;
            pointer-events: auto !important;
        }

        .page-medical-records .modal-content {
            position: relative !important;
            z-index: 1052 !important;
            pointer-events: auto !important;
        }

        /* Nếu có backdrop bị nhân đôi -> ẩn cái thứ 2 trở đi */
        .page-medical-records .modal-backdrop+.modal-backdrop {
            display: none !important;
        }

        /* Hiệu ứng hoa đào rơi */
        .petals-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 9999;
        }

        .petal {
            position: absolute;
            top: -10px;
            width: 15px;
            height: 15px;
            background: radial-gradient(ellipse at center, #ffb7d5 0%, #ff69b4 40%, #ff1493 100%);
            border-radius: 50% 0 50% 0;
            opacity: 0.8;
            animation: fall linear infinite;
            transform-origin: center;
        }

        .petal::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.5) 0%, transparent 50%);
            border-radius: 50% 0 50% 0;
            transform: rotate(90deg);
        }

        @keyframes fall {
            0% {
                transform: translateY(0) rotateZ(0deg) rotateY(0deg);
                opacity: 0.8;
            }

            50% {
                transform: translateY(50vh) rotateZ(180deg) rotateY(180deg);
                opacity: 0.6;
            }

            100% {
                transform: translateY(100vh) rotateZ(360deg) rotateY(360deg);
                opacity: 0;
            }
        }

        .petal:nth-child(odd) {
            animation-duration: 8s;
        }

        .petal:nth-child(even) {
            animation-duration: 12s;
        }

        .petal:nth-child(3n) {
            animation-duration: 10s;
            width: 12px;
            height: 12px;
        }

        .petal:nth-child(5n) {
            animation-duration: 15s;
            width: 18px;
            height: 18px;
            opacity: 0.6;
        }
    </style>
</head>

<body class="page-medical-records">
    <!-- Container cho hoa đào rơi -->
    <div class="petals-container" id="petals"></div>
    <script>
        function createPetals() {
            const petalsContainer = document.getElementById('petals');
            const numberOfPetals = 25;
            for (let i = 0; i < numberOfPetals; i++) {
                const petal = document.createElement('div');
                petal.className = 'petal';
                petal.style.left = Math.random() * 100 + '%';
                petal.style.animationDelay = Math.random() * 10 + 's';
                petal.style.animationDuration = (8 + Math.random() * 10) + 's';
                petalsContainer.appendChild(petal);
            }
        }
        window.addEventListener('load', createPetals);
    </script>
    <?php displayMessage(); ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-user-shield"></i></div>
                <div>
                    <h1 class="sidebar-title">Bệnh viện Global</h1>
                    <div class="sidebar-subtitle">Cổng Quản trị</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="?page=dashboard" class="sidebar-menu-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large sidebar-menu-icon"></i>
                        <span>Bảng điều khiển</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=doctors" class="sidebar-menu-link <?php echo ($page === 'doctors') ? 'active' : ''; ?>">
                        <i class="fas fa-user-md sidebar-menu-icon"></i>
                        <span>Danh sách bác sĩ</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=manage_schedule" class="sidebar-menu-link <?php echo ($page === 'manage_schedule') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-week sidebar-menu-icon"></i>
                        <span>Sắp xếp lịch bác sĩ</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=patients" class="sidebar-menu-link <?php echo ($page === 'patients') ? 'active' : ''; ?>">
                        <i class="fas fa-users sidebar-menu-icon"></i>
                        <span>Danh sách bệnh nhân</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=appointments" class="sidebar-menu-link <?php echo ($page === 'appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt sidebar-menu-icon"></i>
                        <span>Lịch hẹn</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=medical-records" class="sidebar-menu-link <?php echo ($page === 'medical-records') ? 'active' : ''; ?>">
                        <i class="fas fa-file-medical sidebar-menu-icon"></i>
                        <span>Hồ sơ bệnh án</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=prescriptions" class="sidebar-menu-link <?php echo ($page === 'prescriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i>
                        <span>Đơn thuốc</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=queries" class="sidebar-menu-link <?php echo ($page === 'queries') ? 'active' : ''; ?>">
                        <i class="fas fa-comments sidebar-menu-icon"></i>
                        <span>Liên hệ</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Bảng điều khiển Quản trị</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="navbarUserDropdown" data-toggle="dropdown">
                            <div class="navbar-user-avatar"><i class="fas fa-user-shield"></i></div>
                            <div class="navbar-user-info">
                                <div class="navbar-user-name">Quản trị viên</div>
                                <div class="navbar-user-role">Lễ tân</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="../../index.php"><i class="fas fa-home mr-2"></i> Quay về trang chủ</a>
                        </div>
                    </div>
                </div>
            </nav>

            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Chào mừng, Quản trị viên!</h2>
                        <p class="section-subtitle">Quản lý bác sĩ, bệnh nhân và lịch hẹn</p>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-user-md"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số bác sĩ</div>
                                <div class="stat-value"><?php $query = $pdo->query("select count(*) as total from doctb");
                                                        echo $query->fetch(PDO::FETCH_ASSOC)['total']; ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-users"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số bệnh nhân</div>
                                <div class="stat-value"><?php $query = $pdo->query("select count(*) as total from patreg");
                                                        echo $query->fetch(PDO::FETCH_ASSOC)['total']; ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số lịch hẹn</div>
                                <div class="stat-value"><?php $query = $pdo->query("select count(*) as total from appointmenttb");
                                                        echo $query->fetch(PDO::FETCH_ASSOC)['total']; ?></div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-file-medical"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số đơn thuốc</div>
                                <div class="stat-value"><?php $query = $pdo->query("select count(*) as total from prestb");
                                                        echo $query->fetch(PDO::FETCH_ASSOC)['total']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ thống kê -->
                    <div class="row mt-4">
                        <!-- Biểu đồ bệnh nhân mới theo tháng -->
                        <div class="col-lg-6 mb-4">
                            <div class="data-table-container">
                                <div class="data-table-header">
                                    <h3 class="data-table-title"><i class="fas fa-user-plus"></i> Bệnh nhân mới theo tháng</h3>
                                </div>
                                <div class="p-4">
                                    <canvas id="patientsChart" style="max-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ doanh thu -->
                        <div class="col-lg-6 mb-4">
                            <div class="data-table-container">
                                <div class="data-table-header">
                                    <h3 class="data-table-title"><i class="fas fa-money-bill-wave"></i> Doanh thu theo tháng</h3>
                                </div>
                                <div class="p-4">
                                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Tỷ lệ lịch khám -->
                        <div class="col-lg-6 mb-4">
                            <div class="data-table-container">
                                <div class="data-table-header">
                                    <h3 class="data-table-title"><i class="fas fa-chart-pie"></i> Tỷ lệ lịch khám</h3>
                                </div>
                                <div class="p-4">
                                    <div class="row text-center mb-3">
                                        <div class="col-md-4">
                                            <div class="stat-mini success">
                                                <div class="stat-mini-icon"><i class="fas fa-check-circle"></i></div>
                                                <div class="stat-mini-value"><?php echo number_format($appointmentStats['success']); ?></div>
                                                <div class="stat-mini-label">Thành công</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stat-mini danger">
                                                <div class="stat-mini-icon"><i class="fas fa-times-circle"></i></div>
                                                <div class="stat-mini-value"><?php echo number_format($appointmentStats['cancelled']); ?></div>
                                                <div class="stat-mini-label">Đã hủy</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stat-mini info">
                                                <div class="stat-mini-icon"><i class="fas fa-calendar-check"></i></div>
                                                <div class="stat-mini-value"><?php echo number_format($appointmentStats['total']); ?></div>
                                                <div class="stat-mini-label">Tổng số</div>
                                            </div>
                                        </div>
                                    </div>
                                    <canvas id="appointmentChart" style="max-height: 250px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Top bác sĩ -->
                        <div class="col-lg-6 mb-4">
                            <div class="data-table-container">
                                <div class="data-table-header">
                                    <h3 class="data-table-title"><i class="fas fa-trophy"></i> Top bác sĩ có nhiều lịch khám nhất</h3>
                                </div>
                                <div class="p-4">
                                    <div class="top-doctors-list">
                                        <?php
                                        if (empty($topDoctors) || count($topDoctors) == 0):
                                        ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Chưa có dữ liệu lịch khám để xếp hạng bác sĩ</p>
                                            </div>
                                            <?php
                                        else:
                                            $rank = 1;
                                            foreach ($topDoctors as $doctor):
                                                $successRate = $doctor['appointment_count'] > 0 ?
                                                    round(($doctor['success_count'] / $doctor['appointment_count']) * 100, 1) : 0;
                                            ?>
                                                <div class="top-doctor-item rank-<?php echo $rank; ?>">
                                                    <div class="doctor-rank">
                                                        <span class="rank-number"><?php echo $rank; ?></span>
                                                        <?php if ($rank === 1): ?>
                                                            <i class="fas fa-crown rank-icon"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="doctor-info">
                                                        <div class="doctor-name"><?php echo htmlspecialchars($doctor['fullname']); ?></div>
                                                        <div class="doctor-spec"><?php echo htmlspecialchars($doctor['spec']); ?></div>
                                                    </div>
                                                    <div class="doctor-stats">
                                                        <div class="stat-item">
                                                            <i class="fas fa-calendar-check"></i>
                                                            <span><?php echo number_format($doctor['appointment_count']); ?> lịch</span>
                                                        </div>
                                                        <div class="stat-item success-rate">
                                                            <i class="fas fa-check-circle"></i>
                                                            <span><?php echo $successRate; ?>%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                                $rank++;
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <?php if ($page === 'doctors') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-user-md"></i> Quản lý bác sĩ</h2>
                    </div>

                    <div class="data-table-container mb-4">
                        <div class="data-table-header" style="background: linear-gradient(135deg, #d2302c 0%, #8b0000 100%);">
                            <h3 class="data-table-title" style="color: white;"><i class="fas fa-user-plus"></i> Thêm bác sĩ mới</h3>
                        </div>
                        <div class="p-4">
                            <form method="post" action="?page=doctors">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Tên bác sĩ *</label><input type="text" class="form-control" name="doctor" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Chuyên khoa *</label><select name="special" class="form-control" required>
                                                <option value="General">Đa khoa</option>
                                                <option value="Cardiologist">Tim mạch</option>
                                                <option value="Neurologist">Thần kinh</option>
                                                <option value="Pediatrician">Nhi khoa</option>
                                                <option value="Dermatologist">Da liễu</option>
                                                <option value="Orthopedic">Chỉnh hình</option>
                                            </select></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Email *</label><input type="email" class="form-control" name="demail" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Phí khám *</label><input type="number" class="form-control" name="docFees" min="0" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Mật khẩu *</label><input type="password" class="form-control" name="dpassword" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group"><label>Xác nhận mật khẩu *</label><input type="password" class="form-control" name="cdpassword" required></div>
                                    </div>
                                    <div class="col-md-12"><button type="submit" name="docsub" class="btn btn-success btn-lg">Thêm bác sĩ</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="search-card">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="position-relative">
                                    <i class="fas fa-search search-icon-overlay"></i>
                                    <input type="text" id="live_search" class="form-control custom-search-input" placeholder="Nhập tên bác sĩ hoặc email để tìm...">
                                </div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <select id="filter_spec" class="form-control custom-select-filter">
                                    <option value="">-- Tất cả chuyên khoa --</option>
                                    <option value="Pediatrics">Nhi khoa</option>
                                    <option value="Obstetrics_Gynecology">Sản phụ khoa</option>
                                    <option value="Dermatology">Da liễu</option>
                                    <option value="Gastroenterology">Tiêu hóa</option>
                                    <option value="Rheumatology">Cơ xương khớp</option>
                                    <option value="Allergy_Immunology">Dị ứng - Miễn dịch</option>
                                    <option value="Anesthesiology">Gây mê hồi sức</option>
                                    <option value="ENT">Tai - Mũi - Họng</option>
                                    <option value="Oncology">Ung bướu</option>
                                    <option value="Cardiology">Tim mạch</option>
                                    <option value="Geriatrics">Lão khoa</option>
                                    <option value="Orthopedics">Chấn thương chỉnh hình</option>
                                    <option value="Emergency_Medicine">Hồi sức cấp cứu</option>
                                    <option value="General_Surgery">Ngoại tổng quát</option>
                                    <option value="Preventive_Medicine">Y học dự phòng</option>
                                    <option value="Dentistry">Răng - Hàm - Mặt</option>
                                    <option value="Infectious_Disease">Truyền nhiễm</option>
                                    <option value="Nephrology">Nội thận</option>
                                    <option value="Endocrinology">Nội tiết</option>
                                    <option value="Psychiatry">Tâm thần</option>
                                    <option value="Pulmonology">Hô hấp</option>
                                    <option value="Laboratory">Xét nghiệm</option>
                                    <option value="Hematology">Huyết học</option>
                                    <option value="Psychology">Tâm lý</option>
                                    <option value="Neurology">Nội thần kinh</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-right">
                                <span class="text-muted small"><i class="fas fa-spinner fa-spin" style="display:none;" id="loading_spinner"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Danh sách bác sĩ</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tên bác sĩ</th>
                                    <th>Chuyên khoa</th>
                                    <th>Email</th>
                                    <th>Phí khám</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="doctor_table_body">
                                <?php
                                // Load dữ liệu ban đầu
                                $query = "SELECT d.*, s.name_vi FROM doctb d LEFT JOIN specializations s ON d.spec_id = s.id ORDER BY d.fullname ASC";
                                $result = $pdo->query($query);
                                $serial = 1;
                                if ($result->rowCount() > 0) {
                                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
                                        <tr>
                                            <td><?php echo $serial++; ?></td>
                                            <td><strong>BS. <?php echo htmlspecialchars($row['fullname'] ?? $row['username']); ?></strong></td>
                                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['name_vi'] ?? $row['spec']); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>₹<?php echo htmlspecialchars($row['docFees']); ?></td>
                                            <td>
                                                <form method="post" action="?page=doctors" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa?');">
                                                    <input type="hidden" name="demail" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                    <button type="submit" name="docsub1" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // 1. Khai báo các element cần thao tác
                        const searchInput = document.getElementById('live_search');
                        const filterSpec = document.getElementById('filter_spec');
                        const tableBody = document.getElementById('doctor_table_body');
                        const loadingSpinner = document.getElementById('loading_spinner');

                        // 2. Hàm xử lý Fetch dữ liệu
                        function load_data(query = '', spec = '') {
                            // Hiện icon loading
                            if (loadingSpinner) loadingSpinner.style.display = 'inline-block';

                            // Tạo dữ liệu gửi đi (dạng Form Data)
                            const formData = new FormData();
                            formData.append('search', query);
                            formData.append('spec', spec);

                            // Gọi Fetch API
                            fetch('ajax/get_doctors.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.text(); // Vì server trả về HTML table row
                                })
                                .then(data => {
                                    // Cập nhật bảng dữ liệu
                                    tableBody.innerHTML = data;
                                    // Ẩn icon loading
                                    if (loadingSpinner) loadingSpinner.style.display = 'none';
                                })
                                .catch(error => {
                                    console.error('Lỗi Fetch:', error);
                                    if (loadingSpinner) loadingSpinner.style.display = 'none';
                                });
                        }

                        // 3. Bắt sự kiện Gõ phím (Tìm theo tên)
                        if (searchInput) {
                            searchInput.addEventListener('keyup', function() {
                                const query = this.value;
                                const spec = filterSpec ? filterSpec.value : '';
                                load_data(query, spec);
                            });
                        }

                        // 4. Bắt sự kiện Đổi Select (Lọc theo khoa)
                        if (filterSpec) {
                            filterSpec.addEventListener('change', function() {
                                const query = searchInput ? searchInput.value : '';
                                const spec = this.value;
                                load_data(query, spec);
                            });
                        }
                    });
                </script>

            <?php } ?>

            <?php if ($page === 'manage_schedule') {
                $is_saturday = (date('N') == 6);
                $doctors = $pdo->query("SELECT id, fullname FROM doctb ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
                $sql_docs = "SELECT d.id, d.fullname, s.name_vi as spec_name FROM doctb d LEFT JOIN specializations s ON d.spec_id = s.id ORDER BY d.fullname";
                $all_docs = $pdo->query($sql_docs)->fetchAll(PDO::FETCH_ASSOC);
                $sql_sch = "SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_schedules";
                $all_schedules = $pdo->query($sql_sch)->fetchAll(PDO::FETCH_ASSOC);
                $scheduleMap = [];
                foreach ($all_schedules as $sch) {
                    $scheduleMap[$sch['doctor_id']][$sch['day_of_week']] = date('H:i', strtotime($sch['start_time'])) . ' - ' . date('H:i', strtotime($sch['end_time']));
                }
                $daysOfWeek = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 0 => 'CN'];
            ?>

                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Phân công lịch trực</h2>
                    </div>
                    <?php if ($is_saturday): ?>
                        <div class="alert saturday-alert mb-4 shadow-sm animate__animated animate__fadeIn">
                            <div class="d-flex align-items-center"><i class="fas fa-bell fa-2x mr-3 animate__animated animate__swing animate__infinite"></i>
                                <div>
                                    <h5 class="mb-1 font-weight-bold">Nhắc nhở quan trọng!</h5>
                                    <p class="mb-0">Hôm nay là Thứ 7. Vui lòng kiểm tra và đăng ký lịch làm việc cho tuần tới.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>


                    <div class="card shadow-sm mb-5 border-0">
                        <div class="card-header bg-white font-weight-bold py-3"><i class="fas fa-plus-circle text-primary"></i> Thêm lịch mới</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group"><label class="font-weight-bold">Chọn Bác sĩ:</label>
                                            <select name="doctor_id" class="form-control" required>
                                                <option value="">-- Chọn bác sĩ --</option><?php foreach ($doctors as $doc): ?><option value="<?php echo $doc['id']; ?>">BS. <?php echo $doc['fullname']; ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Chọn Thứ (Bấm để chọn nhiều):</label>
                                            <div class="weekDays-selector">
                                                <input type="checkbox" id="weekday-1" name="day_of_week[]" value="1"><label for="weekday-1">Thứ 2</label>
                                                <input type="checkbox" id="weekday-2" name="day_of_week[]" value="2"><label for="weekday-2">Thứ 3</label>
                                                <input type="checkbox" id="weekday-3" name="day_of_week[]" value="3"><label for="weekday-3">Thứ 4</label>
                                                <input type="checkbox" id="weekday-4" name="day_of_week[]" value="4"><label for="weekday-4">Thứ 5</label>
                                                <input type="checkbox" id="weekday-5" name="day_of_week[]" value="5"><label for="weekday-5">Thứ 6</label>
                                                <input type="checkbox" id="weekday-6" name="day_of_week[]" value="6"><label for="weekday-6">Thứ 7</label>
                                                <input type="checkbox" id="weekday-0" name="day_of_week[]" value="0"><label for="weekday-0">CN</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="font-weight-bold">Khung giờ:</label>
                                        <div class="row">
                                            <div class="col-6"><input type="time" name="start_time" class="form-control" required><small>Bắt đầu</small></div>
                                            <div class="col-6"><input type="time" name="end_time" class="form-control" required><small>Kết thúc</small></div>
                                        </div>
                                        <button type="submit" name="assign_schedule" class="btn btn-primary btn-block mt-3"><i class="fas fa-save"></i> Lưu Lịch</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>



                    <?php if ($page === 'manage_schedule') {
                        $is_saturday = (date('N') == 6);
                        // Lấy danh sách cho dropdown thêm lịch
                        $doctors_dropdown = $pdo->query("SELECT id, fullname FROM doctb ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);

                        // --- Xử lý hiển thị ban đầu (PHP thuần) ---
                        $sql_docs = "SELECT d.id, d.fullname, s.name_vi as spec_name FROM doctb d LEFT JOIN specializations s ON d.spec_id = s.id ORDER BY d.fullname";
                        $all_docs = $pdo->query($sql_docs)->fetchAll(PDO::FETCH_ASSOC);

                        $sql_sch = "SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_schedules";
                        $all_schedules = $pdo->query($sql_sch)->fetchAll(PDO::FETCH_ASSOC);

                        $scheduleMap = [];
                        foreach ($all_schedules as $sch) {
                            $scheduleMap[$sch['doctor_id']][$sch['day_of_week']] = date('H:i', strtotime($sch['start_time'])) . ' - ' . date('H:i', strtotime($sch['end_time']));
                        }
                        $daysOfWeek = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 0 => 'CN'];
                    ?>




                        <div class="search-card">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <div class="position-relative">
                                        <i class="fas fa-search search-icon-overlay"></i>
                                        <input type="text" id="matrix_search" class="form-control custom-search-input" placeholder="Nhập tên bác sĩ để lọc bảng...">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <select id="matrix_spec" class="form-control custom-select-filter">
                                        <option value="">-- Tất cả chuyên khoa --</option>
                                        <option value="Pediatrics">Nhi khoa</option>
                                        <option value="Obstetrics_Gynecology">Sản phụ khoa</option>
                                        <option value="Dermatology">Da liễu</option>
                                        <option value="Gastroenterology">Tiêu hóa</option>
                                        <option value="Rheumatology">Cơ xương khớp</option>
                                        <option value="Allergy_Immunology">Dị ứng - Miễn dịch</option>
                                        <option value="Anesthesiology">Gây mê hồi sức</option>
                                        <option value="ENT">Tai - Mũi - Họng</option>
                                        <option value="Oncology">Ung bướu</option>
                                        <option value="Cardiology">Tim mạch</option>
                                        <option value="Geriatrics">Lão khoa</option>
                                        <option value="Orthopedics">Chấn thương chỉnh hình</option>
                                        <option value="Emergency_Medicine">Hồi sức cấp cứu</option>
                                        <option value="General_Surgery">Ngoại tổng quát</option>
                                        <option value="Preventive_Medicine">Y học dự phòng</option>
                                        <option value="Dentistry">Răng - Hàm - Mặt</option>
                                        <option value="Infectious_Disease">Truyền nhiễm</option>
                                        <option value="Nephrology">Nội thận</option>
                                        <option value="Endocrinology">Nội tiết</option>
                                        <option value="Psychiatry">Tâm thần</option>
                                        <option value="Pulmonology">Hô hấp</option>
                                        <option value="Laboratory">Xét nghiệm</option>
                                        <option value="Hematology">Huyết học</option>
                                        <option value="Psychology">Tâm lý</option>
                                        <option value="Neurology">Nội thần kinh</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-right">
                                    <span class="text-muted small"><i class="fas fa-spinner fa-spin" style="display:none;" id="matrix_loading"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="data-table-container shadow-sm bg-white rounded">
                            <div class="data-table-header border-bottom">
                                <h3 class="data-table-title">Bảng phân công nhân sự</h3>
                            </div>
                            <div class="table-responsive p-0">
                                <table class="table table-bordered table-striped table-hover table-schedule mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 20%;">Bác sĩ</th>
                                            <th style="width: 15%;">Chuyên ngành</th>
                                            <?php foreach ($daysOfWeek as $day): ?><th><?php echo $day; ?></th><?php endforeach; ?>
                                            <th style="width: 5%;">Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="matrix_body">
                                        <?php foreach ($all_docs as $doc): $docId = $doc['id']; ?>
                                            <tr>
                                                <td class="doctor-name-col"><?php echo $doc['fullname']; ?></td>
                                                <td class="spec-col"><?php echo $doc['spec_name'] ?? '---'; ?></td>
                                                <?php foreach ($daysOfWeek as $dayKey => $dayLabel): ?>
                                                    <td>
                                                        <?php if (isset($scheduleMap[$docId][$dayKey])): ?>
                                                            <i class="fas fa-check-circle check-icon" title="<?php echo $scheduleMap[$docId][$dayKey]; ?>"></i><br><small class="text-success font-weight-bold"><?php echo $scheduleMap[$docId][$dayKey]; ?></small>
                                                        <?php else: ?><i class="fas fa-times cross-icon"></i><?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td><a href="?page=manage_schedule&reset_schedule=<?php echo $docId; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa hết lịch của BS này?');"><i class="fas fa-trash-alt"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                </section>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const mSearch = document.getElementById('matrix_search');
                        const mSpec = document.getElementById('matrix_spec');
                        const mBody = document.getElementById('matrix_body');
                        const mLoad = document.getElementById('matrix_loading');

                        function filter_matrix(query = '', spec = '') {
                            if (mLoad) mLoad.style.display = 'inline-block';

                            const fd = new FormData();
                            fd.append('search', query);
                            fd.append('spec', spec);

                            fetch('ajax/get_schedules.php', {
                                    method: 'POST',
                                    body: fd
                                })
                                .then(r => r.text())
                                .then(html => {
                                    mBody.innerHTML = html;
                                    if (mLoad) mLoad.style.display = 'none';
                                })
                                .catch(e => {
                                    console.error(e);
                                    if (mLoad) mLoad.style.display = 'none';
                                });
                        }

                        if (mSearch) mSearch.addEventListener('keyup', function() {
                            filter_matrix(this.value, mSpec.value);
                        });
                        if (mSpec) mSpec.addEventListener('change', function() {
                            filter_matrix(mSearch.value, this.value);
                        });
                    });
                </script>
            <?php } ?>
            <div class="data-table-container shadow-sm bg-white rounded">
                <div class="data-table-header border-bottom">
                    <h3 class="data-table-title">Bảng phân công nhân sự</h3>
                </div>
                <div class="table-responsive p-0">
                    <table class="table table-bordered table-striped table-hover table-schedule mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 20%;">Bác sĩ</th>
                                <th style="width: 15%;">Chuyên ngành</th>
                                <?php foreach ($daysOfWeek as $day): ?><th><?php echo $day; ?></th><?php endforeach; ?>
                                <th style="width: 5%;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_docs as $doc): $docId = $doc['id']; ?>
                                <tr>
                                    <td class="doctor-name-col"><?php echo $doc['fullname']; ?></td>
                                    <td class="spec-col"><?php echo $doc['spec_name'] ?? '---'; ?></td>
                                    <?php foreach ($daysOfWeek as $dayKey => $dayLabel): ?>
                                        <td>
                                            <?php if (isset($scheduleMap[$docId][$dayKey])): ?>
                                                <i class="fas fa-check-circle check-icon" title="<?php echo $scheduleMap[$docId][$dayKey]; ?>"></i><br><small class="text-success font-weight-bold"><?php echo $scheduleMap[$docId][$dayKey]; ?></small>
                                            <?php else: ?><i c lass="fas fa-times cross-icon"></i><?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td><a href="?page=manage_schedule&reset_schedule=<?php echo $docId; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa hết lịch của BS này?');"><i class="fas fa-trash-alt"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </section>
        <?php } ?>

        <?php if ($page === 'patients') { ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Hồ sơ bệnh nhân</h2>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã BN</th>
                                <th>Họ tên</th>
                                <th>Giới tính</th>
                                <th>Email</th>
                                <th>Liên hệ</th>
                            </tr>
                        </thead>
                        <tbody><?php $query = "select * from patreg";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_BOTH)) { ?><tr>
                                    <td>#<?php echo $row['pid']; ?></td>
                                    <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                    <td><?php echo $row['gender']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['contact']; ?></td>
                                </tr><?php } ?></tbody>
                    </table>
                </div>
            </section>
        <?php } ?>

        <?php if ($page === 'appointments') { ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Chi tiết lịch hẹn</h2>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã LH</th>
                                <th>Tên bệnh nhân</th>
                                <th>Liên hệ</th>
                                <th>Bác sĩ</th>
                                <th>Phí</th>
                                <th>Ngày</th>
                                <th>Giờ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody><?php $query = "select * from appointmenttb order by appdate desc, apptime desc";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_BOTH)) { ?><tr>
                                    <td>#<?php echo $row['ID']; ?></td>
                                    <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                    <td><?php echo $row['contact']; ?></td>
                                    <td><?php echo $row['doctor']; ?></td>
                                    <td>₹<?php echo $row['docFees']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                    <td><?php if ($row['userStatus'] == 1 && $row['doctorStatus'] == 1) echo '<span class="badge badge-success">Đang hoạt động</span>';
                                        else echo '<span class="badge badge-danger">Đã hủy</span>'; ?></td>
                                </tr><?php } ?></tbody>
                    </table>
                </div>
            </section>
        <?php } ?>

        <?php if ($page === 'prescriptions') { ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Hồ sơ đơn thuốc</h2>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bác sĩ</th>
                                <th>Tên bệnh nhân</th>
                                <th>Ngày</th>
                                <th>Bệnh</th>
                                <th>Đơn thuốc</th>
                            </tr>
                        </thead>
                        <tbody><?php $query = "select * from prestb order by appdate desc";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_BOTH)) { ?><tr>
                                    <td><?php echo $row['doctor']; ?></td>
                                    <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                    <td><?php echo $row['disease']; ?></td>
                                    <td><?php echo $row['prescription']; ?></td>
                                </tr><?php } ?></tbody>
                    </table>
                </div>
            </section>
        <?php } ?>

        <?php if ($page === 'queries') { ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Thắc mắc khách hàng</h2>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Liên hệ</th>
                                <th>Tin nhắn</th>
                            </tr>
                        </thead>
                        <tbody><?php $query = "select * from contact";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_BOTH)) { ?><tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['contact']; ?></td>
                                    <td><?php echo $row['message']; ?></td>
                                </tr><?php } ?></tbody>
                    </table>
                </div>
            </section>
        <?php } ?>

        <?php if ($page === 'medical-records') {
            // Lấy danh sách chuyên khoa
            $specs = $pdo->query("SELECT * FROM specializations")->fetchAll(PDO::FETCH_ASSOC);
            // Lấy danh sách bác sĩ
            $doctors = $pdo->query("SELECT id, fullname, spec_id FROM doctb")->fetchAll(PDO::FETCH_ASSOC);

            // Xử lý lọc
            $filter_spec_id = isset($_POST['filter_spec_id']) ? $_POST['filter_spec_id'] : '';

            // Logic: Lọc bệnh nhân đã từng khám ở khoa đó
            $sql_pat = "SELECT DISTINCT p.*,
                            (SELECT COUNT(*) FROM medical_records m WHERE m.patient_id = p.pid) as total_records,
                            (SELECT MAX(record_date) FROM medical_records m WHERE m.patient_id = p.pid) as last_visit
                            FROM patreg p ";
            $params = [];

            if (!empty($filter_spec_id)) {
                $sql_pat .= " JOIN medical_records mr ON p.pid = mr.patient_id
                                  JOIN doctb d ON mr.doctor_id = d.id
                                  WHERE d.spec_id = ?";
                $params[] = $filter_spec_id;
            }
            $sql_pat .= " ORDER BY p.pid DESC";

            $stmt = $pdo->prepare($sql_pat);
            $stmt->execute($params);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-notes-medical"></i> Quản lý Hồ sơ Bệnh án</h2>
                </div>

                <div class="search-card mb-4">
                    <form method="POST" class="row align-items-center">
                        <div class="col-md-4"><label class="font-weight-bold mb-0">Lọc bệnh nhân theo khoa đã khám:</label></div>
                        <div class="col-md-6">
                            <select name="filter_spec_id" class="form-control custom-select-filter" onchange="this.form.submit()">
                                <option value="">-- Tất cả bệnh nhân --</option>
                                <?php foreach ($specs as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php if ($filter_spec_id == $s['id']) echo 'selected'; ?>><?php echo $s['name_vi']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 text-right">
                            <?php if ($filter_spec_id): ?><a href="?page=medical-records" class="btn btn-sm btn-secondary"><i class="fas fa-sync"></i> Reset</a><?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="data-table-container">
                    <div class="data-table-header">
                        <h3 class="data-table-title">Danh sách bệnh nhân</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã BN</th>
                                <th>Họ tên</th>
                                <th>Liên hệ</th>
                                <th>Số hồ sơ</th>
                                <th>Khám lần cuối</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($patients) > 0): foreach ($patients as $p): ?>
                                    <tr>
                                        <td>#<?php echo $p['pid']; ?></td>
                                        <td><strong><?php echo $p['fname'] . ' ' . $p['lname']; ?></strong></td>
                                        <td><?php echo $p['contact']; ?></td>
                                        <td><span class="badge badge-info"><?php echo $p['total_records']; ?> hồ sơ</span></td>
                                        <td><?php echo $p['last_visit'] ? date('d/m/Y', strtotime($p['last_visit'])) : '-'; ?></td>
                                        <td><button class="btn btn-primary btn-sm" onclick="viewPatientHistory(<?php echo $p['pid']; ?>)"><i class="fas fa-folder-open"></i> Chi tiết</button></td>
                                    </tr>
                            <?php endforeach;
                            else: echo '<tr><td colspan="6" class="text-center py-4">Không tìm thấy dữ liệu.</td></tr>';
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Lịch sử khám bệnh</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body" id="history_content" style="background-color: #f8f9fa;"></div>
                    </div>
                </div>
            </div>

            <div class="modal fade modal-right"
                id="recordModal"
                tabindex="-1"
                role="dialog"
                aria-hidden="true">

                <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="recordModalLabel"><i class="fas fa-file-medical-alt text-primary mr-2"></i>Phiếu Khám Bệnh</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="recordForm">
                                <input type="hidden" name="action" id="record_action" value="add">
                                <input type="hidden" name="patient_id" id="record_pid">
                                <input type="hidden" name="record_id" id="record_id">

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Bác sĩ khám <span class="text-danger">*</span></label>
                                            <select name="doctor_id" id="record_doctor" class="form-control" required>
                                                <option value="">-- Chọn bác sĩ --</option>
                                                <?php foreach ($doctors as $d): ?><option value="<?php echo $d['id']; ?>">BS. <?php echo $d['fullname']; ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Ngày giờ khám</label>
                                            <input type="datetime-local" name="record_date" id="record_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <fieldset class="vital-signs-group">
                                    <legend class="vital-legend"><i class="fas fa-heartbeat"></i> Chỉ số sinh tồn</legend>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <label class="small text-muted">Chiều cao (cm)</label>
                                            <input type="number" step="0.01" name="height" id="height" class="form-control" oninput="calcBMI()" placeholder="Ví dụ: 170">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="small text-muted">Cân nặng (kg)</label>
                                            <input type="number" step="0.01" name="weight" id="weight" class="form-control" oninput="calcBMI()" placeholder="Ví dụ: 65">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="small text-muted">BMI</label>
                                            <input type="text" name="bmi" id="bmi" class="form-control bg-light" readonly>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="small text-muted">Nhiệt độ (°C)</label>
                                            <input type="number" step="0.1" name="temperature" id="temp" class="form-control" placeholder="37">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small text-muted">Huyết áp (mmHg)</label>
                                            <input type="text" name="blood_pressure" id="bp" class="form-control" placeholder="120/80">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small text-muted">Mạch (lần/phút)</label>
                                            <input type="number" name="heart_rate" id="hr" class="form-control">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small text-muted">Nhịp thở (lần/phút)</label>
                                            <input type="number" name="respiratory_rate" id="rr" class="form-control">
                                        </div>
                                    </div>
                                </fieldset>

                                <div class="form-group">
                                    <label>Lý do khám bệnh</label>
                                    <input type="text" name="chief_complaint" id="chief" class="form-control" placeholder="Ví dụ: Đau đầu, chóng mặt...">
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Triệu chứng lâm sàng</label>
                                            <textarea name="symptoms" id="symp" class="form-control" rows="3" placeholder="Mô tả triệu chứng..."></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Chẩn đoán sơ bộ <span class="text-danger">*</span></label>
                                            <textarea name="diagnosis" id="diag" class="form-control" rows="3" required placeholder="Kết luận bệnh..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Hướng điều trị</label>
                                            <textarea name="treatment_plan" id="plan" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Đơn thuốc</label>
                                            <textarea name="prescription" id="pres" class="form-control" rows="3" placeholder="Tên thuốc - Liều lượng..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Ghi chú thêm</label>
                                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label>Hẹn tái khám</label>
                                            <input type="date" name="follow_up_date" id="fup" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer bg-light" style="border-top: 1px solid #f0f0f0;">
                            <button type="button" class="btn btn-secondary" onclick="backToHistory()"><i class="fas fa-arrow-left mr-1"></i> Quay lại</button>
                            <button type="button" class="btn btn-primary px-4" onclick="saveRecord()"><i class="fas fa-save mr-1"></i> Lưu Hồ Sơ</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function backToHistory() {
                    $('#recordModal').modal('hide');
                    setTimeout(function() {
                        $('#historyModal').modal('show');
                    }, 300);
                }
            </script>
        <?php } ?>

        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function checkPassword() {
            if (document.getElementById('dpassword').value == document.getElementById('cdpassword').value) {
                document.getElementById('message').style.color = '#d2302c';
                document.getElementById('message').innerHTML = '✓ Khớp';
            } else {
                document.getElementById('message').style.color = '#EF4444';
                document.getElementById('message').innerHTML = '✗ Không khớp';
            }
        }
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })

        <?php if ($page === 'dashboard'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Biểu đồ bệnh nhân mới theo tháng
                <?php
                $monthLabels = [];
                $patientCounts = [];

                // Tạo mảng 12 tháng với giá trị 0
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $monthLabels[$date] = date('m/Y', strtotime("-$i months"));
                    $patientCounts[$date] = 0;
                }

                // Điền dữ liệu thực tế
                foreach ($patientsMonthlyData as $row) {
                    if (isset($monthLabels[$row['month']])) {
                        $patientCounts[$row['month']] = (int)$row['count'];
                    }
                }
                ?>

                const patientsCtx = document.getElementById('patientsChart');
                if (patientsCtx) {
                    new Chart(patientsCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_values($monthLabels)); ?>,
                            datasets: [{
                                label: 'Bệnh nhân mới',
                                data: <?php echo json_encode(array_values($patientCounts)); ?>,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                pointBackgroundColor: '#3b82f6',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        padding: 15
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: {
                                        size: 14
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' bệnh nhân';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        font: {
                                            size: 12
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 12
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }

                // Biểu đồ doanh thu theo tháng
                <?php
                $revenueLabels = [];
                $revenueCounts = [];

                // Tạo mảng 12 tháng với giá trị 0
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $revenueLabels[$date] = date('m/Y', strtotime("-$i months"));
                    $revenueCounts[$date] = 0;
                }

                // Điền dữ liệu thực tế
                foreach ($revenueMonthlyData as $row) {
                    if (isset($revenueLabels[$row['month']])) {
                        $revenueCounts[$row['month']] = (float)$row['revenue'];
                    }
                }
                ?>

                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    new Chart(revenueCtx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_values($revenueLabels)); ?>,
                            datasets: [{
                                label: 'Doanh thu (VNĐ)',
                                data: <?php echo json_encode(array_values($revenueCounts)); ?>,
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                borderColor: '#22c55e',
                                borderWidth: 2,
                                borderRadius: 8,
                                hoverBackgroundColor: 'rgba(34, 197, 94, 0.9)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        padding: 15
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: {
                                        size: 14
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {
                                                style: 'currency',
                                                currency: 'VND'
                                            }).format(context.parsed.y);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        callback: function(value) {
                                            return new Intl.NumberFormat('vi-VN', {
                                                notation: 'compact',
                                                compactDisplay: 'short'
                                            }).format(value);
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 12
                                        }
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }

                // Biểu đồ tỷ lệ lịch khám
                const appointmentCtx = document.getElementById('appointmentChart');
                if (appointmentCtx) {
                    new Chart(appointmentCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Thành công', 'Đã hủy'],
                            datasets: [{
                                data: [
                                    <?php echo (int)$appointmentStats['success']; ?>,
                                    <?php echo (int)$appointmentStats['cancelled']; ?>
                                ],
                                backgroundColor: [
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(239, 68, 68, 0.8)'
                                ],
                                borderColor: [
                                    '#22c55e',
                                    '#ef4444'
                                ],
                                borderWidth: 2,
                                hoverOffset: 15
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            size: 13,
                                            weight: 'bold'
                                        },
                                        padding: 15,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: {
                                        size: 14
                                    },
                                    bodyFont: {
                                        size: 13
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            const total = <?php echo (int)$appointmentStats['total']; ?>;
                                            const value = context.parsed;
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return context.label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });

        <?php endif; ?>
        $(document).ready(function() {
            $('.modal').on('hidden.bs.modal', function() {
                if ($('.modal:visible').length == 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                }
            });
        });

        function calcBMI() {
            var h = document.getElementById('height').value;
            var w = document.getElementById('weight').value;
            if (h > 0 && w > 0) {
                var bmi = w / ((h / 100) * (h / 100));
                document.getElementById('bmi').value = bmi.toFixed(2);
            }
        }

        function viewPatientHistory(pid) {
            $('#historyModal').modal('show');
            $('#history_content').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
            $.post('ajax/get_medical_records.php', {
                pid: pid
            }, function(data) {
                $('#history_content').html(data);
            });
        }

        function openAddRecordModal(pid) {
            $('#recordForm')[0].reset();
            $('#record_action').val('add');
            $('#record_pid').val(pid);

            $('#historyModal').modal('hide');

            $('#recordModal').modal({
                backdrop: false,
                keyboard: false,
                show: true
            });
        }

        function editRecord(rec) {
            $('#record_action').val('edit');
            $('#record_id').val(rec.id);
            $('#record_pid').val(rec.patient_id);

            $('#record_doctor').val(rec.doctor_id);
            $('#record_date').val(rec.record_date.replace(' ', 'T').substring(0, 16));

            $('#height').val(rec.height);
            $('#weight').val(rec.weight);
            $('#bmi').val(rec.bmi);
            $('#bp').val(rec.blood_pressure);
            $('#hr').val(rec.heart_rate);
            $('#rr').val(rec.respiratory_rate);
            $('#temp').val(rec.temperature);

            $('#chief').val(rec.chief_complaint);
            $('#symp').val(rec.symptoms);
            $('#diag').val(rec.diagnosis);
            $('#plan').val(rec.treatment_plan);
            $('#pres').val(rec.prescription);
            $('#notes').val(rec.notes);
            $('#fup').val(rec.follow_up_date);

            $('#historyModal').modal('hide');

            $('#recordModal').modal({
                backdrop: false,
                keyboard: false,
                show: true
            });
        }


        function saveRecord() {
            if ($('#record_doctor').val() == '' || $('#diag').val() == '') {
                alert("Vui lòng nhập bác sĩ và chẩn đoán.");
                return;
            }
            $.post('ajax/manage_record.php', $('#recordForm').serialize(), function(res) {
                if (res.trim() == 'success') {
                    alert("Đã lưu thành công!");
                    $('#recordModal').modal('hide');
                    viewPatientHistory($('#record_pid').val());
                } else {
                    alert("Lỗi: " + res);
                }
            });
        }

        function deleteRecord(id, pid) {
            if (confirm('Chắc chắn xóa?')) {
                $.post('ajax/manage_record.php', {
                    action: 'delete',
                    record_id: id
                }, function(res) {
                    if (res.trim() == 'success') viewPatientHistory(pid);
                    else alert("Lỗi xóa.");
                });
            }
        }

        $('#recordModal').on('hidden.bs.modal', function() {
            // Tùy chọn: Mở lại history khi đóng form
            // viewPatientHistory($('#record_pid').val());
        });

        $('.modal').on('hidden.bs.modal', function() {
            if ($('.modal.show').length === 0) {
                $('body').removeClass('modal-open');
                $('body').css({
                    overflow: '',
                    paddingRight: ''
                });
                $('.modal-backdrop').remove();
            }
        });
    </script>
</body>

</html>