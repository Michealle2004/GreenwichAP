<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];
$feedback_target = trim($_POST['feedback_target'] ?? '');
$person_in_charge = trim($_POST['person_in_charge'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if (empty($feedback_target) || empty($reason)) {
    header("Location: feedback_quality.php?status=error&message=MissingFields");
    exit();
}

$conn = connectToDatabase();

$sql = 'INSERT INTO feedback (student_id, feedback_target, person_in_charge, reason) VALUES ($1, $2, $3, $4)';

$stmt = pg_prepare($conn, "insert_feedback", $sql);
if ($stmt) {
    $result = pg_execute($conn, "insert_feedback", array($student_id, $feedback_target, $person_in_charge, $reason));

    if ($result) {
        header("Location: feedback_quality.php?status=success");
    } else {
        // Log lỗi nếu cần: error_log(pg_last_error($conn));
        header("Location: feedback_quality.php?status=error&message=ExecutionFailed");
    }
} else {
    header("Location: feedback_quality.php?status=error&message=PrepareFailed");
}

pg_close($conn);
exit();
?>