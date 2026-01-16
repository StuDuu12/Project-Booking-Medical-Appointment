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
$allowed_pages = array('dashboard', 'doctors', 'patients', 'appointments', 'prescriptions', 'queries', 'medical-records');
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

if (isset($_POST['docsub'])) {
    try {
        $doctor = $_POST['doctor'];
        $dpassword = password_hash($_POST['dpassword'], PASSWORD_DEFAULT);
        $demail = $_POST['demail'];
        $spec = $_POST['special'];
        $docFees = $_POST['docFees'];

        $stmt = $pdo->prepare("INSERT INTO doctb(username,password,email,spec,docFees) VALUES(:doctor,:dpassword,:demail,:spec,:docFees)");
        $stmt->execute([
            ':doctor' => $doctor,
            ':dpassword' => $dpassword,
            ':demail' => $demail,
            ':spec' => $spec,
            ':docFees' => $docFees
        ]);
        redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Doctor added successfully!');
    } catch (PDOException $e) {
        error_log("Add doctor error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Error adding doctor!');
    }
}

if (isset($_POST['docsub1'])) {
    try {
        $demail = $_POST['demail'];
        $stmt = $pdo->prepare("DELETE FROM doctb WHERE email = :demail");
        $stmt->execute([':demail' => $demail]);
        redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Doctor removed successfully!');
    } catch (PDOException $e) {
        error_log("Delete doctor error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Error removing doctor!');
    }
}

?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Bảng điều khiển Quản trị - Bệnh viện Global</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
</head>

<body>
    <?php displayMessage(); ?>
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
                <li class="sidebar-menu-item">
                    <a href="?page=medical-records" class="sidebar-menu-link <?php echo ($page === 'medical-records') ? 'active' : ''; ?>">
                        <i class="fas fa-file-medical sidebar-menu-icon"></i>
                        <span>Hồ sơ bệnh án</span>
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
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Bảng điều khiển Quản trị</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user">
                        <div class="navbar-user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="navbar-user-info">
                            <div class="navbar-user-name">Quản trị viên</div>
                            <div class="navbar-user-role">Lễ tân</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Section -->
            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Chào mừng, Quản trị viên!</h2>
                        <p class="section-subtitle">Quản lý bác sĩ, bệnh nhân và lịch hẹn</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số bác sĩ</div>
                                <div class="stat-value">
                                    <?php
                                    $query = $pdo->query("select count(*) as total from doctb");
                                    $row = $query->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số bệnh nhân</div>
                                <div class="stat-value">
                                    <?php
                                    $query = $pdo->query("select count(*) as total from patreg");
                                    $row = $query->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số lịch hẹn</div>
                                <div class="stat-value">
                                    <?php
                                    $query = $pdo->query("select count(*) as total from appointmenttb");
                                    $row = $query->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon danger">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số đơn thuốc</div>
                                <div class="stat-value">
                                    <?php
                                    $query = $pdo->query("select count(*) as total from prestb");
                                    $row = $query->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Grid -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <a href="?page=doctors" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon primary">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Quản lý bác sĩ</h6>
                                    <p class="text-muted mb-0 small">Xem và quản lý bác sĩ</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="?page=patients" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon success">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Hồ sơ bệnh nhân</h6>
                                    <p class="text-muted mb-0 small">Xem danh sách bệnh nhân</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="?page=appointments" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon warning">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Lịch hẹn</h6>
                                    <p class="text-muted mb-0 small">Theo dõi lịch hẹn</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="?page=prescriptions" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon danger">
                                    <i class="fas fa-file-prescription"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Đơn thuốc</h6>
                                    <p class="text-muted mb-0 small">Xem đơn thuốc</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Doctors Section - Unified Management -->
            <?php if ($page === 'doctors') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-user-md"></i> Quản lý bác sĩ</h2>
                        <p class="section-subtitle">Quản lý tài khoản bác sĩ - Xem, thêm và xóa bác sĩ</p>
                    </div>

                    <!-- Add Doctor Card -->
                    <div class="data-table-container mb-4">
                        <div class="data-table-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <h3 class="data-table-title" style="color: white;"><i class="fas fa-user-plus"></i> Thêm bác sĩ mới</h3>
                        </div>
                        <div class="p-4">
                            <form method="post" action="?page=doctors">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-user"></i> Tên bác sĩ *</label>
                                            <input type="text" class="form-control" name="doctor" placeholder="Nhập họ tên đầy đủ" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-stethoscope"></i> Chuyên khoa *</label>
                                            <select name="special" class="form-control" required>
                                                <option value="" disabled selected>Chọn chuyên khoa</option>
                                                <option value="General">Đa khoa</option>
                                                <option value="Cardiologist">Tim mạch</option>
                                                <option value="Neurologist">Thần kinh</option>
                                                <option value="Pediatrician">Nhi khoa</option>
                                                <option value="Dermatologist">Da liễu</option>
                                                <option value="Orthopedic">Chỉnh hình</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-envelope"></i> Email *</label>
                                            <input type="email" class="form-control" name="demail" placeholder="bacsi@example.com" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-money-bill-wave"></i> Phí khám *</label>
                                            <input type="number" class="form-control" name="docFees" placeholder="Nhập phí khám" min="0" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-lock"></i> Mật khẩu *</label>
                                            <input type="password" class="form-control" name="dpassword" id="dpassword" onkeyup="checkPassword()" placeholder="Nhập mật khẩu" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-lock"></i> Xác nhận mật khẩu *</label>
                                            <input type="password" class="form-control" name="cdpassword" id="cdpassword" onkeyup="checkPassword()" placeholder="Xác nhận mật khẩu" required>
                                            <small id="message"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" name="docsub" class="btn btn-success btn-lg">
                                            <i class="fas fa-user-plus"></i> Thêm bác sĩ
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Doctor List Card -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title"><i class="fas fa-list"></i> Danh sách bác sĩ</h3>
                            <div class="data-table-actions">
                                <span class="badge badge-info">
                                    <?php
                                    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM doctb");
                                    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    echo $count . ' bác sĩ';
                                    ?>
                                </span>
                            </div>
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
                            <tbody>
                                <?php
                                $query = "SELECT d.*, s.name_vi FROM doctb d 
                                         LEFT JOIN specializations s ON d.spec_id = s.id 
                                         ORDER BY d.fullname ASC";
                                $result = $pdo->query($query);
                                $serial = 1;
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <tr>
                                        <td><?php echo $serial++; ?></td>
                                        <td>
                                            <strong><i class="fas fa-user-md text-primary"></i> Dr. <?php echo htmlspecialchars($row['fullname']); ?></strong>
                                        </td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['name_vi'] ?? $row['spec']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><strong>₹<?php echo htmlspecialchars($row['docFees']); ?></strong></td>
                                        <td>
                                            <form method="post" action="?page=doctors" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa BS. <?php echo htmlspecialchars($row['username']); ?>?');">
                                                <input type="hidden" name="demail" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                <button type="submit" name="docsub1" class="btn btn-danger btn-sm" title="Xóa bác sĩ">
                                                    <i class="fas fa-trash-alt"></i> Xóa
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>

            <!-- Patients Section -->
            <?php if ($page === 'patients') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Hồ sơ bệnh nhân</h2>
                        <p class="section-subtitle">Xem bệnh nhân đã đăng ký</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Danh sách bệnh nhân</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../patientsearch.php" class="form-inline">
                                    <input type="text" name="patient_contact" placeholder="Nhập số điện thoại" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="patient_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Tìm kiếm
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
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
                                <tbody>
                                    <?php
                                    $query = "select * from patreg";
                                    $result = $pdo->query($query);
                                    while ($row = $result->fetch(PDO::FETCH_BOTH)) {
                                    ?>
                                        <tr>
                                            <td>#<?php echo $row['pid']; ?></td>
                                            <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                            <td><?php echo $row['gender']; ?></td>
                                            <td><?php echo $row['email']; ?></td>
                                            <td><?php echo $row['contact']; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Appointments Section -->
            <?php if ($page === 'appointments') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Chi tiết lịch hẹn</h2>
                        <p class="section-subtitle">Xem tất cả các lịch hẹn đã đặt</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Tất cả lịch hẹn</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../appsearch.php" class="form-inline">
                                    <input type="text" name="app_contact" placeholder="Nhập số điện thoại" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="app_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Tìm kiếm
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
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
                                <tbody>
                                    <?php
                                    $query = "select * from appointmenttb order by appdate desc, apptime desc";
                                    $result = $pdo->query($query);
                                    while ($row = $result->fetch(PDO::FETCH_BOTH)) {
                                    ?>
                                        <tr>
                                            <td>#<?php echo $row['ID']; ?></td>
                                            <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                            <td><?php echo $row['contact']; ?></td>
                                            <td><?php echo $row['doctor']; ?></td>
                                            <td>₹<?php echo $row['docFees']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                            <td>
                                                <?php
                                                if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) {
                                                    echo '<span class="badge badge-success">Đang hoạt động</span>';
                                                }
                                                if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                    echo '<span class="badge badge-warning">Bệnh nhân đã hủy</span>';
                                                }
                                                if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                    echo '<span class="badge badge-danger">Bác sĩ đã hủy</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Prescriptions Section -->
            <?php if ($page === 'prescriptions') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Hồ sơ đơn thuốc</h2>
                        <p class="section-subtitle">Xem tất cả các đơn thuốc</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Tất cả đơn thuốc</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Bác sĩ</th>
                                        <th>Mã BN</th>
                                        <th>Tên bệnh nhân</th>
                                        <th>Mã LH</th>
                                        <th>Ngày</th>
                                        <th>Bệnh</th>
                                        <th>Dị ứng</th>
                                        <th>Đơn thuốc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "select * from prestb order by appdate desc";
                                    $result = $pdo->query($query);
                                    while ($row = $result->fetch(PDO::FETCH_BOTH)) {
                                    ?>
                                        <tr>
                                            <td><?php echo $row['doctor']; ?></td>
                                            <td>#<?php echo $row['pid']; ?></td>
                                            <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                            <td>#<?php echo $row['ID']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                            <td><?php echo $row['disease']; ?></td>
                                            <td><?php echo $row['allergy']; ?></td>
                                            <td><?php echo $row['prescription']; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Queries Section -->
            <?php if ($page === 'queries') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Thắc mắc khách hàng</h2>
                        <p class="section-subtitle">Xem tin nhắn từ biểu mẫu liên hệ</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Tin nhắn liên hệ</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../messearch.php" class="form-inline">
                                    <input type="text" name="mes_contact" placeholder="Nhập số điện thoại" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="mes_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Tìm kiếm
                                    </button>
                                </form>
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Liên hệ</th>
                                    <th>Tin nhắn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "select * from contact";
                                $result = $pdo->query($query);
                                while ($row = $result->fetch(PDO::FETCH_BOTH)) {
                                ?>
                                    <tr>
                                        <td><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['contact']; ?></td>
                                        <td><?php echo $row['message']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>

            <!-- Medical Records Section -->
            <?php if ($page === 'medical-records') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-file-medical"></i> Hồ sơ bệnh án</h2>
                        <p class="section-subtitle">Quản lý và theo dõi hồ sơ bệnh án của tất cả bệnh nhân</p>
                    </div>

                    <!-- Search and Filter -->
                    <div class="data-table-container mb-4">
                        <div class="p-4" style="background: #f8fafc; border-radius: 8px;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <label style="font-weight: 600; color: #1f2937; display: block; margin-bottom: 8px;">Tìm kiếm bệnh nhân</label>
                                    <input type="text" id="searchPatient" class="form-control" placeholder="Tên hoặc số điện thoại..." style="border-radius: 6px;">
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #1f2937; display: block; margin-bottom: 8px;">Bác sĩ</label>
                                    <select id="filterDoctor" class="form-control" style="border-radius: 6px;">
                                        <option value="">-- Tất cả bác sĩ --</option>
                                        <?php
                                        $doctors = $pdo->query("SELECT id, fullname FROM doctb ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($doctors as $doc) {
                                            echo "<option value='" . $doc['id'] . "'>" . $doc['fullname'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #1f2937; display: block; margin-bottom: 8px;">Trạng thái</label>
                                    <select id="filterStatus" class="form-control" style="border-radius: 6px;">
                                        <option value="">-- Tất cả --</option>
                                        <option value="completed">Hoàn thành</option>
                                        <option value="pending">Đang chờ</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Records Table -->
                    <div class="data-table-container">
                        <div class="data-table-header" style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);">
                            <h3 class="data-table-title" style="color: white;"><i class="fas fa-list"></i> Danh sách hồ sơ bệnh án</h3>
                        </div>
                        <table class="table table-striped" style="margin: 0;">
                            <thead style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                                <tr>
                                    <th style="color: #374151; font-weight: 700; padding: 15px;">Bệnh nhân</th>
                                    <th style="color: #374151; font-weight: 700; padding: 15px;">Bác sĩ</th>
                                    <th style="color: #374151; font-weight: 700; padding: 15px;">Chẩn đoán</th>
                                    <th style="color: #374151; font-weight: 700; padding: 15px;">Ngày tạo</th>
                                    <th style="color: #374151; font-weight: 700; padding: 15px;">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="recordsTableBody">
                                <?php
                                try {
                                    $query = "
                                        SELECT mr.*, 
                                               p.fname, p.lname, p.contact,
                                               d.fullname as doctor_name
                                        FROM medical_records mr
                                        LEFT JOIN patreg p ON mr.patient_id = p.pid
                                        LEFT JOIN doctb d ON mr.doctor_id = d.id
                                        ORDER BY mr.created_at DESC
                                    ";
                                    $result = $pdo->query($query);
                                    $records = $result->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($records)) {
                                        echo "<tr><td colspan='5' style='text-align: center; padding: 40px; color: #6b7280;'>
                                                <i class='fas fa-inbox' style='font-size: 32px; margin-bottom: 10px; display: block;'></i>
                                                Không có hồ sơ bệnh án
                                            </td></tr>";
                                    } else {
                                        foreach ($records as $record) {
                                            $status_class = $record['status'] === 'completed' ? 'badge-success' : 'badge-warning';
                                            $status_text = $record['status'] === 'completed' ? 'Hoàn thành' : 'Đang chờ';
                                            echo "<tr class='medical-record-row' data-doctor='" . $record['doctor_id'] . "' data-status='" . $record['status'] . "' data-patient='" . strtolower($record['fname'] . ' ' . $record['lname']) . "'>";
                                            echo "<td style='padding: 15px;'>" . $record['fname'] . " " . $record['lname'] . "<br><small style='color: #6b7280;'>" . $record['contact'] . "</small></td>";
                                            echo "<td style='padding: 15px;'>" . ($record['doctor_name'] ?? 'Chưa xác định') . "</td>";
                                            echo "<td style='padding: 15px;'>" . substr($record['diagnosis'] ?? 'N/A', 0, 50) . "...</td>";
                                            echo "<td style='padding: 15px;'>" . date('d/m/Y H:i', strtotime($record['created_at'])) . "</td>";
                                            echo "<td style='padding: 15px;'><span class='badge " . $status_class . "'>" . $status_text . "</span></td>";
                                            echo "</tr>";
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: #ef4444;'>Lỗi: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon primary">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">Tổng hồ sơ</div>
                                    <div class="stat-value">
                                        <?php
                                        $query = $pdo->query("SELECT COUNT(*) as total FROM medical_records");
                                        $row = $query->fetch(PDO::FETCH_ASSOC);
                                        echo $row['total'];
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">Hoàn thành</div>
                                    <div class="stat-value">
                                        <?php
                                        $query = $pdo->query("SELECT COUNT(*) as total FROM medical_records WHERE status = 'completed'");
                                        $row = $query->fetch(PDO::FETCH_ASSOC);
                                        echo $row['total'];
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">Đang chờ</div>
                                    <div class="stat-value">
                                        <?php
                                        $query = $pdo->query("SELECT COUNT(*) as total FROM medical_records WHERE status = 'pending'");
                                        $row = $query->fetch(PDO::FETCH_ASSOC);
                                        echo $row['total'];
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon info">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-label">Bệnh nhân</div>
                                    <div class="stat-value">
                                        <?php
                                        $query = $pdo->query("SELECT COUNT(DISTINCT patient_id) as total FROM medical_records");
                                        $row = $query->fetch(PDO::FETCH_ASSOC);
                                        echo $row['total'];
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php } ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Password Matching
        function checkPassword() {
            if (document.getElementById('dpassword').value == document.getElementById('cdpassword').value) {
                document.getElementById('message').style.color = '#10B981';
                document.getElementById('message').innerHTML = '✓ Khớp';
            } else {
                document.getElementById('message').style.color = '#EF4444';
                document.getElementById('message').innerHTML = '✗ Không khớp';
            }
        }

        // Medical Records Filter
        function filterMedicalRecords() {
            const searchText = document.getElementById('searchPatient').value.toLowerCase();
            const doctorId = document.getElementById('filterDoctor').value;
            const status = document.getElementById('filterStatus').value;

            const rows = document.querySelectorAll('.medical-record-row');

            rows.forEach(row => {
                let show = true;

                // Filter by patient name
                if (searchText && !row.dataset.patient.includes(searchText)) {
                    show = false;
                }

                // Filter by doctor
                if (doctorId && row.dataset.doctor != doctorId) {
                    show = false;
                }

                // Filter by status
                if (status && row.dataset.status !== status) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchPatient');
            const doctorFilter = document.getElementById('filterDoctor');
            const statusFilter = document.getElementById('filterStatus');

            if (searchInput) searchInput.addEventListener('keyup', filterMedicalRecords);
            if (doctorFilter) doctorFilter.addEventListener('change', filterMedicalRecords);
            if (statusFilter) statusFilter.addEventListener('change', filterMedicalRecords);
        });
    </script>
</body>

</html>