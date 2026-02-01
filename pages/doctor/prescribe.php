<!DOCTYPE html>
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
$doctor_username = $_SESSION['dname'];
$doctor_id = null;
$doctor_info = null;

$stmt = $pdo->prepare("SELECT id, fullname, spec FROM doctb WHERE username = ?");
$stmt->execute([$doctor_username]);
$doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($doctor_info) {
    $doctor_id = $doctor_info['id'];
} else {
    error_log("Warning: Doctor with username '$doctor_username' not found in doctb.");
}

// Initialize variables
$pid = '';
$ID = '';
$appdate = '';
$apptime = '';
$fname = '';
$lname = '';

// Check if parameters are passed via GET (from Appointments list)
if (isset($_GET['pid']) && isset($_GET['ID'])) {
    $pid = $_GET['pid'];
    $ID = $_GET['ID'];
    $fname = $_GET['fname'] ?? '';
    $lname = $_GET['lname'] ?? '';
    $appdate = $_GET['appdate'] ?? '';
    $apptime = $_GET['apptime'] ?? '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescribe'])) {
    try {
        $pdo->beginTransaction();

        // Get POST data
        $pid = $_POST['pid'];
        $ID = $_POST['ID'] ?? null;
        $disease = $_POST['disease'];
        $allergy = $_POST['allergy'] ?? '';
        $treatment_duration = $_POST['treatment_duration'];
        $general_notes = $_POST['general_notes'] ?? '';

        $fname = $_POST['fname'] ?? '';
        $lname = $_POST['lname'] ?? '';
        $appdate = $_POST['appdate'] ?? date('Y-m-d');
        $apptime = $_POST['apptime'] ?? date('H:i:s');

        // Use a summary of medications for the old 'prescription' column
        $med_summary = "";
        if (isset($_POST['medications']) && is_array($_POST['medications'])) {
            foreach ($_POST['medications'] as $med) {
                if (!empty($med['name'])) {
                    $med_summary .= $med['name'] . " (" . $med['dosage'] . ") - " . $med['frequency'] . "; ";
                }
            }
        }

        if (empty($med_summary)) {
            $med_summary = "Chi tiết trong bảng thuốc";
        }

        $stmt = $pdo->prepare("
            INSERT INTO prestb (doctor, pid, ID, fname, lname, appdate, apptime, disease, allergy, prescription, treatment_duration, general_notes, created_at)
            VALUES (:doctor, :pid, :ID, :fname, :lname, :appdate, :apptime, :disease, :allergy, :prescription, :treatment_duration, :general_notes, NOW())
        ");

        $result = $stmt->execute([
            ':doctor' => $doctor_info['fullname'] ?? $doctor,
            ':pid' => $pid,
            ':ID' => $ID,
            ':fname' => $fname,
            ':lname' => $lname,
            ':appdate' => $appdate,
            ':apptime' => $apptime,
            ':disease' => $disease,
            ':allergy' => $allergy,
            ':prescription' => $med_summary,
            ':treatment_duration' => $treatment_duration,
            ':general_notes' => $general_notes
        ]);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to insert prescription: " . $errorInfo[2]);
        }

        $prescription_id = $pdo->lastInsertId();

        // Insert medications
        if (isset($_POST['medications']) && is_array($_POST['medications'])) {
            $stmt = $pdo->prepare("
                INSERT INTO prescription_medications (prescription_id, medication_name, dosage, frequency, duration, special_notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($_POST['medications'] as $med) {
                if (!empty($med['name'])) {
                    $stmt->execute([
                        $prescription_id,
                        $med['name'],
                        $med['dosage'],
                        $med['frequency'],
                        $med['duration'],
                        $med['notes'] ?? ''
                    ]);
                }
            }
        }

        $pdo->commit();
        redirectWithMessage('prescriptions.php', 'success', 'Kê đơn thuốc thành công!');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Lỗi khi tạo đơn thuốc: " . $e->getMessage();
        error_log($error_message);
    }
}
?>

<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Kê đơn thuốc - Bệnh viện Global</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">

    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f0f9ff 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 30px 15px;
        }

        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #d2302c;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #8b0000;
            text-decoration: none;
            transform: translateX(-5px);
        }

        .page-header {
            background: linear-gradient(135deg, #d2302c 0%, #ff4d4d 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(210, 48, 44, 0.15);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-header p {
            margin: 8px 0 0 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .patient-info-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.1);
        }

        .patient-info-card h4 {
            color: #78350f;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-item small {
            display: block;
            color: #92400e;
            font-weight: 500;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .info-item strong {
            color: #78350f;
            font-size: 15px;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.12);
            border-left: 5px solid #d2302c;
        }

        .section-title {
            color: #d2302c;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffe6e6;
        }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .required-field::after {
            content: " *";
            color: #f43f5e;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #d2302c;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .medication-item {
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #ddd6fe;
            position: relative;
        }

        .medication-number {
            position: absolute;
            top: -12px;
            left: 20px;
            background: linear-gradient(135deg, #d2302c, #ff4d4d);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 4px 8px rgba(210, 48, 44, 0.3);
        }

        .btn-remove-medication {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f43f5e;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove-medication:hover {
            background: #e11d48;
            transform: rotate(90deg);
        }

        .btn-medical {
            background: linear-gradient(135deg, #d2302c, #ff4d4d);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-medical:hover {
            background: linear-gradient(135deg, #8b0000, #d2302c);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(210, 48, 44, 0.3);
        }

        .btn-add-med {
            background: linear-gradient(135deg, #ffd700, #d4af37);
            color: #8b0000;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-add-med:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            color: #8b0000;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .patient-info-card .row>div {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>

    <div class="container-custom">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại bảng điều khiển
        </a>

        <div class="page-header">
            <h1><i class="fas fa-file-prescription"></i> Kê Đơn Thuốc Chi Tiết</h1>
            <p>Tạo đơn thuốc và hướng dẫn điều trị cho bệnh nhân</p>
        </div>

        <?php if ($pid): ?>
            <div class="patient-info-card">
                <h4><i class="fas fa-user-injured mr-2"></i>Thông tin bệnh nhân</h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-item">
                            <small>Tên bệnh nhân</small>
                            <strong><?php echo htmlspecialchars($fname . ' ' . $lname); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <small>Mã hồ sơ</small>
                            <strong>#<?php echo htmlspecialchars($pid); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <small>Ngày hẹn</small>
                            <strong><?php echo $appdate ? date('d/m/Y', strtotime($appdate)) : 'N/A'; ?></strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <small>Giờ hẹn</small>
                            <strong><?php echo htmlspecialchars($apptime); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="prescriptionForm">
            <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
            <input type="hidden" name="ID" value="<?php echo htmlspecialchars($ID); ?>">
            <input type="hidden" name="fname" value="<?php echo htmlspecialchars($fname); ?>">
            <input type="hidden" name="lname" value="<?php echo htmlspecialchars($lname); ?>">
            <input type="hidden" name="appdate" value="<?php echo htmlspecialchars($appdate); ?>">
            <input type="hidden" name="apptime" value="<?php echo htmlspecialchars($apptime); ?>">

            <!-- Diagnosis Section -->
            <div class="form-card">
                <h5 class="section-title"><i class="fas fa-stethoscope mr-2"></i>Chẩn đoán & Điều trị</h5>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label required-field">Chẩn đoán / Bệnh</label>
                            <input type="text" name="disease" class="form-control" placeholder="Nhập tên bệnh hoặc chẩn đoán" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label required-field">Thời gian điều trị</label>
                            <input type="text" name="treatment_duration" class="form-control" placeholder="VD: 7 ngày" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Dị ứng (nếu có)</label>
                    <input type="text" name="allergy" class="form-control" placeholder="Nhập tên thuốc/thực phẩm gây dị ứng">
                </div>
            </div>

            <!-- Medications Section -->
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="fas fa-pills mr-2"></i>Danh sách thuốc</h5>
                    <button type="button" class="btn btn-add-med" onclick="addMedication()">
                        <i class="fas fa-plus mr-1"></i> Thêm thuốc
                    </button>
                </div>

                <div id="medications-container">
                    <!-- Medications added via JS -->
                </div>
            </div>

            <!-- General Notes Section -->
            <div class="form-card">
                <h5 class="section-title"><i class="fas fa-comment-medical mr-2"></i>Hướng dẫn chung</h5>
                <div class="form-group">
                    <label class="form-label">Lời dặn dò của bác sĩ</label>
                    <textarea name="general_notes" class="form-control" rows="4" placeholder="Nhập hướng dẫn chung cho bệnh nhân về chế độ ăn uống, nghỉ ngơi, tái khám..."></textarea>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" name="prescribe" class="btn btn-medical btn-lg px-5">
                    <i class="fas fa-save mr-2"></i> Lưu Đơn Thuốc
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        let medicationCount = 0;

        function addMedication() {
            medicationCount++;
            const container = document.getElementById('medications-container');
            const html = `
                <div class="medication-item" id="medication-${medicationCount}">
                    <div class="medication-number">${medicationCount}</div>
                    <button type="button" class="btn-remove-medication" onclick="removeMedication(${medicationCount})">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required-field">Tên thuốc</label>
                                <input type="text" name="medications[${medicationCount}][name]" class="form-control" placeholder="Nhập tên thuốc" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required-field">Liều lượng</label>
                                <input type="text" name="medications[${medicationCount}][dosage]" class="form-control" placeholder="VD: 500mg, 1 viên" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required-field">Tần suất</label>
                                <input type="text" name="medications[${medicationCount}][frequency]" class="form-control" placeholder="VD: Sáng 1, Chiều 1, Tối 1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label required-field">Thời gian dùng</label>
                                <input type="text" name="medications[${medicationCount}][duration]" class="form-control" placeholder="VD: 7 ngày" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Lưu ý riêng</label>
                        <input type="text" name="medications[${medicationCount}][notes]" class="form-control" placeholder="VD: Uống sau ăn, Tránh uống với sữa">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function removeMedication(id) {
            const el = document.getElementById(`medication-${id}`);
            if (el) el.remove();
        }

        document.addEventListener('DOMContentLoaded', () => {
            addMedication(); // Add first medication by default
        });

        document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
            if (document.querySelectorAll('.medication-item').length === 0) {
                e.preventDefault();
                alert('Vui lòng thêm ít nhất một loại thuốc!');
            }
        });
    </script>
</body>

</html>