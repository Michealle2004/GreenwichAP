<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'] ?? null;
    $course_name = $_POST['course_name'] ?? null;
    $credits = $_POST['credits'] ?? null;
    $fee = $_POST['fee'] ?? null;

    if (empty($course_id) || empty($course_name) || empty($credits) || empty($fee)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql = 'INSERT INTO courses (course_id, course_name, credits, fee) VALUES ($1, $2, $3, $4)';
    pg_prepare($conn, "add_course", $sql);
    $result = pg_execute($conn, "add_course", array($course_id, $course_name, $credits, $fee));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Course added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding course: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>