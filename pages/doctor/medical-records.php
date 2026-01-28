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

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'view';
$allowed_pages = array('view', 'add', 'search');
if (!in_array($page, $allowed_pages)) {
    $page = 'view';
}

// Handle add medical record form
if (isset($_POST['add_medical_record'])) {
    try {
        $patient_id = $_POST['patient_id'];
        $appointment_id = isset($_POST['appointment_id']) && $_POST['appointment_id'] !== '' ? $_POST['appointment_id'] : null;
        $symptoms = $_POST['symptoms'];
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $notes = isset($_POST['notes']) && $_POST['notes'] !== '' ? $_POST['notes'] : null;
        $height = isset($_POST['height']) && $_POST['height'] !== '' ? floatval($_POST['height']) : null;
        $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? floatval($_POST['weight']) : null;
        $blood_pressure = isset($_POST['blood_pressure']) && $_POST['blood_pressure'] !== '' ? $_POST['blood_pressure'] : null;
        $heart_rate = isset($_POST['heart_rate']) && $_POST['heart_rate'] !== '' ? intval($_POST['heart_rate']) : null;
        $temperature = isset($_POST['temperature']) && $_POST['temperature'] !== '' ? floatval($_POST['temperature']) : null;

        // Get doctor ID
        $stmt = $pdo->prepare("SELECT id FROM doctb WHERE fullname = :fullname");
        $stmt->execute([':fullname' => $doctor]);
        $doctor_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $doctor_id = $doctor_result['id'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO medical_records (
                patient_id, doctor_id, appointment_id, record_date, symptoms, diagnosis, treatment_plan, 
                notes, height, weight, blood_pressure, heart_rate, temperature, status, created_by, created_at, updated_at
            ) VALUES (
                :patient_id, :doctor_id, :appointment_id, CURDATE(), :symptoms, :diagnosis, :treatment, 
                :notes, :height, :weight, :blood_pressure, :heart_rate, :temperature, 'completed', " . intval($doctor_id) . ", NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':doctor_id' => $doctor_id,
            ':appointment_id' => $appointment_id,
            ':symptoms' => $symptoms,
            ':diagnosis' => $diagnosis,
            ':treatment' => $treatment,
            ':notes' => $notes,
            ':height' => $height,
            ':weight' => $weight,
            ':blood_pressure' => $blood_pressure,
            ':heart_rate' => $heart_rate,
            ':temperature' => $temperature
        ]);
        redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Hồ sơ bệnh án đã được thêm thành công!');
    } catch (PDOException $e) {
        error_log("Add medical record error: " . $e->getMessage());
        redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Lỗi khi thêm hồ sơ bệnh án: ' . $e->getMessage());
    }
}

// Get doctor ID for queries
$doctor_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM doctb WHERE fullname = :fullname");
    $stmt->execute([':fullname' => $doctor]);
    $doctor_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $doctor_result['id'] ?? null;
} catch (PDOException $e) {
    error_log("Get doctor ID error: " . $e->getMessage());
}

