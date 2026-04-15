<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id']; 
$paper_type = $_POST['paper_type'] ?? '';
$reason = $_POST['reason'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 1);
$file_path = '';

if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] == 0) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_ext = strtolower(pathinfo($_FILES["attachment_file"]["name"], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];

    if (!in_array($file_ext, $allowed)) {
        header("Location: paper_request.php?status=error&message=InvalidFileType");
        exit();
    }

    $file_path = $target_dir . $student_id . '_' . time() . '.' . $file_ext;
    move_uploaded_file($_FILES["attachment_file"]["tmp_name"], $file_path);
}

if (!empty($file_path)) {
    $conn = connectToDatabase();
    $sql = 'INSERT INTO paper_requests (student_id, paper_type, reason, quantity, file_path, status) VALUES ($1, $2, $3, $4, $5, $6)';
    pg_prepare($conn, "ins", $sql);
    $res = pg_execute($conn, "ins", array($student_id, $paper_type, $reason, $quantity, $file_path, 'Pending'));
    
    header("Location: paper_request.php?status=" . ($res ? "success" : "error&message=DBError"));
    pg_close($conn);
}
?>