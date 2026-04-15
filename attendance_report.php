<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$attendance_by_course = [];
$search_id = '';
$user_id_to_view = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($is_admin) {
    if (isset($_GET['student_user_id']) && !empty($_GET['student_user_id'])) {
        $search_id = trim($_GET['student_user_id']);
        $sql_get_student_id = 'SELECT id FROM users WHERE user_id = $1 AND role = \'student\'';
        pg_prepare($conn, "get_student_id", $sql_get_student_id);
        $result = pg_execute($conn, "get_student_id", array($search_id));
        if ($student_found = pg_fetch_assoc($result)) {
            $user_id_to_view = $student_found['id'];
        } else {
            $user_id_to_view = null;
        }
    }
}

if ($user_id_to_view) {
    $sql = 'SELECT
            c.course_name,
            a.session_date,
            a.status,
            a.attendance_id
        FROM attendance a
        JOIN enrollments e ON a.enrollment_id = e.enrollment_id
        JOIN schedules s ON e.schedule_id = s.schedule_id
        JOIN courses c ON s.course_id = c.course_id
        WHERE e.student_id = $1
        ORDER BY c.course_name, a.session_date DESC';

    pg_prepare($conn, "attendance_query", $sql);
    $result = pg_execute($conn, "attendance_query", array($user_id_to_view));

    while ($row = pg_fetch_assoc($result)) {
        $attendance_by_course[$row['course_name']][] = $row;
    }
}

pg_close($conn);
?>

<title>Attendance Report - Greenwich AP</title>

<div class="page-container">
    <h1>Attendance Report</h1>
    
    <?php if ($is_admin): ?>
        <h2>Admin Tools</h2>
        <div id="status-message" class="message" style="display: none;"></div>
        <form method="GET" action="attendance_report.php" class="request-form" style="margin-bottom: 30px;">
            <div class="form-group">
                <label for="student_user_id">Enter Student ID to view attendance</label>
                <input type="text" name="student_user_id" id="student_user_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="Leave blank to view your own report">
            </div>
            <button type="submit" class="btn-submit">View Report</button>
        </form>
        <hr>
    <?php endif; ?>

    <?php if (empty($attendance_by_course)): ?>
        <p>No attendance records found.</p>
    <?php else: ?>
        <?php foreach ($attendance_by_course as $course_name => $records): ?>
            <div class="course-marks-card">
                <h3><?php echo htmlspecialchars($course_name); ?></h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Session Date</th>
                            <th>Status</th>
                            <?php if ($is_admin): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr id="attendance-row-<?php echo htmlspecialchars($record['attendance_id']); ?>">
                                <td><?php echo date('l, F j, Y', strtotime($record['session_date'])); ?></td>
                                <td>
                                    <?php if ($is_admin): ?>
                                        <form class="update-attendance-form" action="admin/update_attendance_process.php" method="POST">
                                            <input type="hidden" name="attendance_id" value="<?php echo htmlspecialchars($record['attendance_id']); ?>">
                                            <select name="status">
                                                <option value="present" <?php echo $record['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo $record['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                <option value="not_yet" <?php echo $record['status'] == 'not_yet' ? 'selected' : ''; ?>>Not Yet</option>
                                            </select>
                                            <button type="submit" class="btn-submit" style="padding: 5px 10px; font-size: 0.9em;">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <?php
                                        $status_class = strtolower($record['status']);
                                        echo '<span class="status-badge status-' . $status_class . '">' . htmlspecialchars($record['status']) . '</span>';
                                        ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    const updateAttendanceForms = document.querySelectorAll('.update-attendance-form');

    function showMessage(message, type = 'success') {
        statusMessage.textContent = message;
        statusMessage.className = 'message ' + type;
        statusMessage.style.display = 'block';
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 5000);
    }
    
    updateAttendanceForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const row = this.closest('tr');
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('Attendance updated successfully!');
                    const newStatus = formData.get('status');
                    const statusCell = row.querySelector('td:nth-child(2)');
                    const statusBadge = statusCell.querySelector('.status-badge');
                    
                    if (statusBadge) {
                        statusBadge.textContent = newStatus;
                        statusBadge.className = 'status-badge status-' + newStatus.toLowerCase();
                    }
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            });
        });
    });

});
</script>