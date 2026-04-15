<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php"); 
    exit();
}

$teacher_user_id = $_SESSION['user_code']; 
$conn = connectToDatabase();
$classes = [];

$sql = 'SELECT
            s.schedule_id, 
            s.term,
            s.year,
            c.course_id,
            c.course_name,
            s.day_of_week,
            s.start_time,
            s.end_time,
            s.room,
            (SELECT COUNT(e.enrollment_id) FROM enrollments e WHERE e.schedule_id = s.schedule_id) AS student_count
        FROM schedules s
        JOIN courses c ON s.course_id = c.course_id
        WHERE s.teacher_id = $1
        ORDER BY s.year DESC, s.term, s.day_of_week, s.start_time';

pg_prepare($conn, "teacher_classes_query", $sql);
$result = pg_execute($conn, "teacher_classes_query", array($teacher_user_id));

while ($row = pg_fetch_assoc($result)) {
    $classes[] = $row;
}

pg_close($conn);
?>

<title>Teacher Dashboard - Greenwich AP</title>

<div class="page-container">
    <h1>Teacher Dashboard</h1>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
    <p>Below are the classes currently assigned to you. Click on the actions to manage marks and attendance.</p>
    
    <hr>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Schedule ID</th>
                    <th>Course</th>
                    <th>Term/Year</th>
                    <th>Schedule</th>
                    <th>Room</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="7">You are not currently assigned to any classes.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['schedule_id']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_id']) . ' - ' . htmlspecialchars($class['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['term']) . ' ' . htmlspecialchars($class['year']); ?></td>
                            <td><?php echo htmlspecialchars($class['day_of_week']) . ' (' . date('H:i', strtotime($class['start_time'])) . ' - ' . date('H:i', strtotime($class['end_time'])) . ')'; ?></td>
                            <td><?php echo htmlspecialchars($class['room']); ?></td>
                            <td><?php echo htmlspecialchars($class['student_count']); ?></td>
                            <td>
                                <a href="teacher_mark_management.php?schedule_id=<?php echo $class['schedule_id']; ?>" class="btn-submit" style="padding: 5px 10px; font-size: 0.9em; background-color: #17a2b8;">Marks</a>
                                <a href="teacher_attendance_management.php?schedule_id=<?php echo $class['schedule_id']; ?>" class="btn-submit" style="padding: 5px 10px; font-size: 0.9em; background-color: #ffc107; color: #333;">Attendance</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>