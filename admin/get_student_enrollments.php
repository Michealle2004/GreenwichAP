<?php
require_once '../includes/db_connect.php';
$conn = connectToDatabase();
$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT e.enrollment_id, c.course_id, c.course_name 
        FROM enrollments e
        JOIN schedules s ON e.schedule_id = s.schedule_id
        JOIN courses c ON s.course_id = c.course_id
        WHERE e.student_id = $1 AND e.status = 'approved'";

$res = pg_query_params($conn, $sql, [$student_id]);
$data = [];
while($row = pg_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);