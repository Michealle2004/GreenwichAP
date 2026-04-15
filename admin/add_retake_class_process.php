<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $fee = $_POST['fee'] ?? null;

    if (empty($student_id) || empty($course_id) || empty($fee)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql = 'INSERT INTO retake_classes (student_id, course_id, fee, date_added) VALUES ($1, $2, $3, NOW())';
    pg_prepare($conn, "add_retake_class", $sql);
    $result = pg_execute($conn, "add_retake_class", array($student_id, $course_id, $fee));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Retake class added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding retake class: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>