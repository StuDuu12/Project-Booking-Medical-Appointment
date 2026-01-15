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
        $appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
        $symptoms = $_POST['symptoms'];
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $height = isset($_POST['height']) ? $_POST['height'] : null;
        $weight = isset($_POST['weight']) ? $_POST['weight'] : null;
        $blood_pressure = isset($_POST['blood_pressure']) ? $_POST['blood_pressure'] : null;
        $heart_rate = isset($_POST['heart_rate']) ? $_POST['heart_rate'] : null;
        $temperature = isset($_POST['temperature']) ? $_POST['temperature'] : null;

        // Get doctor ID
        $stmt = $pdo->prepare("SELECT id FROM doctb WHERE fullname = :fullname");
        $stmt->execute([':fullname' => $doctor]);
        $doctor_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $doctor_id = $doctor_result['id'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO medical_records (
                patient_id, doctor_id, appointment_id, symptoms, diagnosis, treatment, 
                notes, height, weight, blood_pressure, heart_rate, temperature, status, created_at, updated_at
            ) VALUES (
                :patient_id, :doctor_id, :appointment_id, :symptoms, :diagnosis, :treatment, 
                :notes, :height, :weight, :blood_pressure, :heart_rate, :temperature, 'completed', NOW(), NOW()
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
        redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Lỗi khi thêm hồ sơ bệnh án!');
    }
}

