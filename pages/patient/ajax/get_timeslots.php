<?php
/**
 * Get Time Slots for Doctor and Date
 * Generates and returns available time slots like cinema seats
 */

session_start();
require_once('../../../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['pid'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$doctor_id = $_POST['doctor_id'] ?? null;
$date = $_POST['date'] ?? null;

if (!$doctor_id || !$date) {
    echo json_encode(['error' => 'Doctor ID and date required']);
    exit();
}

try {
    // Get day of week (0 = Sunday, 1 = Monday, etc.)
    $day_of_week = date('w', strtotime($date));
    
    // Check if doctor has schedule for this day
    $schedule_stmt = $pdo->prepare("
        SELECT start_time, end_time, slot_duration, max_patients
        FROM doctor_schedules
        WHERE doctor_id = :doctor_id AND day_of_week = :day_of_week AND is_active = 1
        LIMIT 1
    ");
    
    $schedule_stmt->execute([
        ':doctor_id' => $doctor_id,
        ':day_of_week' => $day_of_week
    ]);
    
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode([
            'success' => false,
            'message' => 'Bác sĩ không làm việc vào ngày này',
            'slots' => []
        ]);
        exit();
    }
    
    // Generate time slots
    $slots = [];
    $start_time = new DateTime($schedule['start_time']);
    $end_time = new DateTime($schedule['end_time']);
    $slot_duration = intval($schedule['slot_duration']);
    
    $current_time = clone $start_time;
    
    while ($current_time < $end_time) {
        $slot_time = $current_time->format('H:i:s');
        $slot_time_display = $current_time->format('H:i');
        
        // Check if this slot already exists in database
        $check_stmt = $pdo->prepare("
            SELECT id, status, appointment_id
            FROM time_slots
            WHERE doctor_id = :doctor_id 
            AND slot_date = :slot_date 
            AND slot_time = :slot_time
        ");
        
        $check_stmt->execute([
            ':doctor_id' => $doctor_id,
            ':slot_date' => $date,
            ':slot_time' => $slot_time
        ]);
        
        $existing_slot = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_slot) {
            // Slot exists in database
            $slots[] = [
                'id' => $existing_slot['id'],
                'slot_time' => $slot_time_display,
                'slot_time_full' => $slot_time,
                'status' => $existing_slot['status'],
                'appointment_id' => $existing_slot['appointment_id']
            ];
        } else {
            // Create new slot in database
            $insert_stmt = $pdo->prepare("
                INSERT INTO time_slots (doctor_id, slot_date, slot_time, status)
                VALUES (:doctor_id, :slot_date, :slot_time, 'available')
            ");
            
            $insert_stmt->execute([
                ':doctor_id' => $doctor_id,
                ':slot_date' => $date,
                ':slot_time' => $slot_time
            ]);
            
            $new_slot_id = $pdo->lastInsertId();
            
            $slots[] = [
                'id' => $new_slot_id,
                'slot_time' => $slot_time_display,
                'slot_time_full' => $slot_time,
                'status' => 'available',
                'appointment_id' => null
            ];
        }
        
        // Move to next slot
        $current_time->modify("+{$slot_duration} minutes");
    }
    
    // If date is today, mark past slots as blocked
    if ($date === date('Y-m-d')) {
        $current_time_now = date('H:i:s');
        foreach ($slots as &$slot) {
            if ($slot['slot_time_full'] <= $current_time_now && $slot['status'] === 'available') {
                $slot['status'] = 'blocked';
                
                // Update in database
                $update_stmt = $pdo->prepare("
                    UPDATE time_slots 
                    SET status = 'blocked' 
                    WHERE id = :id
                ");
                $update_stmt->execute([':id' => $slot['id']]);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'schedule' => [
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'slot_duration' => $schedule['slot_duration']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get time slots error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'slots' => []
    ]);
}
