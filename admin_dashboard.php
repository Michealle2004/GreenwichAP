<?php
require_once 'includes/header.php';
require_once 'admin_check.php';
?>

<title>Admin Dashboard - Greenwich AP</title>

<div class="page-container">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the administration panel. Use the links below to manage the academic portal.</p>

    <div class="admin-menu">
        <a href="#" class="admin-menu-item">
            <h3>Manage Students</h3>
            <p>Add, edit, or delete student accounts.</p>
        </a>
        <a href="#" class="admin-menu-item">
            <h3>Manage Courses</h3>
            <p>Manage courses, subjects, and curricula.</p>
        </a>
        <a href="#" class="admin-menu-item">
            <h3>Manage Timetable</h3>
            <p>View and edit the weekly class schedules.</p>
        </a>
        <a href="#" class="admin-menu-item">
            <h3>Manage Exam Deadlines</h3>
            <p>Add and modify exam and assignment deadlines.</p>
        </a>
        <a href="admin/view_feedback.php" class="admin-menu-item">
            <h3>View Feedback</h3>
            <p>Review and respond to student feedback.</p>
        </a>
        <a href="#" class="admin-menu-item">
            <h3>View Reports</h3>
            <p>Access various system reports and statistics.</p>
        </a>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>