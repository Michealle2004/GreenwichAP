<?php
session_start();
require_once 'includes/auth_check.php'; 
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;
    
    if (empty($student_id) || empty($course_id)) {
        header("Location: register.php?status=error&message=Please select a course.");
        exit();
    }
    
    $conn = connectToDatabase();
    
    $sql_check = 'SELECT e.enrollment_id FROM enrollments e
                  JOIN schedules s ON e.schedule_id = s.schedule_id
                  WHERE e.student_id = $1 AND s.course_id = $2 AND (e.status = $3 OR e.status = $4)';
    pg_prepare($conn, "check_enrollment", $sql_check);
    $result_check = pg_execute($conn, "check_enrollment", array($student_id, $course_id, 'approved', 'pending'));
    
    if (pg_num_rows($result_check) > 0) {
        pg_close($conn);
        header("Location: register.php?status=error&message=You are already enrolled or have a pending request for this course.");
        exit();
    }
    
    $sql_schedule = 'SELECT schedule_id FROM schedules WHERE course_id = $1 LIMIT 1';
    pg_prepare($conn, "get_schedule", $sql_schedule);
    $result_schedule = pg_execute($conn, "get_schedule", array($course_id));
    $schedule = pg_fetch_assoc($result_schedule);
    
    if (!$schedule) {
        pg_close($conn);
        header("Location: register.php?status=error&message=No available schedule for this course.");
        exit();
    }
    
    $schedule_id = $schedule['schedule_id'];

    $sql_insert = 'INSERT INTO enrollments (student_id, schedule_id, status) VALUES ($1, $2, $3)';
    pg_prepare($conn, "insert_enrollment_request", $sql_insert);
    $result_insert = pg_execute($conn, "insert_enrollment_request", array($student_id, $schedule_id, 'pending'));
    
    if ($result_insert) {
        header("Location: register.php?status=success&message=Your request has been sent successfully. Please wait for admin approval.");
    } else {
        $error_message = urlencode("Database error: " . pg_last_error($conn));
        header("Location: register.php?status=error&message=" . $error_message);
    }
    
    pg_close($conn);
} else {
    header("Location: register.php");
    exit();
}
?>