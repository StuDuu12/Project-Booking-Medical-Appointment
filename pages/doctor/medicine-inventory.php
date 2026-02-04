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
$stmt = $pdo->prepare("SELECT username, fullname FROM doctb WHERE username = :doctor");
$stmt->execute([':doctor' => $doctor]);
$doc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_username = $doc_info['username'] ?? '';

// Xử lý thêm thuốc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine'])) {
    $name = trim($_POST['medicine_name']);
    $category = trim($_POST['category']);
    $dosage = trim($_POST['dosage']);
    $unit_price = floatval($_POST['unit_price']);
    $quantity = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'];
    $manufacturer = trim($_POST['manufacturer']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($category) || $quantity < 0) {
        redirectWithMessage('medicine-inventory.php', 'error', 'Vui lòng điền đầy đủ thông tin bắt buộc!');
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO medicines (name, category, dosage_form, unit_price, quantity, expiry_date, manufacturer, description, created_by, created_at)
            VALUES (:name, :category, :dosage, :unit_price, :quantity, :expiry_date, :manufacturer, :description, :created_by, NOW())
        ");
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':dosage' => $dosage,
            ':unit_price' => $unit_price,
            ':quantity' => $quantity,
            ':expiry_date' => $expiry_date,
            ':manufacturer' => $manufacturer,
            ':description' => $description,
            ':created_by' => $doctor_username
        ]);
        redirectWithMessage('medicine-inventory.php', 'success', 'Thêm thuốc thành công!');
    } catch (PDOException $e) {
        redirectWithMessage('medicine-inventory.php', 'error', 'Lỗi khi thêm thuốc: ' . $e->getMessage());
    }
}

// Xử lý cập nhật thuốc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medicine'])) {
    $id = intval($_POST['medicine_id']);
    $name = trim($_POST['medicine_name']);
    $category = trim($_POST['category']);
    $dosage = trim($_POST['dosage']);
    $unit_price = floatval($_POST['unit_price']);
    $quantity = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'];
    $manufacturer = trim($_POST['manufacturer']);
    $description = trim($_POST['description']);

    try {
        $stmt = $pdo->prepare("
            UPDATE medicines 
            SET name = :name, category = :category, dosage_form = :dosage, 
                unit_price = :unit_price, quantity = :quantity, expiry_date = :expiry_date,
                manufacturer = :manufacturer, description = :description, updated_at = NOW()
            WHERE id = :id AND created_by = :created_by
        ");
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':dosage' => $dosage,
            ':unit_price' => $unit_price,
            ':quantity' => $quantity,
            ':expiry_date' => $expiry_date,
            ':manufacturer' => $manufacturer,
            ':description' => $description,
            ':id' => $id,
            ':created_by' => $doctor_username
        ]);
        redirectWithMessage('medicine-inventory.php', 'success', 'Cập nhật thuốc thành công!');
    } catch (PDOException $e) {
        redirectWithMessage('medicine-inventory.php', 'error', 'Lỗi khi cập nhật: ' . $e->getMessage());
    }
}

// Xử lý xóa thuốc
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = :id AND created_by = :created_by");
        $stmt->execute([':id' => $id, ':created_by' => $doctor_username]);
        redirectWithMessage('medicine-inventory.php', 'success', 'Xóa thuốc thành công!');
    } catch (PDOException $e) {
        redirectWithMessage('medicine-inventory.php', 'error', 'Lỗi khi xóa: ' . $e->getMessage());
    }
}

// Xử lý cập nhật số lượng nhanh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = intval($_POST['medicine_id']);
    $quantity = intval($_POST['quantity']);

    try {
        $stmt = $pdo->prepare("UPDATE medicines SET quantity = :quantity, updated_at = NOW() WHERE id = :id AND created_by = :created_by");
        $stmt->execute([':quantity' => $quantity, ':id' => $id, ':created_by' => $doctor_username]);
        redirectWithMessage('medicine-inventory.php', 'success', 'Cập nhật số lượng thành công!');
    } catch (PDOException $e) {
        redirectWithMessage('medicine-inventory.php', 'error', 'Lỗi khi cập nhật số lượng: ' . $e->getMessage());
    }
}

// Lấy thuốc cần sửa
$edit_medicine = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = :id AND created_by = :created_by");
    $stmt->execute([':id' => $edit_id, ':created_by' => $doctor_username]);
    $edit_medicine = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tìm kiếm và lọc
$search_query = '';
$category_filter = '';
$expiry_filter = '';
$search_condition = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $search_condition .= " AND (name LIKE :search OR manufacturer LIKE :search)";
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = $_GET['category'];
    $search_condition .= " AND category = :category";
}

if (isset($_GET['expiry']) && $_GET['expiry'] === 'expiring') {
    $expiry_filter = 'expiring';
    $search_condition .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
}

