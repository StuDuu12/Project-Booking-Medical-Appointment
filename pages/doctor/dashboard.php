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

// 1. LẤY ID BÁC SĨ (Dựa trên username)
$stmt = $pdo->prepare("SELECT id FROM doctb WHERE username = :name");
$stmt->execute([':name' => $doctor]);
$currentDoctorObj = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $currentDoctorObj['id'] ?? 0;
// Handle page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'appointments', 'prescriptions', 'schedule');
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

// Mảng hiển thị tên thứ
$vnDays = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 0 => 'Chủ Nhật'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        /* CSS Tùy chỉnh */
        .navbar-user.dropdown .dropdown-toggle::after { display: none; }
        .navbar-user .dropdown-menu { min-width: 220px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); border: none; padding: 0.5rem 0; margin-top: 0.5rem; }
        .navbar-user .dropdown-item { padding: 0.75rem 1.5rem; font-size: 0.95rem; transition: all 0.2s; display: flex; align-items: center; }
        .navbar-user .dropdown-item i { width: 20px; font-size: 0.9rem; }
        .navbar-user .dropdown-item:hover { background: #f0f9ff; color: #0891b2; padding-left: 1.75rem; }
        .navbar-user .dropdown-item.text-danger:hover { background: #fef2f2; color: #dc2626; }
        .navbar-user .dropdown-divider { margin: 0.5rem 0; }
        .navbar-user-info { margin-left: 1rem; }

        .main-content { width: 100%; max-width: 100%; overflow-x: hidden; }
        .content-section { width: 100%; max-width: 100%; padding: 1.5rem; }
        .data-table-container { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .data-table { min-width: 900px; white-space: nowrap; }
        .btn-sm { font-size: 0.8rem; padding: 0.4rem 0.8rem; white-space: nowrap; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; width: 100%; }
        .top-navbar { padding: 1rem 1.5rem; flex-wrap: wrap; }
        .navbar-title { font-size: 1.25rem; }
        .search-box-form .input-group { width: 100% !important; max-width: 100% !important; }

        /* Style riêng cho trang Lịch (List View) */
        .table-schedule th { background-color: #f8f9fa; color: #495057; font-weight: 600; text-align: center; }
        .table-schedule td { vertical-align: middle; text-align: center; }
        .schedule-badge { font-size: 0.95rem; padding: 6px 12px; border-radius: 4px; }
        .today-row { background-color: #f0f9ff; }

        @media (max-width: 1024px) {
            .sidebar { position: fixed; transform: translateX(-100%); transition: transform 0.3s ease; z-index: 1000; height: 100vh; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0 !important; width: 100%; }
            .mobile-menu-btn { display: block !important; }
        }
        .mobile-menu-btn { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%); color: white; border: none; width: 45px; height: 45px; border-radius: 10px; box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3); cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        body { overflow-x: hidden; }
        .dashboard-container { width: 100%; max-width: 100%; overflow-x: hidden; }
    </style>
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
                <div><h1 class="sidebar-title">Bệnh viện Global</h1><div class="sidebar-subtitle">Cổng Bác sĩ</div></div>
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
                    <a href="?page=prescriptions" class="sidebar-menu-link <?php echo ($page === 'prescriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i><span>Đơn thuốc</span>
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
                <div class="navbar-left"><h1 class="navbar-title">Bảng điều khiển Bác sĩ</h1></div>
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

            <?php if ($page === 'dashboard') {
                 $is_saturday = (date('N') == 6);
            ?>
                <section class="content-section">
                    <?php if($is_saturday): ?>
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
                                    $stmt->execute([':doctor' => trim($doctor)]);
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
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
                                    $today = date('Y-m-d');
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) AND appdate = :today");
                                    $stmt->execute([':doctor' => trim($doctor), ':today' => $today]);
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                         <a href="?page=schedule" class="stat-card text-decoration-none">
                            <div class="stat-icon warning" style="background: #fffbeb; color: #d97706;"><i class="fas fa-calendar-week"></i></div>
                            <div class="stat-content">
                                <h5>Lịch làm việc</h5>
                                <p class="text-muted mb-0 small">Xem ca trực hàng tuần</p>
                            </div>
                        </a>
                    </div>
                </section>
            <?php } ?>

            <?php if ($page === 'schedule') {
                // 1. Lấy dữ liệu thô từ DB
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
                if(count($rawData) > 0){
                    foreach ($rawData as $row) {
                        $day = $row['day_of_week'];
                        $time_str = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));

                        // Thêm vào mảng con của thứ đó
                        $grouped_schedule[$day][] = $time_str;
                    }
                }

                // Mảng tuần tự để lặp hiển thị (Thứ 2 -> CN)
                $daysLoop = [1, 2, 3, 4, 5, 6, 0];
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
                    <div class="section-header"><h2 class="section-title">Lịch hẹn bệnh nhân</h2></div>
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
                            <thead><tr><th>Mã BN</th><th>Tên BN</th><th>Liên hệ</th><th>Ngày hẹn</th><th>Giờ</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
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
                                            <?php if($row['userStatus']==1 && $row['doctorStatus']==1) echo '<span class="badge badge-success">Hoạt động</span>'; else echo '<span class="badge badge-danger">Đã hủy</span>'; ?>
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
                    <div class="section-header"><h2 class="section-title">Quản lý đơn thuốc</h2></div>
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead><tr><th>Mã ĐT</th><th>Bệnh nhân</th><th>Chẩn đoán</th><th>Số thuốc</th><th>Ngày kê</th><th>Thao tác</th></tr></thead>
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
                                <?php } ?>
                            </tbody>
                        </table>
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
</body>
</html>