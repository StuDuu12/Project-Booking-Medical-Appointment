<?php
session_start();
require_once('../../config.php');
require_once('../../TCPDF/tcpdf.php');

// Check if prescription ID is provided
if (!isset($_GET['id'])) {
    die('Prescription ID not provided');
}

$prescription_id = $_GET['id'];

// Get prescription details
$stmt = $pdo->prepare("
    SELECT p.*, 
           p.fname, p.lname,
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
    die('Prescription not found');
}

// Get medications
$med_stmt = $pdo->prepare("
    SELECT * FROM prescription_medications 
    WHERE prescription_id = ?
    ORDER BY id
");
$med_stmt->execute([$prescription_id]);
$medications = $med_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Global Hospitals');
$pdf->SetAuthor($prescription['doctor_name']);
$pdf->SetTitle('Medical Prescription');
$pdf->SetSubject('Prescription for ' . $prescription['fname'] . ' ' . $prescription['lname']);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('dejavusans', '', 10);

// Hospital Header
$pdf->SetFillColor(8, 145, 178);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 18);
$pdf->Cell(0, 15, 'GLOBAL HOSPITALS', 0, 1, 'C', true);

$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 6, 'Professional Healthcare Services', 0, 1, 'C', true);
$pdf->Cell(0, 6, 'Address: Hanoi, Vietnam | Phone: (84) 123-456-789', 0, 1, 'C', true);

$pdf->Ln(10);

// Title
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'MEDICAL PRESCRIPTION', 0, 1, 'C');
$pdf->Ln(5);

// Date
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(0, 6, 'Date: ' . date('F d, Y', strtotime($prescription['created_at'])), 0, 1, 'R');
$pdf->Ln(3);

// Doctor Information
$pdf->SetFillColor(240, 249, 255);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 8, 'Doctor Information', 0, 1, 'L', true);
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(50, 6, 'Doctor Name:', 0, 0);
$pdf->Cell(0, 6, $prescription['doctor_name'], 0, 1);
$pdf->Cell(50, 6, 'Specialization:', 0, 0);
$pdf->Cell(0, 6, $prescription['doctor_spec'], 0, 1);
$pdf->Ln(5);

// Patient Information
$pdf->SetFillColor(240, 249, 255);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 8, 'Patient Information', 0, 1, 'L', true);
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(50, 6, 'Patient Name:', 0, 0);
$pdf->Cell(0, 6, $prescription['fname'] . ' ' . $prescription['lname'], 0, 1);
$pdf->Cell(50, 6, 'Gender:', 0, 0);
$pdf->Cell(0, 6, $prescription['gender'], 0, 1);
$pdf->Cell(50, 6, 'Contact:', 0, 0);
$pdf->Cell(0, 6, $prescription['contact'], 0, 1);
$pdf->Ln(5);

// Diagnosis
$pdf->SetFillColor(240, 249, 255);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 8, 'Diagnosis', 0, 1, 'L', true);
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(50, 6, 'Disease:', 0, 0);
$pdf->Cell(0, 6, $prescription['disease'], 0, 1);
if (!empty($prescription['allergy'])) {
    $pdf->SetTextColor(239, 68, 68);
    $pdf->Cell(50, 6, 'Allergies:', 0, 0);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, $prescription['allergy'], 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
}
$pdf->Cell(50, 6, 'Treatment Duration:', 0, 0);
$pdf->Cell(0, 6, $prescription['treatment_duration'], 0, 1);
$pdf->Ln(5);

// Medications Table
$pdf->SetFillColor(8, 145, 178);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 8, 'Prescribed Medications', 0, 1, 'L', true);
$pdf->Ln(2);

// Table Header
$pdf->SetFillColor(14, 116, 144);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('dejavusans', 'B', 9);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Medication', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Dosage', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Frequency', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Duration', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Notes', 1, 1, 'C', true);

// Table Body
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
foreach ($medications as $index => $med) {
    $pdf->Cell(10, 8, ($index + 1), 1, 0, 'C');
    $pdf->Cell(50, 8, $med['medication_name'], 1, 0, 'L');
    $pdf->Cell(30, 8, $med['dosage'], 1, 0, 'L');
    $pdf->Cell(40, 8, $med['frequency'], 1, 0, 'L');
    $pdf->Cell(25, 8, $med['duration'], 1, 0, 'C');
    $pdf->Cell(25, 8, !empty($med['special_notes']) ? 'Yes' : '-', 1, 1, 'C');
    
    // Special notes on new line if exists
    if (!empty($med['special_notes'])) {
        $pdf->SetFont('dejavusans', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Note: ' . $med['special_notes'], 0, 1);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(0, 0, 0);
    }
}

$pdf->Ln(5);

// General Instructions
if (!empty($prescription['general_notes'])) {
    $pdf->SetFillColor(240, 249, 255);
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->Cell(0, 8, 'General Instructions', 0, 1, 'L', true);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(0, 6, $prescription['general_notes'], 0, 'L');
    $pdf->Ln(5);
}

// Signature Section
$pdf->Ln(10);
$pdf->Cell(0, 6, '___________________________', 0, 1, 'R');
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(0, 6, $prescription['doctor_name'], 0, 1, 'R');
$pdf->SetFont('dejavusans', '', 9);
$pdf->Cell(0, 6, $prescription['doctor_spec'], 0, 1, 'R');

// Footer
$pdf->SetY(-20);
$pdf->SetFont('dejavusans', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 6, 'This is a computer-generated prescription and is valid without signature.', 0, 1, 'C');
$pdf->Cell(0, 6, 'Global Hospitals | Email: info@globalhospitals.com | Emergency: (84) 123-456-789', 0, 1, 'C');

// Output PDF
$filename = 'Prescription_' . $prescription['fname'] . '_' . $prescription['lname'] . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'I'); // 'I' for inline display, 'D' for download
?>
