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

// Create services table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT PRIMARY KEY AUTO_INCREMENT,
        service_name VARCHAR(255) NOT NULL,
        description LONGTEXT,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0,
        status ENUM('0', '1') NOT NULL DEFAULT '1' COMMENT '0: Inactive, 1: Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by VARCHAR(255),
        INDEX idx_service_name (service_name),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Insert sample data if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM services");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['cnt'] == 0) {
        $pdo->exec("INSERT INTO services (service_name, description, price, status, created_by) VALUES
            ('Khám tổng quát', 'Khám sức khỏe tổng quát đầy đủ', 500000, '1', 'System'),
            ('Siêu âm', 'Siêu âm chẩn đoán', 300000, '1', 'System'),
            ('Xét nghiệm máu', 'Xét nghiệm máu toàn bộ', 350000, '1', 'System'),
            ('Chụp X-quang', 'Chụp X-quang các bộ phận', 400000, '1', 'System'),
            ('Nhổ răng', 'Dịch vụ nhổ răng', 200000, '1', 'System')");
    }
} catch (PDOException $e) {
    error_log("Services table creation error: " . $e->getMessage());
}
// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'appointments', 'documents', 'patient_history', 'medicine_inventory', 'service_pricing');
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
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'success', 'Thuốc đã được cập nhật.');
    } catch (PDOException $e) {
        error_log("Update medicine error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Lỗi khi cập nhật thuốc.');
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
        
        $stmt = $pdo->prepare("SELECT quantity FROM medicines WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $current_quantity = $stmt->fetchColumn();
        
        $new_quantity = $current_quantity + $quantity_change;
        if ($new_quantity < 0) {
            $pdo->rollBack();
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=medicine_inventory', 'error', 'Số lượng không thể âm.');
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE medicines SET quantity = :quantity WHERE id = :id");
        $stmt->execute([':quantity' => $new_quantity, ':id' => $id]);
        
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

// Handle service pricing operations
if (isset($_POST['add_service'])) {
    try {
        $service_name = $_POST['service_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? '1';

        if (empty($service_name) || $price < 0) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'error', 'Vui lòng điền đầy đủ thông tin.');
        }

        $stmt = $pdo->prepare("INSERT INTO services (service_name, description, price, status) VALUES (:name, :desc, :price, :status)");
        $stmt->execute([
            ':name' => $service_name,
            ':desc' => $description,
            ':price' => $price,
            ':status' => $status
        ]);

        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'success', 'Dịch vụ đã được thêm thành công.');
    } catch (PDOException $e) {
        error_log("Add service error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'error', 'Lỗi khi thêm dịch vụ.');
    }
}

if (isset($_POST['edit_service'])) {
    try {
        $service_id = $_POST['service_id'] ?? '';
        $service_name = $_POST['service_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? '1';

        if (empty($service_id) || empty($service_name) || $price < 0) {
            redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'error', 'Vui lòng điền đầy đủ thông tin.');
        }

        $stmt = $pdo->prepare("UPDATE services SET service_name = :name, description = :desc, price = :price, status = :status WHERE id = :id");
        $stmt->execute([
            ':id' => $service_id,
            ':name' => $service_name,
            ':desc' => $description,
            ':price' => $price,
            ':status' => $status
        ]);

        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'success', 'Dịch vụ đã được cập nhật.');
    } catch (PDOException $e) {
        error_log("Edit service error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'error', 'Lỗi khi cập nhật dịch vụ.');
    }
}

if (isset($_GET['delete_service'])) {
    try {
        $service_id = $_GET['delete_service'];
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id");
        $stmt->execute([':id' => $service_id]);
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'success', 'Dịch vụ đã được xóa thành công.');
    } catch (PDOException $e) {
        error_log("Delete service error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=service_pricing', 'error', 'Lỗi khi xóa dịch vụ.');
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
        /* Dropdown Menu Styling */
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
            background: #f0f9ff;
            color: #0891b2;
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

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8f0f7 100%) !important;
            border: 2px solid #0891b2 !important;
            border-radius: 50% !important;
            width: 45px !important;
            height: 45px !important;
            cursor: pointer !important;
            color: #0891b2 !important;
            font-size: 1.2rem !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-right: 1rem !important;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2) !important;
            position: relative !important;
            overflow: hidden !important;
            outline: none !important;
        }

        .dark-mode-toggle:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 20px rgba(8, 145, 178, 0.35) !important;
            border-color: #14b8a6 !important;
        }
        }

        .dark-mode-toggle:active {
            transform: translateY(-1px);
        }

        .dark-mode-toggle i {
            transition: transform 0.4s ease;
        }

        /* Hide desktop button on mobile */
        .desktop-dark-mode-toggle {
            display: flex;
        }

        @media (max-width: 768px) {
            .desktop-dark-mode-toggle {
                display: none;
            }
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .dark-mode-toggle {
            background: linear-gradient(135deg, #2a2a2a 0%, #333 100%) !important;
            border-color: #14b8a6 !important;
            color: #14b8a6 !important;
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2) !important;
        }

        body.dark-mode .dark-mode-toggle:hover {
            border-color: #06b6d4 !important;
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.35) !important;
            transform: translateY(-3px) !important;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        body.dark-mode .sidebar {
            background-color: #252525;
            border-right: 1px solid #333;
        }

        body.dark-mode .sidebar-menu-link {
            color: #b0b0b0;
        }

        body.dark-mode .sidebar-menu-link:hover,
        body.dark-mode .sidebar-menu-link.active {
            color: #0891b2;
            background-color: #2a2a2a;
        }

        body.dark-mode .top-navbar {
            background-color: #252525;
            border-bottom: 1px solid #333;
        }

        body.dark-mode .navbar-title {
            color: #e0e0e0;
        }

        body.dark-mode .navbar-user-name {
            color: #e0e0e0;
        }

        body.dark-mode .navbar-user-role {
            color: #b0b0b0;
        }

        body.dark-mode .content-section {
            background-color: #1a1a1a;
        }

        body.dark-mode .section-title {
            color: #e0e0e0;
        }

        body.dark-mode .section-subtitle {
            color: #b0b0b0;
        }

        body.dark-mode .stat-card {
            background-color: #252525;
            border: 1px solid #333;
            color: #e0e0e0;
        }

        body.dark-mode .stat-label {
            color: #b0b0b0;
        }

        body.dark-mode .dropdown-menu {
            background-color: #252525;
            border: 1px solid #333;
        }

        body.dark-mode .dropdown-item {
            color: #b0b0b0;
        }

        body.dark-mode .dropdown-item:hover {
            background-color: #2a2a2a;
            color: #0891b2;
        }

        body.dark-mode .dropdown-divider {
            border-top-color: #333;
        }

        body.dark-mode .data-table {
            background-color: #252525;
            color: #e0e0e0;
        }

        body.dark-mode .data-table thead {
            background-color: #2a2a2a;
            color: #e0e0e0;
        }

        body.dark-mode .data-table tbody tr {
            border-bottom: 1px solid #333;
        }

        body.dark-mode .data-table tbody tr:hover {
            background-color: #2a2a2a;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-control:focus,
        body.dark-mode textarea.form-control {
            background-color: #252525;
            color: #e0e0e0;
            border-color: #333;
        }

        body.dark-mode .card {
            background-color: #252525;
            border: 1px solid #333;
        }

        /* Navbar Right Alignment */
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
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
            .mobile-controls {
                display: flex;
            }

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

        /* Mobile Controls Container */
        .mobile-controls {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            gap: 10px;
            align-items: center;
        }

        .mobile-menu-btn {
            background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .mobile-menu-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(8, 145, 178, 0.4);
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

    <!-- jQuery and Bootstrap JS for dropdown functionality -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Dark Mode Function -->
    <script>
        function toggleDarkMode() {
            // Toggle dark mode class on body
            const isDarkMode = document.body.classList.toggle('dark-mode');
            
            // Save preference to localStorage
            localStorage.setItem('darkMode', isDarkMode);
            
            // Update all dark mode buttons
            const darkModeToggles = document.querySelectorAll('#darkModeToggle');
            darkModeToggles.forEach(btn => {
                if (isDarkMode) {
                    btn.innerHTML = '<i class="fas fa-sun"></i>';
                } else {
                    btn.innerHTML = '<i class="fas fa-moon"></i>';
                }
            });
            
            console.log('Dark mode toggled:', isDarkMode);
        }

        // Initialize dark mode from localStorage on page load
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode') === 'true';
            if (darkMode) {
                document.body.classList.add('dark-mode');
                const darkModeToggles = document.querySelectorAll('#darkModeToggle');
                darkModeToggles.forEach(btn => {
                    btn.innerHTML = '<i class="fas fa-sun"></i>';
                });
            }
        });
    </script>
</head>

<body>
    <?php displayMessage(); ?>
    <div class="mobile-controls">
        <button class="dark-mode-toggle" id="darkModeToggle" onclick="toggleDarkMode()" title="Chế độ tối/sáng">
            <i class="fas fa-moon"></i>
        </button>
        <button class="mobile-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
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
                    <a href="?page=service_pricing" class="sidebar-menu-link <?php echo ($page === 'service_pricing') ? 'active' : ''; ?>">
                        <i class="fas fa-tag sidebar-menu-icon"></i>
                        <span>Giá dịch vụ</span>
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
                    <button class="dark-mode-toggle desktop-dark-mode-toggle" id="darkModeToggle" onclick="toggleDarkMode()" title="Chế độ tối/sáng">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="navbar-user dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="navbarUserDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                            <div class="navbar-user-avatar">
                                <?php echo strtoupper(substr($doctor, 0, 1)); ?>
                            </div>
                            <div class="navbar-user-info">
                                <div class="navbar-user-name">BS. <?php echo $doctor; ?></div>
                                <div class="navbar-user-role">Bác sĩ</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarUserDropdown">
                            <a class="dropdown-item" href="../../index.php">
                                <i class="fas fa-home mr-2"></i> Quay về trang chủ
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
                            </a>
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
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor)");
                                    $stmt->execute([':doctor' => trim($doctor)]);
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
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) AND userStatus = '1' AND doctorStatus = '1'");
                                    $stmt->execute([':doctor' => trim($doctor)]);
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

                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-file-upload"></i>
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
                                    // Debug: Show all appointments (regardless of doctor name)
                                    $debug_stmt = $pdo->prepare("SELECT DISTINCT doctor FROM appointmenttb ORDER BY doctor");
                                    $debug_stmt->execute();
                                    $all_doctors = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Get appointments for this doctor
                                    $stmt = $pdo->prepare("SELECT pid,ID,fname,lname,gender,email,contact,appdate,apptime,userStatus,doctorStatus,doctor FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) ORDER BY appdate DESC, apptime DESC");
                                    $stmt->execute([':doctor' => trim($doctor)]);
                                    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($appointments)) {
                                        echo '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #999;">
                                            Không có lịch hẹn nào<br>
                                            <small style="color: #ccc;">Session doctor: "' . htmlspecialchars($doctor) . '"</small><br>
                                            <small style="color: #ccc;">Doctors in DB: ' . implode(', ', array_column($all_doctors, 'doctor')) . '</small>
                                        </td></tr>';
                                    } else {
                                        foreach ($appointments as $row) {
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
                                                } elseif (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                    echo '<span class="badge badge-warning">Bệnh nhân đã hủy</span>';
                                                } elseif (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                    echo '<span class="badge badge-danger">Bạn đã hủy</span>';
                                                } else {
                                                    echo '<span class="badge badge-info">Chờ xác nhận</span>';
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
                                                <a href="prescribe.php?pid=<?php echo $row['pid']; ?>&ID=<?php echo $row['ID']; ?>&fname=<?php echo $row['fname']; ?>&lname=<?php echo $row['lname']; ?>&appdate=<?php echo $row['appdate']; ?>&apptime=<?php echo $row['apptime']; ?>"
                                                   class="btn btn-sm btn-primary"
                                                   title="Kê đơn thuốc"
                                                   style="white-space: nowrap;">
                                                    <i class="fas fa-prescription"></i> Kê đơn
                                                </a>
                                            </td>
                                        </tr>
                                    <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Prescriptions Section -->
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
                                            $stmt = $pdo->prepare("SELECT DISTINCT pid, pname, pemail FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor)");
                                            $stmt->execute([':doctor' => trim($doctor)]);
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
                                            $stmt = $pdo->prepare("SELECT ID, pid, pname, appdate FROM appointmenttb WHERE TRIM(doctor) = TRIM(:doctor) ORDER BY appdate DESC");
                                            $stmt->execute([':doctor' => trim($doctor)]);
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
                                            // Get patients from appointments or all patients
                                            $stmt = $pdo->prepare("SELECT DISTINCT p.pid, p.pname, p.pemail FROM appointmenttb a 
                                                                   INNER JOIN patienttb p ON a.pid = p.pid 
                                                                   WHERE TRIM(a.doctor) = TRIM(:doctor) 
                                                                   ORDER BY p.pname");
                                            $stmt->execute([':doctor' => trim($doctor)]);
                                            $hasPatients = false;
                                            while ($patient = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $hasPatients = true;
                                                $selected = (isset($_GET['search_pid']) && $_GET['search_pid'] == $patient['pid']) ? 'selected' : '';
                                                echo "<option value='{$patient['pid']}' $selected>{$patient['pname']} ({$patient['pemail']})</option>";
                                            }
                                            
                                            // If no patients found, show all patients
                                            if (!$hasPatients) {
                                                $stmt = $pdo->query("SELECT pid, pname, pemail FROM patienttb ORDER BY pname");
                                                while ($patient = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    $selected = (isset($_GET['search_pid']) && $_GET['search_pid'] == $patient['pid']) ? 'selected' : '';
                                                    echo "<option value='{$patient['pid']}' $selected>{$patient['pname']} ({$patient['pemail']})</option>";
                                                }
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
                                    $stmt->execute([':pid' => $search_pid, ':doctor' => trim($doctor)]);
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
                                    $stmt->execute([':pid' => $search_pid, ':doctor' => $doctor]);
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

            <!-- Service Pricing Section -->
            <?php if ($page === 'service_pricing') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Quản lý giá dịch vụ</h2>
                        <p class="section-subtitle">Cập nhật giá dịch vụ y tế</p>
                    </div>

                    <div class="data-card">
                        <button class="btn btn-primary mb-3" onclick="showAddServiceModal()">
                            <i class="fas fa-plus"></i> Thêm dịch vụ mới
                        </button>

                        <div class="data-table-container">
                            <table class="table table-striped data-table">
                                <thead>
                                    <tr>
                                        <th>Tên dịch vụ</th>
                                        <th>Mô tả</th>
                                        <th>Giá (VNĐ)</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name ASC");
                                    if ($stmt->rowCount() > 0) {
                                        while ($service = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $status = $service['status'] == '1' ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-danger">Tạm dừng</span>';
                                            echo "<tr>";
                                            echo "<td><strong>" . htmlspecialchars($service['service_name']) . "</strong></td>";
                                            echo "<td>" . htmlspecialchars(substr($service['description'] ?? '', 0, 50)) . "...</td>";
                                            echo "<td>" . number_format($service['price'], 0, ',', '.') . " VNĐ</td>";
                                            echo "<td>" . $status . "</td>";
                                            echo "<td>
                                                    <button class='btn btn-sm btn-info' onclick='editService(" . json_encode($service) . ")' title='Sửa'>
                                                        <i class='fas fa-edit'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-danger' onclick='deleteService({$service['id']}, \"" . addslashes($service['service_name']) . "\")' title='Xóa'>
                                                        <i class='fas fa-trash'></i>
                                                    </button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center text-muted'>Chưa có dịch vụ nào</td></tr>";
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

        function showAddServiceModal() {
            document.getElementById('serviceForm').reset();
            document.getElementById('service_id').value = '';
            document.getElementById('serviceModalTitle').textContent = 'Thêm dịch vụ mới';
            document.getElementById('serviceModalButton').textContent = 'Thêm dịch vụ';
            document.getElementById('serviceModalButton').name = 'add_service';
            $('#serviceModal').modal('show');
        }

        function editService(service) {
            document.getElementById('service_id').value = service.id;
            document.getElementById('service_name').value = service.service_name;
            document.getElementById('description').value = service.description || '';
            document.getElementById('price').value = service.price;
            document.getElementById('status').value = service.status;
            document.getElementById('serviceModalTitle').textContent = 'Sửa dịch vụ';
            document.getElementById('serviceModalButton').textContent = 'Cập nhật dịch vụ';
            document.getElementById('serviceModalButton').name = 'edit_service';
            $('#serviceModal').modal('show');
        }

        function deleteService(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa dịch vụ "' + name + '"?')) {
                window.location.href = '?page=service_pricing&delete_service=' + id;
            }
        }
    </script>

    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="serviceModalTitle"><i class="fas fa-plus"></i> Thêm dịch vụ mới</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="serviceForm">
                    <div class="modal-body">
                        <input type="hidden" id="service_id" name="service_id" value="">
                        <div class="form-group">
                            <label>Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" id="service_name" name="service_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Mô tả</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Giá (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" id="price" name="price" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select id="status" name="status" class="form-control">
                                <option value="1">Hoạt động</option>
                                <option value="0">Tạm dừng</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" id="serviceModalButton" name="add_service" class="btn btn-primary">
                            <i class="fas fa-save"></i> Thêm dịch vụ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>