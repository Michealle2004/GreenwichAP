<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['course_id']) && isset($_POST['title']) && isset($_POST['deadline_date'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $deadline_date = $_POST['deadline_date'];
    
    $conn = connectToDatabase();

    $sql = 'DELETE FROM exam_deadlines WHERE course_id = $1 AND title = $2 AND deadline_date = $3';
    pg_prepare($conn, "delete_deadline", $sql);
    $result = pg_execute($conn, "delete_deadline", array($course_id, $title, $deadline_date));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Deadline deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting deadline: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing parameters.']);
}
?>