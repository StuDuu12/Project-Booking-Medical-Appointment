<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$doctor = $_SESSION['dname'] ?? null;


if (!$doctor) {
    header("Location: ../../pages/auth/login.php");
    exit();
}

// Get doctor ID and Full Name
$stmt = $pdo->prepare("SELECT id, fullname FROM doctb WHERE username = :doctor");
$stmt->execute([':doctor' => $doctor]);
$doc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doc_info['id'] ?? 0;
$doctor_fullname = $doc_info['fullname'] ?? $doctor;

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'appointments', 'schedule');
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Xử lý Hủy lịch hẹn
if (isset($_GET['cancel'])) {
    try {
        $stmt = $pdo->prepare("UPDATE appointmenttb SET doctorStatus='0' WHERE ID = :id");
        $stmt->execute([':id' => $_GET['ID']]);
        redirectWithMessage("dashboard.php?page=appointments", 'success', 'Đã hủy lịch hẹn thành công');
    } catch (PDOException $e) {
        error_log("Cancel appointment error: " . $e->getMessage());
    }
}

?>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Bảng điều khiển Bác sĩ - Bệnh viện Global</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        /* CSS Tùy chỉnh */
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

        .main-content {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .content-section {
            width: 100%;
            max-width: 100%;
            padding: 1.5rem;
        }

        .data-table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            min-width: 900px;
            white-space: nowrap;
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            white-space: nowrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }

        .top-navbar {
            padding: 1rem 1.5rem;
            flex-wrap: wrap;
        }

        .navbar-title {
            font-size: 1.25rem;
        }

        .search-box-form .input-group {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Style riêng cho trang Lịch (List View) */
        .table-schedule th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-align: center;
        }

        .table-schedule td {
            vertical-align: middle;
            text-align: center;
        }

        .schedule-badge {
            font-size: 0.95rem;
            padding: 6px 12px;
            border-radius: 4px;
        }

        .today-row {
            background-color: #f0f9ff;
        }

        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
                height: 100vh;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                width: 100%;
            }

            .mobile-menu-btn {
                display: block !important;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: linear-gradient(135deg, #d2302c 0%, #ff4d4d 100%);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(210, 48, 44, 0.3);
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        body {
            overflow-x: hidden;
        }

        .dashboard-container {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>

    <!-- jQuery and Bootstrap JS for dropdown functionality -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <?php displayMessage(); ?>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-user-md"></i></div>
                <div>
                    <h1 class="sidebar-title">Bệnh viện Global</h1>
                    <div class="sidebar-subtitle">Cổng Bác sĩ</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="?page=dashboard" class="sidebar-menu-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large sidebar-menu-icon"></i><span>Bảng điều khiển</span>
                    </a>
                </li>

                <li class="sidebar-menu-item">
                    <a href="?page=schedule" class="sidebar-menu-link <?php echo ($page === 'schedule') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check sidebar-menu-icon"></i><span>Lịch làm việc</span>
                    </a>
                </li>

                <li class="sidebar-menu-item">
                    <a href="?page=appointments" class="sidebar-menu-link <?php echo ($page === 'appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt sidebar-menu-icon"></i><span>Lịch hẹn</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="prescriptions.php" class="sidebar-menu-link">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i><span>Đơn thuốc</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="documents.php" class="sidebar-menu-link">
                        <i class="fas fa-file-upload sidebar-menu-icon"></i>
                        <span>Upload tài liệu</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="patient-history.php" class="sidebar-menu-link">
                        <i class="fas fa-history sidebar-menu-icon"></i>
                        <span>Lịch sử bệnh án</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="medicine-inventory.php" class="sidebar-menu-link">
                        <i class="fas fa-pills sidebar-menu-icon"></i>
                        <span>Quản lý kho thuốc</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="medical-records.php" class="sidebar-menu-link">
                        <i class="fas fa-file-medical sidebar-menu-icon"></i><span>Hồ sơ bệnh án</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Bảng điều khiển Bác sĩ</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="navbarUserDropdown" data-toggle="dropdown">
                            <div class="navbar-user-avatar"><?php echo strtoupper(substr($doctor, 0, 1)); ?></div>
                            <div class="navbar-user-info">
                                <div class="navbar-user-name">BS. <?php echo $doctor; ?></div>
                                <div class="navbar-user-role">Bác sĩ</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="../../index.php"><i class="fas fa-home mr-2"></i> Trang chủ</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất</a>
                        </div>
                    </div>
                </div>
            </nav>

            <?php if ($page === 'dashboard') { ?>
                <?php $is_saturday = (date('N') == 6); ?>
                <section class="content-section">
                    <?php if ($is_saturday): ?>
                        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid #17a2b8;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clipboard-check fa-2x mr-3 animate__animated animate__headShake animate__infinite"></i>
                                <div>
                                    <strong>Nhắc nhở:</strong> Hôm nay là Thứ 7. Vui lòng kiểm tra lại lịch trực được phân công cho tuần sau!
                                </div>
                            </div>
                            <a href="?page=schedule" class="btn btn-sm btn-info ml-auto" style="position: absolute; right: 40px; top: 15px;">Xem lịch ngay</a>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="section-header">
                        <h2 class="section-title">Xin chào, BS. <?php echo $doctor; ?>!</h2>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng lịch hẹn</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor)");
                                    $stmt->execute([':doctor' => $doctor_fullname]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-users"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Bệnh nhân hôm nay</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) AND userStatus = '1' AND doctorStatus = '1'");
                                    $stmt->execute([':doctor' => $doctor_fullname]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['active'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning" style="background: #fffdf0; color: #d4af37;"><i class="fas fa-calendar-week"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Đơn thuốc đã kê</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as pres FROM prestb WHERE doctor = :doctor");
                                    $stmt->execute([':doctor' => $doctor_fullname]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['pres'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <a href="documents.php" class="stat-card text-decoration-none">
                            <div class="stat-icon info">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tài liệu đã upload</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as docs FROM medical_documents WHERE doctor = :doctor");
                                    $stmt->execute([':doctor' => $doctor]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['docs'];
                                    ?>
                                </div>
                            </div>
                        </a>

                        <a href="medical-records.php" class="stat-card text-decoration-none">
                            <div class="stat-icon" style="background: #ffe6e6; color: #ff4d4d;"><i class="fas fa-file-medical"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Hồ sơ bệnh án</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as records FROM medical_records WHERE doctor_id = :doctor_id");
                                    $stmt->execute([':doctor_id' => $doctor_id]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['records'];
                                    ?>
                                </div>
                            </div>
                        </a>

                        <a href="medicine-inventory.php" class="stat-card text-decoration-none">
                            <div class="stat-icon" style="background: #fff5f5; color: #d2302c;"><i class="fas fa-capsules"></i></div>
                            <div class="stat-content">
                                <div class="stat-label">Kho thuốc</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as meds FROM medicines WHERE created_by = :doctor");
                                    $stmt->execute([':doctor' => $doctor]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['meds'];
                                    ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </section>
            <?php } ?>

            <?php if ($page === 'schedule') {
                // Code to fetch schedule data
                $stmt = $pdo->prepare("
                    SELECT day_of_week, start_time, end_time
                    FROM doctor_schedules
                    WHERE doctor_id = ?
                    ORDER BY start_time ASC
                ");
                $stmt->execute([$doctor_id]);
                $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Gom nhóm dữ liệu theo ngày (Key sẽ là 0, 1, 2... 6)
                // Ví dụ: $grouped_schedule[1] = ['08:00 - 12:00', '13:00 - 17:00'];
                $grouped_schedule = [];
                if (count($rawData) > 0) {
                    foreach ($rawData as $row) {
                        $day = $row['day_of_week'];
                        $time_str = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));

                        // Thêm vào mảng con của thứ đó
                        $grouped_schedule[$day][] = $time_str;
                    }
                }

                // Mảng tuần tự để lặp hiển thị (Thứ 2 -> CN)
                $daysLoop = [1, 2, 3, 4, 5, 6, 0];

                // Tạo mảng tên ngày tiếng Việt
                $vnDays = [
                    0 => 'Chủ Nhật',
                    1 => 'Thứ 2',
                    2 => 'Thứ 3',
                    3 => 'Thứ 4',
                    4 => 'Thứ 5',
                    5 => 'Thứ 6',
                    6 => 'Thứ 7'
                ];
            ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Lịch làm việc cố định</h2>
                        <p class="section-subtitle">Dưới đây là thời khóa biểu hàng tuần của bạn.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white font-weight-bold border-bottom-0 pt-3">
                                    <i class="fas fa-calendar-alt text-primary"></i> Thời gian biểu
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-schedule mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 20%;">Thứ</th>
                                                    <th>Khung giờ làm việc</th>
                                                    <th style="width: 15%;">Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($daysLoop as $dayNum):
                                                    // Kiểm tra xem hôm nay có phải là thứ đang xét không
                                                    $isToday = (date('w') == $dayNum);
                                                    $rowClass = $isToday ? "today-row" : "";

                                                    // Tạo nhãn tên thứ (Thêm badge nếu là hôm nay)
                                                    $dayLabel = $vnDays[$dayNum];
                                                    if ($isToday) $dayLabel .= ' <span class="badge badge-primary ml-1">Hôm nay</span>';
                                                ?>
                                                    <tr class="<?php echo $rowClass; ?>">
                                                        <td class="font-weight-bold text-dark text-center align-middle">
                                                            <?php echo $dayLabel; ?>
                                                        </td>

                                                        <td class="text-left pl-4 align-middle">
                                                            <?php
                                                            // Kiểm tra xem thứ này có dữ liệu trong mảng gom nhóm không
                                                            if (isset($grouped_schedule[$dayNum]) && count($grouped_schedule[$dayNum]) > 0) {
                                                                // Lặp qua tất cả khung giờ của ngày đó và hiển thị
                                                                foreach ($grouped_schedule[$dayNum] as $timeSlot) {
                                                                    echo '<span class="time-pill"><i class="far fa-clock"></i> ' . $timeSlot . '</span>';
                                                                }
                                                            } else {
                                                                // Nếu không có lịch
                                                                echo '<span class="off-day text-muted pl-2">-- Nghỉ --</span>';
                                                            }
                                                            ?>
                                                        </td>

                                                        <td class="text-center align-middle">
                                                            <?php if (isset($grouped_schedule[$dayNum])): ?>
                                                                <span class="text-success font-weight-bold small"><i class="fas fa-check-circle"></i> Có lịch</span>
                                                            <?php else: ?>
                                                                <span class="text-muted small">Vắng</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-muted small pl-2">
                                <i class="fas fa-info-circle"></i> Lịch này được áp dụng lặp lại hàng tuần. Vui lòng liên hệ Admin nếu cần thay đổi.
                            </div>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <?php if ($page === 'appointments') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Lịch hẹn bệnh nhân</h2>
                    </div>
                    <div class="mb-4">
                        <form method="post" action="search.php" class="search-box-form">
                            <div class="input-group" style="max-width: 500px;">
                                <input type="text" class="form-control" placeholder="Tìm theo SĐT..." name="contact">
                                <div class="input-group-append"><button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tìm</button></div>
                            </div>
                        </form>
                    </div>
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã BN</th>
                                    <th>Tên BN</th>
                                    <th>Liên hệ</th>
                                    <th>Ngày hẹn</th>
                                    <th>Giờ</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT pid,ID,fname,lname,contact,appdate,apptime,userStatus,doctorStatus FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) ORDER BY appdate DESC");
                                $stmt->execute([':doctor' => trim($doctor)]);
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <tr>
                                        <td>#<?php echo $row['pid']; ?></td>
                                        <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                        <td><?php echo $row['contact']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['appdate'])); ?></td>
                                        <td><?php echo $row['apptime']; ?></td>
                                        <td>
                                            <?php if ($row['userStatus'] == 1 && $row['doctorStatus'] == 1) echo '<span class="badge badge-success">Hoạt động</span>';
                                            else echo '<span class="badge badge-danger">Đã hủy</span>'; ?>
                                        </td>
                                        <td><a href="prescribe.php?pid=<?php echo $row['pid']; ?>&ID=<?php echo $row['ID']; ?>" class="btn btn-sm btn-primary">Kê đơn</a></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>

            <?php if ($page === 'prescriptions') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Quản lý đơn thuốc</h2>
                    </div>
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã ĐT</th>
                                    <th>Bệnh nhân</th>
                                    <th>Chẩn đoán</th>
                                    <th>Số thuốc</th>
                                    <th>Ngày kê</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM prestb WHERE doctor = :doctor ORDER BY appdate DESC");
                                $stmt->execute([':doctor' => $doctor]);
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                                    <tr>
                                        <td>#<?php echo $row['ID']; ?></td>
                                        <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                        <td><?php echo $row['disease']; ?></td>
                                        <td><span class="badge badge-info">Chi tiết</span></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['appdate'])); ?></td>
                                        <td><a href="view_prescription.php?id=<?php echo $row['pres_id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                    </tr>
                                    </thead>
                            <tbody>
                                <?php
                                    // Modified query to support enhanced prestb
                                    // We select pres_id as ID for view/export links
                                    $stmt = $pdo->prepare("
                                        SELECT p.pres_id, p.ID as app_id, p.pid, p.disease, p.treatment_duration, p.created_at, p.appdate,
                                               p.fname, p.lname,
                                               (SELECT COUNT(id) FROM prescription_medications WHERE prescription_id = p.pres_id) as medication_count
                                        FROM prestb p
                                        WHERE p.doctor = :doctor
                                        ORDER BY p.appdate DESC, p.created_at DESC
                                    ");
                                    $stmt->execute([':doctor' => $doctor_fullname]);

                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // Handle cases where pres_id might be null (old records before migration)
                                            // If pres_id is missing, we can't easily view details or export PDF for old records unless we backfill.
                                            // But new records will have it.
                                            $view_id = $row['pres_id'];
                                ?>
                                        <tr>
                                            <td>#<?php echo $row['app_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($row['disease']); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo $row['medication_count']; ?> thuốc
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['treatment_duration'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['appdate'])); ?></td>
                                            <td>
                                                <?php if ($view_id): ?>
                                                    <a href="view_prescription.php?id=<?php echo $view_id; ?>"
                                                        class="btn btn-sm btn-info"
                                                        title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="export_prescription_pdf.php?id=<?php echo $view_id; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        target="_blank"
                                                        title="Tải PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <small class="text-muted">Đơn cũ</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>Chưa có đơn thuốc nào. Nhấn "Tạo đơn thuốc mới" để bắt đầu.</p>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
    </div>
    </section>
<?php } ?>

<!-- Documents Section -->
<?php if ($page === 'documents') { ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Upload tài liệu y tế</h2>
            <p class="section-subtitle">Quản lý tài liệu y tế của bệnh nhân</p>
        </div>

        <div class="data-card">
            <h5 class="mb-3"><i class="fas fa-file-upload"></i> Upload tài liệu mới</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bệnh nhân *</label>
                            <select name="pid" class="form-control" required>
                                <option value="">Chọn bệnh nhân</option>
                                <?php
                                $stmt = $pdo->prepare("SELECT DISTINCT pid, CONCAT(fname, ' ', lname) as pname, email as pemail FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor)");
                                $stmt->execute([':doctor' => $doctor_fullname]);
                                while ($patient = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$patient['pid']}'>{$patient['pname']} ({$patient['pemail']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Lịch hẹn (Tùy chọn)</label>
                            <select name="appointment_id" class="form-control">
                                <option value="">Không liên kết lịch hẹn</option>
                                <?php
                                $stmt = $pdo->prepare("SELECT ID, pid, CONCAT(fname, ' ', lname) as pname, appdate FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) ORDER BY appdate DESC");
                                $stmt->execute([':doctor' => $doctor_fullname]);
                                while ($appt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$appt['ID']}'>{$appt['pname']} - " . date('d/m/Y', strtotime($appt['appdate'])) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>File tài liệu *</label>
                            <input type="file" name="document_file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                            <small class="text-muted">PDF, hình ảnh, Word (Tối đa 10MB)</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Mô tả tài liệu..."></textarea>
                </div>
                <button type="submit" name="upload_document" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload tài liệu
                </button>
            </form>
        </div>

        <div class="data-card mt-4">
            <h5 class="mb-3"><i class="fas fa-file-medical"></i> Danh sách tài liệu</h5>
            <div class="data-table-container">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Bệnh nhân</th>
                            <th>Tên file</th>
                            <th>Loại</th>
                            <th>Kích thước</th>
                            <th>Mô tả</th>
                            <th>Ngày upload</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $filter_appointment = isset($_GET['appointment']) ? $_GET['appointment'] : '';
                        $query = "SELECT md.*, p.fname, p.lname FROM medical_documents md 
                                              LEFT JOIN patreg p ON md.pid = p.pid 
                                              WHERE md.doctor = :doctor";
                        if ($filter_appointment) {
                            $query .= " AND md.appointment_id = :appointment_id";
                        }
                        $query .= " ORDER BY md.uploaded_at DESC";

                        $stmt = $pdo->prepare($query);
                        $params = [':doctor' => $doctor];
                        if ($filter_appointment) {
                            $params[':appointment_id'] = $filter_appointment;
                        }
                        $stmt->execute($params);

                        if ($stmt->rowCount() > 0) {
                            while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $file_size_mb = round($doc['file_size'] / (1024 * 1024), 2);
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($doc['fname'] . ' ' . $doc['lname']) . "</td>";
                                echo "<td>" . htmlspecialchars($doc['document_name']) . "</td>";
                                echo "<td><span class='badge badge-info'>" . strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) . "</span></td>";
                                echo "<td>{$file_size_mb} MB</td>";
                                echo "<td>" . htmlspecialchars($doc['description']) . "</td>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($doc['uploaded_at'])) . "</td>";
                                echo "<td>
                                                    <a href='../../uploads/medical_documents/{$doc['file_path']}' target='_blank' class='btn btn-sm btn-primary' title='Xem'>
                                                        <i class='fas fa-eye'></i>
                                                    </a>
                                                  </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center text-muted'>Chưa có tài liệu nào được upload.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php } ?>

<!-- Patient History Section -->
<?php if ($page === 'patient_history') { ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Lịch sử bệnh án</h2>
            <p class="section-subtitle">Tìm kiếm và xem lịch sử điều trị của bệnh nhân</p>
        </div>

        <div class="data-card">
            <h5 class="mb-3"><i class="fas fa-search"></i> Tìm kiếm bệnh nhân</h5>
            <form method="GET" action="">
                <input type="hidden" name="page" value="patient_history">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Chọn bệnh nhân</label>
                            <select name="search_pid" class="form-control">
                                <option value="">-- Chọn bệnh nhân --</option>
                                <?php
                                $stmt = $pdo->prepare("SELECT DISTINCT pid, CONCAT(fname, ' ', lname) as pname, email as pemail FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) ORDER BY fname, lname");
                                $stmt->execute([':doctor' => $doctor_fullname]);
                                while ($patient = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (isset($_GET['search_pid']) && $_GET['search_pid'] == $patient['pid']) ? 'selected' : '';
                                    echo "<option value='{$patient['pid']}' $selected>{$patient['pname']} ({$patient['pemail']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['search_pid']) && !empty($_GET['search_pid'])) {
                        $search_pid = $_GET['search_pid'];

                        // Get patient info
                        $stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = :pid");
                        $stmt->execute([':pid' => $search_pid]);
                        $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($patient_info) {
        ?>
                <div class="data-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-user"></i> Thông tin bệnh nhân</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($patient_info['fname'] . ' ' . $patient_info['lname']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($patient_info['contact']); ?></p>
                            <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($patient_info['gender']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Ngày sinh:</strong> <?php echo date('d/m/Y', strtotime($patient_info['dob'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="data-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-calendar-alt"></i> Lịch sử lịch hẹn</h5>
                    <div class="data-table-container">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Ngày hẹn</th>
                                    <th>Giờ hẹn</th>
                                    <th>Triệu chứng</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM appointmenttb WHERE pid = :pid AND TRIM(doctor) = TRIM(:doctor) ORDER BY appdate DESC");
                                $stmt->execute([':pid' => $search_pid, ':doctor' => $doctor_fullname]);
                                while ($appt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $status = ($appt['userStatus'] == 1 && $appt['doctorStatus'] == 1) ?
                                        '<span class="badge badge-success">Đang hoạt động</span>' :
                                        '<span class="badge badge-secondary">Đã hủy</span>';
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($appt['appdate'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($appt['apptime']) . "</td>";
                                    echo "<td>" . htmlspecialchars($appt['symptoms']) . "</td>";
                                    echo "<td>$status</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="data-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-file-prescription"></i> Lịch sử đơn thuốc</h5>
                    <div class="data-table-container">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Ngày kê đơn</th>
                                    <th>Bệnh</th>
                                    <th>Dị ứng</th>
                                    <th>Đơn thuốc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM prestb WHERE pid = :pid AND doctor = :doctor ORDER BY appdate DESC");
                                $stmt->execute([':pid' => $search_pid, ':doctor' => $doctor_fullname]);
                                while ($pres = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y', strtotime($pres['appdate'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($pres['disease']) . "</td>";
                                    echo "<td>" . htmlspecialchars($pres['allergy']) . "</td>";
                                    echo "<td>" . htmlspecialchars($pres['prescription']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="data-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-notes-medical"></i> Hồ sơ lâm sàng</h5>
                    <div class="data-table-container">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Ngày khám</th>
                                    <th>Loại khám</th>
                                    <th>Chẩn đoán</th>
                                    <th>Triệu chứng</th>
                                    <th>Kế hoạch điều trị</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE patient_id = :pid AND (doctor_id = :doc_id OR created_by = :doc_id) ORDER BY record_date DESC");
                                $stmt->execute([':pid' => $search_pid, ':doc_id' => $doctor_id]);
                                if ($stmt->rowCount() > 0) {
                                    while ($rec = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $type_map = [
                                            'consultation' => 'Khám bệnh',
                                            'checkup' => 'Kiểm tra',
                                            'emergency' => 'Cấp cứu',
                                            'followup' => 'Tái khám',
                                            'surgery' => 'Phẫu thuật'
                                        ];
                                        $rec_type = $type_map[$rec['record_type']] ?? $rec['record_type'];

                                        echo "<tr>";
                                        echo "<td>" . date('d/m/Y', strtotime($rec['record_date'])) . "</td>";
                                        echo "<td><span class='badge badge-primary'>" . htmlspecialchars($rec_type) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($rec['diagnosis']) . "</td>";
                                        echo "<td>" . htmlspecialchars($rec['symptoms']) . "</td>";
                                        echo "<td>" . htmlspecialchars($rec['treatment_plan']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Chưa có hồ sơ lâm sàng.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="data-card mt-4">
                    <h5 class="mb-3"><i class="fas fa-file-medical"></i> Tài liệu y tế</h5>
                    <div class="data-table-container">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Tên file</th>
                                    <th>Loại</th>
                                    <th>Mô tả</th>
                                    <th>Ngày upload</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM medical_documents WHERE pid = :pid AND doctor = :doctor ORDER BY uploaded_at DESC");
                                $stmt->execute([':pid' => $search_pid, ':doctor' => $doctor]);
                                if ($stmt->rowCount() > 0) {
                                    while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($doc['document_name']) . "</td>";
                                        echo "<td><span class='badge badge-info'>" . strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($doc['description']) . "</td>";
                                        echo "<td>" . date('d/m/Y H:i', strtotime($doc['uploaded_at'])) . "</td>";
                                        echo "<td>
                                                    <a href='../../uploads/medical_documents/{$doc['file_path']}' target='_blank' class='btn btn-sm btn-primary'>
                                                        <i class='fas fa-eye'></i>
                                                    </a>
                                                  </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Chưa có tài liệu nào.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        <?php
                        }
                    } ?>
    </section>
<?php } ?>

<!-- Medicine Inventory Section -->
<?php if ($page === 'medicine_inventory') { ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Quản lý kho thuốc</h2>
            <p class="section-subtitle">Quản lý kho thuốc và tồn kho</p>
        </div>

        <div class="data-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-pills"></i> Danh sách thuốc</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addMedicineModal">
                    <i class="fas fa-plus"></i> Thêm thuốc mới
                </button>
            </div>
            <div class="data-table-container">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Tên thuốc</th>
                            <th>Tên chung</th>
                            <th>Danh mục</th>
                            <th>Dạng thuốc</th>
                            <th>Hàm lượng</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Hạn dùng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
                        while ($medicine = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $low_stock = $medicine['quantity'] < 10 ? 'text-danger font-weight-bold' : '';
                            $expired = (strtotime($medicine['expiry_date']) < time()) ? 'text-danger' : '';
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($medicine['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($medicine['generic_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($medicine['category']) . "</td>";
                            echo "<td>" . htmlspecialchars($medicine['dosage_form']) . "</td>";
                            echo "<td>" . htmlspecialchars($medicine['strength']) . "</td>";
                            echo "<td class='$low_stock'>" . $medicine['quantity'] . "</td>";
                            echo "<td>" . number_format($medicine['unit_price'], 0, ',', '.') . " VNĐ</td>";
                            echo "<td class='$expired'>" . date('d/m/Y', strtotime($medicine['expiry_date'])) . "</td>";
                            echo "<td>
                                                <button class='btn btn-sm btn-info' onclick='editMedicine(" . json_encode($medicine) . ")' title='Sửa'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-sm btn-warning' onclick='updateStock({$medicine['id']}, \"{$medicine['name']}\")' title='Cập nhật kho'>
                                                    <i class='fas fa-box'></i>
                                                </button>
                                                <button class='btn btn-sm btn-danger' onclick='deleteMedicine({$medicine['id']}, \"{$medicine['name']}\")' title='Xóa'>
                                                    <i class='fas fa-trash'></i>
                                                </button>
                                              </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php } ?>
</main>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
        document.querySelector('.sidebar-overlay').classList.toggle('active');
    }
    document.querySelector('.sidebar-overlay').addEventListener('click', toggleSidebar);
</script>

<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedicineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Thêm thuốc mới</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên thuốc *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên chung</label>
                                <input type="text" name="generic_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Danh mục</label>
                                <input type="text" name="category" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dạng thuốc</label>
                                <input type="text" name="dosage_form" class="form-control" placeholder="Viên, Siro, v.v.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hàm lượng</label>
                                <input type="text" name="strength" class="form-control" placeholder="500mg, v.v.">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nhà sản xuất</label>
                                <input type="text" name="manufacturer" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Số lượng *</label>
                                <input type="number" name="quantity" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Đơn giá (VNĐ) *</label>
                                <input type="number" name="unit_price" class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hạn dùng *</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" name="add_medicine" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Medicine Modal -->
<div class="modal fade" id="editMedicineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh sửa thuốc</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="medicine_id" id="edit_medicine_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên thuốc *</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên chung</label>
                                <input type="text" name="generic_name" id="edit_generic_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Danh mục</label>
                                <input type="text" name="category" id="edit_category" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dạng thuốc</label>
                                <input type="text" name="dosage_form" id="edit_dosage_form" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hàm lượng</label>
                                <input type="text" name="strength" id="edit_strength" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nhà sản xuất</label>
                                <input type="text" name="manufacturer" id="edit_manufacturer" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Số lượng *</label>
                                <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Đơn giá (VNĐ) *</label>
                                <input type="number" name="unit_price" id="edit_unit_price" class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hạn dùng *</label>
                                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_medicine" class="btn btn-info">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-box"></i> Cập nhật kho</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="medicine_id" id="stock_medicine_id">
                <div class="modal-body">
                    <p><strong>Thuốc:</strong> <span id="stock_medicine_name"></span></p>
                    <div class="form-group">
                        <label>Thay đổi số lượng *</label>
                        <input type="number" name="quantity_change" class="form-control"
                            placeholder="Nhập số dương để thêm, số âm để trừ" required>
                        <small class="text-muted">Ví dụ: +50 để thêm 50 đơn vị, -20 để trừ 20 đơn vị</small>
                    </div>
                    <div class="form-group">
                        <label>Lý do *</label>
                        <textarea name="reason" class="form-control" rows="2"
                            placeholder="Nhập khẩu, Xuất kho, v.v." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_stock" class="btn btn-warning">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editMedicine(medicine) {
        document.getElementById('edit_medicine_id').value = medicine.id;
        document.getElementById('edit_name').value = medicine.name;
        document.getElementById('edit_generic_name').value = medicine.generic_name || '';
        document.getElementById('edit_category').value = medicine.category || '';
        document.getElementById('edit_dosage_form').value = medicine.dosage_form || '';
        document.getElementById('edit_strength').value = medicine.strength || '';
        document.getElementById('edit_manufacturer').value = medicine.manufacturer || '';
        document.getElementById('edit_quantity').value = medicine.quantity;
        document.getElementById('edit_unit_price').value = medicine.unit_price;
        document.getElementById('edit_expiry_date').value = medicine.expiry_date;
        document.getElementById('edit_description').value = medicine.description || '';
        $('#editMedicineModal').modal('show');
    }

    function updateStock(id, name) {
        document.getElementById('stock_medicine_id').value = id;
        document.getElementById('stock_medicine_name').textContent = name;
        $('#updateStockModal').modal('show');
    }

    function deleteMedicine(id, name) {
        if (confirm('Bạn có chắc chắn muốn xóa thuốc "' + name + '"?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="medicine_id" value="' + id + '">' +
                '<input type="hidden" name="delete_medicine" value="1">';
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
</body>

</html>
<?php } // Closing brace for unknown unclosed block 
?>