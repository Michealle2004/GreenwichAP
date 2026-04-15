<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback_id'])) {
    $feedback_id = $_POST['feedback_id'];
    $conn = connectToDatabase();

    $sql = 'DELETE FROM feedback WHERE feedback_id = $1';
    pg_prepare($conn, "delete_feedback", $sql);

    $result = pg_execute($conn, "delete_feedback", array($feedback_id));

    if ($result) {
        header("Location: view_feedback.php?status=deleted");
        exit();
    } else {
        die("Error deleting record: " . pg_last_error($conn));
    }
    pg_close($conn);
} else {
    header("Location: view_feedback.php");
    exit();
}
?>