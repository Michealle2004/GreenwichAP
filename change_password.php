<?php
require_once 'includes/header.php';
?>

<title>Change Password - Greenwich AP</title>

<div class="page-container">
    <h1>Change Password</h1>

    <?php
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'success') {
            echo '<p class="message success">Password changed successfully!</p>';
        } elseif ($_GET['status'] == 'error') {
            $error_message = htmlspecialchars($_GET['message']);
            echo '<p class="message error">' . $error_message . '</p>';
        }
    }
    ?>

    <form action="change_password_process.php" method="POST" class="request-form">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" name="confirm_new_password" id="confirm_new_password" required>
        </div>
        <button type="submit" class="btn-submit">Update Password</button>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>