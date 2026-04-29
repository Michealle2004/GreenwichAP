
<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
$conn = connectToDatabase();

$is_student_staff = isset($_SESSION['user_id']);
$is_parent = isset($_SESSION['parent_id']);


if (!$is_student_staff && !$is_parent) {
    header("Location: login.php");
    exit();
}


if ($is_parent) {
    $user_code = $_SESSION['student_user_code'] ?? 'N/A';
    $full_name = ($_SESSION['student_full_name'] ?? 'Student') . ' (Parent)';
    $role = 'parent';
    $gre_wallet_balance = 0; 
    $user_id_for_query = $_SESSION['student_user_id'] ?? null;
} else {
    $user_code = $_SESSION['user_code'] ?? '';
    $full_name = $_SESSION['full_name'] ?? '';
    $role = $_SESSION['role'] ?? '';
    $user_id_for_query = $_SESSION['id'] ?? $_SESSION['user_id']; 
    
    $gre_wallet_balance = 0;

    if ($user_id_for_query) {
        $res_bal = @pg_query_params($conn, "SELECT wallet_balance FROM users WHERE id = $1", [$user_id_for_query]);
        if ($res_bal && pg_num_rows($res_bal) > 0) {
            $gre_wallet_balance = pg_fetch_result($res_bal, 0, 0);
        }
    }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/GreenwichAP/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="logo-container">
                <a href="/GreenwichAP/index.php">
                    <img src="https://greenwich.edu.vn/wp-content/uploads/2024/06/2022-Greenwich-Eng.webp" alt="Greenwich Vietnam Logo">
                </a>
            </div>
            <nav class="main-nav">
                <ul>

                    <?php if ($is_parent):  ?>
                        <li><a href="/GreenwichAP/parent_dashboard.php">Dashboard</a></li>


                    <?php elseif ($role === 'teacher'): ?>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Register/Feedback</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/teacher_feedback.php">Teacher Feedback</a>
                                <a href="/GreenwichAP/teacher_feedback_history.php">Teacher Feedback History</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Information Access</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/view_schedule.php">View Schedule</a>
                                <a href="/GreenwichAP/weekly_timetable.php">Weekly timetable</a>
                                <a href="/GreenwichAP/exam_schedule.php">Exam Deadline Schedule</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Reports</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/attendance_report.php">Attendance Report</a>
                                <a href="/GreenwichAP/mark_report.php">Mark Report</a>
                                <a href="/GreenwichAP/curriculum.php">Curriculum</a>
                            </div>
                        </li>
                        <li><a href="/GreenwichAP/regulations.php">Regulations</a></li>



                    <?php elseif ($role === 'admin'):  ?>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Register/Feedback</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/admin/view_teacher_feedback.php">Teacher Feedback</a>
                                <a href="/GreenwichAP/submission_history.php">Submission & Pay History</a>
                                <a href="/GreenwichAP/feedback_quality.php">Feedback quality</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Information Access</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/view_schedule.php">View Schedule</a>
                                <a href="/GreenwichAP/weekly_timetable.php">Weekly timetable</a>
                                <a href="/GreenwichAP/exam_schedule.php">Exam Deadline Schedule</a>
                                <a href="/GreenwichAP/course_fee.php">Course Fee</a>
                                <a href="/GreenwichAP/major_create.php">Major Create</a>
                                <a href="/GreenwichAP/account_manager.php">Account Manager</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Reports</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/attendance_report.php">Attendance Report</a>
                                <a href="/GreenwichAP/mark_report.php">Mark Report</a>
                                <a href="/GreenwichAP/curriculum.php">Curriculum</a>
                                <a href="/GreenwichAP/list_retake_class.php">List retake class & fee</a>
                            </div>
                        </li>
                        <li><a href="/GreenwichAP/regulations.php">Regulations</a></li>




                    <?php else:  ?>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Register/Feedback</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/paper_request.php">Paper Request</a>
                                <a href="/GreenwichAP/submission_history.php">Submission & Pay History</a>
                                <a href="/GreenwichAP/feedback_quality.php">Feedback quality</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Information Access</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/view_schedule.php">View Schedule</a>
                                <a href="/GreenwichAP/weekly_timetable.php">Weekly timetable</a>
                                <a href="/GreenwichAP/exam_schedule.php">Exam Deadline Schedule</a>
                                <a href="/GreenwichAP/course_fee.php">Course Fee</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropbtn">Reports</a>
                            <div class="dropdown-content">
                                <a href="/GreenwichAP/attendance_report.php">Attendance Report</a>
                                <a href="/GreenwichAP/mark_report.php">Mark Report</a>
                                <a href="/GreenwichAP/curriculum.php">Curriculum</a>
                                <a href="/GreenwichAP/list_retake_class.php">List retake class & fee</a>
                            </div>
                        </li>
                        <li><a href="/GreenwichAP/regulations.php">Regulations</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            

            <div class="user-info">
                 <div class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn user-name">
                        <?php echo htmlspecialchars($full_name); ?>
                    </a>
                    <div class="dropdown-content user-dropdown">
                        <span class="user-code-display"><?php echo htmlspecialchars($user_code); ?></span>
                        <hr>
                        

                        <?php if (!$is_parent && $role !== 'teacher'): ?>
                            <a href="/GreenwichAP/grewallet.php" class="grewallet-link">
                                <strong>GreWallet</strong>
                                <span class="wallet-amt"><?php echo number_format($gre_wallet_balance, 0, ',', '.'); ?> VNĐ</span>
                            </a>
                            <hr>
                        <?php endif; ?>

                        <?php if (!$is_parent): ?>
                            <a href="/GreenwichAP/change_password.php">Change Password</a>
                        <?php endif; ?>
                        
                        <a href="/GreenwichAP/logout.php" class="logout-link">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">