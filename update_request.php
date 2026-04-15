<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: submission_history.php");
    exit();
}

$request_id = (int)$_POST['request_id'];
$status = $_POST['status'];
$response = trim($_POST['response']);

$conn = connectToDatabase();

$sql = 'UPDATE paper_requests SET status = $1, response = $2 WHERE request_id = $3';

pg_prepare($conn, "update_req", $sql);
$result = pg_execute($conn, "update_req", array($status, $response, $request_id));

if ($result) {
    header("Location: submission_history.php?status=success");
} else {
    header("Location: submission_history.php?status=error");
}

pg_close($conn);
exit();