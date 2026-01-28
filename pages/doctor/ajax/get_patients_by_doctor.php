<?php
/**
 * Get patients by doctor for medical records
 * Returns list of patients seen by the specified doctor
 */

session_start();
require_once('../../../config.php');

if (!isset($_POST['doctor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'doctor_id is required']);
    exit;
}

$doctor_id = intval($_POST['doctor_id']);

try {
    // Get all patients that have appointments with this doctor
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.pid, p.fname, p.lname, p.contact
        FROM patreg p
        INNER JOIN appointmenttb a ON p.pid = a.pid
        INNER JOIN doctb d ON a.doctor = d.fullname
        WHERE d.id = :doctor_id
        ORDER BY p.fname, p.lname
    ");
    $stmt->execute([':doctor_id' => $doctor_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'patients' => $patients
    ]);
} catch (PDOException $e) {
    error_log("Get patients by doctor error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
