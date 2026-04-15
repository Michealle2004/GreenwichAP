<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../includes/db_connect.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $conn = connectToDatabase();
    
    // Nhận dữ liệu
    $cur_id = (int)($_POST['curriculum_id'] ?? 0);
    $old_id = $_POST['old_course_id'] ?? '';
    $new_id = trim($_POST['new_course_id'] ?? '');
    $name   = trim($_POST['course_name'] ?? '');
    $cre    = (int)($_POST['credits'] ?? 0);
    $fee    = (float)($_POST['fee'] ?? 0);
    $term   = (int)($_POST['term_no'] ?? 0);

    if (!$cur_id || !$old_id || !$new_id) {
        throw new Exception("Missing required fields.");
    }

    pg_query($conn, "BEGIN");

    $sql1 = 'UPDATE courses SET course_id = $1, course_name = $2, credits = $3, fee = $4 WHERE course_id = $5';
    $res1 = pg_query_params($conn, $sql1, array($new_id, $name, $cre, $fee, $old_id));
    if (!$res1) throw new Exception("Failed to update courses table.");

    $sql2 = 'UPDATE curriculum SET term_no = $1 WHERE curriculum_id = $2';
    $res2 = pg_query_params($conn, $sql2, array($term, $cur_id));
    if (!$res2) throw new Exception("Failed to update curriculum table.");

    pg_query($conn, "COMMIT");
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if (isset($conn)) pg_query($conn, "ROLLBACK");
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>