<?php
session_start();
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$conn = connectToDatabase();
$student_id = $_SESSION['id'] ?? ($_SESSION['user_id'] ?? null);
$amount = $_POST['amount'] ?? 0;

if (!$student_id) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Login again.']);
    exit;
}

$sql = "INSERT INTO wallet_requests (student_id, amount, status) VALUES ($1, $2, 'pending')";
$res = @pg_query_params($conn, $sql, array($student_id, $amount));

if ($res) {
    echo json_encode(['status' => 'success', 'message' => 'Request sent to Admin!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error. Check table wallet_requests.']);
}