// Fetch doctor's patients
$doctor_patients = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.pid, p.fname, p.lname, p.contact, p.email, p.blood_group,
               COUNT(a.ID) as total_appointments,
               MAX(a.appointmentDate) as last_visit
        FROM patreg p
        LEFT JOIN appointmenttb a ON p.pid = a.pid AND a.doctor = :doctor
        WHERE a.doctor = :doctor OR p.pid IN (
            SELECT DISTINCT pid FROM appointmenttb WHERE doctor = :doctor
        )
        GROUP BY p.pid
        ORDER BY MAX(a.appointmentDate) DESC
    ");
    $stmt->execute([':doctor' => $doctor]);
    $doctor_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch doctor patients error: " . $e->getMessage());
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
            WHERE mr.patient_id = :patient_id
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([':patient_id' => $selected_patient_id]);
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
                    <i class="fas fa-list"></i> Xem hồ sơ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="add-tab" data-toggle="tab" href="#add-content" role="tab">
                    <i class="fas fa-plus"></i> Thêm hồ sơ
                </a>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="medicalTabsContent">
            <!-- View Tab -->
            <div class="tab-pane fade show active" id="view-content" role="tabpanel">
                <div class="row">
                    <!-- Patient List -->
                    <div class="col-lg-3">
                        <h5 class="mb-3" style="color: #1f2937; font-weight: 700;">
                            <i class="fas fa-users"></i> Danh sách bệnh nhân
                        </h5>
                        <div class="patient-list-container">
                            <?php if (empty($doctor_patients)) { ?>
                                <div style="text-align: center; color: #6b7280; padding: 20px;">
                                    <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px;"></i>
                                    <p>Không có bệnh nhân</p>
                                </div>
                            <?php } else { ?>
                                <?php foreach ($doctor_patients as $patient) { ?>
                                    <a href="?page=view&patient_id=<?php echo $patient['pid']; ?>" style="text-decoration: none; color: inherit;">
                                        <div class="patient-card <?php echo ($selected_patient_id === intval($patient['pid'])) ? 'active' : ''; ?>">
                                            <div class="patient-name">
                                                <?php echo $patient['fname'] . ' ' . $patient['lname']; ?>
                                            </div>
                                            <div class="patient-meta">
                                                <span><i class="fas fa-phone"></i> <?php echo $patient['contact']; ?></span>
                                                <span><i class="fas fa-calendar"></i> <?php echo $patient['total_appointments']; ?> lần khám</span>
                                            </div>
                                        </div>
                                    </a>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Patient Records -->
                    <div class="col-lg-9">
                        <?php if ($selected_patient_info) { ?>
                            <div style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #10b981;">
                                <h5 style="color: #1f2937; font-weight: 700; margin-bottom: 15px;">
                                    <i class="fas fa-user-circle"></i> <?php echo $selected_patient_info['fname'] . ' ' . $selected_patient_info['lname']; ?>
                                </h5>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    <div>
                                        <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Email</div>
                                        <div style="color: #1f2937; font-weight: 500;"><?php echo $selected_patient_info['email']; ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Số điện thoại</div>
                                        <div style="color: #1f2937; font-weight: 500;"><?php echo $selected_patient_info['contact']; ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Nhóm máu</div>
                                        <div style="color: #1f2937; font-weight: 500;"><?php echo $selected_patient_info['blood_group'] ?? 'Chưa xác định'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Medical Records List -->
                            <?php if (empty($patient_medical_records)) { ?>
                                <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px;">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                                    <h5 style="color: #6b7280;">Không có hồ sơ bệnh án</h5>
                                    <p style="color: #9ca3af;">Bệnh nhân chưa có hồ sơ bệnh án nào. Hãy thêm một hồ sơ mới.</p>
                                </div>
                            <?php } else { ?>
                                <?php foreach ($patient_medical_records as $record) { ?>
                                    <div class="medical-record-card">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px;">
                                            <div>
                                                <div style="font-size: 14px; color: #6b7280;">
                                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                                </div>
                                            </div>
                                            <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #d1fae5; color: #065f46;">
                                                Hoàn thành
                                            </span>
                                        </div>

                                        <?php if ($record['symptoms']) { ?>
                                            <div style="margin-bottom: 15px;">
                                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 5px;">Triệu chứng</div>
                                                <div style="color: #374151;"><?php echo $record['symptoms']; ?></div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($record['diagnosis']) { ?>
                                            <div style="margin-bottom: 15px;">
                                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 5px;">Chẩn đoán</div>
                                                <div style="color: #374151;"><?php echo $record['diagnosis']; ?></div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($record['treatment']) { ?>
                                            <div style="margin-bottom: 15px;">
                                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 5px;">Điều trị</div>
                                                <div style="color: #374151;"><?php echo $record['treatment']; ?></div>
                                            </div>
                                        <?php } ?>

                                        <!-- Vitals Display -->
                                        <?php if ($record['height'] || $record['weight'] || $record['blood_pressure'] || $record['heart_rate'] || $record['temperature']) { ?>
                                            <div style="background: white; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #e5e7eb;">
                                                <div style="font-size: 12px; color: #047857; text-transform: uppercase; font-weight: 600; margin-bottom: 10px;">
                                                    <i class="fas fa-heartbeat"></i> Chỉ số sức khỏe
                                                </div>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px;">
                                                    <?php if ($record['height']) { ?>
                                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 4px;">
                                                            <div style="font-size: 10px; color: #047857; font-weight: 600;">Chiều cao</div>
                                                            <div style="font-size: 14px; font-weight: 700; color: #065f46;"><?php echo $record['height']; ?> cm</div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if ($record['weight']) { ?>
                                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 4px;">
                                                            <div style="font-size: 10px; color: #047857; font-weight: 600;">Cân nặng</div>
                                                            <div style="font-size: 14px; font-weight: 700; color: #065f46;"><?php echo $record['weight']; ?> kg</div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if ($record['blood_pressure']) { ?>
                                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 4px;">
                                                            <div style="font-size: 10px; color: #047857; font-weight: 600;">Huyết áp</div>
                                                            <div style="font-size: 14px; font-weight: 700; color: #065f46;"><?php echo $record['blood_pressure']; ?></div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if ($record['heart_rate']) { ?>
                                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 4px;">
                                                            <div style="font-size: 10px; color: #047857; font-weight: 600;">Nhịp tim</div>
                                                            <div style="font-size: 14px; font-weight: 700; color: #065f46;"><?php echo $record['heart_rate']; ?> bpm</div>
                                                        </div>
                                                    <?php } ?>
                                                    <?php if ($record['temperature']) { ?>
                                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 4px;">
                                                            <div style="font-size: 10px; color: #047857; font-weight: 600;">Nhiệt độ</div>
                                                            <div style="font-size: 14px; font-weight: 700; color: #065f46;"><?php echo $record['temperature']; ?>°C</div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($record['notes']) { ?>
                                            <div style="margin-top: 15px; padding: 12px; background: #fef3c7; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                                <div style="font-size: 12px; color: #92400e; font-weight: 600; margin-bottom: 5px;">
                                                    <i class="fas fa-sticky-note"></i> Ghi chú
                                                </div>
                                                <div style="color: #78350f; font-size: 14px;"><?php echo nl2br($record['notes']); ?></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        <?php } else { ?>
                            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px; color: #6b7280;">
                                <i class="fas fa-hand-point-left" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                                <p>Chọn một bệnh nhân từ danh sách bên trái để xem hồ sơ bệnh án</p>
                            </div>
                        <?php } ?>
                    </div>
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
                            <label class="form-label">Bệnh nhân <span style="color: #ef4444;">*</span></label>
                            <select name="patient_id" class="form-control" required>
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
                            <select name="appointment_id" class="form-control">
                                <option value="">-- Không chọn --</option>
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
</body>

</html>