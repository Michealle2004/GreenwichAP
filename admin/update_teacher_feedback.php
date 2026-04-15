<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fb_id = $_POST['feedback_id'];
    $status = $_POST['status'];
    $response = $_POST['admin_response'];

    $conn = connectToDatabase();
    $sql = 'UPDATE teacher_feedback SET status = $1, admin_response = $2 WHERE feedback_id = $3';
    pg_query_params($conn, $sql, array($status, $response, $fb_id));

    header("Location: view_teacher_feedback.php");
    pg_close($conn);
}