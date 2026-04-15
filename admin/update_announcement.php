<?php
session_start();
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if ($_SESSION['role'] === 'admin') {
    $conn = connectToDatabase();
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $link = $_POST['link'];
    $target = $_POST['target_role'];

    $sql = "UPDATE announcements SET title=$1, content=$2, link=$3, target_role=$4 WHERE id=$5";
    $result = pg_query_params($conn, $sql, [$title, $content, $link, $target, $id]);

    if ($result) echo json_encode(['status' => 'success']);
    pg_close($conn);
}