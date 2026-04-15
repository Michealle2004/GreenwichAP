<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$timetable = [];
$role = $_SESSION['role'] ?? 'student';
$user_id = $_SESSION['user_id'] ?? null;
$user_code = $_SESSION['user_code'] ?? null;
$today = date('Y-m-d'); 

$search_user_id = $_GET['student_user_id'] ?? ''; 
$user_id_to_view = null; 
$student_info = null;

if ($role === 'admin' && !empty($search_user_id)) {
    $res = pg_query_params($conn, "SELECT id, full_name, user_id FROM users WHERE user_id = $1 AND role = 'student'", [$search_user_id]);
    if ($user = pg_fetch_assoc($res)) {
        $user_id_to_view = $user['id'];
        $student_info = $user;
    }
}

$result = null;

if ($role === 'teacher') {
    $sql = 'SELECT s.schedule_id, s.day_of_week, 
                   to_char(s.start_time, \'HH24:MI\') as start_hm, 
                   to_char(s.end_time, \'HH24:MI\') as end_hm, 
                   s.room, c.course_id, c.course_name,
                   (SELECT COUNT(*) FROM attendance a 
                    WHERE a.enrollment_id IN (SELECT enrollment_id FROM enrollments WHERE schedule_id = s.schedule_id) 
                    AND a.session_date = $2) as is_submitted
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.teacher_id = $1';
    $result = pg_query_params($conn, $sql, [$user_code, $today]);

} elseif ($role === 'admin') {
    if (!empty($search_user_id) && $user_id_to_view) {
        $sql = 'SELECT s.schedule_id, s.day_of_week, 
                       to_char(s.start_time, \'HH24:MI\') as start_hm, 
                       to_char(s.end_time, \'HH24:MI\') as end_hm, 
                       s.room, c.course_id, c.course_name, t.full_name AS teacher_name,
                       (SELECT a.status FROM attendance a 
                        JOIN enrollments e2 ON a.enrollment_id = e2.enrollment_id 
                        WHERE e2.student_id = $1 AND e2.schedule_id = s.schedule_id AND a.session_date = $2 
                        LIMIT 1) as attendance_status
                FROM enrollments e
                JOIN schedules s ON e.schedule_id = s.schedule_id
                JOIN courses c ON s.course_id = c.course_id
                JOIN users t ON s.teacher_id = t.user_id
                WHERE e.student_id = $1 AND e.status = \'approved\'';
        $result = pg_query_params($conn, $sql, [$user_id_to_view, $today]);
    } 
} else {
    $sql = 'SELECT s.schedule_id, s.day_of_week, 
                   to_char(s.start_time, \'HH24:MI\') as start_hm, 
                   to_char(s.end_time, \'HH24:MI\') as end_hm, 
                   s.room, c.course_id, c.course_name, t.full_name AS teacher_name,
                   (SELECT a.status FROM attendance a 
                    JOIN enrollments e2 ON a.enrollment_id = e2.enrollment_id 
                    WHERE e2.student_id = $1 AND e2.schedule_id = s.schedule_id AND a.session_date = $2 
                    LIMIT 1) as attendance_status
            FROM enrollments e
            JOIN schedules s ON e.schedule_id = s.schedule_id
            JOIN courses c ON s.course_id = c.course_id
            JOIN users t ON s.teacher_id = t.user_id
            WHERE e.student_id = $1 AND e.status = \'approved\'';
    $result = pg_query_params($conn, $sql, [$user_id, $today]);
}

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $timetable[$row['day_of_week']][$row['start_hm']][] = $row;
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$slots = [
    ['n' => '1', 's' => '08:00', 'e' => '09:30'],
    ['n' => '2', 's' => '09:30', 'e' => '11:00'],
    ['n' => '3', 's' => '12:00', 'e' => '13:30'],
    ['n' => '4', 's' => '13:30', 'e' => '15:00'],
    ['n' => '5', 's' => '15:30', 'e' => '17:00'],
    ['n' => '6', 's' => '17:00', 'e' => '18:30'],
];
pg_close($conn);
?>

<style>
    :root { --primary: #1a365d; --secondary: #3182ce; }
    .search-bar { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0; }
    .search-input { padding: 10px 15px; border: 1px solid #cbd5e0; border-radius: 6px; width: 300px; font-size: 0.95em; }
    .search-btn { background: var(--secondary); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    
    .timetable-grid { width: 100%; border-collapse: collapse; background: #fff; table-layout: fixed; border: 1px solid #cbd5e0; }
    .timetable-grid th { background: #3c4b64; color: #fff; padding: 12px; font-size: 0.9em; border: 1px solid #2d3748; }
    .timetable-grid td { border: 1px solid #e2e8f0; vertical-align: top; min-height: 120px; padding: 8px; }
    .slot-num { background: #f8fafc; text-align: center; font-weight: bold; width: 60px !important; }

    .class-card { text-align: center; padding: 10px; background: #f0f9ff; border-radius: 8px; border-top: 3px solid #3182ce; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 10px; }
    .course-code { font-weight: 800; color: #2d3748; font-size: 0.95em; display: block; }
    .teacher-name { font-weight: 700; color: #4a5568; font-size: 0.85em; display: block; margin: 4px 0; }
    .att-status { font-size: 0.85em; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 4px; margin: 5px 0; }
    .status-attended { color: #38a169; }
    .status-notyet { color: #d69e2e; }
    .status-absent { color: #e53e3e; }
    .empty-state { text-align: center; padding: 50px; background: #f8fafc; border: 2px dashed #cbd5e0; border-radius: 12px; margin-top: 20px; }
</style>

<div class="page-container">
    <h1 style="color: var(--primary); margin-bottom: 20px;">Weekly Timetable</h1>

    <?php if ($role === 'admin'): ?>
        <div class="search-bar">
            <span style="font-weight: bold; color: #4a5568;">🔍 Search Student Schedule:</span>
            <form method="GET" style="display: flex; gap: 10px; flex-grow: 1;">
                <input type="text" name="student_user_id" class="search-input" 
                       placeholder="Enter Student ID (e.g., GCS220234)" 
                       value="<?= htmlspecialchars($search_user_id) ?>" required>
                <button type="submit" class="search-btn">View Schedule</button>
                <?php if (!empty($search_user_id)): ?>
                    <a href="weekly_timetable.php" style="padding: 10px; color: #e53e3e; text-decoration: none; font-weight: 600;">✕ Clear</a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($role === 'admin' && empty($search_user_id)): ?>
        <div class="empty-state">
            <h3 style="color: #4a5568;">Welcome, Admin</h3>
            <p style="color: #718096;">Please enter a <strong>Student ID</strong> above to view their specific timetable and manage attendance.</p>
        </div>
    <?php elseif ($role === 'admin' && !empty($search_user_id) && !$student_info): ?>
        <div class="empty-state" style="border-color: #feb2b2;">
            <h3 style="color: #e53e3e;">Student Not Found</h3>
            <p style="color: #718096;">We couldn't find any student with ID: <strong><?= htmlspecialchars($search_user_id) ?></strong></p>
        </div>
    <?php else: ?>
        <?php if ($student_info): ?>
            <div style="margin-bottom: 10px; font-weight: bold; color: #2d3748;">
                📅 Showing schedule for: <span style="color: #3182ce;"><?= htmlspecialchars($student_info['full_name']) ?> (<?= htmlspecialchars($student_info['user_id']) ?>)</span>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table class="timetable-grid">
                <thead>
                    <tr>
                        <th class="slot-num">Slot</th>
                        <?php foreach ($days as $day): ?>
                            <th><?= $day ?><br><span style="font-weight: normal; font-size: 0.8em; opacity: 0.8;"><?= date('d/m', strtotime($day . ' this week')) ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td class="slot-num"><?= $slot['n'] ?></td>
                            <?php foreach ($days as $day): ?>
                                <td>
                                    <?php if (isset($timetable[$day][$slot['s']])): 
                                        foreach ($timetable[$day][$slot['s']] as $class): ?>
                                            <div class="class-card">
                                                <span class="course-code"><?= htmlspecialchars($class['course_id']) ?></span>
                                                <span class="teacher-name"><?= htmlspecialchars($class['teacher_name'] ?? '') ?></span>
                                                <div style="font-size: 0.8em; color: #2b6cb0;">📹 Online | <?= htmlspecialchars($class['room']) ?></div>

                                                <?php if ($role === 'teacher'): ?>
                                                    <?php if ($class['is_submitted'] > 0): ?>
                                                        <div class="att-status status-attended">✅ Attended <br> <a href="teacher_attendance_management.php?schedule_id=<?= $class['schedule_id'] ?>" style="color:#3182ce; font-size:0.9em; font-weight:bold;">(Retake)</a></div>
                                                    <?php else: ?>
                                                        <div class="att-status status-notyet">⌛ Not Yet</div>
                                                        <a href="teacher_attendance_management.php?schedule_id=<?= $class['schedule_id'] ?>" class="btn-take-att">Take Attendance</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($class['attendance_status']): ?>
                                                        <div class="att-status <?= $class['attendance_status'] === 'present' ? 'status-attended' : 'status-absent' ?>">
                                                            <?= $class['attendance_status'] === 'present' ? '✅ Attended' : '❌ Absent' ?>
                                                        </div>
                                                        <?php if ($role === 'admin'): ?>
                                                             <a href="teacher_attendance_management.php?schedule_id=<?= $class['schedule_id'] ?>" style="font-size: 0.75em; color: #3182ce;">Edit Attendance</a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="att-status status-notyet">⌛ Not Yet</div>
                                                        <?php if ($role === 'admin'): ?>
                                                             <a href="teacher_attendance_management.php?schedule_id=<?= $class['schedule_id'] ?>" style="font-size: 0.75em; color: #3182ce;">Mark Attendance</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div style="font-weight: bold; font-size: 0.75em; margin-top: 5px; color: #2d3748;">Time: <?= $slot['s'] ?> - <?= $slot['e'] ?></div>
                                            </div>
                                        <?php endforeach; 
                                    endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>