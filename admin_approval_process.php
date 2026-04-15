<?php
session_start();
require_once 'admin_check.php'; 
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (empty($enrollment_id) || !in_array($action, ['approve', 'reject'])) {
        header("Location: register.php?status=error&message=Invalid action.");
        exit();
    }

    $new_status = ($action == 'approve') ? 'approved' : 'rejected';

    $conn = connectToDatabase();

    $sql_update = 'UPDATE enrollments SET status = $1 WHERE enrollment_id = $2';
    pg_prepare($conn, "update_enrollment_status", $sql_update);
    $result_update = pg_execute($conn, "update_enrollment_status", array($new_status, $enrollment_id));

    if ($result_update) {
        header("Location: register.php?status=success&message=Request has been " . $new_status . ".");
    } else {
        $error_message = urlencode("Database error: " . pg_last_error($conn));
        header("Location: register.php?status=error&message=" . $error_message);
    }

    pg_close($conn);

} else {
    header("Location: register.php");
    exit();
}
?>