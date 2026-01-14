<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
$pid = '';
$ID = '';
$appdate = '';
$apptime = '';
$fname = '';
$lname = '';
$doctor = $_SESSION['dname'];

if (isset($_GET['pid']) && isset($_GET['ID']) && isset($_GET['appdate']) && isset($_GET['apptime']) && isset($_GET['fname']) && isset($_GET['lname'])) {
    $pid = $_GET['pid'];
    $ID = $_GET['ID'];
    $fname = $_GET['fname'];
    $lname = $_GET['lname'];
    $appdate = $_GET['appdate'];
    $apptime = $_GET['apptime'];
}

if (isset($_POST['prescribe']) && isset($_POST['pid']) && isset($_POST['ID']) && isset($_POST['appdate']) && isset($_POST['apptime']) && isset($_POST['lname']) && isset($_POST['fname'])) {
    $appdate = $_POST['appdate'];
    $apptime = $_POST['apptime'];
    $disease = $_POST['disease'];
    $allergy = $_POST['allergy'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $pid = $_POST['pid'];
    $ID = $_POST['ID'];
    $prescription = $_POST['prescription'];

    $stmt = $pdo->prepare("INSERT INTO prestb(doctor, pid, ID, fname, lname, appdate, apptime, disease, allergy, prescription) VALUES (:doctor, :pid, :ID, :fname, :lname, :appdate, :apptime, :disease, :allergy, :prescription)");
    $query = $stmt->execute([
        ':doctor' => $doctor,
        ':pid' => $pid,
        ':ID' => $ID,
        ':fname' => $fname,
        ':lname' => $lname,
        ':appdate' => $appdate,
        ':apptime' => $apptime,
        ':disease' => $disease,
        ':allergy' => $allergy,
        ':prescription' => $prescription
    ]);

    if ($query) {
        redirectWithMessage('dashboard.php', 'success', 'Prescribed successfully!');
    } else {
        redirectWithMessage('dashboard.php', 'error', 'Unable to process your request. Try again!');
    }
}
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <title>Prescribe Medication - Global Hospital</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --success: #10B981;
            --danger: #EF4444;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
            min-height: 100vh;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 25% 75%, rgba(20, 184, 166, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .container-custom {
            max-width: 900px;
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

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
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
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateX(-5px);
        }

        .patient-info {
            background: var(--gray-50);
            padding: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .patient-info h3 {
            color: var(--gray-900);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-content label {
            font-size: 0.75rem;
            color: var(--gray-700);
            font-weight: 600;
            text-transform: uppercase;
            display: block;
        }

        .info-content span {
            font-size: 1rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .form-section {
            padding: 2rem;
        }

        .form-section h3 {
            color: var(--gray-900);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--success);
            color: white;
        }

        .btn-primary:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .back-button {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
                width: 100%;
                justify-content: center;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>
    <a href="dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container-custom">
        <div class="header">
            <h1><i class="fas fa-prescription"></i> Prescribe Medication</h1>
            <p>Issue prescription for patient appointment</p>
        </div>

        <div class="patient-info">
            <h3>Patient Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-content">
                        <label>Patient Name</label>
                        <span><?php echo $fname . ' ' . $lname; ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div class="info-content">
                        <label>Patient ID</label>
                        <span>#<?php echo $pid; ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="info-content">
                        <label>Appointment Date</label>
                        <span><?php echo date('d M Y', strtotime($appdate)); ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <label>Appointment Time</label>
                        <span><?php echo date('h:i A', strtotime($apptime)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="prescribe.php" class="form-section">
            <h3><i class="fas fa-file-medical"></i> Prescription Details</h3>

            <input type="hidden" name="fname" value="<?php echo $fname; ?>">
            <input type="hidden" name="lname" value="<?php echo $lname; ?>">
            <input type="hidden" name="appdate" value="<?php echo $appdate; ?>">
            <input type="hidden" name="apptime" value="<?php echo $apptime; ?>">
            <input type="hidden" name="pid" value="<?php echo $pid; ?>">
            <input type="hidden" name="ID" value="<?php echo $ID; ?>">

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-disease"></i> Disease / Diagnosis
                </label>
                <input type="text" name="disease" class="form-control" placeholder="Enter diagnosis" required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-exclamation-triangle"></i> Allergies
                </label>
                <input type="text" name="allergy" class="form-control" placeholder="Enter known allergies (if any)">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-pills"></i> Prescription / Medication
                </label>
                <textarea name="prescription" class="form-control" placeholder="Enter medication details, dosage, and instructions" required></textarea>
            </div>

            <div class="button-group">
                <button type="submit" name="prescribe" class="btn btn-primary">
                    <i class="fas fa-check"></i> Issue Prescription
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>