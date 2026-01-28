<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php'); // Ensure this is included

$doctor = $_SESSION['dname'] ?? null;

if (!$doctor) {
    header("Location: ../../pages/auth/login.php");
    exit();
}

// Get doctor ID
// Try to get doctor info, but don't blocking properly if username mismatch.
// Use session dname (username) as the primary identifier since database uses username in most relations.
$doctor_username = $_SESSION['dname'];
$doctor_id = null;
$doctor_info = null;

$stmt = $pdo->prepare("SELECT id, fullname, spec FROM doctb WHERE username = ?");
$stmt->execute([$doctor_username]);
$doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($doctor_info) {
    $doctor_id = $doctor_info['id'];
} else {
    // If not found in doctb, we can still proceed if we just need the username for the prescription record.
    // However, it's better to log this.
    error_log("Warning: Doctor with username '$doctor_username' not found in doctb.");
    // We can continue, just doc id will be null, but we insert 'doctor' as username in prestb anyway.
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
        $ID = $_POST['ID'] ?? null; // Appointment ID
        $disease = $_POST['disease'];
        $allergy = $_POST['allergy'] ?? '';
        $treatment_duration = $_POST['treatment_duration'];
        $general_notes = $_POST['general_notes'] ?? '';
        
        // Insert into prestb (Enhanced Existing Table)
        // Note: prestb has columns: doctor, pid, ID (app_id), fname, lname, appdate, apptime, disease, allergy, prescription, treatment_duration, general_notes
        
        // We need to fetch patient details to fill fname, lname, appdate, apptime if they are not in POST
        // But the form sends them as hidden inputs.
        
        $fname = $_POST['fname'] ?? '';
        $lname = $_POST['lname'] ?? '';
        $appdate = $_POST['appdate'] ?? date('Y-m-d');
        $apptime = $_POST['apptime'] ?? date('H:i:s');
        
        // Use a summary of medications for the old 'prescription' column to maintain backward compatibility
        $med_summary = "";
        if (isset($_POST['medications']) && is_array($_POST['medications'])) {
            foreach ($_POST['medications'] as $med) {
                if (!empty($med['name'])) {
                    $med_summary .= $med['name'] . " (" . $med['dosage'] . ") - " . $med['frequency'] . "; ";
                }
            }
        }
        
        // Ensure med_summary is not empty if required
        if (empty($med_summary)) {
             $med_summary = "Chi tiết trong bảng thuốc";
        }

        $stmt = $pdo->prepare("
            INSERT INTO prestb (doctor, pid, ID, fname, lname, appdate, apptime, disease, allergy, prescription, treatment_duration, general_notes, created_at)
            VALUES (:doctor, :pid, :ID, :fname, :lname, :appdate, :apptime, :disease, :allergy, :prescription, :treatment_duration, :general_notes, NOW())
        ");
        
        $result = $stmt->execute([
            ':doctor' => $doctor, // username
            ':pid' => $pid,
            ':ID' => $ID,
            ':fname' => $fname,
            ':lname' => $lname,
            ':appdate' => $appdate,
            ':apptime' => $apptime,
            ':disease' => $disease,
            ':allergy' => $allergy,
            ':prescription' => $med_summary, // Backward compatibility
            ':treatment_duration' => $treatment_duration,
            ':general_notes' => $general_notes
        ]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to insert prescription: " . $errorInfo[2]);
        }
        
        // Use new column name for PK only if it was renamed to pres_id, but migration script adds pres_id. 
        // lastInsertId needs to know nothing special if it's auto_increment.
        $prescription_id = $pdo->lastInsertId(); 
        
        // Update appointment status if ID is present (optional, but good practice to mark as visited/prescribed)
        if ($ID) {
           // Maybe update status? Leaving as is for now unless requested.
        }
        
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
        redirectWithMessage('dashboard.php?page=prescriptions', 'success', 'Kê đơn thuốc thành công!');
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
    <title>Kê đơn thuốc - Bệnh viện Global</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --success: #10B981;
            --danger: #EF4444;
            --light-bg: #f0f9ff;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container-custom {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #7C3AED 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .back-button {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .patient-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .medication-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 2px solid #e2e8f0;
            position: relative;
        }
        
        .medication-number {
            position: absolute;
            top: -12px;
            left: 15px;
            background: var(--primary);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .btn-remove-medication {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .required-field::after {
            content: " *";
            color: var(--danger);
        }
    </style>
</head>
<body>
    <?php displayMessage(); ?>
    
    <a href="dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <div class="container-custom">
        <div class="header">
            <h1><i class="fas fa-file-prescription"></i> Kê Đơn Thuốc Chi Tiết</h1>
            <p>Tạo đơn thuốc cho bệnh nhân</p>
        </div>

        <?php if ($pid): ?>
        <div class="patient-info">
            <h4 class="mb-3"><i class="fas fa-user-injured mr-2"></i>Thông tin bệnh nhân</h4>
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">Tên bệnh nhân</small>
                    <strong><?php echo htmlspecialchars($fname . ' ' . $lname); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Mã hồ sơ</small>
                    <strong>#<?php echo htmlspecialchars($pid); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Ngày hẹn</small>
                    <strong><?php echo htmlspecialchars($appdate); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Giờ hẹn</small>
                    <strong><?php echo htmlspecialchars($apptime); ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="p-4">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" id="prescriptionForm">
                <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="hidden" name="ID" value="<?php echo htmlspecialchars($ID); ?>">
                
                <!-- Diagnosis -->
                <div class="mb-4">
                    <h5 class="text-secondary border-bottom pb-2 mb-3">Chẩn đoán & Điều trị</h5>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="required-field">Chẩn đoán / Bệnh</label>
                                <input type="text" name="disease" class="form-control" placeholder="Nhập tên bệnh" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field">Thời gian điều trị</label>
                                <input type="text" name="treatment_duration" class="form-control" placeholder="VD: 7 ngày" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Dị ứng (nếu có)</label>
                        <input type="text" name="allergy" class="form-control" placeholder="Nhập tên thuốc/thực phẩm gây dị ứng">
                    </div>
                </div>

                <!-- Medications -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-secondary border-bottom pb-2 mb-0 w-100">Danh sách thuốc</h5>
                        <button type="button" class="btn btn-success btn-sm ml-2" style="min-width: 120px;" onclick="addMedication()">
                            <i class="fas fa-plus mr-1"></i> Thêm thuốc
                        </button>
                    </div>
                    
                    <div id="medications-container">
                        <!-- Medications added via JS -->
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <h5 class="text-secondary border-bottom pb-2 mb-3">Hướng dẫn chung</h5>
                    <div class="form-group">
                        <textarea name="general_notes" class="form-control" rows="3" placeholder="Lời dặn dò của bác sĩ..."></textarea>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" name="prescribe" class="btn btn-lg btn-primary px-5">
                        <i class="fas fa-save mr-2"></i> Lưu Đơn Thuốc
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
                                <label class="required-field">Tên thuốc</label>
                                <input type="text" name="medications[${medicationCount}][name]" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Liều lượng</label>
                                <input type="text" name="medications[${medicationCount}][dosage]" class="form-control" placeholder="VD: 500mg, 1 viên" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Tần suất</label>
                                <input type="text" name="medications[${medicationCount}][frequency]" class="form-control" placeholder="VD: Sáng 1, Tối 1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Thời gian</label>
                                <input type="text" name="medications[${medicationCount}][duration]" class="form-control" placeholder="VD: 7 ngày" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lưu ý riêng</label>
                        <input type="text" name="medications[${medicationCount}][notes]" class="form-control" placeholder="VD: Uống sau ăn">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function removeMedication(id) {
            const el = document.getElementById(`medication-${id}`);
            if (el) el.remove();
            // Optional: Renumber items, but ID uniqueness matters more for form submission
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            addMedication(); // Add first item by default
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