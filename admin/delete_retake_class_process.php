<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['retake_id'])) {
    $retake_id = $_POST['retake_id'];
    $conn = connectToDatabase();

    $sql = 'DELETE FROM retake_classes WHERE retake_id = $1';
    pg_prepare($conn, "delete_retake_class", $sql);
    $result = pg_execute($conn, "delete_retake_class", array($retake_id));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Retake class deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting retake class: ' . pg_last_error($conn)]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing parameters.']);
}
?>