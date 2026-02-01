<?php
session_start();

// 1. Kết nối CSDL
if (file_exists('../../../config.php')) {
    require_once('../../../config.php');
} else {
    require_once('../../config.php');
}

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') exit('Unauthorized');

if (!isset($pdo)) {
    try {
        $host = defined('DB_SERVER') ? DB_SERVER : 'localhost';
        $dbname = defined('DB_NAME') ? DB_NAME : 'chuduyit_medical_k73';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    } catch (PDOException $e) { die("Lỗi: " . $e->getMessage()); }
}

// 2. Lấy dữ liệu tìm kiếm
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$spec = isset($_POST['spec']) ? trim($_POST['spec']) : '';

// 3. Map chuyên khoa (Anh -> Việt) để tìm kiếm chính xác
$spec_map = [
    'General' => 'Đa khoa', 'Cardiologist' => 'Tim mạch', 'Neurologist' => 'Thần kinh',
    'Pediatrician' => 'Nhi khoa', 'Dermatologist' => 'Da liễu', 'Orthopedic' => 'Chỉnh hình'
];
$spec_vi = isset($spec_map[$spec]) ? $spec_map[$spec] : $spec;

// 4. LẤY DANH SÁCH BÁC SĨ (CÓ LỌC)
$sql_docs = "SELECT d.id, d.fullname, d.email, s.name_vi as spec_name
             FROM doctb d
             LEFT JOIN specializations s ON d.spec_id = s.id
             WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql_docs .= " AND (d.fullname LIKE :s1 OR d.username LIKE :s2)";
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
}
if (!empty($spec)) {
    $sql_docs .= " AND (d.spec = :sp1 OR s.name_vi = :sp2)";
    $params[':sp1'] = $spec;
    $params[':sp2'] = $spec_vi;
}
$sql_docs .= " ORDER BY d.fullname ASC";

$stmt = $pdo->prepare($sql_docs);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. LẤY TOÀN BỘ LỊCH ĐỂ MAP (Logic cũ)
$sql_sch = "SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_schedules";
$all_schedules = $pdo->query($sql_sch)->fetchAll(PDO::FETCH_ASSOC);

$scheduleMap = [];
foreach ($all_schedules as $sch) {
    $scheduleMap[$sch['doctor_id']][$sch['day_of_week']] = date('H:i', strtotime($sch['start_time'])) . ' - ' . date('H:i', strtotime($sch['end_time']));
}

$daysOfWeek = [1=>'Thứ 2', 2=>'Thứ 3', 3=>'Thứ 4', 4=>'Thứ 5', 5=>'Thứ 6', 6=>'Thứ 7', 0=>'CN'];

// 6. XUẤT HTML (Giữ nguyên cấu trúc bảng cũ)
if (count($doctors) > 0) {
    foreach ($doctors as $doc) {
        $docId = $doc['id'];
        echo "<tr>";
        echo "<td class='doctor-name-col'>" . htmlspecialchars($doc['fullname']) . "</td>";
        echo "<td class='spec-col'>" . htmlspecialchars($doc['spec_name'] ?? '---') . "</td>";

        foreach ($daysOfWeek as $dayKey => $dayLabel) {
            echo "<td>";
            if (isset($scheduleMap[$docId][$dayKey])) {
                echo "<i class='fas fa-check-circle check-icon' title='" . $scheduleMap[$docId][$dayKey] . "'></i><br>";
                echo "<small class='text-success font-weight-bold'>" . $scheduleMap[$docId][$dayKey] . "</small>";
            } else {
                echo "<i class='fas fa-times cross-icon'></i>";
            }
            echo "</td>";
        }

        echo "<td><a href='?page=manage_schedule&reset_schedule=$docId' class='btn btn-outline-danger btn-sm' onclick=\"return confirm('Xóa hết lịch của BS này?');\"><i class='fas fa-trash-alt'></i></a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='10' class='text-center py-4'>Không tìm thấy bác sĩ nào phù hợp.</td></tr>";
}
?>