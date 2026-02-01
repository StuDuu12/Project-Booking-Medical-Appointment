<?php
session_start();
// Kiểm tra đường dẫn config
if (file_exists('../../../config.php')) require_once('../../../config.php');
else require_once('../../config.php');

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') exit('Unauthorized');

$action = $_POST['action'] ?? '';

// (Đã xóa hàm nullIfEmpty ở đây để tránh lỗi Redeclare)

// --- XỬ LÝ THÊM / SỬA ---
if ($action == 'add' || $action == 'edit') {
    try {
        // 1. Lấy dữ liệu bắt buộc
        $patient_id = $_POST['patient_id'];
        $doctor_id  = $_POST['doctor_id'];
        $record_date = !empty($_POST['record_date']) ? $_POST['record_date'] : date('Y-m-d H:i:s');

        // 2. Xử lý các chỉ số sinh tồn (Sửa lỗi Incorrect decimal value)
        // Logic mới: Kiểm tra trực tiếp, nếu rỗng thì gán NULL, ngược lại lấy giá trị
        $height = (isset($_POST['height']) && $_POST['height'] !== '') ? $_POST['height'] : null;
        $weight = (isset($_POST['weight']) && $_POST['weight'] !== '') ? $_POST['weight'] : null;
        $bmi    = (isset($_POST['bmi'])    && $_POST['bmi']    !== '') ? $_POST['bmi']    : null;

        $bp     = (isset($_POST['blood_pressure'])   && $_POST['blood_pressure']   !== '') ? $_POST['blood_pressure']   : null;
        $hr     = (isset($_POST['heart_rate'])       && $_POST['heart_rate']       !== '') ? $_POST['heart_rate']       : null;
        $rr     = (isset($_POST['respiratory_rate']) && $_POST['respiratory_rate'] !== '') ? $_POST['respiratory_rate'] : null;
        $temp   = (isset($_POST['temperature'])      && $_POST['temperature']      !== '') ? $_POST['temperature']      : null;

        // 3. Thông tin lâm sàng (String - Dùng ?? '' để tránh lỗi undefined index)
        $chief  = $_POST['chief_complaint'] ?? '';
        $symp   = $_POST['symptoms'] ?? '';
        $diag   = $_POST['diagnosis'] ?? '';
        $plan   = $_POST['treatment_plan'] ?? '';
        $pres   = $_POST['prescription'] ?? '';
        $note   = $_POST['notes'] ?? '';

        // Ngày tái khám (Sửa lỗi Incorrect date value)
        $fup    = (isset($_POST['follow_up_date']) && $_POST['follow_up_date'] !== '') ? $_POST['follow_up_date'] : null;

        if ($action == 'add') {
            $sql = "INSERT INTO medical_records
                    (patient_id, doctor_id, record_date, height, weight, bmi, blood_pressure, heart_rate, respiratory_rate, temperature,
                     chief_complaint, symptoms, diagnosis, treatment_plan, prescription, notes, follow_up_date, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $pdo->prepare($sql);
            $res = $stmt->execute([
                $patient_id, $doctor_id, $record_date, $height, $weight, $bmi, $bp, $hr, $rr, $temp,
                $chief, $symp, $diag, $plan, $pres, $note, $fup
            ]);

            echo "success";
        }
        else if ($action == 'edit') {
            $id = $_POST['record_id'];
            $sql = "UPDATE medical_records SET
                    doctor_id=?, record_date=?, height=?, weight=?, bmi=?, blood_pressure=?, heart_rate=?, respiratory_rate=?, temperature=?,
                    chief_complaint=?, symptoms=?, diagnosis=?, treatment_plan=?, prescription=?, notes=?, follow_up_date=?, updated_at=NOW()
                    WHERE id=?";

            $stmt = $pdo->prepare($sql);
            $res = $stmt->execute([
                $doctor_id, $record_date, $height, $weight, $bmi, $bp, $hr, $rr, $temp,
                $chief, $symp, $diag, $plan, $pres, $note, $fup, $id
            ]);

            echo "success";
        }
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
}

// --- XỬ LÝ XÓA ---
if ($action == 'delete') {
    try {
        $id = $_POST['record_id'];
        $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ?");
        if($stmt->execute([$id])) echo "success";
        else echo "Lỗi khi xóa record";
    } catch (PDOException $e) {
        echo "Lỗi SQL: " . $e->getMessage();
    }
}
?>