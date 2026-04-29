<?php
session_start();
require_once 'includes/db_connect.php';

// Xử lý form đăng nhập khi người dùng submit

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $campus_code = trim($_POST['campus']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Kiểm tra nếu có trường nào bị bỏ trống

    if (empty($campus_code) || empty($username) || empty($password)) {
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }

    // Kết nối database và kiểm tra thông tin đăng nhập
    
    $conn = connectToDatabase();

    $sql = 'SELECT u.id, u.user_id, u.full_name, u.password, u.role 
            FROM users u
            JOIN campuses c ON u.campus_id = c.campus_id
            WHERE u.user_id = $1 AND c.campus_code = $2';
    
    pg_prepare($conn, "login_query", $sql);
    $result = pg_execute($conn, "login_query", array($username, $campus_code));

    // Kiểm tra nếu tìm thấy người dùng và mật khẩu khớp

    if (pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['user_code'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['campus_code'] = $campus_code;
            header("Location: index.php");
            exit();

        // Nếu mật khẩu không khớp, chuyển hướng về login với thông báo lỗi

        } else {
            header("Location: login.php?error=Invalid username or password");
            exit();
        }
        // Nếu không tìm thấy người dùng nào khớp với username và campus, chuyển hướng về login với thông báo lỗi
        
    } else {
        header("Location: login.php?error=User not found in this campus");
        exit();
    }
    
    pg_close($conn);

} else {
    header("Location: login.php");
    exit();
}
?>