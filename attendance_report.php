<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$attendance_by_course = [];
$search_id = '';
$flash_status = $_GET['status'] ?? '';
$flash_message = $_GET['message'] ?? '';
$role = $_SESSION['role'] ?? '';
$user_db_id = $_SESSION['user_id']; // ID số của user đang đăng nhập
$is_admin = ($role === 'admin');
$is_teacher = ($role === 'teacher');

$user_id_to_view = $user_db_id;

// 1. XỬ LÝ QUYỀN ADMIN: Tìm kiếm theo mã sinh viên
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

// 2. TRUY VẤN DỮ LIỆU DỰA TRÊN ROLE
if ($user_id_to_view || $is_teacher) {
    if ($is_teacher) {
        // LOGIC CHO GIÁO VIÊN: Xem điểm danh các lớp mình phụ trách
        $sql = 'SELECT 
                c.course_name, 
                a.session_date, 
                a.status, 
                a.attendance_id,
                u.full_name as student_name,
                u.user_id as student_code
            FROM attendance a
            JOIN enrollments e ON a.enrollment_id = e.enrollment_id
            JOIN schedules s ON e.schedule_id = s.schedule_id
            JOIN courses c ON s.course_id = c.course_id
            JOIN users u ON e.student_id = u.id
            WHERE s.teacher_id = (SELECT user_id FROM users WHERE id = $1)
            ORDER BY c.course_name, a.session_date DESC';
            
        pg_prepare($conn, "teacher_query", $sql);
        $result = pg_execute($conn, "teacher_query", array($user_db_id));
    } else {
        // LOGIC CHO HỌC SINH HOẶC ADMIN XEM 1 HỌC SINH
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
    }

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $attendance_by_course[$row['course_name']][] = $row;
        }
    }
}

pg_close($conn);
?>

<title>Attendance Report - Greenwich AP</title>

<div class="page-container">
    <h1>Attendance Report</h1>

    <?php if (!empty($flash_message)): ?>
        <div style="margin-bottom: 20px; padding: 10px 14px; border-radius: 6px; color: <?= $flash_status === 'success' ? '#155724' : '#721c24' ?>; background: <?= $flash_status === 'success' ? '#d4edda' : '#f8d7da' ?>; border: 1px solid <?= $flash_status === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
            <?= htmlspecialchars($flash_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($is_admin): ?>
        <h2>Admin Tools</h2>
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
            <div class="course-marks-card" style="margin-bottom: 40px; border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                <h3 style="color: #003366; border-bottom: 2px solid #003366; padding-bottom: 5px;">
                    <?php echo htmlspecialchars($course_name); ?>
                </h3>
                <table class="report-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f4f4f4;">
                            <th>Session Date</th>
                            <?php if ($is_teacher): ?>
                                <th>Student</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <?php if ($is_admin): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo date('l, d-m-Y', strtotime($record['session_date'])); ?></td>
                                
                                <?php if ($is_teacher): ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['student_code']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($record['student_name']); ?></small>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <?php if ($is_admin): ?>
                                        <form class="update-attendance-form" action="admin/update_attendance_process.php" method="POST">
                                            <input type="hidden" name="attendance_id" value="<?php echo htmlspecialchars($record['attendance_id']); ?>">
                                            <input type="hidden" name="student_user_id" value="<?php echo htmlspecialchars($search_id); ?>">
                                            <select name="status">
                                                <option value="present" <?php echo $record['status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo $record['status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                <option value="not_yet" <?php echo $record['status'] == 'not_yet' ? 'selected' : ''; ?>>Not Yet</option>
                                            </select>
                                            <button type="submit" class="btn-submit" style="padding: 3px 8px;">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <?php
                                        $status = strtolower($record['status']);
                                        $color = ($status == 'present') ? '#28a745' : (($status == 'absent') ? '#dc3545' : '#6c757d');
                                        echo '<span style="color: white; background: '.$color.'; padding: 4px 10px; border-radius: 4px; font-size: 0.85em;">' . ucfirst($record['status']) . '</span>';
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

<?php require_once 'includes/footer.php'; ?>