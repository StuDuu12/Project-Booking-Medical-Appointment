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
</head>

<body>
    <?php displayMessage(); ?>
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
                            <div class="navbar-user-name">Dr. <?php echo $doctor; ?></div>
                            <div class="navbar-user-role">Doctor</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Section -->
            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Welcome, Dr. <?php echo $doctor; ?>!</h2>
                        <p class="section-subtitle">Manage your appointments and prescriptions</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Appointments</div>
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
                                <div class="stat-label">Active Appointments</div>
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
                                <div class="stat-label">Prescriptions Issued</div>
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
                                    <h5>View Appointments</h5>
                                    <p class="text-muted mb-0">Manage and view your scheduled appointments</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-6">
                            <a href="?page=prescriptions" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon success">
                                    <i class="fas fa-file-prescription"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Prescription List</h5>
                                    <p class="text-muted mb-0">View all issued prescriptions</p>
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
                        <h2 class="section-title">Patient Appointments</h2>
                        <p class="section-subtitle">Manage your scheduled appointments</p>
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
                            <h3 class="data-table-title">Appointment List</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Appointment ID</th>
                                        <th>Patient Name</th>
                                        <th>Gender</th>
                                        <th>Contact</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                        <th>Prescribe</th>
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
                                                    echo '<span class="badge badge-success">Active</span>';
                                                }
                                                if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                    echo '<span class="badge badge-warning">Cancelled by Patient</span>';
                                                }
                                                if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                    echo '<span class="badge badge-danger">Cancelled by You</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                    <a href="doctor-dashboard.php?ID=<?php echo $row['ID'] ?>&cancel=update"
                                                        onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                        class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                <?php } else {
                                                    echo '<span class="text-muted">Cancelled</span>';
                                                } ?>
                                            </td>
                                            <td>
                                                <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                    <a href="prescribe.php?pid=<?php echo $row['pid'] ?>&ID=<?php echo $row['ID'] ?>&fname=<?php echo $row['fname'] ?>&lname=<?php echo $row['lname'] ?>&appdate=<?php echo $row['appdate'] ?>&apptime=<?php echo $row['apptime'] ?>"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-prescription"></i> Prescribe
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
                        <h2 class="section-title">Prescription History</h2>
                        <p class="section-subtitle">View all issued prescriptions</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Issued Prescriptions</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Appointment ID</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Disease</th>
                                        <th>Allergy</th>
                                        <th>Prescription</th>
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
</body>

</html>