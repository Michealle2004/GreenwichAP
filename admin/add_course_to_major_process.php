<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $major_id = $_POST['major_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    $term_no = $_POST['term_no'] ?? null;

    if (empty($major_id) || empty($course_id) || empty($term_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit();
    }

    $conn = connectToDatabase();

    $sql_check = 'SELECT 1 FROM curriculum WHERE major_id = $1 AND course_id = $2';
    pg_prepare($conn, "check_exists", $sql_check);
    $result_check = pg_execute($conn, "check_exists", array($major_id, $course_id));

    if (pg_num_rows($result_check) > 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'This course is already assigned to this major!'
        ]);
        pg_close($conn);
        exit();
    }

    $sql = 'INSERT INTO curriculum (major_id, course_id, term_no) VALUES ($1, $2, $3)';
    pg_prepare($conn, "add_course_to_major", $sql);
    $result = pg_execute($conn, "add_course_to_major", array($major_id, $course_id, $term_no));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Course added to major successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding course to major: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>