<?php 
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$major_id_to_view = null;
$major_name_to_view = "Not specified";
$majors_list = [];
$curriculum_courses = [];

if ($is_admin) {
    $sql_majors = 'SELECT major_id, major_name FROM majors ORDER BY major_name';
    $majors_result = pg_query($conn, $sql_majors);
    while ($row = pg_fetch_assoc($majors_result)) {
        $majors_list[] = $row;
    }
    
    if (isset($_GET['major_id']) && !empty($_GET['major_id'])) {
        $major_id_to_view = $_GET['major_id'];
    }
}

if (!$is_admin || empty($major_id_to_view)) {
    $sql_major = 'SELECT u.major_id, m.major_name 
                  FROM users u 
                  JOIN majors m ON u.major_id = m.major_id 
                  WHERE u.id = $1';
    pg_prepare($conn, "get_major", $sql_major);
    $result_major = pg_execute($conn, "get_major", array($_SESSION['user_id']));
    if ($major_info = pg_fetch_assoc($result_major)) {
        $major_id_to_view = $major_info['major_id'];
        $major_name_to_view = $major_info['major_name'];
    }
} else {
    $sql_major_name = 'SELECT major_name FROM majors WHERE major_id = $1';
    pg_prepare($conn, "get_major_name_by_id", $sql_major_name);
    $result_major_name = pg_execute($conn, "get_major_name_by_id", array($major_id_to_view));
    if ($major_info = pg_fetch_assoc($result_major_name)) {
        $major_name_to_view = $major_info['major_name'];
    }
}

if ($major_id_to_view) {
    $sql_curriculum = 'SELECT c.course_id, c.course_name, cur.term_no
                       FROM curriculum cur
                       JOIN courses c ON cur.course_id = c.course_id
                       WHERE cur.major_id = $1
                       ORDER BY cur.term_no, c.course_id';
    pg_prepare($conn, "get_curriculum_list", $sql_curriculum);
    $result_curriculum = pg_execute($conn, "get_curriculum_list", array($major_id_to_view));
    if ($result_curriculum) {
        $curriculum_courses = pg_fetch_all($result_curriculum) ?: [];
    }
}

pg_close($conn);
?>

<div class="page-container">
    <h1>Student Curriculum</h1>
    
    <?php if ($is_admin): ?>
        <section class="admin-panel" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px; border-radius: 8px;">
            <h3>Admin Tools</h3>
            <div id="status-message" class="message" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 5px;"></div>
            
            <form id="major-select-form" method="GET" action="curriculum.php">
                <div class="form-group">
                    <label for="major_id_select"><strong>Select Major to View/Edit:</strong></label><br>
                    <select name="major_id" id="major_id_select" onchange="this.form.submit()" style="padding: 10px; width: 300px; margin-top: 10px;">
                        <option value="">-- Select Major --</option>
                        <?php foreach ($majors_list as $major): ?>
                            <option value="<?php echo htmlspecialchars($major['major_id']); ?>" <?php echo ($major_id_to_view == $major['major_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($major['major_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <div class="curriculum-info" style="margin-bottom: 20px;">
        <p style="font-size: 1.2em;"><strong>Major:</strong> <span style="color: #0056b3;"><?php echo htmlspecialchars($major_name_to_view); ?></span></p>
    </div>
    
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 15%;">Code</th>
                    <th>Subject Name</th>
                    <th style="width: 10%;">Term</th>
                    <?php if ($is_admin): ?>
                        <th style="width: 10%;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($curriculum_courses)): ?>
                    <tr>
                        <td colspan="<?php echo $is_admin ? '5' : '4'; ?>" style="text-align: center;">No curriculum information available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($curriculum_courses as $index => $course): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($course['course_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($course['term_no']); ?></td>
                            <?php if ($is_admin): ?>
                                <td>
                                    <form class="delete-form" action="admin/update_curriculum_process.php" method="POST">
                                        <input type="hidden" name="delete_task" value="delete">
                                        <input type="hidden" name="major_id" value="<?php echo htmlspecialchars($major_id_to_view); ?>">
                                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                        <button type="submit" class="btn-submit" style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px;">Delete</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    const deleteForms = document.querySelectorAll('.delete-form');
    
    function showMessage(message, type = 'success') {
        if(!statusMessage) return;
        statusMessage.textContent = message;
        statusMessage.style.display = 'block';
        statusMessage.style.backgroundColor = (type === 'success') ? '#d4edda' : '#f8d7da';
        statusMessage.style.color = (type === 'success') ? '#155724' : '#721c24';
        statusMessage.style.border = '1px solid ' + ((type === 'success') ? '#c3e6cb' : '#f5c6cb');
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { statusMessage.style.display = 'none'; }, 4000);
    }

    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to remove this course from the curriculum?')) {
                const formData = new FormData(this);
                // Sử dụng this.getAttribute('action') để lấy đúng đường dẫn chuỗi
                const targetUrl = this.getAttribute('action');

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMessage('Course deleted successfully!', 'success');
                        this.closest('tr').remove(); 
                    } else {
                        showMessage('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error or invalid response from server.', 'error');
                    console.error('Error:', error);
                });
            }
        });
    });
});
</script>