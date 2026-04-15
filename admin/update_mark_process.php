<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $assignment_name = $_POST['assignment_name'] ?? null;
    $score = $_POST['score'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (empty($action) || empty($enrollment_id) || empty($assignment_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing required fields.']);
        exit();
    }
    
    $conn = connectToDatabase();
    
    if ($action == 'add') {
        $sql = 'INSERT INTO marks (enrollment_id, assignment_name, score, status) VALUES ($1, $2, $3, $4)';
        pg_prepare($conn, "add_mark", $sql);
        $result = pg_execute($conn, "add_mark", array($enrollment_id, $assignment_name, $score, $status));
    } elseif ($action == 'update') {
        $sql = 'UPDATE marks SET score = $1, status = $2 WHERE enrollment_id = $3 AND assignment_name = $4';
        pg_prepare($conn, "update_mark", $sql);
        $result = pg_execute($conn, "update_mark", array($score, $status, $enrollment_id, $assignment_name));
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        exit();
    }

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Mark updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating mark: ' . pg_last_error($conn)]);
    }
    
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>