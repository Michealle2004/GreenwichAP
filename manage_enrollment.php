<?php
require_once 'admin_check.php'; 
require_once 'includes/db_connect.php';

$student_details = null;
$enrollments = [];
$search_id = '';

if (isset($_GET['student_user_id'])) {
    $search_id = trim($_GET['student_user_id']);
    if (!empty($search_id)) {
        $conn = connectToDatabase();

        $sql_student = 'SELECT id, user_id, full_name FROM users WHERE user_id = $1 AND role = \'student\'';
        pg_prepare($conn, "find_student", $sql_student);
        $result_student = pg_execute($conn, "find_student", array($search_id));
        $student_details = pg_fetch_assoc($result_student);

        if ($student_details) {
            $sql_enrollments = 'SELECT e.enrollment_id, c.course_id, c.course_name, s.day_of_week, s.start_time
                                FROM enrollments e
                                JOIN schedules s ON e.schedule_id = s.schedule_id
                                JOIN courses c ON s.course_id = c.course_id
                                WHERE e.student_id = $1';
            pg_prepare($conn, "get_enrollments", $sql_enrollments);
            $result_enrollments = pg_execute($conn, "get_enrollments", array($student_details['id']));
            $enrollments = pg_fetch_all($result_enrollments);
        }
        pg_close($conn);
    }
}
require_once 'includes/header.php';
?>

<title>Manage Student Enrollment - Admin</title>

<div class="page-container">
    <h1>Manage Student Enrollment</h1>

    <form method="GET" action="manage_enrollment.php" class="request-form" style="margin-bottom: 30px;">
        <div class="form-group">
            <label for="student_user_id">Enter Student ID to find</label>
            <input type="text" name="student_user_id" id="student_user_id" value="<?php echo htmlspecialchars($search_id); ?>" required>
        </div>
        <button type="submit" class="btn-submit">Search Student</button>
    </form>

    <?php if (isset($_GET['student_user_id'])): ?>
        <hr>
        <?php if ($student_details): ?>
            <div class="history-section">
                <h2 class="section-header">Enrollments for <?php echo htmlspecialchars($student_details['full_name']); ?> (<?php echo htmlspecialchars($student_details['user_id']); ?>)</h2>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Course ID</th>
                                <th>Course Name</th>
                                <th>Schedule</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrollments)): ?>
                                <tr><td colspan="4">This student is not enrolled in any classes.</td></tr>
                            <?php else: ?>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['course_id']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['day_of_week']) . ' @ ' . date('H:i', strtotime($enrollment['start_time'])); ?></td>
                                        <td>
                                            <button disabled>Unenroll</button>
                                            <button disabled>Transfer</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <p class="message error">Student with ID "<?php echo htmlspecialchars($search_id); ?>" not found.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>