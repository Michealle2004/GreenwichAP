<?php
session_start();
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

$conn = connectToDatabase();
$request_id = $_POST['request_id'] ?? null;
$status = $_POST['status'] ?? null;

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

if ($status === 'approved') {
    pg_query($conn, "BEGIN");
    
    $res = pg_query_params($conn, "SELECT student_id, amount FROM wallet_requests WHERE request_id = $1", [$request_id]);
    $data = pg_fetch_assoc($res);

    if ($data) {
        $sid = $data['student_id'];
        $amt = $data['amount'];

        pg_query_params($conn, "UPDATE users SET wallet_balance = COALESCE(wallet_balance, 0) + $1 WHERE id = $2", [$amt, $sid]);
        
        pg_query_params($conn, "INSERT INTO wallet_transactions (user_id, amount, description) VALUES ($1, $2, 'Top-up Approved')", [$sid, $amt]);
        
        pg_query_params($conn, "UPDATE wallet_requests SET status = 'approved' WHERE request_id = $1", [$request_id]);
        
        pg_query($conn, "COMMIT");
        echo json_encode(['status' => 'success', 'message' => 'Approved successfully!']);
    } else {
        pg_query($conn, "ROLLBACK");
        echo json_encode(['status' => 'error', 'message' => 'Request data not found.']);
    }
} else {
    pg_query_params($conn, "UPDATE wallet_requests SET status = 'rejected' WHERE request_id = $1", [$request_id]);
    echo json_encode(['status' => 'success', 'message' => 'Request rejected.']);
}
pg_close($conn);