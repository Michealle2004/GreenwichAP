<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $teacher_id = $_POST['teacher_id'] ?? null;
    $room = $_POST['room'] ?? null;
    
    if (empty($schedule_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing schedule ID.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql_update = 'UPDATE schedules SET teacher_id = $1, room = $2 WHERE schedule_id = $3';
    pg_prepare($conn, "update_schedule", $sql_update);
    $result_update = pg_execute($conn, "update_schedule", array($teacher_id, $room, $schedule_id));
    
    if ($result_update) {
        echo json_encode(['status' => 'success', 'message' => 'Schedule updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => pg_last_error($conn)]);
    }
    
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>