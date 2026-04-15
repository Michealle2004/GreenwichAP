<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Quyền truy cập bị từ chối']);
        exit();
    }

    $conn = connectToDatabase();
    $title = trim($_POST['title']);
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $link = trim($_POST['link']);
    $target = $_POST['target_role'];

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tiêu đề thông báo']);
        exit();
    }

    $sql = "INSERT INTO announcements (title, content, link, target_role) VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($conn, $sql, array($title, $content, $link, $target));

    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu']);
    }
    pg_close($conn);
}