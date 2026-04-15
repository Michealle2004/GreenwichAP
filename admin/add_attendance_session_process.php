<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $session_date = $_POST['new_session_date'] ?? null;
    
    if (empty($schedule_id) || empty($session_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing schedule ID or session date.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql_enrollments = 'SELECT enrollment_id FROM enrollments WHERE schedule_id = $1';
    pg_prepare($conn, "get_enrollments_for_session", $sql_enrollments);
    $result_enrollments = pg_execute($conn, "get_enrollments_for_session", array($schedule_id));
    $enrollment_ids = pg_fetch_all_columns($result_enrollments, 0);

    if (empty($enrollment_ids)) {
        pg_close($conn);
        echo json_encode(['status' => 'error', 'message' => 'No students enrolled in this class to add a session for.']);
        exit();
    }

    $insert_values = [];
    $params = [];
    $i = 1;
    foreach ($enrollment_ids as $enrollment_id) {
        $insert_values[] = "(\$" . $i++ . ", \$" . $i++ . ", 'not_yet')";
        $params[] = $enrollment_id;
        $params[] = $session_date;
    }
    
    $sql_insert = 'INSERT INTO attendance (enrollment_id, session_date, status) VALUES ' . implode(', ', $insert_values);
    
    $result_insert = pg_query_params($conn, $sql_insert, $params);

    if ($result_insert) {
        echo json_encode(['status' => 'success', 'message' => 'New session added for all ' . count($enrollment_ids) . ' students.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error inserting session: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>