<?php
header('Content-Type: application/json');
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

try {
    $cur_id = (int)($_POST['curriculum_id'] ?? 0);
    if (!$cur_id) throw new Exception("ID không hợp lệ.");

    $conn = connectToDatabase();
    $sql = 'DELETE FROM curriculum WHERE curriculum_id = $1';
    pg_prepare($conn, "del_cur", $sql);
    $res = pg_execute($conn, "del_cur", array($cur_id));

    if ($res) echo json_encode(['status' => 'success']);
    else throw new Exception("Không thể xóa khỏi database.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>