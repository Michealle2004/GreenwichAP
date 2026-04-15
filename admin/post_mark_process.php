<?php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');
$conn = connectToDatabase();

$mark_id = $_POST['mark_id'] ?? null;
$enrollment_id = $_POST['enrollment_id'] ?? null;
$assignment = $_POST['assignment_name'] ?? null;
$score = $_POST['score'] ?? null;

if (!$enrollment_id || !$assignment || $score === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

if ($mark_id) {
    $sql = "UPDATE marks SET score = $1, assignment_name = $2 WHERE mark_id = $3";
    $res = pg_query_params($conn, $sql, array($score, $assignment, $mark_id));
} else {
    $sql = "INSERT INTO marks (enrollment_id, assignment_name, score, status) VALUES ($1, $2, $3, 'not_yet')";
    $res = pg_query_params($conn, $sql, array($enrollment_id, $assignment, $score));
}

if ($res) echo json_encode(['status' => 'success', 'message' => 'Saved successfully!']);
else echo json_encode(['status' => 'error', 'message' => pg_last_error($conn)]);