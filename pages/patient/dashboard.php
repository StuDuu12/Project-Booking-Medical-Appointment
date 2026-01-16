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

if (isset($_POST['app-submit'])) {
    $doctor = $_POST['doctor'];
    // Remove currency symbols and convert to integer
    $docFees = preg_replace('/[^0-9]/', '', $_POST['docFees']);
    $docFees = intval($docFees);
    $appdate = $_POST['appdate'];
    $apptime = $_POST['apptime'];
    $cur_date = date("Y-m-d");
    date_default_timezone_set('Asia/Kolkata');
    $cur_time = date("H:i:s");
    $apptime1 = strtotime($apptime);
    $appdate1 = strtotime($appdate);

    if (date("Y-m-d", $appdate1) >= $cur_date) {
        if ((date("Y-m-d", $appdate1) == $cur_date and date("H:i:s", $apptime1) > $cur_time) or date("Y-m-d", $appdate1) > $cur_date) {
            $check_stmt = $pdo->prepare("SELECT apptime FROM appointmenttb WHERE doctor = :doctor AND appdate = :appdate AND apptime = :apptime");
            $check_stmt->execute([':doctor' => $doctor, ':appdate' => $appdate, ':apptime' => $apptime]);

            if ($check_stmt->rowCount() == 0) {
                $stmt = $pdo->prepare("INSERT INTO appointmenttb(pid,fname,lname,gender,email,contact,doctor,docFees,appdate,apptime,userStatus,doctorStatus) VALUES(:pid,:fname,:lname,:gender,:email,:contact,:doctor,:docFees,:appdate,:apptime,'1','1')");
                $result = $stmt->execute([
                    ':pid' => $pid,
                    ':fname' => $fname,
                    ':lname' => $lname,
                    ':gender' => $gender,
                    ':email' => $email,
                    ':contact' => $contact,
                    ':doctor' => $doctor,
                    ':docFees' => $docFees,
                    ':appdate' => $appdate,
                    ':apptime' => $apptime
                ]);

                if ($result) {
                    redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Your appointment successfully booked');
                } else {
                    redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Unable to process your request. Please try again!');
                }
            } else {
                redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'We are sorry to inform that the doctor is not available in this time or date. Please choose different time or date!');
            }
        } else {
            redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Select a time or date in the future!');
        }
    } else {
        redirectWithMessage($_SERVER['PHP_SELF'], 'error', 'Select a time or date in the future!');
    }
}

if (isset($_GET['cancel'])) {
    $stmt = $pdo->prepare("UPDATE appointmenttb SET userStatus='0' WHERE ID = :id");
    $result = $stmt->execute([':id' => $_GET['ID']]);
    if ($result) {
        redirectWithMessage($_SERVER['PHP_SELF'], 'success', 'Your appointment successfully cancelled');
    }
}

function generate_bill()
{
    global $pdo;
    $pid = $_SESSION['pid'];
    $output = '';
    $stmt = $pdo->prepare("SELECT p.pid,p.ID,p.fname,p.lname,p.doctor,p.appdate,p.apptime,p.disease,p.allergy,p.prescription,a.docFees FROM prestb p INNER JOIN appointmenttb a ON p.ID=a.ID WHERE p.pid = :pid AND p.ID = :id");
    $stmt->execute([':pid' => $pid, ':id' => $_GET['ID']]);
    while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
        $output .= '
    <label> Patient ID : </label>' . $row["pid"] . '<br/><br/>
    <label> Appointment ID : </label>' . $row["ID"] . '<br/><br/>
    <label> Patient Name : </label>' . $row["fname"] . ' ' . $row["lname"] . '<br/><br/>
    <label> Doctor Name : </label>' . $row["doctor"] . '<br/><br/>
    <label> Appointment Date : </label>' . $row["appdate"] . '<br/><br/>
    <label> Appointment Time : </label>' . $row["apptime"] . '<br/><br/>
    <label> Disease : </label>' . $row["disease"] . '<br/><br/>
    <label> Allergies : </label>' . $row["allergy"] . '<br/><br/>
    <label> Prescription : </label>' . $row["prescription"] . '<br/><br/>
    <label> Fees Paid : </label>' . $row["docFees"] . '<br/>
    ';
    }
    return $output;
}

if (isset($_GET["generate_bill"])) {
    require_once("../../TCPDF/tcpdf.php");
    $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Generate Bill");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->SetHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $obj_pdf->SetFooterFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $obj_pdf->SetMargins(PDF_MARGIN_LEFT, '5', PDF_MARGIN_RIGHT);
    $obj_pdf->SetPrintHeader(false);
    $obj_pdf->SetPrintFooter(false);
    $obj_pdf->SetAutoPageBreak(TRUE, 10);
    $obj_pdf->SetFont('helvetica', '', 12);
    $obj_pdf->AddPage();

    $content = '';
    $content .= '
      <br/>
      <h2 align ="center"> Global Hospitals</h2></br>
      <h3 align ="center"> Bill</h3>
  ';
    $content .= generate_bill();
    $obj_pdf->writeHTML($content);
    ob_end_clean();
    $obj_pdf->Output("bill.pdf", 'I');
}

