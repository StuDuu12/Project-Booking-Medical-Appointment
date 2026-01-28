<?php
/**
 * Get appointments by patient and doctor
 * Returns list of appointments for the selected patient and doctor
 */

session_start();
require_once('../../../config.php');

if (!isset($_POST['patient_id']) || !isset($_POST['doctor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'patient_id and doctor_id are required']);
    exit;
}

$patient_id = intval($_POST['patient_id']);
$doctor_id = intval($_POST['doctor_id']);

try {
    // Get doctor name by ID
    $stmt = $pdo->prepare("SELECT fullname FROM doctb WHERE id = :doctor_id");
    $stmt->execute([':doctor_id' => $doctor_id]);
    $doctor_result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor_result) {
        echo json_encode(['success' => false, 'appointments' => []]);
        exit;
    }

    $doctor_name = $doctor_result['fullname'];

    // Get all appointments for this patient AND doctor
    $stmt = $pdo->prepare("
        SELECT ID, appdate, apptime, doctor, userStatus as status
        FROM appointmenttb
        WHERE pid = :patient_id AND doctor = :doctor_name
        ORDER BY appdate DESC, apptime DESC
    ");
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':doctor_name' => $doctor_name
    ]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log for debugging
    error_log("DEBUG: patient_id=$patient_id, doctor_name=$doctor_name, found " . count($appointments) . " appointments");

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
} catch (PDOException $e) {
    error_log("Get appointments by patient and doctor error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
