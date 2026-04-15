<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION['role'] === 'teacher') {
    $teacher_id = $_SESSION['user_id'];
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);

    $conn = connectToDatabase();
    $sql = 'INSERT INTO teacher_feedback (teacher_id, subject, content) VALUES ($1, $2, $3)';
    pg_prepare($conn, "ins_t_fb", $sql);
    $res = pg_execute($conn, "ins_t_fb", array($teacher_id, $subject, $content));

    header("Location: teacher_feedback.php?status=" . ($res ? "success" : "error"));
    pg_close($conn);
}