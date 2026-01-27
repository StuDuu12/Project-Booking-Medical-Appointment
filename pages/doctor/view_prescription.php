<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');

// Check if prescription ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php?page=prescriptions");
    exit();
}

$prescription_id = $_GET['id'];

// Get prescription details
$stmt = $pdo->prepare("
    SELECT p.*, 
           p.fname, p.lname, p.ID as app_id,
           pat.email, pat.gender, pat.contact,
           d.fullname as doctor_name, d.spec as doctor_spec
    FROM prestb p
    JOIN patreg pat ON p.pid = pat.pid
    JOIN doctb d ON p.doctor = d.username
    WHERE p.pres_id = ?
");
$stmt->execute([$prescription_id]);
$prescription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prescription) {
    header("Location: dashboard.php?page=prescriptions");
    exit();
}

// Get medications
$med_stmt = $pdo->prepare("
    SELECT * FROM prescription_medications 
    WHERE prescription_id = ?
    ORDER BY id
");
$med_stmt->execute([$prescription_id]);
$medications = $med_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #0891b2;
            --secondary-color: #0e7490;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .prescription-view {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .prescription-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .prescription-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .prescription-body {
            padding: 2rem;
        }
        
        .info-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-section h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #64748b;
            width: 150px;
        }
        
        .info-value {
            color: #1e293b;
        }
        
        .medications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .medications-table th {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .medications-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .medications-table tr:hover {
            background: #f1f5f9;
        }
        
        .medication-number {
            background: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-pdf {
            background: #ef4444;
            color: white;
        }
        
        .btn-pdf:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #6b7280;
            color: white;
        }
        
        .btn-back:hover {
            background: #4b5563;
        }
        
        @media print {
            body {
                background: white;
            }
            .prescription-view {
                box-shadow: none;
            }
            .btn-action {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="prescription-view">
        <div class="prescription-header">
            <h1><i class="fas fa-file-medical"></i> Medical Prescription</h1>
            <p class="mb-0">Global Hospitals - Professional Healthcare</p>
        </div>
        
        <div class="prescription-body">
            <!-- Action Buttons -->
            <div class="text-right mb-4">
                <a href="export_prescription_pdf.php?id=<?php echo $prescription_id; ?>" class="btn-action btn-pdf" target="_blank">
                    <i class="fas fa-file-pdf mr-2"></i>Download PDF
                </a>
                <a href="dashboard.php?page=prescriptions" class="btn-action btn-back ml-2">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>
            
            <!-- Doctor Information -->
            <div class="info-section">
                <h3><i class="fas fa-user-md mr-2"></i>Doctor Information</h3>
                <div class="info-row">
                    <div class="info-label">Doctor Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Specialization:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['doctor_spec']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date:</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($prescription['created_at'])); ?></div>
                </div>
            </div>
            
            <!-- Patient Information -->
            <div class="info-section">
                <h3><i class="fas fa-user-injured mr-2"></i>Patient Information</h3>
                <div class="info-row">
                    <div class="info-label">Patient Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['fname'] . ' ' . $prescription['lname']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['gender']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['contact']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['email']); ?></div>
                </div>
            </div>
            
            <!-- Diagnosis -->
            <div class="info-section">
                <h3><i class="fas fa-stethoscope mr-2"></i>Diagnosis</h3>
                <div class="info-row">
                    <div class="info-label">Disease:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['disease']); ?></div>
                </div>
                <?php if (!empty($prescription['allergy'])): ?>
                <div class="info-row">
                    <div class="info-label">Allergies:</div>
                    <div class="info-value text-danger font-weight-bold"><?php echo htmlspecialchars($prescription['allergy']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">Treatment Duration:</div>
                    <div class="info-value"><?php echo htmlspecialchars($prescription['treatment_duration']); ?></div>
                </div>
            </div>
            
            <!-- Medications -->
            <div class="info-section">
                <h3><i class="fas fa-pills mr-2"></i>Prescribed Medications</h3>
                <?php if (count($medications) > 0): ?>
                <table class="medications-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medications as $index => $med): ?>
                        <tr>
                            <td><span class="medication-number"><?php echo $index + 1; ?></span></td>
                            <td>
                                <strong><?php echo htmlspecialchars($med['medication_name']); ?></strong>
                                <?php if (!empty($med['special_notes'])): ?>
                                <br><small class="text-muted"><i class="fas fa-info-circle mr-1"></i><?php echo htmlspecialchars($med['special_notes']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($med['duration']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No medications prescribed.</p>
                <?php endif; ?>
            </div>
            
            <!-- General Instructions -->
            <?php if (!empty($prescription['general_notes'])): ?>
            <div class="info-section">
                <h3><i class="fas fa-notes-medical mr-2"></i>General Instructions</h3>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($prescription['general_notes'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="text-center mt-4 pt-4 border-top">
                <p class="text-muted mb-1"><small>This is a computer-generated prescription</small></p>
                <p class="text-muted mb-0"><small>Global Hospitals | Contact: (84) 123-456-789 | Email: info@globalhospitals.com</small></p>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
