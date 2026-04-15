<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_user_id = $_POST['student_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $title = $_POST['title'] ?? null;
    $deadline_date = $_POST['deadline_date'] ?? null;
    $details = $_POST['details'] ?? null;

    if (empty($student_user_id) || empty($course_id) || empty($title) || empty($deadline_date) || empty($details)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql_check_enrollment = 'SELECT 1 FROM enrollments e
                             JOIN schedules s ON e.schedule_id = s.schedule_id
                             JOIN users u ON e.student_id = u.id
                             WHERE u.user_id = $1 AND s.course_id = $2';
    pg_prepare($conn, "check_enrollment", $sql_check_enrollment);
    $result_check = pg_execute($conn, "check_enrollment", array($student_user_id, $course_id));
    
    if (pg_num_rows($result_check) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Error: The selected student is not enrolled in this course. You can only add deadlines for enrolled students.']);
        exit();
    }
    
    $sql = 'INSERT INTO exam_deadlines (course_id, title, deadline_date, details) VALUES ($1, $2, $3, $4)';
    pg_prepare($conn, "add_deadline", $sql);
    $result = pg_execute($conn, "add_deadline", array($course_id, $title, $deadline_date, $details));
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Deadline added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding deadline: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>