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

// Ensure doctor variable is properly set
if (empty($doctor)) {
    die("Session error: Doctor name not found. Please login again.");
}

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'appointments', 'prescriptions', 'documents', 'patient_history', 'medicine_inventory');
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

// Handle document upload
if (isset($_POST['upload_document'])) {
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $pid = $_POST['pid'];
        $appointment_id = $_POST['appointment_id'] ?? null;
        $description = $_POST['description'] ?? '';

        $file = $_FILES['document_file'];
        $fileName = basename($file['name']);
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];

        // Validate file type (allow common medical document types)
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($fileType, $allowedTypes)) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'error', 'Loại file không được phép. Chỉ cho phép PDF, hình ảnh và tài liệu Word.');
        }

        // Validate file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'error', 'Kích thước file quá lớn. Tối đa 10MB.');
        }

        // Generate unique filename
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('med_doc_') . '.' . $fileExt;
        $uploadPath = '../../uploads/medical_documents/' . $newFileName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO medical_documents (pid, doctor, appointment_id, document_name, file_path, file_type, file_size, description) VALUES (:pid, :doctor, :appointment_id, :document_name, :file_path, :file_type, :file_size, :description)");
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
                redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'success', 'Tài liệu đã được upload thành công.');
            } catch (PDOException $e) {
                error_log("Upload document error: " . $e->getMessage());
                redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'error', 'Lỗi khi lưu thông tin tài liệu.');
            }
        } else {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'error', 'Lỗi khi upload file.');
        }
    } else {
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=documents', 'error', 'Vui lòng chọn file để upload.');
    }
}

// Handle medicine management
if (isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $generic_name = trim($_POST['generic_name']);
    $category = trim($_POST['category']);
    $dosage_form = trim($_POST['dosage_form']);
    $strength = trim($_POST['strength']);
    $manufacturer = trim($_POST['manufacturer']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $expiry_date = $_POST['expiry_date'];
    $description = trim($_POST['description']);

    if (empty($name) || $quantity < 0 || $unit_price < 0) {
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Vui lòng điền đầy đủ thông tin và đảm bảo số lượng và giá không âm.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, category, dosage_form, strength, manufacturer, quantity, unit_price, expiry_date, description, created_by) VALUES (:name, :generic_name, :category, :dosage_form, :strength, :manufacturer, :quantity, :unit_price, :expiry_date, :description, :created_by)");
            $stmt->execute([
                ':name' => $name,
                ':generic_name' => $generic_name,
                ':category' => $category,
                ':dosage_form' => $dosage_form,
                ':strength' => $strength,
                ':manufacturer' => $manufacturer,
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':expiry_date' => $expiry_date,
                ':description' => $description,
                ':created_by' => $doctor
            ]);
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'success', 'Thuốc đã được thêm thành công.');
        } catch (PDOException $e) {
            error_log("Add medicine error: " . $e->getMessage());
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Lỗi khi thêm thuốc.');
        }
    }
}

if (isset($_POST['update_medicine'])) {
    $id = intval($_POST['medicine_id']);
    $name = trim($_POST['name']);
    $generic_name = trim($_POST['generic_name']);
    $category = trim($_POST['category']);
    $dosage_form = trim($_POST['dosage_form']);
    $strength = trim($_POST['strength']);
    $manufacturer = trim($_POST['manufacturer']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $expiry_date = $_POST['expiry_date'];
    $description = trim($_POST['description']);

    if (empty($name) || $quantity < 0 || $unit_price < 0) {
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Vui lòng điền đầy đủ thông tin và đảm bảo số lượng và giá không âm.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE medicines SET name = :name, generic_name = :generic_name, category = :category, dosage_form = :dosage_form, strength = :strength, manufacturer = :manufacturer, quantity = :quantity, unit_price = :unit_price, expiry_date = :expiry_date, description = :description WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':generic_name' => $generic_name,
                ':category' => $category,
                ':dosage_form' => $dosage_form,
                ':strength' => $strength,
                ':manufacturer' => $manufacturer,
                ':quantity' => $quantity,
                ':unit_price' => $unit_price,
                ':expiry_date' => $expiry_date,
                ':description' => $description,
                ':id' => $id
            ]);
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'success', 'Thuốc đã được cập nhật thành công.');
        } catch (PDOException $e) {
            error_log("Update medicine error: " . $e->getMessage());
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Lỗi khi cập nhật thuốc.');
        }
    }
}

