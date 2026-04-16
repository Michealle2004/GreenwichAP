<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SỬA TẠI ĐÂY: Nhận 'delete_task' thay vì 'action'
    $action = $_POST['delete_task'] ?? null;
    $major_id = $_POST['major_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;

    if (empty($major_id) || empty($course_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing Major ID or Course ID.']);
        exit();
    }

    $conn = connectToDatabase();

    if ($action === 'delete') {
        $sql = 'DELETE FROM curriculum WHERE major_id = $1 AND course_id = $2';
        
        $stmt_name = "delete_cur_" . time(); 
        $prep = pg_prepare($conn, $stmt_name, $sql);
        
        if ($prep) {
            $result = pg_execute($conn, $stmt_name, array($major_id, $course_id));

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Course removed.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . pg_last_error($conn)]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    }

    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
?>