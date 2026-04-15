<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;
    $major_id = $_POST['major_id'] ?? null;
    $major_name = $_POST['major_name'] ?? null;

    if (empty($action) || empty($major_id) || empty($major_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit();
    }

    $conn = connectToDatabase();
    $result = false;

    if ($action == 'add') {
        $sql = 'INSERT INTO majors (major_id, major_name) VALUES ($1, $2)';
        pg_prepare($conn, "add_major", $sql);
        $result = pg_execute($conn, "add_major", array($major_id, $major_name));
    } elseif ($action == 'update') {
        $sql = 'UPDATE majors SET major_name = $1 WHERE major_id = $2';
        pg_prepare($conn, "update_major", $sql);
        $result = pg_execute($conn, "update_major", array($major_name, $major_id));
    } elseif ($action == 'delete') {
        $sql = 'DELETE FROM majors WHERE major_id = $1';
        pg_prepare($conn, "delete_major", $sql);
        $result = pg_execute($conn, "delete_major", array($major_id));
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        exit();
    }

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Major updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating major: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>