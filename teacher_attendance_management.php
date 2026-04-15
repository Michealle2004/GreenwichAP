<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$schedule_id = $_GET['schedule_id'] ?? null;
$students = [];
$class_info = null;

if (!$schedule_id) {
    die("Invalid Access: Schedule ID is missing.");
}

$sql_class = 'SELECT c.course_name, s.day_of_week, s.term, s.year 
              FROM schedules s 
              JOIN courses c ON s.course_id = c.course_id 
              WHERE s.schedule_id = $1';
$res_class = pg_query_params($conn, $sql_class, [$schedule_id]);
$class_info = pg_fetch_assoc($res_class);

$sql_students = 'SELECT e.enrollment_id, u.user_id, u.full_name 
                 FROM enrollments e
                 JOIN users u ON e.student_id = u.id
                 WHERE e.schedule_id = $1 AND e.status = \'approved\'
                 ORDER BY u.user_id ASC';
$res_students = pg_query_params($conn, $sql_students, [$schedule_id]);
$students = pg_fetch_all($res_students) ?: [];

pg_close($conn);
?>

<style>
    .attendance-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
    .attendance-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .attendance-table th { background: #f8fafc; padding: 15px; text-align: left; border-bottom: 2px solid #edf2f7; color: #4a5568; }
    .attendance-table td { padding: 15px; border-bottom: 1px solid #edf2f7; }
    .radio-group { display: flex; gap: 20px; }
    .radio-item { display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; }
    .radio-item input { width: 18px; height: 18px; cursor: pointer; }
    .present-label { color: #38a169; }
    .absent-label { color: #e53e3e; }
    .btn-save { background: #3182ce; color: white; border: none; padding: 15px 40px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1.1em; transition: 0.3s; margin-top: 30px; display: block; width: 100%; box-shadow: 0 4px 6px rgba(49, 130, 206, 0.2); }
    .btn-save:hover { background: #2b6cb0; transform: translateY(-2px); }
</style>

<div class="page-container">
    <div style="margin-bottom: 20px;">
        <a href="weekly_timetable.php" style="text-decoration: none; color: #3182ce; font-weight: 600;">← Back to Weekly Timetable</a>
    </div>

    <div class="attendance-card">
        <h1 style="margin-bottom: 10px; color: #2d3748;">Take Attendance</h1>
        <p style="color: #718096; font-size: 1.1em; margin-bottom: 25px;">
            <span style="background: #edf2f7; padding: 5px 12px; border-radius: 20px; color: #2d3748; font-weight: bold;">Class: <?= htmlspecialchars($class_info['course_name']) ?></span>
            <span style="margin-left: 10px;">Schedule: <?= htmlspecialchars($class_info['day_of_week']) ?> (<?= htmlspecialchars($class_info['term']) ?>)</span>
        </p>

        <form id="attendance-form" action="admin/submit_attendance_process.php" method="POST">
            <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
            
            <div style="margin-bottom: 30px; background: #fffaf0; padding: 20px; border-radius: 10px; border: 1px solid #feebc8;">
                <label style="font-weight: bold; color: #c05621; display: block; margin-bottom: 8px;">Select Session Date:</label>
                <input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required 
                       style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e0; width: 250px; font-size: 1em;">
            </div>

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: #a0aec0; font-style: italic;">No students enrolled in this class.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td style="font-weight: 700; color: #4a5568;"><?= htmlspecialchars($s['user_id']) ?></td>
                                <td style="color: #2d3748;"><?= htmlspecialchars($s['full_name']) ?></td>
                                <td>
                                    <div class="radio-group" style="justify-content: center;">
                                        <label class="radio-item present-label">
                                            <input type="radio" name="attendance[<?= $s['enrollment_id'] ?>]" value="present" checked> Present
                                        </label>
                                        <label class="radio-item absent-label">
                                            <input type="radio" name="attendance[<?= $s['enrollment_id'] ?>]" value="absent"> Absent
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students)): ?>
                <button type="submit" class="btn-save">Submit Attendance Report</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.getElementById('attendance-form').onsubmit = function(e) {
    return confirm('Do you want to submit the attendance for all students on this date?');
};
</script>

<?php require_once 'includes/footer.php'; ?>