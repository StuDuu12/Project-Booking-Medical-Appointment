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
$allowed_pages = array('dashboard', 'doctors', 'patients', 'appointments', 'prescriptions', 'queries');
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
                        <h2 class="section-title"><i class="fas fa-user-md"></i> Doctor Management</h2>
                        <p class="section-subtitle">Manage doctor accounts - View, Add, and Remove doctors</p>
                    </div>

                    <!-- Add Doctor Card -->
                    <div class="data-table-container mb-4">
                        <div class="data-table-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <h3 class="data-table-title" style="color: white;"><i class="fas fa-user-plus"></i> Add New Doctor</h3>
                        </div>
                        <div class="p-4">
                            <form method="post" action="?page=doctors">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-user"></i> Doctor Name *</label>
                                            <input type="text" class="form-control" name="doctor" placeholder="Enter full name" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-stethoscope"></i> Specialization *</label>
                                            <select name="special" class="form-control" required>
                                                <option value="" disabled selected>Select Specialization</option>
                                                <option value="General">General</option>
                                                <option value="Cardiologist">Cardiologist</option>
                                                <option value="Neurologist">Neurologist</option>
                                                <option value="Pediatrician">Pediatrician</option>
                                                <option value="Dermatologist">Dermatologist</option>
                                                <option value="Orthopedic">Orthopedic</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-envelope"></i> Email ID *</label>
                                            <input type="email" class="form-control" name="demail" placeholder="doctor@example.com" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-money-bill-wave"></i> Consultancy Fees *</label>
                                            <input type="number" class="form-control" name="docFees" placeholder="Enter fees" min="0" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                                            <input type="password" class="form-control" name="dpassword" id="dpassword" onkeyup="checkPassword()" placeholder="Enter password" required>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-lock"></i> Confirm Password *</label>
                                            <input type="password" class="form-control" name="cdpassword" id="cdpassword" onkeyup="checkPassword()" placeholder="Confirm password" required>
                                            <small id="message"></small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" name="docsub" class="btn btn-success btn-lg">
                                            <i class="fas fa-user-plus"></i> Add Doctor
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Doctor List Card -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title"><i class="fas fa-list"></i> Doctor List</h3>
                            <div class="data-table-actions">
                                <span class="badge badge-info">
                                    <?php
                                    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM doctb");
                                    $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    echo $count . ' doctors';
                                    ?>
                                </span>
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>Email</th>
                                    <th>Consultancy Fees</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM doctb ORDER BY username ASC";
                                $result = $pdo->query($query);
                                $serial = 1;
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                    <tr>
                                        <td><?php echo $serial++; ?></td>
                                        <td>
                                            <strong><i class="fas fa-user-md text-primary"></i> Dr. <?php echo htmlspecialchars($row['username']); ?></strong>
                                        </td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($row['spec']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><strong>₹<?php echo htmlspecialchars($row['docFees']); ?></strong></td>
                                        <td>
                                            <form method="post" action="?page=doctors" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete Dr. <?php echo htmlspecialchars($row['username']); ?>?');">
                                                <input type="hidden" name="demail" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                <button type="submit" name="docsub1" class="btn btn-danger btn-sm" title="Delete Doctor">
                                                    <i class="fas fa-trash-alt"></i> Delete
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
                        <h2 class="section-title">Patient Records</h2>
                        <p class="section-subtitle">View registered patients</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Patient List</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../patientsearch.php" class="form-inline">
                                    <input type="text" name="patient_contact" placeholder="Enter Contact" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="patient_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Contact</th>
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
                        <h2 class="section-title">Appointment Details</h2>
                        <p class="section-subtitle">View all scheduled appointments</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">All Appointments</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../appsearch.php" class="form-inline">
                                    <input type="text" name="app_contact" placeholder="Enter Contact" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="app_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>App ID</th>
                                        <th>Patient Name</th>
                                        <th>Contact</th>
                                        <th>Doctor</th>
                                        <th>Fees</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
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
                                                    echo '<span class="badge badge-success">Active</span>';
                                                }
                                                if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                    echo '<span class="badge badge-warning">Cancelled by Patient</span>';
                                                }
                                                if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                    echo '<span class="badge badge-danger">Cancelled by Doctor</span>';
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
                        <h2 class="section-title">Prescription Records</h2>
                        <p class="section-subtitle">View all medical prescriptions</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">All Prescriptions</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>App ID</th>
                                        <th>Date</th>
                                        <th>Disease</th>
                                        <th>Allergy</th>
                                        <th>Prescription</th>
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
                        <h2 class="section-title">Customer Queries</h2>
                        <p class="section-subtitle">View messages from contact form</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Contact Messages</h3>
                            <div class="data-table-actions">
                                <form method="post" action="../../messearch.php" class="form-inline">
                                    <input type="text" name="mes_contact" placeholder="Enter Contact" class="form-control form-control-sm mr-2">
                                    <button type="submit" name="mes_search_submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Message</th>
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
                document.getElementById('message').innerHTML = '✓ Matched';
            } else {
                document.getElementById('message').style.color = '#EF4444';
                document.getElementById('message').innerHTML = '✗ Not Matching';
            }
        }
    </script>
</body>

</html>