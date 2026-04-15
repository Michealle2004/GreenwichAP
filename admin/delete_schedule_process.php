<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];
    $conn = connectToDatabase();

    pg_query($conn, "BEGIN");

    try {
        $sql_marks = 'DELETE FROM marks WHERE enrollment_id IN (SELECT enrollment_id FROM enrollments WHERE schedule_id = $1)';
        $res_marks = pg_query_params($conn, $sql_marks, array($schedule_id));
        if (!$res_marks) throw new Exception("Could not delete related marks.");

        $sql_att = 'DELETE FROM attendance WHERE enrollment_id IN (SELECT enrollment_id FROM enrollments WHERE schedule_id = $1)';
        $res_att = pg_query_params($conn, $sql_att, array($schedule_id));
        if (!$res_att) throw new Exception("Could not delete related attendance.");

        $sql_enroll = 'DELETE FROM enrollments WHERE schedule_id = $1';
        $res_enroll = pg_query_params($conn, $sql_enroll, array($schedule_id));
        if (!$res_enroll) throw new Exception("Could not delete enrollments.");

        $sql_sched = 'DELETE FROM schedules WHERE schedule_id = $1';
        $res_sched = pg_query_params($conn, $sql_sched, array($schedule_id));
        
        if ($res_sched) {
            pg_query($conn, "COMMIT");
            echo json_encode(['status' => 'success', 'message' => 'Schedule and all related data deleted successfully!']);
        } else {
            throw new Exception("Could not delete schedule.");
        }

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or missing ID.']);
}
exit;