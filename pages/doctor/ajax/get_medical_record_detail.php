<?php
/**
 * Get medical record detail or all records by patient_id
 */

session_start();
require_once('../../../config.php');

// Get single record by record_id
if (isset($_POST['record_id'])) {
    $record_id = intval($_POST['record_id']);

    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, 
                   p.fname, p.lname, p.contact, p.email,
                   d.fullname as doctor_name
            FROM medical_records mr
            LEFT JOIN patreg p ON mr.patient_id = p.pid
            LEFT JOIN doctb d ON mr.doctor_id = d.id
            WHERE mr.id = :record_id
        ");
        $stmt->execute([':record_id' => $record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            echo json_encode(['error' => 'Record not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'record' => $record
        ]);
    } catch (PDOException $e) {
        error_log("Get medical record detail error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
// Get all records by patient_id
elseif (isset($_POST['patient_id'])) {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = null;

    // Get current doctor ID from session
    $doctor = $_SESSION['dname'] ?? null;
    if ($doctor) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM doctb WHERE fullname = :fullname");
            $stmt->execute([':fullname' => $doctor]);
            $doctor_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $doctor_id = $doctor_result['id'] ?? null;
        } catch (PDOException $e) {
            error_log("Get doctor ID error: " . $e->getMessage());
        }
    }

    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, 
                   p.fname, p.lname, p.contact, p.email,
                   d.fullname as doctor_name
            FROM medical_records mr
            LEFT JOIN patreg p ON mr.patient_id = p.pid
            LEFT JOIN doctb d ON mr.doctor_id = d.id
            WHERE mr.patient_id = :patient_id AND mr.doctor_id = :doctor_id
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':doctor_id' => $doctor_id
        ]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'records' => $records
        ]);
    } catch (PDOException $e) {
        error_log("Get patient records error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
else {
    http_response_code(400);
    echo json_encode(['error' => 'record_id or patient_id is required']);
}
?>
