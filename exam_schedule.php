<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$deadlines = [];
$search_query = '';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$user_id = $_SESSION['user_id']; 


if ($is_admin) {
    if (isset($_GET['query']) && !empty($_GET['query'])) {
        $search_query = trim($_GET['query']);
        $sql = 'SELECT d.*, c.course_name, u.user_id, u.full_name
                FROM exam_deadlines d
                JOIN courses c ON d.course_id = c.course_id
                JOIN enrollments e ON c.course_id = (SELECT course_id FROM schedules WHERE schedule_id = e.schedule_id)
                JOIN users u ON e.student_id = u.id
                WHERE LOWER(u.user_id) LIKE LOWER($1) OR LOWER(u.full_name) LIKE LOWER($2) OR LOWER(c.course_id) LIKE LOWER($3)
                GROUP BY d.deadline_id, c.course_name, u.user_id, u.full_name
                ORDER BY d.deadline_date ASC';
        pg_prepare($conn, "admin_search", $sql);
        $result = pg_execute($conn, "admin_search", array("%$search_query%", "%$search_query%", "%$search_query%"));
    } else {
        $sql = 'SELECT d.*, c.course_name, u.user_id, u.full_name
                FROM exam_deadlines d
                JOIN courses c ON d.course_id = c.course_id
                JOIN enrollments e ON c.course_id = (SELECT course_id FROM schedules WHERE schedule_id = e.schedule_id)
                JOIN users u ON e.student_id = u.id
                GROUP BY d.deadline_id, c.course_name, u.user_id, u.full_name
                ORDER BY d.deadline_date ASC';
        pg_prepare($conn, "admin_all", $sql);
        $result = pg_execute($conn, "admin_all", array());
    }
} else {
    $sql = "SELECT d.course_id, c.course_name, d.title, d.deadline_date, d.details
            FROM exam_deadlines d
            JOIN courses c ON d.course_id = c.course_id
            JOIN schedules s ON c.course_id = s.course_id
            JOIN enrollments e ON s.schedule_id = e.schedule_id
            WHERE e.student_id = $1 AND e.status = 'approved'
            GROUP BY d.deadline_id, c.course_name
            ORDER BY d.deadline_date ASC";

    pg_prepare($conn, "student_deadline_query", $sql);
    $result = pg_execute($conn, "student_deadline_query", array($user_id));
}

if ($result) {
    $deadlines = pg_fetch_all($result) ?: [];
}

$courses_list = [];
$students_list = [];
if ($is_admin) {
    $sql_courses = 'SELECT course_id, course_name FROM courses ORDER BY course_id';
    $courses_result = pg_query($conn, $sql_courses);
    $courses_list = pg_fetch_all($courses_result) ?: [];
    
    $sql_students = 'SELECT user_id, full_name FROM users WHERE role = \'student\' ORDER BY user_id';
    $students_result = pg_query($conn, $sql_students);
    $students_list = pg_fetch_all($students_result) ?: [];
}

pg_close($conn);
?>

<title>Exam Deadline Schedule - Greenwich AP</title>

<div class="page-container">
    <h1>Exam Deadline Schedule</h1>
    
    <?php if ($is_admin): ?>
        <div id="status-message" class="message" style="display: none;"></div>
        <div style="margin-bottom: 30px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
            <p><strong>Add New Deadline:</strong></p>
            <form id="add-deadline-form" action="admin/add_deadline_process.php" method="POST" class="request-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Target Student ID</label>
                        <select name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students_list as $student): ?>
                                <option value="<?= htmlspecialchars($student['user_id']); ?>"><?= htmlspecialchars($student['user_id'] . ' - ' . $student['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses_list as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_id']); ?>"><?= htmlspecialchars($course['course_id'] . ' - ' . $course['course_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" placeholder="e.g., Mid Term, Final Assignment" required>
                    </div>
                    <div class="form-group">
                        <label>Deadline Date & Time</label>
                        <input type="datetime-local" name="deadline_date" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Details</label>
                    <textarea name="details" rows="3" placeholder="Additional instructions..." required></textarea>
                </div>
                <button type="submit" class="btn-submit" style="width: auto; padding: 10px 30px;">Add Deadline</button>
            </form>
        </div>

        <form method="GET" action="exam_schedule.php" class="request-form" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="query" value="<?= htmlspecialchars($search_query); ?>" placeholder="Search Student ID, Name or Course..." style="flex: 1;">
            <button type="submit" class="btn-submit" style="width: auto; margin-top: 0;">Search</button>
        </form>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <?php if ($is_admin): ?>
                        <th>Student</th>
                    <?php endif; ?>
                    <th>Course</th>
                    <th>Title</th>
                    <th>Deadline</th>
                    <th>Details</th>
                    <?php if ($is_admin): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deadlines)): ?>
                    <tr>
                        <td colspan="<?= $is_admin ? '6' : '4'; ?>" style="text-align: center; padding: 20px;">No deadlines found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deadlines as $deadline): ?>
                        <tr>
                            <?php if ($is_admin): ?>
                                <td><strong><?= htmlspecialchars($deadline['full_name']); ?></strong><br><small><?= htmlspecialchars($deadline['user_id']); ?></small></td>
                            <?php endif; ?>
                            <td><strong><?= htmlspecialchars($deadline['course_id']); ?></strong><br><small><?= htmlspecialchars($deadline['course_name']); ?></small></td>
                            <td><?= htmlspecialchars($deadline['title']); ?></td>
                            <td style="color: #d9534f; font-weight: bold;">
                                <?= date('l, d-m-Y @ H:i', strtotime($deadline['deadline_date'])); ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($deadline['details'])); ?></td>
                            <?php if ($is_admin): ?>
                                <td>
                                    <form class="ajax-delete-form" action="admin/delete_deadline_process.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($deadline['course_id']); ?>">
                                        <input type="hidden" name="title" value="<?= htmlspecialchars($deadline['title']); ?>">
                                        <input type="hidden" name="deadline_date" value="<?= htmlspecialchars($deadline['deadline_date']); ?>">
                                        <button type="submit" class="btn-submit" style="background-color: #dc3545; padding: 5px 10px; font-size: 0.8em; width: auto;">Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.ajax-delete-form');

    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire(
                                'Deleted!',
                                data.message,
                                'success'
                            );
                            this.closest('tr').remove(); 
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Something went wrong!', 'error');
                    });
                }
            });
        });
    });

    const addDeadlineForm = document.getElementById('add-deadline-form');
    if (addDeadlineForm) {
        addDeadlineForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetch(this.action, { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Success', 'Deadline added!', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>