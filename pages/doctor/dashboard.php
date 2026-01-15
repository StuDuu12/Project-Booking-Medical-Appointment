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

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'appointments', 'prescriptions');
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

if (isset($_GET['cancel'])) {
    try {
        $stmt = $pdo->prepare("UPDATE appointmenttb SET doctorStatus='0' WHERE ID = :id");
        $stmt->execute([':id' => $_GET['ID']]);
        redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Your appointment successfully cancelled');
    } catch (PDOException $e) {
        error_log("Cancel appointment error: " . $e->getMessage());
    }
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Bảng điều khiển Bác sĩ - Bệnh viện Global</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        /* Enhanced Responsive Styles */

        /* Ensure main content is flexible */
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

        /* Make tables responsive */
        .data-table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            min-width: 900px;
            white-space: nowrap;
        }

        /* Responsive buttons */
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            white-space: nowrap;
        }

        /* Stats grid responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }

        /* Navbar responsive */
        .top-navbar {
            padding: 1rem 1.5rem;
            flex-wrap: wrap;
        }

        .navbar-title {
            font-size: 1.25rem;
        }

        /* Search form responsive */
        .search-box-form .input-group {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Table text wrapping for smaller screens */
        @media (max-width: 1200px) {
            .data-table {
                font-size: 0.9rem;
            }
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

            .content-section {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }

            .top-navbar {
                padding: 1rem;
            }

            .navbar-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
            }

            .navbar-user-info {
                display: none;
            }

            .section-title {
                font-size: 1.3rem !important;
            }

            .section-subtitle {
                font-size: 0.9rem;
            }

            .data-table {
                font-size: 0.75rem;
                min-width: 800px;
            }

            .data-table th,
            .data-table td {
                padding: 6px 4px !important;
            }

            .btn-sm {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 0.3rem 0.5rem;
            }

            /* Make search input full width */
            .search-box-form .form-control {
                font-size: 0.9rem !important;
            }

            .search-box-form .btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }

            /* Adjust data table title */
            .data-table-title {
                font-size: 1.1rem;
            }

            /* Row spacing */
            .row.mt-4 {
                margin-top: 1rem !important;
            }

            .col-md-6 {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .content-section {
                padding: 0.75rem;
            }

            .stat-card {
                padding: 1rem !important;
            }

            .stat-value {
                font-size: 1.5rem !important;
            }

            .stat-label {
                font-size: 0.85rem;
            }

            .section-title {
                font-size: 1.1rem !important;
            }

            .section-subtitle {
                font-size: 0.85rem;
            }

            .data-table {
                font-size: 0.7rem;
                min-width: 700px;
            }

            .btn-sm {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
            }

            .btn-sm i {
                font-size: 0.7rem;
            }

            .badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.4rem;
            }

            /* Mobile menu button adjustment */
            .mobile-menu-btn {
                top: 15px;
                left: 15px;
                width: 40px;
                height: 40px;
            }

            .top-navbar {
                padding: 0.75rem;
                padding-left: 60px;
            }

            .navbar-title {
                font-size: 1rem;
            }

            .navbar-user-avatar {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
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

        /* Ensure no horizontal overflow */
        body {
            overflow-x: hidden;
        }

        .dashboard-container {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-user-md"></i>
                </div>
                <div>
                    <h1 class="sidebar-title">Bệnh viện Global</h1>
                    <div class="sidebar-subtitle">Cổng Bác sĩ</div>
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
                    <a href="?page=appointments" class="sidebar-menu-link <?php echo ($page === 'appointments') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt sidebar-menu-icon"></i>
                        <span>Lịch hẹn</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=prescriptions" class="sidebar-menu-link <?php echo ($page === 'prescriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i>
                        <span>Danh sách đơn thuốc</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="medical-records.php" class="sidebar-menu-link">
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
                    <h1 class="navbar-title">Bảng điều khiển Bác sĩ</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user">
                        <div class="navbar-user-avatar">
                            <?php echo strtoupper(substr($doctor, 0, 1)); ?>
                        </div>
                        <div class="navbar-user-info">
                            <div class="navbar-user-name">BS. <?php echo $doctor; ?></div>
                            <div class="navbar-user-role">Bác sĩ</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Section -->
            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Xin chào, BS. <?php echo $doctor; ?>!</h2>
                        <p class="section-subtitle">Quản lý lịch hẹn và đơn thuốc của bạn</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng lịch hẹn</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE doctor = :doctor");
                                    $stmt->execute([':doctor' => $doctor]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Lịch hẹn đang hoạt động</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM appointmenttb WHERE doctor = :doctor AND userStatus = '1' AND doctorStatus = '1'");
                                    $stmt->execute([':doctor' => $doctor]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['active'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Đơn thuốc đã kê</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as pres FROM prestb WHERE doctor = :doctor");
                                    $stmt->execute([':doctor' => $doctor]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['pres'];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="?page=appointments" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon primary">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Xem lịch hẹn</h5>
                                    <p class="text-muted mb-0">Quản lý và xem các lịch hẹn đã đặt</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-6">
                            <a href="?page=prescriptions" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon success">
                                    <i class="fas fa-file-prescription"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Danh sách đơn thuốc</h5>
                                    <p class="text-muted mb-0">Xem tất cả các đơn thuốc đã kê</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Appointments Section -->
            <?php if ($page === 'appointments') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Lịch hẹn bệnh nhân</h2>
                        <p class="section-subtitle">Quản lý các lịch hẹn đã đặt của bạn</p>
                    </div>

                    <!-- Search Box -->
                    <div class="mb-4">
                        <form method="post" action="search.php" class="search-box-form">
                            <div class="input-group" style="max-width: 500px;">
                                <input type="text"
                                    class="form-control"
                                    placeholder="Tìm bệnh nhân theo số điện thoại..."
                                    name="contact"
                                    style="border-left: none; padding-left: 0; font-size: 0.95rem;">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" style="background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%); border: none;">
                                        <i class="fas fa-search"></i> Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Danh sách lịch hẹn</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Mã BN</th>
                                        <th>Mã lịch hẹn</th>
                                        <th>Tên bệnh nhân</th>
                                        <th>Giới tính</th>
                                        <th>Liên hệ</th>
                                        <th>Ngày</th>
                                        <th>Giờ</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                        <th>Kê đơn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT pid,ID,fname,lname,gender,email,contact,appdate,apptime,userStatus,doctorStatus FROM appointmenttb WHERE doctor = :doctor ORDER BY appdate DESC, apptime DESC");
                                    $stmt->execute([':doctor' => $doctor]);
                                    while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                    ?>
                                        <tr>
                                            <td>#<?php echo $row['pid']; ?></td>
                                            <td>#<?php echo $row['ID']; ?></td>
                                            <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                            <td><?php echo $row['gender']; ?></td>
                                            <td><?php echo $row['contact']; ?></td>
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
                                                    echo '<span class="badge badge-danger">Bạn đã hủy</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                    <a href="dashboard.php?ID=<?php echo $row['ID'] ?>&cancel=update&page=appointments"
                                                        onclick="return confirm('Bạn có chắc muốn hủy lịch hẹn này?')"
                                                        class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Hủy
                                                    </a>
                                                <?php } else {
                                                    echo '<span class="text-muted">Đã hủy</span>';
                                                } ?>
                                            </td>
                                            <td>
                                                <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                    <a href="prescribe.php?pid=<?php echo $row['pid'] ?>&ID=<?php echo $row['ID'] ?>&fname=<?php echo $row['fname'] ?>&lname=<?php echo $row['lname'] ?>&appdate=<?php echo $row['appdate'] ?>&apptime=<?php echo $row['apptime'] ?>"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-prescription"></i> Kê đơn
                                                    </a>
                                                <?php } else {
                                                    echo '<span class="text-muted">-</span>';
                                                } ?>
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
                        <h2 class="section-title">Lịch sử đơn thuốc</h2>
                        <p class="section-subtitle">Xem tất cả các đơn thuốc đã kê</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Đơn thuốc đã kê</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Mã BN</th>
                                        <th>Tên bệnh nhân</th>
                                        <th>Mã lịch hẹn</th>
                                        <th>Ngày</th>
                                        <th>Giờ</th>
                                        <th>Bệnh</th>
                                        <th>Dị ứng</th>
                                        <th>Đơn thuốc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT pid,fname,lname,ID,appdate,apptime,disease,allergy,prescription FROM prestb WHERE doctor = :doctor ORDER BY appdate DESC");
                                    $stmt->execute([':doctor' => $doctor]);
                                    while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                    ?>
                                        <tr>
                                            <td>#<?php echo $row['pid']; ?></td>
                                            <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                                            <td>#<?php echo $row['ID']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
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
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
        });
    </script>
</body>

</html>