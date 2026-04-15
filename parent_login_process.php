<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $campus_code = trim($_POST['campus']);
    $student_user_id = trim($_POST['student_user_id']); 
    $password = trim($_POST['password']);

    if ($password !== '1') {
        header("Location: parent_login.php?error=Password must be '1'");
        exit();
    }

    $conn = connectToDatabase();
    
    $sql = 'SELECT p.id AS parent_id, u.id AS student_db_id, u.full_name AS student_full_name
            FROM parents p
            JOIN users u ON p.student_id = u.id
            JOIN campuses c ON u.campus_id = c.campus_id
            WHERE u.user_id = $1 AND c.campus_code = $2 AND u.role = \'student\''; 

    pg_prepare($conn, "p_login", $sql);
    $result = pg_execute($conn, "p_login", array($student_user_id, $campus_code));

    if (pg_num_rows($result) === 1) {
        $data = pg_fetch_assoc($result);
        
        $_SESSION['parent_id'] = $data['parent_id'];
        $_SESSION['student_user_id'] = $data['student_db_id']; 
        $_SESSION['student_full_name'] = $data['student_full_name'];
        $_SESSION['student_user_code'] = $student_user_id;

        header("Location: parent_dashboard.php");
        exit();
    } else {
        header("Location: parent_login.php?error=Access Denied: Check Student ID, Campus or Database Link.");
        exit();
    }
    pg_close($conn);
}