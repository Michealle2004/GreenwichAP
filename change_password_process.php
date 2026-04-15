<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_new_password = $_POST['confirm_new_password'];
$user_id = $_SESSION['user_id'];

if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
    header("Location: /GreenwichAP/change_password.php?status=error&message=Please fill in all fields.");
    exit();
}

if ($new_password !== $confirm_new_password) {
    header("Location: /GreenwichAP/change_password.php?status=error&message=New passwords do not match.");
    exit();
}

$conn = connectToDatabase();
$sql_get_pass = 'SELECT password FROM users WHERE id = $1';
pg_prepare($conn, "get_current_password", $sql_get_pass);
$result = pg_execute($conn, "get_current_password", array($user_id));

if ($user = pg_fetch_assoc($result)) {
    $hashed_password_from_db = $user['password'];

    if (password_verify($current_password, $hashed_password_from_db)) {

        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_update_pass = 'UPDATE users SET password = $1 WHERE id = $2';
        pg_prepare($conn, "update_password", $sql_update_pass);
        $update_result = pg_execute($conn, "update_password", array($new_hashed_password, $user_id));

        if ($update_result) {
            header("Location: /GreenwichAP/change_password.php?status=success");
        } else {
            header("Location: /GreenwichAP/change_password.php?status=error&message=Failed to update password. Please try again.");
        }
    } else {
        header("Location: /GreenwichAP/change_password.php?status=error&message=Incorrect current password.");
    }
} else {
    header("Location: /GreenwichAP/change_password.php?status=error&message=User not found.");
}

pg_close($conn);
exit();
?>