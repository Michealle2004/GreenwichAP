<?php
session_start();
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated or invalid request method.']);
    exit();
}

$student_id = $_POST['student_id'] ?? null;
$schedule_id = $_POST['schedule_id'] ?? null;
$note = $_POST['note'] ?? '';

if (empty($student_id) || empty($schedule_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
    exit();
}

$conn = connectToDatabase();

$sql = 'UPDATE enrollments SET note = $1 WHERE student_id = $2 AND schedule_id = $3';
pg_prepare($conn, "update_note", $sql);
$result = pg_execute($conn, "update_note", array($note, $student_id, $schedule_id));

if ($result) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => pg_last_error($conn)]);
}

pg_close($conn);
?>