<?php
/**
 * Update medical record
 */

session_start();
require_once('../../../config.php');

if (!isset($_POST['record_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'record_id is required']);
    exit;
}

$record_id = intval($_POST['record_id']);
$symptoms = $_POST['symptoms'] ?? '';
$diagnosis = $_POST['diagnosis'] ?? '';
$treatment_plan = $_POST['treatment_plan'] ?? '';
$notes = $_POST['notes'] ?? null;
$height = isset($_POST['height']) && $_POST['height'] !== '' ? floatval($_POST['height']) : null;
$weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? floatval($_POST['weight']) : null;
$blood_pressure = $_POST['blood_pressure'] ?? null;
$heart_rate = isset($_POST['heart_rate']) && $_POST['heart_rate'] !== '' ? intval($_POST['heart_rate']) : null;
$temperature = isset($_POST['temperature']) && $_POST['temperature'] !== '' ? floatval($_POST['temperature']) : null;

try {
    $stmt = $pdo->prepare("
        UPDATE medical_records
        SET symptoms = :symptoms,
            diagnosis = :diagnosis,
            treatment_plan = :treatment_plan,
            notes = :notes,
            height = :height,
            weight = :weight,
            blood_pressure = :blood_pressure,
            heart_rate = :heart_rate,
            temperature = :temperature,
            updated_at = NOW()
        WHERE id = :record_id
    ");
    $stmt->execute([
        ':record_id' => $record_id,
        ':symptoms' => $symptoms,
        ':diagnosis' => $diagnosis,
        ':treatment_plan' => $treatment_plan,
        ':notes' => $notes,
        ':height' => $height,
        ':weight' => $weight,
        ':blood_pressure' => $blood_pressure,
        ':heart_rate' => $heart_rate,
        ':temperature' => $temperature
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Hồ sơ bệnh án đã được cập nhật thành công!'
    ]);
} catch (PDOException $e) {
    error_log("Update medical record error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