function get_specs()
{
    global $pdo;
    $stmt = $pdo->query("SELECT username,spec FROM doctb");
    $docarray = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $docarray[] = $row;
    }
    return json_encode($docarray);
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Patient Dashboard - Global Hospital</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">

    <style>
        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }
    </style>
</head>

<body> <?php displayMessage(); ?> <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-hospital"></i>
                </div>
                <div>
                    <h1 class="sidebar-title">Global Hospital</h1>
                    <div class="sidebar-subtitle">Patient Portal</div>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="?page=dashboard" class="sidebar-menu-link <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large sidebar-menu-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=book-appointment" class="sidebar-menu-link <?php echo ($page === 'book-appointment') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus sidebar-menu-icon"></i>
                        <span>Book Appointment</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=appointment-history" class="sidebar-menu-link <?php echo ($page === 'appointment-history') ? 'active' : ''; ?>">
                        <i class="fas fa-history sidebar-menu-icon"></i>
                        <span>Appointment History</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="?page=prescriptions" class="sidebar-menu-link <?php echo ($page === 'prescriptions') ? 'active' : ''; ?>">
                        <i class="fas fa-file-prescription sidebar-menu-icon"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="btn btn-danger btn-block">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="navbar-left">
                    <h1 class="navbar-title">Patient Dashboard</h1>
                </div>
                <div class="navbar-right">
                    <div class="navbar-user">
                        <div class="navbar-user-avatar">
                            <?php echo strtoupper(substr($fname, 0, 1)); ?>
                        </div>
                        <div class="navbar-user-info">
                            <div class="navbar-user-name"><?php echo $fname . ' ' . $lname; ?></div>
                            <div class="navbar-user-role">Patient</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Section -->
            <?php if ($page === 'dashboard') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Welcome, <?php echo $fname; ?>!</h2>
                        <p class="section-subtitle">Manage your appointments and medical records</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Appointments</div>
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
                                <div class="stat-label">Active Appointments</div>
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
                                <div class="stat-label">Prescriptions</div>
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
                                    <h5>Book Appointment</h5>
                                    <p class="text-muted mb-0">Schedule a new appointment with your doctor</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="?page=appointment-history" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon success">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>View History</h5>
                                    <p class="text-muted mb-0">Check your past appointments</p>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="?page=prescriptions" class="stat-card" style="cursor: pointer; text-decoration: none; color: inherit;">
                                <div class="stat-icon warning">
                                    <i class="fas fa-file-prescription"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Prescriptions</h5>
                                    <p class="text-muted mb-0">View your medical prescriptions</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Book Appointment Section -->
            <?php if ($page === 'book-appointment') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Book New Appointment</h2>
                        <p class="section-subtitle">Schedule an appointment with your preferred doctor</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Appointment Form</h3>
                        </div>
                        <div class="p-4">
                            <form method="post" action="dashboard.php?page=book-appointment">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-stethoscope"></i> Specialization *</label>
                                            <select name="spec" class="form-control" id="spec" required>
                                                <option value="" disabled selected>Choose medical specialization</option>
                                                <?php display_specs(); ?>
                                            </select>
                                            <small class="form-text text-muted">Select the type of specialist you need</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-user-md"></i> Doctor *</label>
                                            <select name="doctor" class="form-control" id="doctor" required>
                                                <option value="" disabled selected>Select a doctor</option>
                                                <?php display_docs(); ?>
                                            </select>
                                            <small class="form-text text-muted">Choose your preferred doctor</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-money-bill-wave"></i> Consultancy Fees</label>
                                            <input class="form-control" type="text" name="docFees" id="docFees" readonly placeholder="Fee will be shown here" />
                                            <small class="form-text text-muted">Consultation fee for the selected doctor</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-calendar"></i> Appointment Date *</label>
                                            <input type="date" class="form-control" name="appdate" id="appdate" required min="<?php echo date('Y-m-d'); ?>">
                                            <small class="form-text text-muted">Select your preferred date</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-clock"></i> Appointment Time *</label>
                                            <select name="apptime" class="form-control" required>
                                                <option value="" disabled selected>Select Time</option>
                                                <option value="08:00:00">8:00 AM - Morning</option>
                                                <option value="10:00:00">10:00 AM - Morning</option>
                                                <option value="12:00:00">12:00 PM - Noon</option>
                                                <option value="14:00:00">2:00 PM - Afternoon</option>
                                                <option value="16:00:00">4:00 PM - Evening</option>
                                                <option value="18:00:00">6:00 PM - Evening</option>
                                            </select>
                                            <small class="form-text text-muted">Choose a convenient time slot</small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> Please arrive 10 minutes before your scheduled appointment time.
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" name="app-submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-calendar-check"></i> Book Appointment
                                        </button>
                                        <a href="?page=dashboard" class="btn btn-secondary btn-lg ml-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            <?php } ?>

            <!-- Appointment History Section -->
            <?php if ($page === 'appointment-history') { ?>
                <section class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">Appointment History</h2>
                        <p class="section-subtitle">View and manage your appointments</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">My Appointments</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Fees</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT ID,doctor,docFees,appdate,apptime,userStatus,doctorStatus FROM appointmenttb WHERE fname = :fname AND lname = :lname ORDER BY appdate DESC, apptime DESC");
                                $stmt->execute([':fname' => $fname, ':lname' => $lname]);
                                while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                ?>
                                    <tr>
                                        <td><?php echo $row['doctor']; ?></td>
                                        <td>₹<?php echo $row['docFees']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                        <td>
                                            <?php
                                            if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) {
                                                echo '<span class="badge badge-success">Active</span>';
                                            }
                                            if (($row['userStatus'] == 0) && ($row['doctorStatus'] == 1)) {
                                                echo '<span class="badge badge-danger">Cancelled by You</span>';
                                            }
                                            if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 0)) {
                                                echo '<span class="badge badge-warning">Cancelled by Doctor</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (($row['userStatus'] == 1) && ($row['doctorStatus'] == 1)) { ?>
                                                <a href="patient-dashboard.php?ID=<?php echo $row['ID'] ?>&cancel=update"
                                                    onclick="return confirm('Are you sure you want to cancel this appointment?')"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php } else {
                                                echo '<span class="text-muted">Cancelled</span>';
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
                        <h2 class="section-title">Medical Prescriptions</h2>
                        <p class="section-subtitle">View and download your prescriptions</p>
                    </div>

                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Prescription History</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Appointment ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Disease</th>
                                    <th>Allergies</th>
                                    <th>Prescription</th>
                                    <th>Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT doctor,ID,appdate,apptime,disease,allergy,prescription FROM prestb WHERE pid = :pid ORDER BY appdate DESC");
                                $stmt->execute([':pid' => $pid]);
                                while ($row = $stmt->fetch(PDO::FETCH_BOTH)) {
                                ?>
                                    <tr>
                                        <td><?php echo $row['doctor']; ?></td>
                                        <td>#<?php echo $row['ID']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['appdate'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['apptime'])); ?></td>
                                        <td><?php echo $row['disease']; ?></td>
                                        <td><?php echo $row['allergy']; ?></td>
                                        <td><?php echo $row['prescription']; ?></td>
                                        <td>
                                            <a href="patient-dashboard.php?ID=<?php echo $row['ID'] ?>&generate_bill=true"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-file-pdf"></i> Download Bill
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
        // Specialization filter for doctors
        const specSelect = document.getElementById('spec');
        const doctorSelect = document.getElementById('doctor');
        const docFeesInput = document.getElementById('docFees');

        if (specSelect && doctorSelect) {
            // Filter doctors when specialization changes
            specSelect.onchange = function() {
                let spec = this.value;
                let docs = [...doctorSelect.options];
                let visibleCount = 0;

                // Reset doctor selection
                doctorSelect.value = '';
                if (docFeesInput) docFeesInput.value = '';

                docs.forEach((el, ind) => {
                    if (el.value === '') {
                        // Keep the default "Select a doctor" option visible
                        el.style.display = '';
                        return;
                    }

                    if (el.getAttribute("data-spec") === spec) {
                        el.style.display = '';
                        visibleCount++;
                    } else {
                        el.style.display = 'none';
                    }
                });

                // Update placeholder text based on available doctors
                if (visibleCount > 0) {
                    doctorSelect.options[0].text = `Select a doctor (${visibleCount} available)`;
                } else {
                    doctorSelect.options[0].text = 'No doctors available for this specialization';
                }
            };
        }

        // Update fees when doctor is selected
        if (doctorSelect && docFeesInput) {
            doctorSelect.onchange = function() {
                if (this.value) {
                    var selectedOption = this.options[this.selectedIndex];
                    var fee = selectedOption.getAttribute('data-value');
                    docFeesInput.value = fee ? '₹' + fee : 'Not specified';
                } else {
                    docFeesInput.value = '';
                }
            };
        }

        // Set minimum date to today for appointment date
        const appdateInput = document.getElementById('appdate');
        if (appdateInput) {
            const today = new Date().toISOString().split('T')[0];
            appdateInput.setAttribute('min', today);
        }
    </script>
</body>

</html>