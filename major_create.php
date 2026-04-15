<?php 
require_once 'includes/header.php'; 
require_once 'admin_check.php'; 
require_once 'includes/db_connect.php';

$conn = connectToDatabase();

$sql_majors = 'SELECT major_id, major_name FROM majors ORDER BY major_name';
$majors_result = pg_query($conn, $sql_majors);
$majors_list = pg_fetch_all($majors_result) ?: [];

$sql_courses = 'SELECT course_id, course_name FROM courses ORDER BY course_id';
$courses_result = pg_query($conn, $sql_courses);
$courses_list = pg_fetch_all($courses_result) ?: [];

pg_close($conn);
?>

<title>Major Create - Greenwich AP</title>

<div class="page-container">
    <h1>Major & Course Management</h1>
    <div id="status-message" class="message" style="display: none; margin-bottom: 20px;"></div>

    <div class="admin-tool-section" style="margin-bottom: 40px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h3>Add a New Course</h3>
        <p>Create a brand new course in the system database.</p>
        <form id="add-new-course-form" action="admin/add_course_process.php" method="POST" class="request-form">
            <div class="form-group">
                <label for="course_id_new">Course ID</label>
                <input type="text" name="course_id" id="course_id_new" placeholder="e.g., COMP1752" required>
            </div>
            <div class="form-group">
                <label for="course_name_new">Course Name</label>
                <input type="text" name="course_name" id="course_name_new" placeholder="e.g., Object Oriented Programming" required>
            </div>
            <div class="form-group">
                <label for="credits_new">Credits</label>
                <input type="number" name="credits" id="credits_new" min="1" value="4" required>
            </div>
            <div class="form-group">
                <label for="fee_new">Fee (VND)</label>
                <input type="number" name="fee" id="fee_new" min="0" value="6540000" required>
            </div>
            <button type="submit" class="btn-submit">Add New Course</button>
        </form>
    </div>

    <hr>

    <div class="admin-tool-section" style="margin-top: 40px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h3>Add an Existing Course to a Major's Curriculum</h3>
        <p>Assign a course to a specific major and semester.</p>
        <form id="add-course-to-major-form" action="admin/add_course_to_major_process.php" method="POST" class="request-form">
            <div class="form-group">
                <label for="major_id">Select Major</label>
                <select name="major_id" id="major_id" required>
                    <option value="">-- Select Major --</option>
                    <?php foreach ($majors_list as $major): ?>
                        <option value="<?= htmlspecialchars($major['major_id']); ?>"><?= htmlspecialchars($major['major_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="course_id">Select Course</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses_list as $course): ?>
                        <option value="<?= htmlspecialchars($course['course_id']); ?>"><?= htmlspecialchars($course['course_id'] . ' - ' . $course['course_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="term_no">Term Number</label>
                <input type="number" name="term_no" id="term_no" min="1" placeholder="e.g., 1" required>
            </div>
            <button type="submit" class="btn-submit">Add Course to Major</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');

    function showMessage(message, type = 'success') {
        statusMessage.textContent = message;
        statusMessage.className = 'message ' + type;
        statusMessage.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { statusMessage.style.display = 'none'; }, 5000);
    }
    
    document.getElementById('add-new-course-form').addEventListener('submit', function(event) {
        event.preventDefault();
        fetch(this.action, { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage('New course created successfully!', 'success');
                this.reset();
            } else { showMessage('Error: ' + data.message, 'error'); }
        });
    });

    document.getElementById('add-course-to-major-form').addEventListener('submit', function(event) {
        event.preventDefault();
        fetch(this.action, { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage('Course assigned to major successfully!', 'success');
                this.reset();
            } else { showMessage('Error: ' + data.message, 'error'); }
        });
    });
});
</script>