if (isset($_POST['delete_medicine'])) {
    $id = intval($_POST['medicine_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = :id");
        $stmt->execute([':id' => $id]);
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'success', 'Thuốc đã được xóa thành công.');
    } catch (PDOException $e) {
        error_log("Delete medicine error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Lỗi khi xóa thuốc.');
    }
}

if (isset($_POST['update_stock'])) {
    $id = intval($_POST['medicine_id']);
    $quantity_change = intval($_POST['quantity_change']);
    $reason = trim($_POST['reason']);

    try {
        $pdo->beginTransaction();
        
        // Get current quantity
        $stmt = $pdo->prepare("SELECT quantity FROM medicines WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $current_quantity = $stmt->fetchColumn();
        
        $new_quantity = $current_quantity + $quantity_change;
        if ($new_quantity < 0) {
            $pdo->rollBack();
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Số lượng không thể âm.');
            exit;
        }
        
        // Update quantity
        $stmt = $pdo->prepare("UPDATE medicines SET quantity = :quantity WHERE id = :id");
        $stmt->execute([':quantity' => $new_quantity, ':id' => $id]);
        
        // Log stock change
        $stmt = $pdo->prepare("INSERT INTO medicine_stock_log (medicine_id, quantity_change, new_quantity, reason, updated_by) VALUES (:medicine_id, :quantity_change, :new_quantity, :reason, :updated_by)");
        $stmt->execute([
            ':medicine_id' => $id,
            ':quantity_change' => $quantity_change,
            ':new_quantity' => $new_quantity,
            ':reason' => $reason,
            ':updated_by' => $doctor
        ]);
        
        $pdo->commit();
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'success', 'Kho đã được cập nhật thành công.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Update stock error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Lỗi khi cập nhật kho.');
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* Fee management styles */
        .fee-info-item {
            margin-bottom: 1rem;
        }

        .fee-label {
            display: block;
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .fee-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
        }

        .current-fee {
            color: #0891b2;
            font-size: 1.3rem;
        }

        .total-revenue {
            color: #28a745;
            font-size: 1.2rem;
        }

        .fee-comparison {
            padding: 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }

        .fee-comparison.text-success {
            background: rgba(40, 167, 69, 0.1);
        }

        .fee-comparison.text-warning {
            background: rgba(255, 193, 7, 0.1);
        }

        .fee-comparison.text-info {
            background: rgba(23, 162, 184, 0.1);
        }

        /* Custom Switch Styles */
        .custom-control-label {
            cursor: pointer;
            user-select: none;
        }

        .custom-switch .custom-control-label::before {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #28a745;
            border-color: #28a745;
        }

        .custom-switch .custom-control-label::after {
            background-color: #fff;
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
                    <a href="?page=documents" class="sidebar-menu-link <?php echo ($page === 'documents') ? 'active' : ''; ?>">
                        <i class="fas fa-file-upload sidebar-menu-icon"></i>
                        <span>Upload tài liệu</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=patient_history" class="sidebar-menu-link <?php echo ($page === 'patient_history') ? 'active' : ''; ?>">
                        <i class="fas fa-history sidebar-menu-icon"></i>
                        <span>Lịch sử bệnh án</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=medicine_inventory" class="sidebar-menu-link <?php echo ($page === 'medicine_inventory') ? 'active' : ''; ?>">
                        <i class="fas fa-pills sidebar-menu-icon"></i>
                        <span>Quản lý kho thuốc</span>
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
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE doctor = :doctor");
                                        $stmt->execute([':doctor' => $doctor]);
                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $row['total'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "0";
                                        error_log("Dashboard stats error: " . $e->getMessage());
                                    }
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
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM appointmenttb WHERE doctor = :doctor AND userStatus = '1' AND doctorStatus = '1'");
                                        $stmt->execute([':doctor' => $doctor]);
                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $row['active'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "0";
                                        error_log("Dashboard stats error: " . $e->getMessage());
                                    }
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
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as pres FROM prestb WHERE doctor = :doctor");
                                        $stmt->execute([':doctor' => $doctor]);
                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $row['pres'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "0";
                                        error_log("Dashboard stats error: " . $e->getMessage());
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng bệnh nhân</div>
                                <div class="stat-value">
                                    <?php
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT pid) as patients FROM appointmenttb WHERE doctor = :doctor");
                                        $stmt->execute([':doctor' => $doctor]);
                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $row['patients'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "0";
                                        error_log("Dashboard stats error: " . $e->getMessage());
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon secondary">
                                <i class="fas fa-file-upload"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tài liệu đã upload</div>
                                <div class="stat-value">
                                    <?php
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as docs FROM medical_documents WHERE doctor = :doctor");
                                        $stmt->execute([':doctor' => $doctor]);
                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo $row['docs'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "0";
                                        error_log("Dashboard stats error: " . $e->getMessage());
                                    }
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

                    <!-- Additional Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="?page=patient_history" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon warning">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Lịch sử bệnh án</h5>
                                    <p class="text-muted mb-0">Tìm kiếm và xem bệnh án bệnh nhân</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-6">
                            <a href="?page=documents" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon info">
                                    <i class="fas fa-file-upload"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Upload tài liệu</h5>
                                    <p class="text-muted mb-0">Upload và quản lý tài liệu y tế</p>
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
                                        <th>Tài liệu</th>
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
                                            <td>
                                                <a href="?page=documents&appointment_id=<?php echo $row['ID']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-file-medical"></i> Xem tài liệu
                                                </a>
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

            <!-- Documents Section -->
            <?php if ($page === 'documents') { 
                $appointment_filter = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : null;
            ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Upload tài liệu y tế</h2>
                        <p class="section-subtitle">
                            <?php if ($appointment_filter) { 
                                // Get appointment details
                                $stmt = $pdo->prepare("SELECT fname, lname, appdate FROM appointmenttb WHERE ID = :id AND doctor = :doctor");
                                $stmt->execute([':id' => $appointment_filter, ':doctor' => $doctor]);
                                $appt = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($appt) {
                                    echo 'Quản lý tài liệu cho lịch hẹn: ' . $appt['fname'] . ' ' . $appt['lname'] . ' - ' . date('d M Y', strtotime($appt['appdate']));
                                } else {
                                    echo 'Quản lý tài liệu y tế cho bệnh nhân';
                                }
                            } else { 
                                echo 'Upload và quản lý tài liệu y tế cho bệnh nhân';
                            } ?>
                        </p>
                        <?php if ($appointment_filter) { ?>
                            <div class="mb-3">
                                <a href="?page=documents" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Quay lại tất cả tài liệu
                                </a>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Upload Form -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Upload tài liệu mới</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data" action="?page=documents">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pid">Chọn bệnh nhân</label>
                                                    <select class="form-control" id="pid" name="pid" required <?php echo $appointment_filter ? 'disabled' : ''; ?>>
                                                        <option value="">-- Chọn bệnh nhân --</option>
                                                        <?php
                                                        $stmt = $pdo->prepare("SELECT pid, fname, lname FROM patreg ORDER BY fname, lname");
                                                        $stmt->execute();
                                                        while ($patient = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            $selected = '';
                                                            if ($appointment_filter) {
                                                                // Get patient from appointment
                                                                $stmt2 = $pdo->prepare("SELECT pid FROM appointmenttb WHERE ID = :id");
                                                                $stmt2->execute([':id' => $appointment_filter]);
                                                                $appt_pid = $stmt2->fetchColumn();
                                                                if ($appt_pid == $patient['pid']) $selected = 'selected';
                                                            }
                                                            echo '<option value="' . $patient['pid'] . '" ' . $selected . '>' . $patient['fname'] . ' ' . $patient['lname'] . ' (#' . $patient['pid'] . ')</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php if ($appointment_filter) { ?>
                                                        <input type="hidden" name="pid" value="<?php 
                                                            $stmt2 = $pdo->prepare("SELECT pid FROM appointmenttb WHERE ID = :id");
                                                            $stmt2->execute([':id' => $appointment_filter]);
                                                            echo $stmt2->fetchColumn();
                                                        ?>">
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="appointment_id">Lịch hẹn (tùy chọn)</label>
                                                    <select class="form-control" id="appointment_id" name="appointment_id" <?php echo $appointment_filter ? 'disabled' : ''; ?>>
                                                        <option value="">-- Không liên kết với lịch hẹn --</option>
                                                        <?php
                                                        $stmt = $pdo->prepare("SELECT ID, pid, fname, lname, appdate FROM appointmenttb WHERE doctor = :doctor ORDER BY appdate DESC");
                                                        $stmt->execute([':doctor' => $doctor]);
                                                        while ($appt = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            $selected = ($appointment_filter == $appt['ID']) ? 'selected' : '';
                                                            echo '<option value="' . $appt['ID'] . '" ' . $selected . '>' . $appt['fname'] . ' ' . $appt['lname'] . ' - ' . date('d M Y', strtotime($appt['appdate'])) . ' (#' . $appt['ID'] . ')</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php if ($appointment_filter) { ?>
                                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment_filter; ?>">
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="document_file">Chọn file</label>
                                            <input type="file" class="form-control-file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" required>
                                            <small class="form-text text-muted">Chấp nhận: PDF, hình ảnh (JPG, PNG, GIF), tài liệu Word. Tối đa 10MB.</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="description">Mô tả (tùy chọn)</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Mô tả về tài liệu này..."></textarea>
                                        </div>
                                        <button type="submit" name="upload_document" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> Upload tài liệu
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uploaded Documents List -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">
                                Tài liệu đã upload
                                <?php if ($appointment_filter) echo ' (cho lịch hẹn này)'; ?>
                            </h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Mã BN</th>
                                        <th>Tên bệnh nhân</th>
                                        <th>Tên tài liệu</th>
                                        <th>Mô tả</th>
                                        <th>Kích thước</th>
                                        <th>Ngày upload</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT md.*, p.fname, p.lname FROM medical_documents md LEFT JOIN patreg p ON md.pid = p.pid WHERE md.doctor = :doctor";
                                    $params = [':doctor' => $doctor];
                                    
                                    if ($appointment_filter) {
                                        $query .= " AND md.appointment_id = :appointment_id";
                                        $params[':appointment_id'] = $appointment_filter;
                                    }
                                    
                                    $query .= " ORDER BY md.uploaded_at DESC";
                                    
                                    $stmt = $pdo->prepare($query);
                                    $stmt->execute($params);
                                    while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                        <tr>
                                            <td>#<?php echo $doc['pid']; ?></td>
                                            <td><?php echo $doc['fname'] . ' ' . $doc['lname']; ?></td>
                                            <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['description'] ?? ''); ?></td>
                                            <td><?php echo number_format($doc['file_size'] / 1024, 1) . ' KB'; ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                                <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" download class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i> Tải
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Patient History Section -->
            <?php if ($page === 'patient_history') { 
                $selected_pid = isset($_GET['pid']) ? $_GET['pid'] : null;
                $patient_info = null;
                $appointments = [];
                $prescriptions = [];
                $documents = [];

                if ($selected_pid) {
                    // Get patient info
                    $stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = :pid");
                    $stmt->execute([':pid' => $selected_pid]);
                    $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($patient_info) {
                        // Get appointments
                        $stmt = $pdo->prepare("SELECT * FROM appointmenttb WHERE pid = :pid AND doctor = :doctor ORDER BY appdate DESC, apptime DESC");
                        $stmt->execute([':pid' => $selected_pid, ':doctor' => $doctor]);
                        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Get prescriptions
                        $stmt = $pdo->prepare("SELECT * FROM prestb WHERE pid = :pid AND doctor = :doctor ORDER BY appdate DESC");
                        $stmt->execute([':pid' => $selected_pid, ':doctor' => $doctor]);
                        $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Get documents
                        $stmt = $pdo->prepare("SELECT * FROM medical_documents WHERE pid = :pid AND doctor = :doctor ORDER BY uploaded_at DESC");
                        $stmt->execute([':pid' => $selected_pid, ':doctor' => $doctor]);
                        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Lịch sử bệnh án bệnh nhân</h2>
                        <p class="section-subtitle">Tìm kiếm và xem lịch sử bệnh án đầy đủ của bệnh nhân</p>
                    </div>

                    <!-- Search Patient -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Tìm kiếm bệnh nhân</h5>
                                </div>
                                <div class="card-body">
                                    <form method="get" action="?page=patient_history">
                                        <input type="hidden" name="page" value="patient_history">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="search_patient">Tìm theo tên hoặc số điện thoại</label>
                                                    <input type="text" class="form-control" id="search_patient" name="search" 
                                                           placeholder="Nhập tên hoặc số điện thoại bệnh nhân..." 
                                                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary btn-block">
                                                        <i class="fas fa-search"></i> Tìm kiếm
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])) { ?>
                                        <div class="mt-3">
                                            <h6>Kết quả tìm kiếm:</h6>
                                            <div class="row">
                                                <?php
                                                $search = $_GET['search'];
                                                try {
                                                    $stmt = $pdo->prepare("SELECT * FROM patreg WHERE fname LIKE ? OR lname LIKE ? OR contact LIKE ? ORDER BY fname, lname");
                                                    $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
                                                    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                } catch (PDOException $e) {
                                                    $search_results = [];
                                                    error_log("Patient search error: " . $e->getMessage());
                                                }
                                                
                                                if (empty($search_results)) {
                                                    echo '<div class="col-12"><p class="text-muted">Không tìm thấy bệnh nhân nào.</p></div>';
                                                } else {
                                                    foreach ($search_results as $patient) {
                                                        echo '<div class="col-md-6 col-lg-4 mb-3">';
                                                        echo '<div class="card h-100">';
                                                        echo '<div class="card-body">';
                                                        echo '<h6 class="card-title">' . $patient['fname'] . ' ' . $patient['lname'] . '</h6>';
                                                        echo '<p class="card-text small text-muted">#' . $patient['pid'] . ' | ' . $patient['contact'] . '</p>';
                                                        echo '<a href="?page=patient_history&pid=' . $patient['pid'] . '" class="btn btn-sm btn-primary">Xem bệnh án</a>';
                                                        echo '</div></div></div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($selected_pid && $patient_info) { ?>
                        <!-- Patient Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user"></i> Thông tin bệnh nhân: <?php echo $patient_info['fname'] . ' ' . $patient_info['lname']; ?>
                                            <a href="?page=patient_history" class="btn btn-light btn-sm float-right">
                                                <i class="fas fa-arrow-left"></i> Quay lại tìm kiếm
                                            </a>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Mã bệnh nhân:</strong> #<?php echo $patient_info['pid']; ?></p>
                                                <p><strong>Họ tên:</strong> <?php echo $patient_info['fname'] . ' ' . $patient_info['lname']; ?></p>
                                                <p><strong>Giới tính:</strong> <?php echo $patient_info['gender']; ?></p>
                                                <p><strong>Email:</strong> <?php echo $patient_info['email']; ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Số điện thoại:</strong> <?php echo $patient_info['contact']; ?></p>
                                                <p><strong>Ngày sinh:</strong> <?php echo $patient_info['date_of_birth'] ? date('d/m/Y', strtotime($patient_info['date_of_birth'])) : 'Chưa cập nhật'; ?></p>
                                                <p><strong>Nhóm máu:</strong> <?php echo $patient_info['blood_group'] ?: 'Chưa cập nhật'; ?></p>
                                                <p><strong>Địa chỉ:</strong> <?php echo $patient_info['address'] ?: 'Chưa cập nhật'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointments History -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Lịch sử lịch hẹn</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($appointments)) { ?>
                                            <p class="text-muted">Chưa có lịch hẹn nào.</p>
                                        <?php } else { ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Mã lịch hẹn</th>
                                                            <th>Ngày</th>
                                                            <th>Giờ</th>
                                                            <th>Trạng thái</th>
                                                            <th>Ghi chú</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($appointments as $appt) { ?>
                                                            <tr>
                                                                <td>#<?php echo $appt['ID']; ?></td>
                                                                <td><?php echo date('d/m/Y', strtotime($appt['appdate'])); ?></td>
                                                                <td><?php echo date('H:i', strtotime($appt['apptime'])); ?></td>
                                                                <td>
                                                                    <?php
                                                                    if ($appt['userStatus'] == 1 && $appt['doctorStatus'] == 1) {
                                                                        echo '<span class="badge badge-success">Hoàn thành</span>';
                                                                    } elseif ($appt['userStatus'] == 0 && $appt['doctorStatus'] == 1) {
                                                                        echo '<span class="badge badge-warning">Bệnh nhân hủy</span>';
                                                                    } elseif ($appt['userStatus'] == 1 && $appt['doctorStatus'] == 0) {
                                                                        echo '<span class="badge badge-danger">Bác sĩ hủy</span>';
                                                                    } else {
                                                                        echo '<span class="badge badge-secondary">Chưa xác nhận</span>';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($appt['notes'] ?? ''); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prescriptions History -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-prescription"></i> Lịch sử đơn thuốc</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($prescriptions)) { ?>
                                            <p class="text-muted">Chưa có đơn thuốc nào.</p>
                                        <?php } else { ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Ngày kê</th>
                                                            <th>Bệnh</th>
                                                            <th>Dị ứng</th>
                                                            <th>Đơn thuốc</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($prescriptions as $pres) { ?>
                                                            <tr>
                                                                <td><?php echo date('d/m/Y', strtotime($pres['appdate'])); ?></td>
                                                                <td><?php echo htmlspecialchars($pres['disease']); ?></td>
                                                                <td><?php echo htmlspecialchars($pres['allergy']); ?></td>
                                                                <td><?php echo htmlspecialchars($pres['prescription']); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Documents -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0"><i class="fas fa-file-medical"></i> Tài liệu y tế</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($documents)) { ?>
                                            <p class="text-muted">Chưa có tài liệu y tế nào.</p>
                                        <?php } else { ?>
                                            <div class="row">
                                                <?php foreach ($documents as $doc) { ?>
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title">
                                                                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($doc['document_name']); ?>
                                                                </h6>
                                                                <?php if ($doc['description']) { ?>
                                                                    <p class="card-text small"><?php echo htmlspecialchars($doc['description']); ?></p>
                                                                <?php } ?>
                                                                <div class="small text-muted mb-2">
                                                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?><br>
                                                                    <i class="fas fa-weight"></i> <?php echo number_format($doc['file_size'] / 1024, 1); ?> KB
                                                                </div>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-info">
                                                                        <i class="fas fa-eye"></i> Xem
                                                                    </a>
                                                                    <a href="../../uploads/medical_documents/<?php echo $doc['file_path']; ?>" download class="btn btn-success">
                                                                        <i class="fas fa-download"></i> Tải
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </section>
            <?php } ?>

            <!-- Medicine Inventory Section -->
            <?php if ($page === 'medicine_inventory') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-pills section-icon"></i>
                            Quản lý kho thuốc
                        </h2>
                        <p class="section-description">
                            Quản lý tồn kho, thêm, sửa, xóa thuốc và theo dõi lịch sử nhập xuất
                        </p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addMedicineModal">
                                <i class="fas fa-plus"></i> Thêm thuốc mới
                            </button>
                            <button type="button" class="btn btn-info ml-2" onclick="location.reload()">
                                <i class="fas fa-sync"></i> Làm mới
                            </button>
                        </div>
                    </div>

                    <!-- Medicine Inventory Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Danh sách thuốc trong kho</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Tên thuốc</th>
                                            <th>Tên gốc</th>
                                            <th>Danh mục</th>
                                            <th>Dạng bào chế</th>
                                            <th>Hàm lượng</th>
                                            <th>Số lượng</th>
                                            <th>Giá (VND)</th>
                                            <th>Hạn sử dụng</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        try {
                                            $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name");
                                            $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            if (count($medicines) > 0) {
                                                foreach ($medicines as $medicine) {
                                                    $expiry_status = '';
                                                    $expiry_class = '';
                                                    
                                                    if ($medicine['expiry_date']) {
                                                        $expiry_date = new DateTime($medicine['expiry_date']);
                                                        $today = new DateTime();
                                                        $days_until_expiry = $today->diff($expiry_date)->days;
                                                        
                                                        if ($expiry_date < $today) {
                                                            $expiry_status = 'Đã hết hạn';
                                                            $expiry_class = 'text-danger';
                                                        } elseif ($days_until_expiry <= 30) {
                                                            $expiry_status = 'Sắp hết hạn (' . $days_until_expiry . ' ngày)';
                                                            $expiry_class = 'text-warning';
                                                        } else {
                                                            $expiry_status = date('d/m/Y', strtotime($medicine['expiry_date']));
                                                            $expiry_class = 'text-success';
                                                        }
                                                    }
                                    ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($medicine['generic_name'] ?: '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($medicine['category']); ?></td>
                                                    <td><?php echo htmlspecialchars($medicine['dosage_form']); ?></td>
                                                    <td><?php echo htmlspecialchars($medicine['strength'] ?: '-'); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $medicine['quantity'] > 10 ? 'success' : ($medicine['quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                            <?php echo $medicine['quantity']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($medicine['unit_price']); ?> VND</td>
                                                    <td class="<?php echo $expiry_class; ?>"><?php echo $expiry_status; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning edit-medicine-btn" 
                                                                data-id="<?php echo $medicine['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($medicine['name']); ?>"
                                                                data-generic-name="<?php echo htmlspecialchars($medicine['generic_name'] ?: ''); ?>"
                                                                data-category="<?php echo htmlspecialchars($medicine['category']); ?>"
                                                                data-dosage-form="<?php echo htmlspecialchars($medicine['dosage_form']); ?>"
                                                                data-strength="<?php echo htmlspecialchars($medicine['strength'] ?: ''); ?>"
                                                                data-manufacturer="<?php echo htmlspecialchars($medicine['manufacturer'] ?: ''); ?>"
                                                                data-quantity="<?php echo $medicine['quantity']; ?>"
                                                                data-unit-price="<?php echo $medicine['unit_price']; ?>"
                                                                data-expiry-date="<?php echo $medicine['expiry_date'] ?: ''; ?>"
                                                                data-description="<?php echo htmlspecialchars($medicine['description'] ?: ''); ?>">
                                                            <i class="fas fa-edit"></i> Sửa
                                                        </button>
                                                        <button class="btn btn-sm btn-info" onclick="updateStock(<?php echo $medicine['id']; ?>, '<?php echo addslashes(htmlspecialchars($medicine['name'])); ?>')">
                                                            <i class="fas fa-plus-minus"></i> Cập nhật kho
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteMedicine(<?php echo $medicine['id']; ?>, '<?php echo addslashes(htmlspecialchars($medicine['name'])); ?>')">
                                                            <i class="fas fa-trash"></i> Xóa
                                                        </button>
                                                    </td>
                                                </tr>
                                    <?php
                                                }
                                            } else {
                                    ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">Chưa có thuốc nào trong kho.</td>
                                                </tr>
                                    <?php
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Get medicines error: " . $e->getMessage());
                                    ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-danger">Lỗi khi tải danh sách thuốc.</td>
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Add Medicine Modal -->
                <div class="modal fade" id="addMedicineModal" tabindex="-1" role="dialog" aria-labelledby="addMedicineModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addMedicineModalLabel">Thêm thuốc mới</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="?page=medicine_inventory">
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
                                                <label>Tên gốc</label>
                                                <input type="text" name="generic_name" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Danh mục</label>
                                                <select name="category" class="form-control" required>
                                                    <option value="Thuốc kháng sinh">Thuốc kháng sinh</option>
                                                    <option value="Thuốc giảm đau">Thuốc giảm đau</option>
                                                    <option value="Thuốc tim mạch">Thuốc tim mạch</option>
                                                    <option value="Thuốc tiêu hóa">Thuốc tiêu hóa</option>
                                                    <option value="Thuốc hô hấp">Thuốc hô hấp</option>
                                                    <option value="Vitamin & Khoáng chất">Vitamin & Khoáng chất</option>
                                                    <option value="Thuốc khác">Thuốc khác</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Dạng bào chế</label>
                                                <select name="dosage_form" class="form-control" required>
                                                    <option value="Viên nén">Viên nén</option>
                                                    <option value="Viên nang">Viên nang</option>
                                                    <option value="Si rô">Si rô</option>
                                                    <option value="Thuốc tiêm">Thuốc tiêm</option>
                                                    <option value="Thuốc mỡ">Thuốc mỡ</option>
                                                    <option value="Thuốc bột">Thuốc bột</option>
                                                    <option value="Dạng khác">Dạng khác</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Hàm lượng</label>
                                                <input type="text" name="strength" class="form-control" placeholder="VD: 500mg, 10ml">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nhà sản xuất</label>
                                                <input type="text" name="manufacturer" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Số lượng *</label>
                                                <input type="number" name="quantity" class="form-control" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Giá đơn vị (VND) *</label>
                                                <input type="number" name="unit_price" class="form-control" min="0" step="100" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Hạn sử dụng</label>
                                                <input type="date" name="expiry_date" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Mô tả</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                    <button type="submit" name="add_medicine" class="btn btn-primary">Thêm thuốc</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Medicine Modal -->
                <div class="modal fade" id="editMedicineModal" tabindex="-1" role="dialog" aria-labelledby="editMedicineModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editMedicineModalLabel">Chỉnh sửa thuốc</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="?page=medicine_inventory">
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
                                                <label>Tên gốc</label>
                                                <input type="text" name="generic_name" id="edit_generic_name" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Danh mục</label>
                                                <select name="category" id="edit_category" class="form-control" required>
                                                    <option value="Thuốc kháng sinh">Thuốc kháng sinh</option>
                                                    <option value="Thuốc giảm đau">Thuốc giảm đau</option>
                                                    <option value="Thuốc tim mạch">Thuốc tim mạch</option>
                                                    <option value="Thuốc tiêu hóa">Thuốc tiêu hóa</option>
                                                    <option value="Thuốc hô hấp">Thuốc hô hấp</option>
                                                    <option value="Vitamin & Khoáng chất">Vitamin & Khoáng chất</option>
                                                    <option value="Thuốc khác">Thuốc khác</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Dạng bào chế</label>
                                                <select name="dosage_form" id="edit_dosage_form" class="form-control" required>
                                                    <option value="Viên nén">Viên nén</option>
                                                    <option value="Viên nang">Viên nang</option>
                                                    <option value="Si rô">Si rô</option>
                                                    <option value="Thuốc tiêm">Thuốc tiêm</option>
                                                    <option value="Thuốc mỡ">Thuốc mỡ</option>
                                                    <option value="Thuốc bột">Thuốc bột</option>
                                                    <option value="Dạng khác">Dạng khác</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Hàm lượng</label>
                                                <input type="text" name="strength" id="edit_strength" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nhà sản xuất</label>
                                                <input type="text" name="manufacturer" id="edit_manufacturer" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Số lượng *</label>
                                                <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Giá đơn vị (VND) *</label>
                                                <input type="number" name="unit_price" id="edit_unit_price" class="form-control" min="0" step="100" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Hạn sử dụng</label>
                                                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Mô tả</label>
                                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                    <button type="submit" name="update_medicine" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Update Stock Modal -->
                <div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog" aria-labelledby="updateStockModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="updateStockModalLabel">Cập nhật kho</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="?page=medicine_inventory">
                                <input type="hidden" name="medicine_id" id="stock_medicine_id">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Thuốc: <span id="stock_medicine_name"></span></label>
                                    </div>
                                    <div class="form-group">
                                        <label>Số lượng thay đổi</label>
                                        <input type="number" name="quantity_change" class="form-control" placeholder="VD: 10 (nhập), -5 (xuất)" required>
                                        <small class="form-text text-muted">Số dương để nhập kho, số âm để xuất kho</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Lý do</label>
                                        <select name="reason" class="form-control" required>
                                            <option value="Nhập kho">Nhập kho</option>
                                            <option value="Xuất sử dụng">Xuất sử dụng</option>
                                            <option value="Hết hạn">Hết hạn</option>
                                            <option value="Hỏng hóc">Hỏng hóc</option>
                                            <option value="Điều chuyển">Điều chuyển</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                    <button type="submit" name="update_stock" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

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

            // Update switch labels dynamically
            const switches = document.querySelectorAll('.custom-control-input');
            switches.forEach(function(switchEl) {
                switchEl.addEventListener('change', function() {
                    const label = this.nextElementSibling;
                    if (this.checked) {
                        label.textContent = 'Hoạt động';
                    } else {
                        label.textContent = 'Tạm dừng';
                    }
                });
            });
        });

        // Medicine management functions
        function editMedicine(id, name, genericName, category, dosageForm, strength, manufacturer, quantity, unitPrice, expiryDate, description) {
            console.log('editMedicine called with:', id, name);
            console.log('Modal element exists:', document.getElementById('editMedicineModal') !== null);
            
            // Populate form fields
            document.getElementById('edit_medicine_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_generic_name').value = genericName;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_dosage_form').value = dosageForm;
            document.getElementById('edit_strength').value = strength;
            document.getElementById('edit_manufacturer').value = manufacturer;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_unit_price').value = unitPrice;
            document.getElementById('edit_expiry_date').value = expiryDate;
            document.getElementById('edit_description').value = description;
            console.log('About to show modal');
            // Try jQuery modal first
            if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                console.log('Using jQuery modal');
                $('#editMedicineModal').modal('show');
                console.log('jQuery modal shown');
            } else {
                console.log('jQuery not available, using simple display');
                const modal = document.getElementById('editMedicineModal');
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    console.log('Simple modal shown');
                }
            }
                    modalDialog.style.setProperty('z-index', '1055', 'important');
                    modalDialog.style.setProperty('position', 'relative', 'important');
                    modalDialog.style.setProperty('pointer-events', 'auto', 'important');
                    modalDialog.style.setProperty('top', '50%', 'important');
                    modalDialog.style.setProperty('left', '50%', 'important');
                    modalDialog.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
                    modalDialog.style.setProperty('margin', '0', 'important');
                    
                    const modalContent = modalDialog.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.style.setProperty('z-index', '1060', 'important');
                        modalContent.style.setProperty('pointer-events', 'auto', 'important');
                        
                        // Ensure all form elements have pointer-events
                        const formElements = modalContent.querySelectorAll('input, select, textarea, button');
                        formElements.forEach(el => {
                            el.style.setProperty('pointer-events', 'auto', 'important');
                        });
                    }
                }
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Add backdrop
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.style.position = 'fixed';
                    backdrop.style.top = '0';
                    backdrop.style.left = '0';
                    backdrop.style.width = '100%';
                    backdrop.style.height = '100%';
                    backdrop.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    backdrop.style.zIndex = '1020';
                    backdrop.style.setProperty('z-index', '1020', 'important');
                    document.body.appendChild(backdrop);
                    
                    // Add click handler to close modal
                    backdrop.addEventListener('click', function(e) {
                        // Only close if clicked on backdrop, not on modal
                        if (e.target === backdrop) {
                            hideModal();
                        }
                    });
                }
                
                // Add close button handler
                const closeBtn = modal.querySelector('[data-dismiss="modal"]');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        hideModal();
                    });
                }
                
                // Focus trap for accessibility
                const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstFocusable = focusableElements[0];
                const lastFocusable = focusableElements[focusableElements.length - 1];
                
                modal.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hideModal();
                    }
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstFocusable) {
                                lastFocusable.focus();
                                e.preventDefault();
                            }
                        } else {
                            if (document.activeElement === lastFocusable) {
                                firstFocusable.focus();
                                e.preventDefault();
                            }
                        }
                    }
                console.log('Found focusable elements:', focusableElements.length);
                
                // Focus first focusable element
                if (firstFocusable) {
                    firstFocusable.focus();
                    console.log('Focused first element:', firstFocusable.tagName);
                }
                
                console.log('Modal shown successfully');
                console.log('Modal display:', modal.style.display);
                console.log('Modal z-index:', modal.style.zIndex);
            } else {
                console.error('Modal element not found');
            }
            
            function hideModal() {
                const modal = document.getElementById('editMedicineModal');
                const backdrop = document.querySelector('.modal-backdrop');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }

        // Event listener for edit medicine buttons
        console.log('Setting up edit buttons immediately');
        
        // Use event delegation for dynamically loaded content
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.edit-medicine-btn');
            if (button) {
                console.log('Edit button clicked via delegation');
                console.log('Button data-id:', button.getAttribute('data-id'));
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const genericName = button.getAttribute('data-generic-name');
                const category = button.getAttribute('data-category');
                const dosageForm = button.getAttribute('data-dosage-form');
                const strength = button.getAttribute('data-strength');
                const manufacturer = button.getAttribute('data-manufacturer');
                const quantity = button.getAttribute('data-quantity');
                const unitPrice = button.getAttribute('data-unit-price');
                const expiryDate = button.getAttribute('data-expiry-date');
                const description = button.getAttribute('data-description');

                editMedicine(id, name, genericName, category, dosageForm, strength, manufacturer, quantity, unitPrice, expiryDate, description);
            }
        });

        function updateStock(id, name) {
            document.getElementById('stock_medicine_id').value = id;
            document.getElementById('stock_medicine_name').textContent = name;
            $('#updateStockModal').modal('show');
        }

        function deleteMedicine(id, name) {
            if (confirm('Bạn có chắc muốn xóa thuốc "' + name + '"? Hành động này không thể hoàn tác.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?page=medicine_inventory';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'medicine_id';
                idInput.value = id;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'delete_medicine';
                actionInput.value = '1';

                form.appendChild(idInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>
