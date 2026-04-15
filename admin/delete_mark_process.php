<?php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = connectToDatabase();
$mark_id = $_POST['mark_id'] ?? null;

if ($mark_id) {
    $sql = "DELETE FROM marks WHERE mark_id = $1";
    $res = pg_query_params($conn, $sql, [$mark_id]);
    
    if ($res) {
        echo json_encode(['status' => 'success', 'message' => 'Mark deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
}
pg_close($conn);