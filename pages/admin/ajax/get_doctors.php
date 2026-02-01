<?php
session_start();

// Kiểm tra đường dẫn config
if (file_exists('../../../config.php')) {
    require_once('../../../config.php');
} else {
    require_once('../../config.php');
}

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    exit('Unauthorized');
}

// Kết nối PDO
if (!isset($pdo)) {
    try {
        $host = defined('DB_SERVER') ? DB_SERVER : 'localhost';
        $dbname = defined('DB_NAME') ? DB_NAME : 'chuduyit_medical_k73';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        // Bật chế độ báo lỗi
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Lỗi kết nối: " . $e->getMessage());
    }
}

// Lấy dữ liệu đầu vào
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$spec = isset($_POST['spec']) ? trim($_POST['spec']) : '';

// --- XÂY DỰNG QUERY (SỬA LỖI TRÙNG THAM SỐ) ---
$sql = "SELECT d.*, s.name_vi
        FROM doctb d
        LEFT JOIN specializations s ON d.spec_id = s.id
        WHERE 1=1";
$params = [];

// Xử lý tìm kiếm từ khóa
if (!empty($search)) {
    $searchTerm = "%$search%";
    // SỬA LỖI: Dùng :s1, :s2, :s3 thay vì dùng chung :search
    $sql .= " AND (d.fullname LIKE :s1 OR d.username LIKE :s2 OR d.email LIKE :s3)";
    $params[':s1'] = $searchTerm;
    $params[':s2'] = $searchTerm;
    $params[':s3'] = $searchTerm;
}

// Xử lý lọc chuyên khoa
if (!empty($spec)) {

    $sql .= " AND (d.spec = :sp1 OR s.name_vi = :sp2)";
    $params[':sp1'] = $spec;
    $params[':sp2'] = $spec;
}

$sql .= " ORDER BY d.fullname ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); 
    $serial = 1;

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = htmlspecialchars($row['fullname'] ?? $row['username']);
            $specialization = htmlspecialchars($row['name_vi'] ?? $row['spec']);
            $email = htmlspecialchars($row['email']);
            $fees = htmlspecialchars($row['docFees']);

            echo "<tr>";
            echo "<td>" . $serial++ . "</td>";
            echo "<td><strong><i class='fas fa-user-md text-primary'></i> BS. $name</strong></td>";
            echo "<td><span class='badge badge-primary'>$specialization</span></td>";
            echo "<td>$email</td>";
            echo "<td><strong>₹$fees</strong></td>";
            echo "<td>
                    <form method='post' action='?page=doctors' style='display: inline;' onsubmit=\"return confirm('Bạn có chắc muốn xóa bác sĩ này?');\">
                        <input type='hidden' name='demail' value='$email'>
                        <button type='submit' name='docsub1' class='btn btn-danger btn-sm' title='Xóa'>
                            <i class='fas fa-trash-alt'></i>
                        </button>
                    </form>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center py-4 text-muted'><i class='fas fa-search-minus fa-2x mb-2'></i><br>Không tìm thấy bác sĩ nào phù hợp.</td></tr>";
    }
} catch (PDOException $e) {
    // Hiển thị lỗi rõ ràng nếu có
    echo "<tr><td colspan='6' class='text-center text-danger'>Lỗi hệ thống: " . $e->getMessage() . "</td></tr>";
}
?>