<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'] ?? null;
    $teacher_id = $_POST['teacher_id'] ?? null;
    $day_of_week = $_POST['day_of_week'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $room = $_POST['room'] ?? null;
    $term = $_POST['term'] ?? null;
    $year = $_POST['year'] ?? null;
    
    if (empty($course_id) || empty($teacher_id) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($room) || empty($term) || empty($year)) {
        die("Please fill in all required fields.");
    }
    
    $conn = connectToDatabase();
    
    $campus_id = 'hcm'; 
    if (isset($_SESSION['campus_code'])) {
        $campus_id = $_SESSION['campus_code'];
    }

    $sql_insert = 'INSERT INTO schedules (course_id, teacher_id, campus_id, day_of_week, start_time, end_time, room, term, year) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING schedule_id';
    pg_prepare($conn, "insert_schedule", $sql_insert);
    $result_insert = pg_execute($conn, "insert_schedule", array($course_id, $teacher_id, $campus_id, $day_of_week, $start_time, $end_time, $room, $term, $year));
    
    if ($result_insert) {
        $new_schedule = pg_fetch_assoc($result_insert);
        $new_schedule_id = $new_schedule['schedule_id'];
        
        header("Location: ../view_schedule.php?status=success&message=Schedule added successfully with ID: " . $new_schedule_id);
    } else {
        die("Error adding schedule: " . pg_last_error($conn));
    }
    
    pg_close($conn);
} else {
    header("Location: ../view_schedule.php");
    exit();
}
?>