<?php 
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$retake_classes = [];
$search_id = '';
$user_id_to_view = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$students_list = [];
$courses_list = [];

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

    $sql_courses = 'SELECT course_id, course_name FROM courses ORDER BY course_id';
    $courses_result = pg_query($conn, $sql_courses);
    while ($row = pg_fetch_assoc($courses_result)) {
        $courses_list[] = $row;
    }
    
    $sql_students = 'SELECT id, user_id, full_name FROM users WHERE role = \'student\' ORDER BY user_id';
    $students_result = pg_query($conn, $sql_students);
    while ($row = pg_fetch_assoc($students_result)) {
        $students_list[] = $row;
    }
}

if ($user_id_to_view) {
    $sql = 'SELECT
                rc.retake_id, rc.fee, rc.date_added,
                c.course_id, c.course_name
            FROM retake_classes rc
            JOIN courses c ON rc.course_id = c.course_id
            WHERE rc.student_id = $1
            ORDER BY rc.date_added DESC';
    pg_prepare($conn, "retake_query", $sql);
    $result = pg_execute($conn, "retake_query", array($user_id_to_view));

    while ($row = pg_fetch_assoc($result)) {
        $retake_classes[] = $row;
    }
}

pg_close($conn);
?>

<title>Retake Class List - Greenwich AP</title>

<div class="page-container">
    <h1>List Retake Class & Fee</h1>
    
    <?php if ($is_admin): ?>
        <h2>Admin Tools</h2>
        <div id="status-message" class="message" style="display: none;"></div>
        
        <form method="GET" action="list_retake_class.php" class="request-form" style="margin-bottom: 30px;">
            <div class="form-group">
                <label for="student_user_id">Enter Student ID to view retake history</label>
                <input type="text" name="student_user_id" id="student_user_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="Leave blank for your own report">
            </div>
            <button type="submit" class="btn-submit">View History</button>
        </form>
        <hr>

        <h3>Add New Retake Class</h3>
        <p>This section allows you to add a new retake class for a student.</p>
        <form id="add-retake-class-form" action="admin/add_retake_class_process.php" method="POST" class="request-form">
            <div class="form-group">
                <label for="student_id_add">Select Student</label>
                <select name="student_id" id="student_id_add" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students_list as $student): ?>
                        <option value="<?php echo htmlspecialchars($student['id']); ?>"><?php echo htmlspecialchars($student['user_id'] . ' - ' . $student['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="course_id_add">Select Course</label>
                <select name="course_id" id="course_id_add" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses_list as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>"><?php echo htmlspecialchars($course['course_id'] . ' - ' . $course['course_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fee">Fee (VND)</label>
                <input type="number" name="fee" id="fee" min="0" required>
            </div>
            <button type="submit" class="btn-submit">Add Retake Class</button>
        </form>
        <hr>
    <?php endif; ?>

    <?php if (empty($retake_classes)): ?>
        <p>No retake class records found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Fee (VND)</th>
                        <th>Date Added</th>
                        <?php if ($is_admin): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="retake-class-table-body">
                    <?php foreach ($retake_classes as $class): ?>
                        <tr id="retake-row-<?php echo htmlspecialchars($class['retake_id']); ?>">
                            <td><?php echo htmlspecialchars($class['course_id']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                            <td><?php echo number_format($class['fee'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($class['date_added'])); ?></td>
                            <?php if ($is_admin): ?>
                                <td>
                                    <form class="delete-form" action="admin/delete_retake_class_process.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this retake class?');">
                                        <input type="hidden" name="retake_id" value="<?php echo htmlspecialchars($class['retake_id']); ?>">
                                        <button type="submit" class="btn-submit" style="background-color: #dc3545; padding: 5px 10px; font-size: 0.9em;">Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'includes/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    const addRetakeClassForm = document.getElementById('add-retake-class-form');
    const deleteForms = document.querySelectorAll('.delete-form');

    function showMessage(message, type = 'success') {
        statusMessage.textContent = message;
        statusMessage.className = 'message ' + type;
        statusMessage.style.display = 'block';
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 5000);
    }
    
    if (addRetakeClassForm) {
        addRetakeClassForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('Retake class added successfully!');
                    this.reset();
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            });
        });
    }

    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to delete this retake class?')) {
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMessage('Retake class deleted successfully!');
                        // Remove the row from the table
                        this.closest('tr').remove();
                    } else {
                        showMessage('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('An unexpected error occurred.', 'error');
                    console.error('Error:', error);
                });
            }
        });
    });
});
</script>