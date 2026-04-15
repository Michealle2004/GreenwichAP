<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$conn = connectToDatabase();
?>

<title>Register Course - Greenwich AP</title>

<div class="page-container">
    <h1>Register Course</h1>

    <?php if (isset($_GET['status'])): ?>
        <p class="message <?php echo $_GET['status'] == 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($_GET['message'] ?? ''); ?>
        </p>
    <?php endif; ?>

    <?php if ($is_admin): ?>
        <h2>Pending Enrollment Requests</h2>
        <?php
$sql_pending = 'SELECT e.enrollment_id, u.full_name, c.course_id, c.course_name, e.request_date
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.id
                        JOIN schedules s ON e.schedule_id = s.schedule_id
                        JOIN courses c ON s.course_id = c.course_id
                        WHERE e.status = $1
                        ORDER BY e.request_date ASC';
        pg_prepare($conn, "get_pending_enrollments", $sql_pending);
        $result_pending = pg_execute($conn, "get_pending_enrollments", array('pending'));
        ?>
        
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_requests)): ?>
                        <tr><td colspan="4" style="text-align:center;">No pending requests.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['course_id'] . ' - ' . $request['course_name']); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <form action="admin_approval_process.php" method="POST" style="display: inline-block;">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $request['enrollment_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-submit" style="background-color: #28a745; width:auto;">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-submit" style="background-color: #dc3545; width:auto;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p>Select a course you want to register for.</p>
        <?php
        $sql_courses = 'SELECT course_id, course_name FROM courses ORDER BY course_id';
        $result_courses = pg_query($conn, $sql_courses);
        $courses_list = pg_fetch_all($result_courses) ?: [];
        ?>
        
        <form action="student_register_process.php" method="POST" class="request-form">
            <div class="form-group">
                <label for="course_id">Select Course</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php foreach ($courses_list as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                            <?php echo htmlspecialchars($course['course_id'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Send Register Request</button>
        </form>
    <?php endif; ?>
</div>

<?php
pg_close($conn);
require_once 'includes/footer.php';
?>