// Fetch all medical records for this doctor
$all_medical_records = [];
$grouped_records = []; // Group by patient_id
try {
    $stmt = $pdo->prepare("
        SELECT mr.id, mr.patient_id, mr.doctor_id, mr.created_at, mr.symptoms, mr.diagnosis, mr.treatment_plan, mr.notes,
               mr.height, mr.weight, mr.blood_pressure, mr.heart_rate, mr.temperature, mr.record_date,
               p.fname, p.lname, p.contact, p.email, p.blood_group, p.pid,
               d.fullname as doctor_name
        FROM medical_records mr
        JOIN patreg p ON mr.patient_id = p.pid
        LEFT JOIN doctb d ON mr.doctor_id = d.id
        WHERE mr.doctor_id = :doctor_id
        ORDER BY p.fname, p.lname, mr.created_at DESC
    ");
    $stmt->execute([':doctor_id' => $doctor_id]);
    $all_medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group records by patient
    foreach ($all_medical_records as $record) {
        $patient_id = $record['patient_id'];
        if (!isset($grouped_records[$patient_id])) {
            $grouped_records[$patient_id] = [
                'patient_info' => [
                    'pid' => $record['pid'],
                    'fname' => $record['fname'],
                    'lname' => $record['lname'],
                    'contact' => $record['contact'],
                    'email' => $record['email'],
                    'blood_group' => $record['blood_group']
                ],
                'records' => []
            ];
        }
        $grouped_records[$patient_id]['records'][] = $record;
    }
} catch (PDOException $e) {
    error_log("Fetch all medical records error: " . $e->getMessage());
}

// Fetch patient's medical records if selected
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$patient_medical_records = [];
$selected_patient_info = null;

if ($selected_patient_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = :pid");
        $stmt->execute([':pid' => $selected_patient_id]);
        $selected_patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT mr.*, 
                   d.fullname as doctor_name,
                   apt.appointmentDate, apt.appointmentTime
            FROM medical_records mr
            LEFT JOIN doctb d ON mr.doctor_id = d.id
            LEFT JOIN appointmenttb apt ON mr.appointment_id = apt.ID
            WHERE mr.patient_id = :patient_id AND mr.doctor_id = :doctor_id
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([':patient_id' => $selected_patient_id, ':doctor_id' => $doctor_id]);
        $patient_medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch patient records error: " . $e->getMessage());
    }
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Quản lý Hồ sơ bệnh án - Bệnh viện Global</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .nav-tabs .nav-link {
            color: #6b7280;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            padding: 12px 20px;
        }

        .nav-tabs .nav-link.active {
            color: #10b981;
            background-color: transparent;
            border-bottom-color: #10b981;
        }

        .nav-tabs .nav-link:hover {
            color: #10b981;
        }

        .tab-content {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .patient-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid #d1d5db;
        }

        .patient-card:hover {
            background-color: #f9fafb;
            border-left-color: #10b981;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .patient-card.active {
            background-color: #f0fdf4;
            border-left-color: #10b981;
        }

        .patient-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .patient-meta {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .medical-record-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }

        .form-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-group-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
        }

        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .textarea-control {
            min-height: 100px;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background-color: #10b981;
            border-color: #10b981;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #059669;
            border-color: #059669;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #059669;
        }

        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
        }

        .vital-input-card {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #bbf7d0;
        }

        .vital-input-card .form-label {
            color: #047857;
            font-size: 12px;
        }

        .patient-list-container {
            max-height: 600px;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .form-group-row {
                grid-template-columns: 1fr;
            }

            .vitals-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .tab-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>

    <div class="container-lg py-4">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại bảng điều khiển
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-file-medical"></i> Quản lý Hồ sơ bệnh án</h1>
            <p>Xem, thêm và quản lý hồ sơ bệnh án của bệnh nhân</p>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="medicalTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="view-tab" data-toggle="tab" href="#view-content" role="tab">
                    <i class="fas fa-list"></i> Xem bệnh án
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="add-tab" data-toggle="tab" href="#add-content" role="tab">
                    <i class="fas fa-plus"></i> Thêm bệnh án
                </a>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="medicalTabsContent">
            <!-- View Tab -->
            <div class="tab-pane fade show active" id="view-content" role="tabpanel">
                <div>
                    <h5 class="mb-4" style="color: #1f2937; font-weight: 700;">
                        <i class="fas fa-list"></i> Danh sách bệnh án
                    </h5>

                    <?php if (empty($grouped_records)) { ?>
                        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                            <h5 style="color: #6b7280;">Không có bệnh án</h5>
                            <p style="color: #9ca3af;">Bạn chưa thêm bệnh án nào. Hãy vào tab "Thêm bệnh án" để tạo mới.</p>
                        </div>
                    <?php } else { ?>
                        <div style="display: grid; gap: 12px;">
                            <?php foreach ($grouped_records as $patient_id => $group) { ?>
                                <div class="patient-group" data-patient-id="<?php echo $patient_id; ?>" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                    <!-- Patient Header (Always Visible) -->
                                    <div class="patient-header" style="padding: 18px 20px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                                        <div style="flex: 1;">
                                            <h6 style="margin: 0 0 8px 0; font-weight: 700; font-size: 16px;">
                                                <i class="fas fa-user-circle" style="margin-right: 8px;"></i><?php echo $group['patient_info']['fname'] . ' ' . $group['patient_info']['lname']; ?>
                                            </h6>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; font-size: 12px; opacity: 0.9;">
                                                <div><i class="fas fa-phone" style="margin-right: 4px;"></i><?php echo $group['patient_info']['contact']; ?></div>
                                                <div><i class="fas fa-envelope" style="margin-right: 4px;"></i><?php echo $group['patient_info']['email']; ?></div>
                                                <div><i class="fas fa-file-medical" style="margin-right: 4px;"></i><?php echo count($group['records']); ?> bệnh án</div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 24px; transition: transform 0.3s ease;" class="expand-icon">
                                                <i class="fas fa-chevron-down"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Patient Records (Expandable) -->
                                    <div class="patient-records" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease; background: #f8fafc;">
                                        <div style="padding: 0;">
                                            <?php foreach ($group['records'] as $index => $record) { ?>
                                                <div style="padding: 15px 20px; border-top: 1px solid #e5e7eb; background: white; margin: 0;">
                                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                                        <div>
                                                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">Bệnh án #<?php echo count($group['records']) - $index; ?></div>
                                                            <div style="font-size: 12px; color: #6b7280;">
                                                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                                            </div>
                                                        </div>
                                                        <div style="display: flex; gap: 6px;">
                                                            <button type="button" class="btn btn-sm btn-info view-record" data-record-id="<?php echo $record['id']; ?>" style="padding: 4px 10px; font-size: 11px;">
                                                                <i class="fas fa-eye"></i> Xem
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning edit-record" data-record-id="<?php echo $record['id']; ?>" style="padding: 4px 10px; font-size: 11px;">
                                                                <i class="fas fa-edit"></i> Sửa
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger delete-record" data-record-id="<?php echo $record['id']; ?>" style="padding: 4px 10px; font-size: 11px;">
                                                                <i class="fas fa-trash"></i> Xóa
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($record['symptoms']) { ?>
                                                        <div style="margin-bottom: 8px;">
                                                            <span style="font-weight: 600; color: #6b7280; font-size: 12px;">Triệu chứng:</span>
                                                            <div style="color: #374151; font-size: 13px; margin-top: 2px;"><?php echo substr($record['symptoms'], 0, 120); ?><?php echo strlen($record['symptoms']) > 120 ? '...' : ''; ?></div>
                                                        </div>
                                                    <?php } ?>

                                                    <?php if ($record['diagnosis']) { ?>
                                                        <div style="margin-bottom: 8px;">
                                                            <span style="font-weight: 600; color: #6b7280; font-size: 12px;">Chẩn đoán:</span>
                                                            <div style="color: #374151; font-size: 13px; margin-top: 2px;"><?php echo substr($record['diagnosis'], 0, 120); ?><?php echo strlen($record['diagnosis']) > 120 ? '...' : ''; ?></div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- Add Tab -->
            <div class="tab-pane fade" id="add-content" role="tabpanel">
                <h5 style="color: #1f2937; font-weight: 700; margin-bottom: 25px;">
                    <i class="fas fa-plus-circle"></i> Thêm hồ sơ bệnh án mới
                </h5>

                <form method="POST" action="">
                    <div class="form-section">
                        <h6 style="color: #1f2937; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-user"></i> Chọn bệnh nhân
                        </h6>

                        <div class="form-group">
                            <label class="form-label">Bác sĩ <span style="color: #ef4444;">*</span></label>
                            <select id="doctor_select" class="form-control" required>
                                <option value="">-- Chọn bác sĩ --</option>
                                <?php
                                try {
                                    $doctors = $pdo->query("SELECT id, fullname FROM doctb ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($doctors as $doc) {
                                        $selected = ($doc['fullname'] === $doctor) ? 'selected' : '';
                                        echo "<option value='" . $doc['id'] . "' " . $selected . ">" . $doc['fullname'] . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Get doctors error: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bệnh nhân <span style="color: #ef4444;">*</span></label>
                            <select name="patient_id" id="patient_select" class="form-control" required>
                                <option value="">-- Chọn bệnh nhân --</option>
                                <?php foreach ($doctor_patients as $patient) { ?>
                                    <option value="<?php echo $patient['pid']; ?>">
                                        <?php echo $patient['fname'] . ' ' . $patient['lname'] . ' - ' . $patient['contact']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Lịch hẹn (tùy chọn)</label>
                            <select name="appointment_id" id="appointment_select" class="form-control">
                                <option value="">-- Chọn bệnh nhân trước --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6 style="color: #1f2937; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-stethoscope"></i> Triệu chứng và Chẩn đoán
                        </h6>

                        <div class="form-group">
                            <label class="form-label">Triệu chứng <span style="color: #ef4444;">*</span></label>
                            <textarea name="symptoms" class="form-control textarea-control" placeholder="Mô tả triệu chứng của bệnh nhân..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Chẩn đoán <span style="color: #ef4444;">*</span></label>
                            <textarea name="diagnosis" class="form-control textarea-control" placeholder="Kết quả chẩn đoán..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Điều trị <span style="color: #ef4444;">*</span></label>
                            <textarea name="treatment" class="form-control textarea-control" placeholder="Kế hoạch điều trị..." required></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6 style="color: #1f2937; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-heartbeat"></i> Chỉ số sức khỏe
                        </h6>

                        <div class="vitals-grid">
                            <div class="vital-input-card">
                                <label class="form-label">Chiều cao (cm)</label>
                                <input type="number" name="height" class="form-control" placeholder="cm" step="0.1">
                            </div>

                            <div class="vital-input-card">
                                <label class="form-label">Cân nặng (kg)</label>
                                <input type="number" name="weight" class="form-control" placeholder="kg" step="0.1">
                            </div>

                            <div class="vital-input-card">
                                <label class="form-label">Huyết áp</label>
                                <input type="text" name="blood_pressure" class="form-control" placeholder="e.g., 120/80">
                            </div>

                            <div class="vital-input-card">
                                <label class="form-label">Nhịp tim (bpm)</label>
                                <input type="number" name="heart_rate" class="form-control" placeholder="bpm" step="1">
                            </div>

                            <div class="vital-input-card">
                                <label class="form-label">Nhiệt độ (°C)</label>
                                <input type="number" name="temperature" class="form-control" placeholder="°C" step="0.1">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h6 style="color: #1f2937; font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-sticky-note"></i> Ghi chú bổ sung
                        </h6>

                        <div class="form-group">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control textarea-control" placeholder="Ghi chú thêm về bệnh nhân..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <button type="submit" name="add_medical_record" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Lưu hồ sơ bệnh án
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Modal Xem Chi Tiết -->
    <div class="modal fade" id="viewDetailModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <h5 class="modal-title" id="viewDetailLabel"><i class="fas fa-file-medical"></i> Chi tiết hồ sơ bệnh án</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="viewDetailContent">
                    <p>Đang tải...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Chỉnh Sửa -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <h5 class="modal-title" id="editLabel"><i class="fas fa-edit"></i> Chỉnh sửa hồ sơ bệnh án</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm">
                    <input type="hidden" id="editRecordId" name="record_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Bác sĩ <span style="color: red;">*</span> (không thể chỉnh sửa)</label>
                            <input type="text" class="form-control" id="editDoctorName" disabled>
                        </div>

                        <div class="form-group">
                            <label>Triệu chứng</label>
                            <textarea class="form-control" name="symptoms" id="editSymptoms" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Chẩn đoán</label>
                            <textarea class="form-control" name="diagnosis" id="editDiagnosis" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Điều trị</label>
                            <textarea class="form-control" name="treatment_plan" id="editTreatment" rows="3"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label>Chiều cao (cm)</label>
                                <input type="number" class="form-control" name="height" id="editHeight" step="0.1">
                            </div>
                            <div class="form-group">
                                <label>Cân nặng (kg)</label>
                                <input type="number" class="form-control" name="weight" id="editWeight" step="0.1">
                            </div>
                            <div class="form-group">
                                <label>Huyết áp</label>
                                <input type="text" class="form-control" name="blood_pressure" id="editBloodPressure" placeholder="e.g., 120/80">
                            </div>
                            <div class="form-group">
                                <label>Nhịp tim (bpm)</label>
                                <input type="number" class="form-control" name="heart_rate" id="editHeartRate" step="1">
                            </div>
                            <div class="form-group">
                                <label>Nhiệt độ (°C)</label>
                                <input type="number" class="form-control" name="temperature" id="editTemperature" step="0.1">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea class="form-control" name="notes" id="editNotes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize doctor select on page load
        document.addEventListener('DOMContentLoaded', function() {
            const doctorSelect = document.getElementById('doctor_select');
            if (doctorSelect && doctorSelect.value) {
                // Trigger change event to load patients and appointments
                doctorSelect.dispatchEvent(new Event('change'));
            }

            // Handle patient group expand/collapse
            document.querySelectorAll('.patient-header').forEach(header => {
                header.addEventListener('click', function() {
                    const group = this.closest('.patient-group');
                    const recordsDiv = group.querySelector('.patient-records');
                    const icon = group.querySelector('.expand-icon i');
                    
                    const isExpanded = recordsDiv.style.maxHeight !== '0px' && recordsDiv.style.maxHeight !== '';
                    
                    if (isExpanded) {
                        // Collapse
                        recordsDiv.style.maxHeight = '0';
                        icon.style.transform = 'rotate(0deg)';
                    } else {
                        // Expand
                        recordsDiv.style.maxHeight = recordsDiv.scrollHeight + 'px';
                        icon.style.transform = 'rotate(180deg)';
                    }
                });
            });
        });

        // Xem chi tiết hồ sơ
        $('.view-record').click(function() {
            const recordId = $(this).data('record-id');
            fetch('ajax/get_medical_record_detail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'record_id=' + recordId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const record = data.record;
                    let html = `
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                            <div>
                                <strong style="color: #6b7280;">Bệnh nhân:</strong>
                                <div>${record.fname} ${record.lname}</div>
                            </div>
                            <div>
                                <strong style="color: #6b7280;">Bác sĩ:</strong>
                                <div>${record.doctor_name || 'N/A'}</div>
                            </div>
                            <div>
                                <strong style="color: #6b7280;">Ngày khám:</strong>
                                <div>${new Date(record.record_date).toLocaleDateString('vi-VN')}</div>
                            </div>
                            <div>
                                <strong style="color: #6b7280;">Tạo lúc:</strong>
                                <div>${new Date(record.created_at).toLocaleString('vi-VN')}</div>
                            </div>
                        </div>
                        <hr>
                    `;

                    if (record.symptoms) {
                        html += `<div style="margin-bottom: 15px;">
                            <strong style="color: #6b7280;">Triệu chứng:</strong>
                            <div style="padding: 8px; background: #f8fafc; border-radius: 4px; margin-top: 5px;">${record.symptoms}</div>
                        </div>`;
                    }

                    if (record.diagnosis) {
                        html += `<div style="margin-bottom: 15px;">
                            <strong style="color: #6b7280;">Chẩn đoán:</strong>
                            <div style="padding: 8px; background: #f8fafc; border-radius: 4px; margin-top: 5px;">${record.diagnosis}</div>
                        </div>`;
                    }

                    if (record.treatment_plan) {
                        html += `<div style="margin-bottom: 15px;">
                            <strong style="color: #6b7280;">Điều trị:</strong>
                            <div style="padding: 8px; background: #f8fafc; border-radius: 4px; margin-top: 5px;">${record.treatment_plan}</div>
                        </div>`;
                    }

                    if (record.height || record.weight || record.blood_pressure || record.heart_rate || record.temperature) {
                        html += `<div style="margin-bottom: 15px;">
                            <strong style="color: #6b7280;">Chỉ số sức khỏe:</strong>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 8px;">`;
                        if (record.height) html += `<div>Chiều cao: <strong>${record.height} cm</strong></div>`;
                        if (record.weight) html += `<div>Cân nặng: <strong>${record.weight} kg</strong></div>`;
                        if (record.blood_pressure) html += `<div>Huyết áp: <strong>${record.blood_pressure}</strong></div>`;
                        if (record.heart_rate) html += `<div>Nhịp tim: <strong>${record.heart_rate} bpm</strong></div>`;
                        if (record.temperature) html += `<div>Nhiệt độ: <strong>${record.temperature}°C</strong></div>`;
                        html += `</div></div>`;
                    }

                    if (record.notes) {
                        html += `<div style="margin-bottom: 15px;">
                            <strong style="color: #6b7280;">Ghi chú:</strong>
                            <div style="padding: 8px; background: #fef3c7; border-radius: 4px; margin-top: 5px;">${record.notes.replace(/\n/g, '<br>')}</div>
                        </div>`;
                    }

                    $('#viewDetailContent').html(html);
                    $('#viewDetailModal').modal('show');
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Chỉnh sửa hồ sơ
        $('.edit-record').click(function() {
            const recordId = $(this).data('record-id');
            fetch('ajax/get_medical_record_detail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'record_id=' + recordId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const record = data.record;
                    $('#editRecordId').val(recordId);
                    $('#editDoctorName').val(record.doctor_name || 'N/A');
                    $('#editSymptoms').val(record.symptoms || '');
                    $('#editDiagnosis').val(record.diagnosis || '');
                    $('#editTreatment').val(record.treatment_plan || '');
                    $('#editHeight').val(record.height || '');
                    $('#editWeight').val(record.weight || '');
                    $('#editBloodPressure').val(record.blood_pressure || '');
                    $('#editHeartRate').val(record.heart_rate || '');
                    $('#editTemperature').val(record.temperature || '');
                    $('#editNotes').val(record.notes || '');
                    $('#editModal').modal('show');
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Submit form chỉnh sửa
        $('#editForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('ajax/update_medical_record.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    $('#editModal').modal('hide');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Xóa hồ sơ
        $('.delete-record').click(function() {
            const recordId = $(this).data('record-id');
            if (confirm('Bạn có chắc chắn muốn xóa hồ sơ bệnh án này? Hành động này không thể hoàn tác!')) {
                fetch('ajax/delete_medical_record.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'record_id=' + recordId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Lỗi: ' + (data.error || 'Không xác định'));
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });

        // Handle doctor selection and load patients
        document.getElementById('doctor_select').addEventListener('change', function() {
            const doctorId = this.value;
            const patientSelect = document.getElementById('patient_select');
            const appointmentSelect = document.getElementById('appointment_select');

            if (!doctorId) {
                patientSelect.innerHTML = '<option value="">-- Chọn bác sĩ trước --</option>';
                patientSelect.disabled = true;
                appointmentSelect.innerHTML = '<option value="">-- Chọn bệnh nhân trước --</option>';
                appointmentSelect.disabled = true;
                return;
            }

            patientSelect.disabled = true;
            patientSelect.innerHTML = '<option value="">Đang tải...</option>';

            // Send AJAX request to get patients for this doctor
            fetch('ajax/get_patients_by_doctor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'doctor_id=' + doctorId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.patients.length > 0) {
                    let optionsHtml = '<option value="">-- Chọn bệnh nhân --</option>';
                    data.patients.forEach(patient => {
                        optionsHtml += `<option value="${patient.pid}">
                            ${patient.fname} ${patient.lname} - ${patient.contact}
                        </option>`;
                    });
                    patientSelect.innerHTML = optionsHtml;
                    patientSelect.disabled = false;
                    // Auto-select first patient and load appointments
                    if (data.patients.length > 0) {
                        patientSelect.value = data.patients[0].pid;
                        patientSelect.dispatchEvent(new Event('change'));
                    }
                } else {
                    patientSelect.innerHTML = '<option value="">Bác sĩ này chưa khám bệnh nhân nào</option>';
                    patientSelect.disabled = true;
                    appointmentSelect.innerHTML = '<option value="">-- Chọn bệnh nhân trước --</option>';
                    appointmentSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                patientSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                patientSelect.disabled = true;
                appointmentSelect.innerHTML = '<option value="">-- Chọn bệnh nhân trước --</option>';
                appointmentSelect.disabled = true;
            });
        });

        // Handle patient selection and load appointments
        document.getElementById('patient_select').addEventListener('change', function() {
            const patientId = this.value;
            const doctorId = document.getElementById('doctor_select').value;
            const appointmentSelect = document.getElementById('appointment_select');

            if (!patientId || !doctorId) {
                appointmentSelect.innerHTML = '<option value="">-- Chọn bác sĩ và bệnh nhân trước --</option>';
                appointmentSelect.disabled = true;
                return;
            }

            appointmentSelect.disabled = true;
            appointmentSelect.innerHTML = '<option value="">Đang tải lịch hẹn...</option>';

            // Send AJAX request to get appointments for this patient and doctor
            fetch('ajax/get_appointments_by_patient.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'patient_id=' + patientId + '&doctor_id=' + doctorId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.appointments.length > 0) {
                    let optionsHtml = '<option value="">-- Không chọn lịch hẹn --</option>';
                    data.appointments.forEach(apt => {
                        const appointDate = new Date(apt.appdate);
                        const formattedDate = appointDate.toLocaleDateString('vi-VN');
                        const formattedTime = apt.apptime;
                        optionsHtml += `<option value="${apt.ID}">
                            ${formattedDate} - ${formattedTime}
                        </option>`;
                    });
                    appointmentSelect.innerHTML = optionsHtml;
                    appointmentSelect.disabled = false;
                    // Auto-select latest appointment (first one since sorted DESC)
                    appointmentSelect.value = data.appointments[0].ID;
                } else {
                    appointmentSelect.innerHTML = '<option value="">Bác sĩ này chưa khám bệnh nhân này</option>';
                    appointmentSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                appointmentSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                appointmentSelect.disabled = true;
            });
        });
    </script>
</body>

</html>