<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['attendance_id']) && isset($_POST['status'])) {
    $attendance_id = $_POST['attendance_id'];
    $status = $_POST['status'];

    $conn = connectToDatabase();

    $sql = 'UPDATE attendance SET status = $1 WHERE attendance_id = $2';
    pg_prepare($conn, "update_attendance", $sql);
    $result = pg_execute($conn, "update_attendance", array($status, $attendance_id));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Attendance updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating attendance: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing parameters.']);
}
?>