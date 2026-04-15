<?php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if ($_SESSION['role'] === 'admin' && isset($_GET['id'])) {
    $conn = connectToDatabase();
    $id = (int)$_GET['id'];
    $result = pg_query_params($conn, "DELETE FROM announcements WHERE id = $1", [$id]);
    
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    pg_close($conn);
}