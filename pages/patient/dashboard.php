<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$pid = $_SESSION['pid'] ?? null;
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$fname = $_SESSION['fname'] ?? '';
$gender = $_SESSION['gender'] ?? '';
$lname = $_SESSION['lname'] ?? '';
$contact = $_SESSION['contact'] ?? '';

if (!$pid) {
    header("Location: ../../index.php");
    exit();
}

// Handle page parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = array('dashboard', 'book-appointment', 'appointment-history', 'prescriptions');
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Booking step handling
$booking_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$selected_spec = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
$selected_doctor = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : null;

// Handle date from dropdown or direct date input
$selected_date = null;
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $selected_date = $_GET['date'];
} elseif (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
    // Construct date from dropdown selections
    $year = intval($_GET['year']);
    $month = str_pad(intval($_GET['month']), 2, '0', STR_PAD_LEFT);
    $day = str_pad(intval($_GET['day']), 2, '0', STR_PAD_LEFT);

    // Validate and create date
    $selected_date = "$year-$month-$day";

    // Validate that the date is valid and not in the past
    $date_obj = DateTime::createFromFormat('Y-m-d', $selected_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $selected_date) {
        $selected_date = null; // Invalid date
    } elseif ($date_obj < new DateTime('today')) {
        $selected_date = null; // Date in the past
    }
}

