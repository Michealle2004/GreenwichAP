<?php
session_start();
require_once '../includes/db_connect.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['teacher', 'admin'], true)) {
    header("Location: ../weekly_timetable.php?status=error&message=Unauthorized access.");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $session_date = $_POST['session_date'] ?? null;
    $attendance_data = $_POST['attendance'] ?? []; 

    if (!$schedule_id || !$session_date || empty($attendance_data)) {
        header("Location: ../weekly_timetable.php?status=error&message=Missing required data.");
        exit();
    }

    $conn = connectToDatabase();
    
    pg_query($conn, "BEGIN");

    try {
        $sql_delete = 'DELETE FROM attendance 
                       WHERE session_date = $1 
                       AND enrollment_id IN (SELECT enrollment_id FROM enrollments WHERE schedule_id = $2)';
        
        $res_delete = pg_query_params($conn, $sql_delete, [$session_date, $schedule_id]);
        
        if (!$res_delete) {
            throw new Exception("Failed to clear old attendance records.");
        }

        foreach ($attendance_data as $enrollment_id => $status) {
            $sql_insert = 'INSERT INTO attendance (enrollment_id, session_date, status) VALUES ($1, $2, $3)';
            $res_insert = pg_query_params($conn, $sql_insert, [$enrollment_id, $session_date, $status]);
            
            if (!$res_insert) {
                throw new Exception("Error saving new attendance data.");
            }
        }

        pg_query($conn, "COMMIT");
        header("Location: ../weekly_timetable.php?status=success&message=Attendance updated successfully for " . $session_date);

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        header("Location: ../weekly_timetable.php?status=error&message=" . urlencode($e->getMessage()));
    }

    pg_close($conn);
} else {
    header("Location: ../weekly_timetable.php");
}
exit;