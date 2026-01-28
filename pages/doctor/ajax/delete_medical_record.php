<?php
/**
 * Delete medical record
 */

session_start();
require_once('../../../config.php');

if (!isset($_POST['record_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'record_id is required']);
    exit;
}

$record_id = intval($_POST['record_id']);

try {
    $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = :record_id");
    $stmt->execute([':record_id' => $record_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Hồ sơ bệnh án đã được xóa thành công!'
    ]);
} catch (PDOException $e) {
    error_log("Delete medical record error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
