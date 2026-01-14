<?php
/**
 * Get Doctors by Specialization
 * Returns list of doctors for selected specialization
 */

session_start();
require_once('../../../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['pid'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$spec_id = $_POST['spec_id'] ?? null;

if (!$spec_id) {
    echo json_encode(['error' => 'Specialization ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT d.id, d.username, d.email, d.docFees, d.experience_years, d.bio,
               s.name_vi as spec_name, s.icon as spec_icon
        FROM doctb d
        LEFT JOIN specializations s ON d.spec_id = s.id
        WHERE d.spec_id = :spec_id AND d.status = 1
        ORDER BY d.username
    ");
    
    $stmt->execute([':spec_id' => $spec_id]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($doctors);
    
} catch (PDOException $e) {
    error_log("Get doctors error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
