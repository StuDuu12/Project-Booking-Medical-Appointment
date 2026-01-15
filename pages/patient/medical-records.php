<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$pid = $_SESSION['pid'] ?? null;
$fname = $_SESSION['fname'] ?? '';
$lname = $_SESSION['lname'] ?? '';

if (!$pid) {
    header("Location: ../../index.php");
    exit();
}

// Fetch medical records for patient
$medical_records = [];
try {
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               p.fname, p.lname, p.email, p.contact,
               d.fullname as doctor_name,
               apt.appointmentDate, apt.appointmentTime
        FROM medical_records mr
        LEFT JOIN patreg p ON mr.patient_id = p.pid
        LEFT JOIN doctb d ON mr.doctor_id = d.id
        LEFT JOIN appointmenttb apt ON mr.appointment_id = apt.ID
        WHERE mr.patient_id = :pid
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([':pid' => $pid]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch medical records error: " . $e->getMessage());
}

// Fetch patient profile
$patient_profile = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = :pid");
    $stmt->execute([':pid' => $pid]);
    $patient_profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch patient profile error: " . $e->getMessage());
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Lịch sử bệnh án - Bệnh viện Global</title>

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

        .medical-record-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .medical-record-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .record-date {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .record-doctor {
            color: #0891b2;
            font-weight: 600;
        }

        .record-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .record-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .record-item {
            border-left: 3px solid #cbd5e1;
            padding-left: 15px;
        }

        .record-item-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .record-item-value {
            font-size: 15px;
            color: #1f2937;
            font-weight: 500;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .vital-card {
            background: #f0fdf4;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }

        .vital-label {
            font-size: 11px;
            color: #047857;
            font-weight: 600;
            text-transform: uppercase;
        }

        .vital-value {
            font-size: 18px;
            font-weight: 700;
            color: #065f46;
            margin-top: 5px;
        }

        .records-empty {
            background: white;
            border-radius: 8px;
            padding: 60px 20px;
            text-align: center;
            color: #6b7280;
        }

        .records-empty i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 15px;
        }

        .page-header {
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
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

        .patient-info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #0891b2;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            border-left: 3px solid #cbd5e1;
            padding-left: 15px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0891b2;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #06b6d4;
            gap: 12px;
        }

        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-input:focus {
            outline: none;
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        .btn-filter {
            padding: 8px 16px;
            background-color: #0891b2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background-color: #06b6d4;
        }

        .btn-reset {
            padding: 8px 16px;
            background-color: #e5e7eb;
            color: #374151;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background-color: #d1d5db;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .record-content {
                grid-template-columns: 1fr;
            }

            .vitals-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .patient-info-grid {
                grid-template-columns: 1fr;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-input {
                width: 100%;
            }

            .btn-filter,
            .btn-reset {
                width: 100%;
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
            <h1><i class="fas fa-file-medical-alt"></i> Lịch sử bệnh án</h1>
            <p>Xem và quản lý hồ sơ bệnh án của bạn</p>
        </div>

        <!-- Patient Info Card -->
        <?php if ($patient_profile) { ?>
            <div class="patient-info-card">
                <h5 class="mb-3" style="color: #0891b2; font-weight: 700;">
                    <i class="fas fa-user-circle"></i> Thông tin cá nhân
                </h5>
                <div class="patient-info-grid">
                    <div class="info-item">
                        <div class="info-label">Họ và tên</div>
                        <div class="info-value"><?php echo $patient_profile['fname'] . ' ' . $patient_profile['lname']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $patient_profile['email']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Số điện thoại</div>
                        <div class="info-value"><?php echo $patient_profile['contact']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nhóm máu</div>
                        <div class="info-value"><?php echo $patient_profile['blood_group'] ?? 'Chưa cập nhật'; ?></div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <input type="text" id="searchInput" class="filter-input" placeholder="Tìm kiếm bác sĩ, chẩn đoán...">
                <button class="btn-filter" onclick="filterRecords()">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <button class="btn-reset" onclick="resetFilter()">
                    <i class="fas fa-redo"></i> Đặt lại
                </button>
            </div>
        </div>

        <!-- Medical Records -->
        <div id="recordsContainer">
            <?php if (empty($medical_records)) { ?>
                <div class="records-empty">
                    <i class="fas fa-inbox"></i>
                    <h5>Không có hồ sơ bệnh án</h5>
                    <p>Bạn chưa có hồ sơ bệnh án nào. Các hồ sơ sẽ được tạo sau khi bác sĩ cập nhật thông tin khám phá.</p>
                </div>
            <?php } else { ?>
                <?php foreach ($medical_records as $record) { ?>
                    <div class="medical-record-card record-item-data">
                        <div class="record-header">
                            <div>
                                <div class="record-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?>
                                </div>
                                <div class="record-doctor" style="margin-top: 5px;">
                                    <i class="fas fa-user-md"></i>
                                    <?php echo $record['doctor_name'] ?? 'Chưa xác định'; ?>
                                </div>
                            </div>
                            <div>
                                <span class="record-status <?php echo $record['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $record['status'] === 'completed' ? 'Hoàn thành' : 'Đang chờ'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="record-content">
                            <?php if ($record['diagnosis']) { ?>
                                <div class="record-item">
                                    <div class="record-item-label">Chẩn đoán</div>
                                    <div class="record-item-value"><?php echo $record['diagnosis']; ?></div>
                                </div>
                            <?php } ?>

                            <?php if ($record['symptoms']) { ?>
                                <div class="record-item">
                                    <div class="record-item-label">Triệu chứng</div>
                                    <div class="record-item-value"><?php echo $record['symptoms']; ?></div>
                                </div>
                            <?php } ?>

                            <?php if ($record['treatment']) { ?>
                                <div class="record-item">
                                    <div class="record-item-label">Điều trị</div>
                                    <div class="record-item-value"><?php echo $record['treatment']; ?></div>
                                </div>
                            <?php } ?>

                            <?php if ($record['appointment_date']) { ?>
                                <div class="record-item">
                                    <div class="record-item-label">Ngày khám</div>
                                    <div class="record-item-value">
                                        <?php echo date('d/m/Y', strtotime($record['appointment_date'])); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Vitals Section -->
                        <?php if ($record['height'] || $record['weight'] || $record['blood_pressure'] || $record['heart_rate'] || $record['temperature']) { ?>
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                                <h6 style="color: #0891b2; font-weight: 700; margin-bottom: 15px;">
                                    <i class="fas fa-heartbeat"></i> Chỉ số sức khỏe
                                </h6>
                                <div class="vitals-grid">
                                    <?php if ($record['height']) { ?>
                                        <div class="vital-card">
                                            <div class="vital-label">Chiều cao</div>
                                            <div class="vital-value"><?php echo $record['height']; ?> cm</div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($record['weight']) { ?>
                                        <div class="vital-card">
                                            <div class="vital-label">Cân nặng</div>
                                            <div class="vital-value"><?php echo $record['weight']; ?> kg</div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($record['blood_pressure']) { ?>
                                        <div class="vital-card">
                                            <div class="vital-label">Huyết áp</div>
                                            <div class="vital-value"><?php echo $record['blood_pressure']; ?></div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($record['heart_rate']) { ?>
                                        <div class="vital-card">
                                            <div class="vital-label">Nhịp tim</div>
                                            <div class="vital-value"><?php echo $record['heart_rate']; ?> bpm</div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($record['temperature']) { ?>
                                        <div class="vital-card">
                                            <div class="vital-label">Nhiệt độ</div>
                                            <div class="vital-value"><?php echo $record['temperature']; ?>°C</div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>

                        <!-- Additional Notes -->
                        <?php if ($record['notes']) { ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                                <h6 style="color: #0891b2; font-weight: 700; margin-bottom: 10px;">
                                    <i class="fas fa-sticky-note"></i> Ghi chú
                                </h6>
                                <p style="color: #374151; margin: 0; line-height: 1.6;"><?php echo nl2br($record['notes']); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function filterRecords() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const records = document.querySelectorAll('.record-item-data');

            records.forEach(record => {
                const text = record.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    record.style.display = 'block';
                } else {
                    record.style.display = 'none';
                }
            });
        }

        function resetFilter() {
            document.getElementById('searchInput').value = '';
            const records = document.querySelectorAll('.record-item-data');
            records.forEach(record => {
                record.style.display = 'block';
            });
        }

        document.getElementById('searchInput').addEventListener('keyup', filterRecords);
    </script>
</body>

</html>