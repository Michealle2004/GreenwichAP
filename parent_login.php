<?php
session_start();
if (isset($_SESSION['parent_id'])) {
    header("Location: parent_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Login - Greenwich AP</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">  <div class="login-container">
        <form action="parent_login_process.php" method="POST" class="login-form">
            <img src="https://ap.greenwich.edu.vn/Logo15.png" alt="Greenwich Logo" class="logo">
            <h2>Parent Login Portal</h2>

            <?php if (isset($_GET['error'])): ?>
                <p 
class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></p>
            <?php endif;
?>

            <div class="form-group">
                <label for="campus">Campus</label>
                <select id="campus" name="campus" required>
                    <option value="">-- Select student's campus --</option>
                    <option value="hcm">Ho Chi Minh</option>
   
                  <option value="hanoi">Ha Noi</option>
                    <option value="cantho">Can Tho</option>
                </select>
            </div>
            <div class="form-group">
                <label for="student_user_id">Student 
ID</label>
                <input type="text" id="student_user_id" name="student_user_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
      
          <button type="submit" class="btn-login">Login</button>
            <div class="switch-login">
                <a href="login.php">Login for Student/Staff</a>
            </div>
        </form>
    </div>
</body>
</html>