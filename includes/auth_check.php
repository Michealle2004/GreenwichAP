<!-- Check Session đã tạo thành công hay chưa -->
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check user_id trong session để xác định xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit(); 
}
?>