// Phân trang
$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$records_per_page = 15;
$offset = ($page_num - 1) * $records_per_page;

// Đếm tổng số thuốc
$count_sql = "SELECT COUNT(*) FROM medicines WHERE created_by = :created_by $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(':created_by', $doctor_username, PDO::PARAM_STR);
if ($search_query) {
    $count_stmt->bindValue(':search', "%$search_query%");
}
if ($category_filter) {
    $count_stmt->bindValue(':category', $category_filter);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Lấy danh sách thuốc
$sql = "SELECT * FROM medicines WHERE created_by = :created_by $search_condition ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':created_by', $doctor_username, PDO::PARAM_STR);
if ($search_query) {
    $stmt->bindValue(':search', "%$search_query%");
}
if ($category_filter) {
    $stmt->bindValue(':category', $category_filter);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách danh mục
$categories = ['Thuốc giảm đau', 'Kháng sinh', 'Vitamin', 'Thuốc tim mạch', 'Thuốc tiêu hóa', 'Thuốc da liễu', 'Thuốc thần kinh', 'Thuốc hô hấp', 'Khác'];

// Kiểm tra thuốc sắp hết hạn hoặc hết hàng
function checkMedicineStatus($medicine)
{
    $status = [];
    if ($medicine['quantity'] == 0) {
        $status[] = ['type' => 'danger', 'message' => 'Hết hàng'];
    } elseif ($medicine['quantity'] <= 10) {
        $status[] = ['type' => 'warning', 'message' => 'Sắp hết'];
    }

    if ($medicine['expiry_date']) {
        $expiry = new DateTime($medicine['expiry_date']);
        $today = new DateTime();
        $diff = $today->diff($expiry);

        if ($expiry < $today) {
            $status[] = ['type' => 'danger', 'message' => 'Đã hết hạn'];
        } elseif ($diff->days <= 90) {
            $status[] = ['type' => 'warning', 'message' => 'Sắp hết hạn'];
        }
    }

    return $status;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Kho thuốc - Bệnh viện Global</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

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

        .page-header {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
            padding: 32px;
            border-radius: 20px;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 20px 60px rgba(6, 95, 70, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .page-header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .page-header p {
            position: relative;
            z-index: 1;
            opacity: 0.95;
        }

        .form-card {
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
            border-left: 6px solid #10b981;
        }

        .filter-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #a7f3d0;
        }

        .inventory-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
        }

        .btn-medical {
            background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(5, 150, 105, 0.3);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .btn-medical:hover {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4);
        }

        .table-medicine {
            font-size: 12px;
        }

        .table-medicine thead {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .table-medicine th {
            color: #065f46;
            font-weight: 700;
            border: none;
            padding: 10px 8px;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        .table-medicine td {
            vertical-align: middle;
            padding: 10px 8px;
            font-size: 12px;
        }

        .table-medicine tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid #f0fdf4;
        }

        .table-medicine tbody tr:hover {
            background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #065f46;
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .back-link:hover {
            color: #065f46;
            text-decoration: none;
            transform: translateX(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 8px 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }

        label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .badge {
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 8px;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 4px;
            border: 2px solid #a7f3d0;
            color: #047857;
            font-weight: 600;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #047857, #059669);
            border-color: #047857;
        }

        .pagination .page-link:hover {
            background-color: #d1fae5;
            border-color: #10b981;
        }

        .input-group-sm .form-control {
            padding: 6px 10px;
        }

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
        }
    </style>
</head>

<body>
    <div class="petals-container" id="petals"></div>
    <script>
        function createPetals() {
            const c = document.getElementById('petals');
            for (let i = 0; i < 25; i++) {
                const p = document.createElement('div');
                p.className = 'petal';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDelay = Math.random() * 10 + 's';
                p.style.animationDuration = (8 + Math.random() * 10) + 's';
                c.appendChild(p);
            }
        }
        window.addEventListener('load', createPetals);
    </script>
    <?php displayMessage(); ?>

    <div class="container-lg py-4">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại bảng điều khiển
        </a>

        <!-- Header -->
        <div class="page-header">
            <h1><i class="fas fa-pills mr-3"></i>Quản lý Kho thuốc</h1>
            <p class="mb-0 mt-2">Quản lý thuốc và vật tư y tế</p>
        </div>

        <?php displayMessage(); ?>

        <!-- Form thêm/sửa thuốc -->
        <div class="form-card">
            <h5 class="mb-4">
                <i class="fas fa-<?php echo $edit_medicine ? 'edit' : 'plus-circle'; ?> mr-2" style="color: #d2302c;"></i>
                <?php echo $edit_medicine ? 'Chỉnh sửa Thuốc' : 'Thêm Thuốc Mới'; ?>
            </h5>
            <form method="POST">
                <?php if ($edit_medicine): ?>
                    <input type="hidden" name="medicine_id" value="<?php echo $edit_medicine['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-capsules mr-1"></i>Tên thuốc <span class="text-danger">*</span></label>
                            <input type="text" name="medicine_name" class="form-control"
                                value="<?php echo $edit_medicine ? htmlspecialchars($edit_medicine['name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><i class="fas fa-tags mr-1"></i>Danh mục <span class="text-danger">*</span></label>
                            <select name="category" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>"
                                        <?php echo ($edit_medicine && $edit_medicine['category'] == $cat) ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><i class="fas fa-prescription-bottle mr-1"></i>Dạng bào chế</label>
                            <input type="text" name="dosage_form" class="form-control" placeholder="VD: Viên nén"
                                value="<?php echo $edit_medicine ? htmlspecialchars($edit_medicine['dosage_form']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave mr-1"></i>Giá (VNĐ)</label>
                            <input type="number" name="unit_price" class="form-control" min="0" step="1000"
                                value="<?php echo $edit_medicine ? $edit_medicine['unit_price'] : '0'; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><i class="fas fa-boxes mr-1"></i>Số lượng <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="0" required
                                value="<?php echo $edit_medicine ? $edit_medicine['quantity'] : '0'; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-industry mr-1"></i>Hãng sản xuất</label>
                            <input type="text" name="manufacturer" class="form-control"
                                value="<?php echo $edit_medicine ? htmlspecialchars($edit_medicine['manufacturer']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-calendar-times mr-1"></i>Hạn sử dụng</label>
                            <input type="date" name="expiry_date" class="form-control"
                                value="<?php echo $edit_medicine ? $edit_medicine['expiry_date'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-1"></i>Mô tả</label>
                            <input type="text" name="description" class="form-control" placeholder="Ghi chú thêm..."
                                value="<?php echo $edit_medicine ? htmlspecialchars($edit_medicine['description']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <?php if ($edit_medicine): ?>
                        <a href="medicine-inventory.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-1"></i>Hủy
                        </a>
                    <?php endif; ?>
                    <button type="submit" name="<?php echo $edit_medicine ? 'update_medicine' : 'add_medicine'; ?>"
                        class="btn btn-medical">
                        <i class="fas fa-<?php echo $edit_medicine ? 'save' : 'plus'; ?> mr-1"></i>
                        <?php echo $edit_medicine ? 'Cập nhật' : 'Thêm thuốc'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Inventory List -->
        <div class="inventory-card">
            <!-- Bộ lọc -->
            <div class="filter-card mb-4">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4">
                        <label class="mb-1"><i class="fas fa-search mr-1"></i>Tìm kiếm</label>
                        <input type="text" name="search" class="form-control" placeholder="Tên thuốc hoặc hãng..."
                            value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1"><i class="fas fa-filter mr-1"></i>Danh mục</label>
                        <select name="category" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Hạn sử dụng</label>
                        <select name="expiry" class="form-control">
                            <option value="">-- Tất cả --</option>
                            <option value="expiring" <?php echo ($expiry_filter == 'expiring') ? 'selected' : ''; ?>>
                                Sắp hết hạn (3 tháng)
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-medical btn-block">
                            <i class="fas fa-search"></i> Tìm
                        </button>
                    </div>
                </form>
                <?php if ($search_query || $category_filter || $expiry_filter): ?>
                    <div class="mt-2">
                        <a href="medicine-inventory.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-redo mr-1"></i>Reset bộ lọc
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Danh sách thuốc -->
            <?php if (empty($medicines)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>Không có thuốc nào trong kho.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-medicine table-hover">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Tên thuốc</th>
                                <th style="width: 12%;">Danh mục</th>
                                <th style="width: 10%;">Dạng BC</th>
                                <th style="width: 10%;">Giá</th>
                                <th style="width: 12%;">Số lượng</th>
                                <th style="width: 10%;">Hạn SD</th>
                                <th style="width: 11%;">Trạng thái</th>
                                <th style="width: 10%;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $med): ?>
                                <?php $status = checkMedicineStatus($med); ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo $med['category']; ?></span></td>
                                    <td><?php echo htmlspecialchars($med['dosage_form']); ?></td>
                                    <td><?php echo number_format($med['unit_price'], 0, ',', '.'); ?> đ</td>
                                    <td>
                                        <form method="POST" class="form-inline" style="display: inline-block;">
                                            <input type="hidden" name="medicine_id" value="<?php echo $med['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity" value="<?php echo $med['quantity']; ?>"
                                                    class="form-control" style="width: 60px;" min="0">
                                                <div class="input-group-append">
                                                    <button type="submit" name="update_stock" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                    <td><?php echo $med['expiry_date'] ? date('d/m/Y', strtotime($med['expiry_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php foreach ($status as $s): ?>
                                            <span class="badge badge-<?php echo $s['type']; ?> d-block mb-1">
                                                <?php echo $s['message']; ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (empty($status)): ?>
                                            <span class="badge badge-success">Bình thường</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $med['id']; ?>" class="btn btn-sm btn-warning" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_id=<?php echo $med['id']; ?>" class="btn btn-sm btn-danger" title="Xóa"
                                            onclick="return confirm('Bạn có chắc muốn xóa thuốc này?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page_num > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page_num=<?php echo ($page_num - 1); ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $expiry_filter ? '&expiry=' . $expiry_filter : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Trước
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page_num) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page_num=<?php echo $i; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $expiry_filter ? '&expiry=' . $expiry_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page_num < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page_num=<?php echo ($page_num + 1); ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?><?php echo $expiry_filter ? '&expiry=' . $expiry_filter : ''; ?>">
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <!-- Hiệu ứng hoa đào rơi tráng lệ & quý phái - Premium Edition -->
    <script type="text/javascript">
        (function() {
            const isMobile = window.matchMedia('(max-width: 576px)').matches;
            const isTablet = window.matchMedia('(min-width: 577px) and (max-width: 992px)').matches;
            const petalCount = isMobile ? 15 : (isTablet ? 30 : 50);
            const petalImage = 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEizrrtX-KQtKY8e8pxCHjLROT5pYW7sVkUpET9HHpW8QO-PnoIRKVsvRDxM6shrE4Q-44Oh9teSGK1SApaZ1OJvhR4z7ENgKSJOLWfsdKw9jPszAa2HqaE6W8ohyGHRvff6TgKXEUjnn73LLLp3FHbtMTJnIkPxPhujWwG5ZsFgW7ctQ0zrR5KKSqlewg/s16000/hoadao-anonyviet.com.png';

            const petals = [];
            let docWidth = window.innerWidth;
            let docHeight = window.innerHeight;

            for (let i = 0; i < petalCount; i++) {
                const size = 10 + Math.random() * 14;
                const opacity = 0.5 + Math.random() * 0.4;
                const rotation = Math.random() * 360;
                const blur = Math.random() * 0.5;

                const petal = {
                    x: Math.random() * docWidth,
                    y: Math.random() * docHeight - docHeight,
                    dx: 0,
                    rotation: rotation,
                    rotationSpeed: (Math.random() - 0.5) * 1.5,
                    amplitude: 20 + Math.random() * 35,
                    speedX: 0.01 + Math.random() / 15,
                    speedY: 0.3 + Math.random() * 0.6,
                    size: size,
                    opacity: opacity,
                    blur: blur,
                    element: null
                };

                const div = document.createElement('div');
                div.id = 'cherry-petal-' + i;
                div.style.cssText = `position:fixed;z-index:9998;visibility:visible;pointer-events:none;width:${size}px;left:${petal.x}px;top:${petal.y}px;opacity:${opacity};transition:transform 0.15s ease-out;will-change:transform,top,left`;
                div.innerHTML = `<img src="${petalImage}" alt="Hoa đào" style="width:100%;height:auto;transform:rotate(${rotation}deg);filter:drop-shadow(2px 2px 4px rgba(255,105,180,0.4)) blur(${blur}px) brightness(1.1);">`;
                document.body.appendChild(div);
                petal.element = div;
                petals.push(petal);
            }

            function animate() {
                docWidth = window.innerWidth;
                docHeight = window.innerHeight;

                petals.forEach(petal => {
                    petal.y += petal.speedY;
                    petal.rotation += petal.rotationSpeed;

                    if (petal.y > docHeight + 80) {
                        petal.x = Math.random() * docWidth;
                        petal.y = -80;
                        petal.speedX = 0.01 + Math.random() / 15;
                        petal.speedY = 0.3 + Math.random() * 0.6;
                        petal.rotationSpeed = (Math.random() - 0.5) * 1.5;
                    }

                    petal.dx += petal.speedX;
                    const swayX = petal.x + petal.amplitude * Math.sin(petal.dx);
                    const scaleEffect = 0.95 + Math.sin(petal.dx * 2) * 0.05;

                    petal.element.style.top = petal.y + 'px';
                    petal.element.style.left = swayX + 'px';
                    petal.element.querySelector('img').style.transform = `rotate(${petal.rotation}deg) scale(${scaleEffect})`;
                });

                requestAnimationFrame(animate);
            }

            animate();

            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    docWidth = window.innerWidth;
                    docHeight = window.innerHeight;
                }, 250);
            });
        })();
    </script>
</body>

</html>