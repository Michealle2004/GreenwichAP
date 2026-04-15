<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connectToDatabase();
    
    $student_db_id = $_POST['student_id'] ?? null; // ID số của sinh viên
    $course_id     = $_POST['course_id'] ?? null;
    $teacher_id    = $_POST['teacher_id'] ?? null;
    $day_of_week   = $_POST['day_of_week'] ?? null;
    $room          = $_POST['room'] ?? null;
    $term          = $_POST['term'] ?? null;
    $year          = $_POST['year'] ?? null;
    $slots         = $_POST['slots'] ?? [];

    if (!$student_db_id || !$course_id || empty($slots)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required information. Please select student, course and slots.']);
        exit;
    }

    try {
        pg_query($conn, "BEGIN");

        $sql_user = "SELECT u.user_id, u.major_id, u.campus_id, m.major_name 
                     FROM users u 
                     JOIN majors m ON u.major_id = m.major_id 
                     WHERE u.id = $1";
        $res_user = pg_query_params($conn, $sql_user, [$student_db_id]);
        $u_info = pg_fetch_assoc($res_user);

        if (!$u_info) {
            throw new Exception("Student not found in database.");
        }

        $student_code  = $u_info['user_id'];
        $student_major = $u_info['major_id'];
        $campus_id     = $u_info['campus_id'];
        $major_name    = $u_info['major_name'];

        $sql_check_cur = "SELECT 1 FROM curriculum WHERE major_id = $1 AND course_id = $2";
        $res_cur = pg_query_params($conn, $sql_check_cur, [$student_major, $course_id]);

        if (pg_num_rows($res_cur) == 0) {
            throw new Exception("Major Conflict: Student $student_code ($major_name) cannot enroll in course $course_id because it is not in their curriculum.");
        }

        foreach ($slots as $slot_name) {
            $time_map = [
                'Slot 1' => ['08:00:00', '09:30:00'], 
                'Slot 2' => ['09:30:00', '11:00:00'],
                'Slot 3' => ['12:00:00', '13:30:00'], 
                'Slot 4' => ['13:30:00', '15:00:00'],
                'Slot 5' => ['15:30:00', '17:00:00'], 
                'Slot 6' => ['17:00:00', '18:30:00']
            ];
            
            if (!isset($time_map[$slot_name])) continue;

            $start_time = $time_map[$slot_name][0];
            $end_time   = $time_map[$slot_name][1];

            $sql_conflict = "SELECT s.schedule_id FROM schedules s
                             WHERE s.day_of_week = $1 AND s.start_time = $2 AND s.campus_id = $3
                             AND (s.teacher_id = $4 OR s.schedule_id IN (SELECT schedule_id FROM enrollments WHERE student_id = $5))";
            
            $res_conflict = pg_query_params($conn, $sql_conflict, [$day_of_week, $start_time, $campus_id, $teacher_id, $student_db_id]);
            
            $existing_schedule_id = null;
            if ($conflict = pg_fetch_assoc($res_conflict)) {
                $sql_exact = "SELECT schedule_id FROM schedules 
                              WHERE course_id = $1 AND teacher_id = $2 AND day_of_week = $3 
                              AND start_time = $4 AND room = $5 AND campus_id = $6";
                $res_exact = pg_query_params($conn, $sql_exact, [$course_id, $teacher_id, $day_of_week, $start_time, $room, $campus_id]);
                
                if ($exact = pg_fetch_assoc($res_exact)) {
                    $existing_schedule_id = $exact['schedule_id'];
                } else {
                    throw new Exception("Conflict at $slot_name: Teacher or Student is already busy with another class at this time.");
                }
            }

            if (!$existing_schedule_id) {
                $sql_sched = "INSERT INTO schedules (course_id, teacher_id, day_of_week, start_time, end_time, room, term, year, campus_id) 
                              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING schedule_id";
                
                $res_sched = pg_query_params($conn, $sql_sched, [
                    $course_id, $teacher_id, $day_of_week, $start_time, $end_time, $room, $term, $year, $campus_id
                ]);

                if ($res_sched) {
                    $existing_schedule_id = pg_fetch_result($res_sched, 0, 0);
                } else {
                    throw new Exception("Failed to create new schedule: " . pg_last_error($conn));
                }
            }

            $sql_enroll = "INSERT INTO enrollments (student_id, schedule_id, status, request_date) 
                           VALUES ($1, $2, 'approved', NOW()) 
                           ON CONFLICT DO NOTHING"; 
            pg_query_params($conn, $sql_enroll, [$student_db_id, $existing_schedule_id]);
        }

        pg_query($conn, "COMMIT");
        echo json_encode(['status' => 'success', 'message' => 'Enrollment completed successfully!', 'student_id' => $student_db_id]);

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    pg_close($conn);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}