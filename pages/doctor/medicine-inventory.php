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
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #d2302c 0%, #ff4d4d 100%);
            padding: 30px;
            border-radius: 16px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(210, 48, 44, 0.15);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
            border-left: 5px solid #ffd700;
        }

        .filter-card {
            background: #f0fdf4;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .inventory-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
        }

        .btn-medical {
            background: linear-gradient(135deg, #d2302c, #ff4d4d);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-medical:hover {
            background: linear-gradient(135deg, #8b0000, #6b0000);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(210, 48, 44, 0.3);
        }

        .table-medicine {
            font-size: 14px;
        }

        .table-medicine th {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #064e3b;
            font-weight: 600;
            border: none;
        }

        .table-medicine td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <?php include('../../includes/navbar.php'); ?>

    <div class="page-container">
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
</body>

</html>