// Get specializations for booking
$specializations = [];
if ($page === 'book-appointment') {
    $spec_stmt = $pdo->query("SELECT id, name, name_vi, icon, description FROM specializations WHERE status = 1 ORDER BY name_vi");
    $specializations = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get doctors for selected specialization
$doctors = [];
if ($page === 'book-appointment' && $selected_spec && $booking_step >= 2) {
    $doc_stmt = $pdo->prepare("
        SELECT d.id, d.username, d.email, d.docFees, d.experience_years, d.bio,
               s.name_vi as spec_name
        FROM doctb d
        LEFT JOIN specializations s ON d.spec_id = s.id
        WHERE d.spec_id = :spec_id AND d.status = 1
        ORDER BY d.username
    ");
    $doc_stmt->execute([':spec_id' => $selected_spec]);
    $doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get time slots for selected doctor and date
$time_slots = [];
$schedule_info = null;
if ($page === 'book-appointment' && $selected_doctor && $selected_date && $booking_step >= 4) {
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $selected_date);
    if ($date_obj && $date_obj->format('Y-m-d') === $selected_date) {
        $day_of_week = date('w', strtotime($selected_date));

        // Get doctor schedule for this day
        $schedule_stmt = $pdo->prepare("
            SELECT start_time, end_time, slot_duration, max_patients
            FROM doctor_schedules
            WHERE doctor_id = :doctor_id AND day_of_week = :day_of_week AND is_active = 1
            LIMIT 1
        ");

        $schedule_stmt->execute([
            ':doctor_id' => $selected_doctor,
            ':day_of_week' => $day_of_week
        ]);

        $schedule_info = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

        if ($schedule_info) {
            // Generate time slots
            $start_time = new DateTime($schedule_info['start_time']);
            $end_time = new DateTime($schedule_info['end_time']);
            $slot_duration = intval($schedule_info['slot_duration']);

            $current_time = clone $start_time;

            while ($current_time < $end_time) {
                $slot_time = $current_time->format('H:i:s');
                $slot_time_display = $current_time->format('H:i');

                // Check if this slot already exists in database
                $check_stmt = $pdo->prepare("
                    SELECT id, status, appointment_id
                    FROM time_slots
                    WHERE doctor_id = :doctor_id 
                    AND slot_date = :slot_date 
                    AND slot_time = :slot_time
                ");

                $check_stmt->execute([
                    ':doctor_id' => $selected_doctor,
                    ':slot_date' => $selected_date,
                    ':slot_time' => $slot_time
                ]);

                $existing_slot = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_slot) {
                    // Slot exists in database
                    $time_slots[] = [
                        'id' => $existing_slot['id'],
                        'slot_time' => $slot_time_display,
                        'slot_time_full' => $slot_time,
                        'status' => $existing_slot['status'],
                        'appointment_id' => $existing_slot['appointment_id']
                    ];
                } else {
                    // Create new slot in database
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO time_slots (doctor_id, slot_date, slot_time, status)
                        VALUES (:doctor_id, :slot_date, :slot_time, 'available')
                    ");

                    $insert_stmt->execute([
                        ':doctor_id' => $selected_doctor,
                        ':slot_date' => $selected_date,
                        ':slot_time' => $slot_time
                    ]);

                    $new_slot_id = $pdo->lastInsertId();

                    $time_slots[] = [
                        'id' => $new_slot_id,
                        'slot_time' => $slot_time_display,
                        'slot_time_full' => $slot_time,
                        'status' => 'available',
                        'appointment_id' => null
                    ];
                }

                // Move to next slot
                $current_time->modify("+{$slot_duration} minutes");
            }

            // If date is today, mark past slots as blocked
            if ($selected_date === date('Y-m-d')) {
                $current_time_now = date('H:i:s');
                foreach ($time_slots as &$slot) {
                    if ($slot['slot_time_full'] <= $current_time_now && $slot['status'] === 'available') {
                        $slot['status'] = 'blocked';

                        // Update in database
                        $update_stmt = $pdo->prepare("
                            UPDATE time_slots 
                            SET status = 'blocked' 
                            WHERE id = :id
                        ");
                        $update_stmt->execute([':id' => $slot['id']]);
                    }
                }
            }
        }
    }
}

// Book appointment with new slot system
if (isset($_POST['app-submit'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $slot_id = intval($_POST['slot_id']);
    $appdate = $_POST['appdate'];
    $apptime = $_POST['apptime'];

    // Get doctor info
    $stmt = $pdo->prepare("SELECT username, docFees FROM doctb WHERE id = :id");
    $stmt->execute([':id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doctor) {
        // Remove currency symbols and convert to integer
        $docFees = intval($doctor['docFees']);
        $doctorName = $doctor['username'];

        // Check if slot is still available
        $check_stmt = $pdo->prepare("SELECT status FROM time_slots WHERE id = :slot_id AND status = 'available'");
        $check_stmt->execute([':slot_id' => $slot_id]);

        if ($check_stmt->rowCount() > 0) {
            // Insert appointment
            $stmt = $pdo->prepare("INSERT INTO appointmenttb(pid,fname,lname,gender,email,contact,doctor,docFees,appdate,apptime,slot_id,userStatus,doctorStatus) VALUES(:pid,:fname,:lname,:gender,:email,:contact,:doctor,:docFees,:appdate,:apptime,:slot_id,'1','1')");
            $result = $stmt->execute([
                ':pid' => $pid,
                ':fname' => $fname,
                ':lname' => $lname,
                ':gender' => $gender,
                ':email' => $email,
                ':contact' => $contact,
                ':doctor' => $doctorName,
                ':docFees' => $docFees,
                ':appdate' => $appdate,
                ':apptime' => $apptime,
                ':slot_id' => $slot_id
            ]);

            if ($result) {
                $appointment_id = $pdo->lastInsertId();
                // Update slot status
                $update_stmt = $pdo->prepare("UPDATE time_slots SET status = 'booked', appointment_id = :app_id WHERE id = :slot_id");
                $update_stmt->execute([':app_id' => $appointment_id, ':slot_id' => $slot_id]);

                redirectWithMessage($_SERVER['PHP_SELF'] . '?page=appointment-history', 'success', 'Đặt lịch hẹn thành công!');
            } else {
                redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Không thể xử lý yêu cầu. Vui lòng thử lại!');
            }
        } else {
            redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Khung giờ này đã được đặt. Vui lòng chọn khung giờ khác!');
        }
    }
}

// Cancel appointment
if (isset($_GET['cancel'])) {
    $stmt = $pdo->prepare("SELECT slot_id FROM appointmenttb WHERE ID = :id");
    $stmt->execute([':id' => $_GET['ID']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update appointment status
    $stmt = $pdo->prepare("UPDATE appointmenttb SET userStatus='0' WHERE ID = :id");
    $result = $stmt->execute([':id' => $_GET['ID']]);

    if ($result && $appointment['slot_id']) {
        // Release the time slot
        $slot_stmt = $pdo->prepare("UPDATE time_slots SET status = 'available', appointment_id = NULL WHERE id = :slot_id");
        $slot_stmt->execute([':slot_id' => $appointment['slot_id']]);
    }

    if ($result) {
        redirectWithMessage($_SERVER['PHP_SELF'] . '?page=appointment-history', 'success', 'Đã hủy lịch hẹn thành công');
    }
}

// Generate bill function
function generate_bill()
{
    global $pdo;
    $pid = $_SESSION['pid'];
    $output = '';
    $stmt = $pdo->prepare("SELECT p.pid,p.ID,p.fname,p.lname,p.doctor,p.appdate,p.apptime,p.disease,p.allergy,p.prescription,a.docFees FROM prestb p INNER JOIN appointmenttb a ON p.ID=a.ID WHERE p.pid = :pid AND p.ID = :id");
    $stmt->execute([':pid' => $pid, ':id' => $_GET['ID']]);
    while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
        $output .= '
    <label> Mã bệnh nhân : </label>' . $row["pid"] . '<br/><br/>
    <label> Mã lịch hẹn : </label>' . $row["ID"] . '<br/><br/>
    <label> Tên bệnh nhân : </label>' . $row["fname"] . ' ' . $row["lname"] . '<br/><br/>
    <label> Bác sĩ khám : </label>' . $row["doctor"] . '<br/><br/>
    <label> Ngày khám : </label>' . date('d/m/Y', strtotime($row["appdate"])) . '<br/><br/>
    <label> Giờ khám : </label>' . date('H:i', strtotime($row["apptime"])) . '<br/><br/>
    <label> Chẩn đoán : </label>' . $row["disease"] . '<br/><br/>
    <label> Dị ứng : </label>' . $row["allergy"] . '<br/><br/>
    <label> Đơn thuốc : </label>' . $row["prescription"] . '<br/><br/>
    <label> Chi phí khám : </label>' . number_format($row["docFees"]) . ' VNĐ<br/>
    ';
    }
    return $output;
}

if (isset($_GET["generate_bill"])) {
    require_once("../../TCPDF/tcpdf.php");
    $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Hóa đơn khám bệnh");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->SetHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $obj_pdf->SetFooterFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $obj_pdf->SetMargins(PDF_MARGIN_LEFT, '5', PDF_MARGIN_RIGHT);
    $obj_pdf->SetPrintHeader(false);
    $obj_pdf->SetPrintFooter(false);
    $obj_pdf->SetAutoPageBreak(TRUE, 10);
    $obj_pdf->SetFont('dejavusans', '', 12);
    $obj_pdf->AddPage();

    $content = '';
    $content .= '
      <br/>
      <h2 align ="center"> Bệnh viện Đa khoa Global</h2></br>
      <h3 align ="center"> Hóa đơn khám bệnh</h3>
  ';
    $content .= generate_bill();
    $obj_pdf->writeHTML($content);
    ob_end_clean();
    $obj_pdf->Output("hoa-don-kham-benh.pdf", 'I');
}
?>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Bảng điều khiển bệnh nhân - Bệnh viện Global</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">

    <style>
        /* Time Slots Grid - Cinema Style */
        .specializations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .spec-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.1);
        }

        .spec-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(8, 145, 178, 0.2);
            border-color: var(--medical-blue);
        }

        .spec-card.active {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            border-color: var(--medical-blue-dark);
        }

        .spec-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, rgba(8, 145, 178, 0.1), rgba(6, 182, 212, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--medical-blue);
        }

        .spec-card.active .spec-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .spec-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .spec-count {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .doctor-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.1);
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(8, 145, 178, 0.2);
            border-color: var(--medical-blue);
        }

        .doctor-card.active {
            border-color: var(--medical-blue-dark);
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.05), rgba(8, 145, 178, 0.1));
        }

        .doctor-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--medical-blue), var(--medical-teal));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 700;
        }

        .doctor-name {
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: var(--medical-dark);
        }

        .doctor-spec {
            text-align: center;
            color: var(--medical-blue);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .doctor-fee {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--health-green);
        }

        .time-slots-container {
            margin-top: 2rem;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .time-slot {
            background: white;
            border: 2px solid var(--steel-gray);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .time-slot:hover:not(.booked):not(.blocked) {
            border-color: var(--medical-blue);
            background: rgba(8, 145, 178, 0.05);
            transform: scale(1.05);
        }

        .time-slot.available {
            border-color: var(--health-green);
            color: var(--health-green);
        }

        .time-slot.available:hover {
            background: var(--health-green);
            color: white;
        }

        .time-slot.selected {
            background: var(--medical-blue);
            border-color: var(--medical-blue-dark);
            color: white;
        }

        .time-slot.booked {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .time-slot.blocked {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .slot-time {
            font-size: 1rem;
            display: block;
        }

        .slot-status {
            font-size: 0.75rem;
            display: block;
            margin-top: 0.5rem;
            opacity: 0.8;
        }

        .booking-summary {
            background: linear-gradient(135deg, rgba(8, 145, 178, 0.05), rgba(6, 182, 212, 0.1));
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            border: 2px solid var(--medical-blue-light);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(8, 145, 178, 0.1);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--medical-blue);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.5rem;
            background: white;
            border: 3px solid var(--steel-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--steel-gray);
            position: relative;
            z-index: 2;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%);
            border-color: #0891b2;
            color: white;
            box-shadow: 0 4px 15px rgba(8, 145, 178, 0.3);
        }

        .step.completed .step-number {
            background: #14b8a6;
            border-color: #14b8a6;
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--charcoal);
        }

        .step-line {
            position: absolute;
            top: 25px;
            left: 50%;
            right: -50%;
            height: 3px;
            background: var(--steel-gray);
            z-index: 1;
        }

        .step.completed .step-line {
            background: #14b8a6;
        }

        .step:last-child .step-line {
            display: none;
        }

        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--charcoal);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--steel-gray);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {

            .specializations-grid,
            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .slots-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-hospital"></i>
                </div>
                <div>
                    <h1 class="sidebar-title">Bệnh viện Global</h1>
                    <div class="sidebar-subtitle">Cổng bệnh nhân</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="?page=dashboard" class="sidebar-menu-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large sidebar-menu-icon"></i>
                        <span>Tổng quan</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=book-appointment" class="sidebar-menu-link <?php echo ($page === 'book-appointment') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus sidebar-menu-icon"></i>
                        <span>Đặt lịch khám</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=appointment-history" class="sidebar-menu-link <?php echo ($page === 'appointment-history') ? 'active' : ''; ?>">
                        <i class="fas fa-history sidebar-menu-icon"></i>
                        <span>Lịch sử khám</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=prescriptions" class="sidebar-menu-link <?php echo ($page === 'prescriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i>
                        <span>Đơn thuốc</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="btn btn-danger btn-block">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Bảng điều khiển bệnh nhân</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user">
                        <div class="navbar-user-avatar">
                            <?php echo strtoupper(substr($fname, 0, 1)); ?>
                        </div>
                        <div class="navbar-user-info">
                            <div class="navbar-user-name"><?php echo $fname . ' ' . $lname; ?></div>
                            <div class="navbar-user-role">Bệnh nhân</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Section -->
            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Xin chào, <?php echo $fname; ?>!</h2>
                        <p class="section-subtitle">Quản lý lịch khám và hồ sơ bệnh án của bạn</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng số lịch hẹn</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointmenttb WHERE fname = :fname AND lname = :lname");
                                    $stmt->execute([':fname' => $fname, ':lname' => $lname]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Lịch hẹn đang hoạt động</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM appointmenttb WHERE fname = :fname AND lname = :lname AND userStatus='1' AND doctorStatus='1'");
                                    $stmt->execute([':fname' => $fname, ':lname' => $lname]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['active'];
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-file-prescription"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Đơn thuốc</div>
                                <div class="stat-value">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as pres FROM prestb WHERE pid = :pid");
                                    $stmt->execute([':pid' => $pid]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $row['pres'];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <a href="?page=book-appointment" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon primary">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Đặt lịch khám</h5>
                                    <p class="text-muted mb-0">Đặt lịch hẹn khám bệnh mới</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="?page=appointment-history" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon success">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Xem lịch sử</h5>
                                    <p class="text-muted mb-0">Kiểm tra các lịch hẹn trước đây</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="?page=prescriptions" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon warning">
                                    <i class="fas fa-file-prescription"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Đơn thuốc</h5>
                                    <p class="text-muted mb-0">Xem các đơn thuốc của bạn</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Book Appointment Section - NEW SYSTEM -->
            <?php if ($page === 'book-appointment') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Đặt lịch khám bệnh</h2>
                        <p class="section-subtitle">Chọn chuyên khoa, bác sĩ và thời gian khám phù hợp</p>
                    </div>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step <?php echo ($booking_step >= 1) ? 'active' : ''; ?> <?php echo ($booking_step > 1) ? 'completed' : ''; ?>" id="step1">
                            <div class="step-number"><?php echo ($booking_step > 1) ? '<i class="fas fa-check"></i>' : '1'; ?></div>
                            <div class="step-label">Chọn chuyên khoa</div>
                            <div class="step-line"></div>
                        </div>
                        <div class="step <?php echo ($booking_step >= 2) ? 'active' : ''; ?> <?php echo ($booking_step > 2) ? 'completed' : ''; ?>" id="step2">
                            <div class="step-number"><?php echo ($booking_step > 2) ? '<i class="fas fa-check"></i>' : '2'; ?></div>
                            <div class="step-label">Chọn bác sĩ</div>
                            <div class="step-line"></div>
                        </div>
                        <div class="step <?php echo ($booking_step >= 3) ? 'active' : ''; ?> <?php echo ($booking_step > 3) ? 'completed' : ''; ?>" id="step3">
                            <div class="step-number"><?php echo ($booking_step > 3) ? '<i class="fas fa-check"></i>' : '3'; ?></div>
                            <div class="step-label">Chọn ngày</div>
                            <div class="step-line"></div>
                        </div>
                        <div class="step <?php echo ($booking_step >= 4) ? 'active' : ''; ?>" id="step4">
                            <div class="step-number">4</div>
                            <div class="step-label">Chọn giờ</div>
                        </div>
                    </div>

                    <!-- Step 1: Choose Specialization -->
                    <?php if ($booking_step == 1) { ?>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title"><i class="fas fa-stethoscope"></i> Chọn chuyên khoa y tế</h3>
                            </div>
                            <div class="p-4">
                                <div class="specializations-grid">
                                    <?php foreach ($specializations as $spec) { ?>
                                        <a href="?page=book-appointment&step=2&spec_id=<?php echo $spec['id']; ?>" class="spec-card" style="text-decoration: none; color: inherit;">
                                            <div class="spec-icon">
                                                <i class="<?php echo htmlspecialchars($spec['icon'] ?? 'fas fa-stethoscope'); ?>"></i>
                                            </div>
                                            <div class="spec-name"><?php echo htmlspecialchars($spec['name_vi']); ?></div>
                                            <div class="spec-count">
                                                <?php
                                                $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM doctb WHERE spec_id = :spec_id AND status = 1");
                                                $count_stmt->execute([':spec_id' => $spec['id']]);
                                                $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
                                                echo $count['total'] . ' bác sĩ';
                                                ?>
                                            </div>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Step 2: Choose Doctor -->
                    <?php if ($booking_step == 2 && $selected_spec) { ?>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title"><i class="fas fa-user-md"></i> Chọn bác sĩ</h3>
                                <a href="?page=book-appointment&step=1" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>
                            <div class="p-4">
                                <?php if (count($doctors) > 0) { ?>
                                    <div class="doctors-grid">
                                        <?php foreach ($doctors as $doctor) { ?>
                                            <a href="?page=book-appointment&step=3&spec_id=<?php echo $selected_spec; ?>&doctor_id=<?php echo $doctor['id']; ?>" class="doctor-card" style="text-decoration: none; color: inherit;">
                                                <div class="doctor-avatar">
                                                    <?php echo strtoupper(substr($doctor['username'], 0, 1)); ?>
                                                </div>
                                                <div class="doctor-name">BS. <?php echo htmlspecialchars($doctor['username']); ?></div>
                                                <div class="doctor-spec"><?php echo htmlspecialchars($doctor['spec_name']); ?></div>
                                                <?php if ($doctor['experience_years']) { ?>
                                                    <div class="doctor-spec">
                                                        <i class="fas fa-briefcase"></i> <?php echo $doctor['experience_years']; ?> năm kinh nghiệm
                                                    </div>
                                                <?php } ?>
                                                <div class="doctor-fee">
                                                    <?php echo number_format($doctor['docFees']); ?> VNĐ
                                                </div>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-md"></i>
                                        <h4>Không có bác sĩ nào</h4>
                                        <p>Hiện tại chưa có bác sĩ nào trong chuyên khoa này</p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Step 3: Choose Date -->
                    <?php if ($booking_step == 3 && $selected_doctor) { ?>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title"><i class="fas fa-calendar"></i> Chọn ngày khám</h3>
                                <a href="?page=book-appointment&step=2&spec_id=<?php echo $selected_spec; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>
                            <div class="p-4">
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <form method="GET" action="" class="card p-4">
                                            <input type="hidden" name="page" value="book-appointment">
                                            <input type="hidden" name="step" value="4">
                                            <input type="hidden" name="spec_id" value="<?php echo $selected_spec; ?>">
                                            <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor; ?>">

                                            <h5 class="mb-4"><i class="fas fa-calendar-alt"></i> Chọn ngày khám bệnh</h5>

                                            <?php
                                            // Get current date
                                            $current_year = date('Y');
                                            $current_month = date('m');
                                            $current_day = date('d');
                                            ?>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold"><i class="fas fa-calendar-day"></i> Ngày</label>
                                                        <select name="day" class="form-control form-control-lg" required>
                                                            <option value="">-- Chọn ngày --</option>
                                                            <?php for ($d = 1; $d <= 31; $d++) { ?>
                                                                <option value="<?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?>">
                                                                    <?php echo $d; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold"><i class="fas fa-calendar"></i> Tháng</label>
                                                        <select name="month" class="form-control form-control-lg" required>
                                                            <option value="">-- Chọn tháng --</option>
                                                            <?php
                                                            $months = [
                                                                '01' => 'Tháng 1',
                                                                '02' => 'Tháng 2',
                                                                '03' => 'Tháng 3',
                                                                '04' => 'Tháng 4',
                                                                '05' => 'Tháng 5',
                                                                '06' => 'Tháng 6',
                                                                '07' => 'Tháng 7',
                                                                '08' => 'Tháng 8',
                                                                '09' => 'Tháng 9',
                                                                '10' => 'Tháng 10',
                                                                '11' => 'Tháng 11',
                                                                '12' => 'Tháng 12'
                                                            ];
                                                            foreach ($months as $num => $name) { ?>
                                                                <option value="<?php echo $num; ?>">
                                                                    <?php echo $name; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold"><i class="fas fa-calendar-alt"></i> Năm</label>
                                                        <select name="year" class="form-control form-control-lg" required>
                                                            <option value="">-- Chọn năm --</option>
                                                            <?php for ($y = $current_year; $y <= $current_year + 1; $y++) { ?>
                                                                <option value="<?php echo $y; ?>">
                                                                    <?php echo $y; ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle"></i>
                                                Vui lòng chọn ngày từ hôm nay trở đi. Bác sĩ có thể không làm việc vào một số ngày trong tuần.
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">
                                                <i class="fas fa-arrow-right"></i> Xem lịch trống
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Step 4: Choose Time Slot -->
                    <?php if ($booking_step == 4 && $selected_doctor && $selected_date) {
                        // Get doctor info for display
                        $doc_info_stmt = $pdo->prepare("SELECT username, docFees FROM doctb WHERE id = :id");
                        $doc_info_stmt->execute([':id' => $selected_doctor]);
                        $doc_info = $doc_info_stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title"><i class="fas fa-clock"></i> Chọn giờ khám</h3>
                                <a href="?page=book-appointment&step=3&spec_id=<?php echo $selected_spec; ?>&doctor_id=<?php echo $selected_doctor; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>

                            <div class="p-4">
                                <div class="alert alert-info">
                                    <strong><i class="fas fa-calendar-day"></i> Ngày khám:</strong>
                                    <?php echo date('d/m/Y', strtotime($selected_date)); ?>
                                    (<?php
                                        $days = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
                                        echo $days[date('w', strtotime($selected_date))];
                                        ?>)
                                    <br>
                                    <strong><i class="fas fa-user-md"></i> Bác sĩ:</strong> BS. <?php echo htmlspecialchars($doc_info['username']); ?>
                                </div>

                                <?php if ($schedule_info && count($time_slots) > 0) { ?>
                                    <div class="time-slots-container">
                                        <h5 class="mb-3">
                                            <i class="fas fa-clock"></i>
                                            Lịch làm việc: <?php echo date('H:i', strtotime($schedule_info['start_time'])); ?> -
                                            <?php echo date('H:i', strtotime($schedule_info['end_time'])); ?>
                                        </h5>

                                        <div class="slots-grid">
                                            <?php foreach ($time_slots as $slot) {
                                                $slot_class = 'time-slot ' . $slot['status'];
                                                $is_disabled = ($slot['status'] !== 'available');
                                            ?>
                                                <div class="<?php echo $slot_class; ?>"
                                                    id="slot-<?php echo $slot['id']; ?>"
                                                    <?php if (!$is_disabled) { ?>
                                                    onclick="selectSlot(<?php echo $slot['id']; ?>, '<?php echo $slot['slot_time']; ?>', '<?php echo $slot['slot_time_full']; ?>')"
                                                    <?php } ?>>
                                                    <span class="slot-time"><?php echo $slot['slot_time']; ?></span>
                                                    <span class="slot-status">
                                                        <?php
                                                        if ($slot['status'] === 'available') echo 'Còn trống';
                                                        elseif ($slot['status'] === 'booked') echo 'Đã đặt';
                                                        elseif ($slot['status'] === 'blocked') echo 'Đã qua';
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-circle text-success"></i> Còn trống &nbsp;&nbsp;
                                                <i class="fas fa-circle text-secondary"></i> Đã đặt &nbsp;&nbsp;
                                                <i class="fas fa-circle text-danger"></i> Đã qua giờ
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Booking Form -->
                                    <form method="POST" action="" id="booking-form" class="booking-summary mt-4" style="display: none;">
                                        <h4 class="mb-3">
                                            <i class="fas fa-file-medical"></i> Xác nhận thông tin đặt lịch
                                        </h4>

                                        <div class="summary-item">
                                            <span><i class="fas fa-user-md"></i> Bác sĩ:</span>
                                            <strong>BS. <?php echo htmlspecialchars($doc_info['username']); ?></strong>
                                        </div>

                                        <div class="summary-item">
                                            <span><i class="fas fa-calendar-alt"></i> Ngày khám:</span>
                                            <strong><?php echo date('d/m/Y', strtotime($selected_date)); ?></strong>
                                        </div>

                                        <div class="summary-item">
                                            <span><i class="fas fa-clock"></i> Giờ khám:</span>
                                            <strong id="selected-time-display">--:--</strong>
                                        </div>

                                        <div class="summary-item">
                                            <span><i class="fas fa-money-bill-wave"></i> Chi phí khám:</span>
                                            <strong class="text-success"><?php echo number_format($doc_info['docFees']); ?> VNĐ</strong>
                                        </div>

                                        <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor; ?>">
                                        <input type="hidden" name="slot_id" id="selected-slot-id" value="">
                                        <input type="hidden" name="appdate" value="<?php echo $selected_date; ?>">
                                        <input type="hidden" name="apptime" id="selected-time-value" value="">

                                        <button type="submit" name="app-submit" class="btn btn-primary btn-block btn-lg mt-4">
                                            <i class="fas fa-check-circle"></i> Xác nhận đặt lịch
                                        </button>
                                    </form>

                                <?php } elseif ($schedule_info) { ?>
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times"></i>
                                        <h4>Không có lịch trống</h4>
                                        <p>Bác sĩ không có lịch trống trong ngày này. Vui lòng chọn ngày khác.</p>
                                        <a href="?page=book-appointment&step=3&spec_id=<?php echo $selected_spec; ?>&doctor_id=<?php echo $selected_doctor; ?>" class="btn btn-primary mt-3">
                                            <i class="fas fa-arrow-left"></i> Chọn ngày khác
                                        </a>
                                    </div>
                                <?php } else { ?>
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times"></i>
                                        <h4>Bác sĩ không làm việc</h4>
                                        <p>Bác sĩ không làm việc vào ngày này trong tuần. Vui lòng chọn ngày khác.</p>
                                        <a href="?page=book-appointment&step=3&spec_id=<?php echo $selected_spec; ?>&doctor_id=<?php echo $selected_doctor; ?>" class="btn btn-primary mt-3">
                                            <i class="fas fa-arrow-left"></i> Chọn ngày khác
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </section>
            <?php } ?>

            <!-- Appointment History Section -->
            <?php if ($page === 'appointment-history') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Lịch sử khám bệnh</h2>
                        <p class="section-subtitle">Xem và quản lý các lịch hẹn của bạn</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Danh sách lịch hẹn</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Bác sĩ</th>
                                    <th>Chi phí</th>
                                    <th>Ngày khám</th>
                                    <th>Giờ khám</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT ID,doctor,docFees,appdate,apptime,userStatus,doctorStatus FROM appointmenttb WHERE fname = :fname AND lname = :lname ORDER BY appdate DESC, apptime DESC");
                                $stmt->execute([':fname' => $fname, ':lname' => $lname]);

                                if ($stmt->rowCount() == 0) {
                                    echo '<tr><td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-times"></i>
                                            <p>Bạn chưa có lịch hẹn nào</p>
                                            <a href="?page=book-appointment" class="btn btn-primary mt-3">Đặt lịch ngay</a>
                                        </div>
                                    </td></tr>';
                                }

                                while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                ?>
                                    <tr>
                                        <td>Bác sĩ <?php echo $row['doctor']; ?></td>
                                        <td><?php echo number_format($row['docFees']); ?> VNĐ</td>
                                        <td><?php echo date('d/m/Y', strtotime($row['appdate'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['apptime'])); ?></td>
                                        <td>
                                            <?php
                                            if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) {
                                                echo '<span class="badge badge-success">Đang hoạt động</span>';
                                            }
                                            if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                echo '<span class="badge badge-danger">Đã hủy bởi bạn</span>';
                                            }
                                            if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                echo '<span class="badge badge-warning">Đã hủy bởi bác sĩ</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                <a href="?page=appointment-history&ID=<?php echo $row['ID'] ?>&cancel=update"
                                                    onclick="return confirm('Bạn có chắc muốn hủy lịch hẹn này?')"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Hủy lịch
                                                </a>
                                            <?php } else {
                                                echo '<span class="text-muted">Đã hủy</span>';
                                            } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>

            <!-- Prescriptions Section -->
            <?php if ($page === 'prescriptions') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Đơn thuốc</h2>
                        <p class="section-subtitle">Xem và tải xuống đơn thuốc của bạn</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Danh sách đơn thuốc</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Bác sĩ</th>
                                    <th>Mã lịch hẹn</th>
                                    <th>Ngày khám</th>
                                    <th>Giờ khám</th>
                                    <th>Chẩn đoán</th>
                                    <th>Dị ứng</th>
                                    <th>Đơn thuốc</th>
                                    <th>Hóa đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT doctor,ID,appdate,apptime,disease,allergy,prescription FROM prestb WHERE pid = :pid ORDER BY appdate DESC");
                                $stmt->execute([':pid' => $pid]);

                                if ($stmt->rowCount() == 0) {
                                    echo '<tr><td colspan="8" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-file-prescription"></i>
                                            <p>Bạn chưa có đơn thuốc nào</p>
                                        </div>
                                    </td></tr>';
                                }

                                while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                ?>
                                    <tr>
                                        <td>Bác sĩ <?php echo $row['doctor']; ?></td>
                                        <td>#<?php echo $row['ID']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['appdate'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['apptime'])); ?></td>
                                        <td><?php echo $row['disease']; ?></td>
                                        <td><?php echo $row['allergy'] ?: 'Không có'; ?></td>
                                        <td><?php echo $row['prescription']; ?></td>
                                        <td>
                                            <a href="?page=prescriptions&ID=<?php echo $row['ID'] ?>&generate_bill=true"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-file-pdf"></i> Tải hóa đơn
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php } ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Simple function to select time slot - PHP handles all other logic
        function selectSlot(slotId, slotTime, slotTimeFull) {
            // Remove selected class from all slots
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });

            // Add selected class to clicked slot
            document.getElementById('slot-' + slotId).classList.add('selected');

            // Update form values
            document.getElementById('selected-slot-id').value = slotId;
            document.getElementById('selected-time-value').value = slotTimeFull;
            document.getElementById('selected-time-display').textContent = slotTime;

            // Show booking form
            document.getElementById('booking-form').style.display = 'block';

            // Scroll to form
            document.getElementById('booking-form').scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    </script>
</body>

</html>