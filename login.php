<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Greenwich AP</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <form action="login_process.php" method="POST" class="login-form">
            <img src="https://greenwich.edu.vn/wp-content/uploads/2024/06/2022-Greenwich-Eng.webp" alt="Greenwich Logo" class="logo">
            <h2>Greenwich Academic Portal</h2>
            
            <?php 
                if (isset($_GET['error'])) {
                    echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
                }
            ?>

            <div class="form-group">
                <label for="campus">Campus</label>
                <select id="campus" name="campus" required>
                    <option value="">-- Select campus --</option>
                    <option value="hcm">Ho Chi Minh</option>
                    <option value="hanoi">Ha Noi</option>
                    <option value="cantho">Can Tho</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
            <div class="switch-login">
                <a href="parent_login.php">Login for Parent</a>
            </div>
        </form>
    </div>
</body>
</html>