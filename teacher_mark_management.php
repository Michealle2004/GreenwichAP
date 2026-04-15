<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php"); 
    exit();
}

$schedule_id = $_GET['schedule_id'] ?? null;
$teacher_user_id = $_SESSION['user_code'];
$class_info = null;
$student_marks = [];
$assignments_list = [];

if (empty($schedule_id)) {
    header("Location: teacher_dashboard.php?error=MissingScheduleID");
    exit();
}

$conn = connectToDatabase();

$sql_info = 'SELECT 
                c.course_id, c.course_name, s.term, s.year, s.room
             FROM schedules s
             JOIN courses c ON s.course_id = c.course_id
             WHERE s.schedule_id = $1 AND s.teacher_id = $2';

pg_prepare($conn, "get_class_info", $sql_info);
$result_info = pg_execute($conn, "get_class_info", array($schedule_id, $teacher_user_id));

if (!($class_info = pg_fetch_assoc($result_info))) {
    pg_close($conn);
    die("Error: Class not found or you are not assigned to this class.");
}

$sql_marks = 'SELECT 
    u.id AS student_db_id, 
    u.user_id AS student_user_id, 
    u.full_name AS student_full_name, 
    e.enrollment_id, 
    m.assignment_name, 
    m.score, 
    m.status
FROM enrollments e
JOIN users u ON e.student_id = u.id
LEFT JOIN marks m ON e.enrollment_id = m.enrollment_id
WHERE e.schedule_id = $1
ORDER BY u.user_id, m.assignment_name';

pg_prepare($conn, "get_student_marks", $sql_marks);
$result_marks = pg_execute($conn, "get_student_marks", array($schedule_id));
$raw_marks = pg_fetch_all($result_marks);

$students_data = [];
if ($raw_marks) {
    foreach ($raw_marks as $row) {
        $enrollment_id = $row['enrollment_id'];
        $assignment = $row['assignment_name'];
        
        if (!isset($students_data[$enrollment_id])) {
            $students_data[$enrollment_id] = [
                'user_id' => $row['student_user_id'],
                'full_name' => $row['student_full_name'],
                'enrollment_id' => $enrollment_id,
                'marks' => []
            ];
        }
        
        if (!empty($assignment)) {
            $students_data[$enrollment_id]['marks'][$assignment] = [
                'score' => $row['score'],
                'status' => $row['status']
            ];
            if (!in_array($assignment, $assignments_list)) {
                $assignments_list[] = $assignment;
            }
        }
    }
}
sort($assignments_list);

pg_close($conn);
?>

<title>Mark Management - <?php echo htmlspecialchars($class_info['course_id']); ?></title>

<div class="page-container">
    <h1>Mark Management</h1>
    <h2>Class: <?php echo htmlspecialchars($class_info['course_name']); ?> (<?php echo htmlspecialchars($class_info['course_id']); ?>)</h2>
    <p>Term: <?php echo htmlspecialchars($class_info['term']) . ' ' . htmlspecialchars($class_info['year']); ?> | Room: <?php echo htmlspecialchars($class_info['room']); ?></p>

    <div id="status-message" class="message" style="display: none;"></div>
    <hr>
    
    <div class="table-responsive">
        <form id="mark-update-form">
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Enrollment ID</th>
                        <?php foreach ($assignments_list as $assignment): ?>
                            <th><?php echo htmlspecialchars($assignment); ?></th>
                        <?php endforeach; ?>
                        <th>New Assignment Name:</th>
                        <th>New Score:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students_data)): ?>
                        <tr><td colspan="<?php echo 5 + count($assignments_list); ?>">No students enrolled in this class.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students_data as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['enrollment_id']); ?></td>
                                
                                <?php foreach ($assignments_list as $assignment): 
                                    $mark = $student['marks'][$assignment] ?? ['score' => '', 'status' => 'not_yet'];
                                ?>
                                    <td>
                                        <form class="update-mark-form" action="admin/update_mark_process.php" method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($student['enrollment_id']); ?>">
                                            <input type="hidden" name="assignment_name" value="<?php echo htmlspecialchars($assignment); ?>">
                                            <input type="number" name="score" value="<?php echo htmlspecialchars($mark['score']); ?>" step="0.1" min="0" max="10" style="width: 60px; padding: 5px;" required>
                                            <button type="submit" class="btn-submit" style="padding: 3px 6px; font-size: 0.8em; margin-top: 5px;">Update</button>
                                        </form>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <input type="text" name="new_assignment_<?php echo $student['enrollment_id']; ?>" placeholder="New name" style="width: 80px; padding: 5px; margin-bottom: 5px;">
                                </td>
                                <td>
                                    <form class="add-mark-form" action="admin/update_mark_process.php" method="POST">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($student['enrollment_id']); ?>">
                                        <input type="number" name="score" step="0.1" min="0" max="10" style="width: 60px; padding: 5px;" required>
                                        <input type="hidden" name="status" value="done">
                                        <button type="submit" class="btn-submit add-mark-btn" data-enrollment-id="<?php echo $student['enrollment_id']; ?>" style="padding: 3px 6px; font-size: 0.8em;">Add</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    const updateMarkForms = document.querySelectorAll('.update-mark-form');
    const addMarkForms = document.querySelectorAll('.add-mark-form');

    function showMessage(message, type = 'success') {
        statusMessage.textContent = message;
        statusMessage.className = 'message ' + type;
        statusMessage.style.display = 'block';
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 5000);
    }
    
    updateMarkForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.set('status', 'done'); 

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('Mark updated successfully!');
                } else {
                    showMessage('Error updating mark: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            });
        });
    });

    addMarkForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const enrollmentId = this.querySelector('input[name="enrollment_id"]').value;
            const newAssignmentInput = document.querySelector(`input[name="new_assignment_${enrollmentId}"]`);
            const newAssignmentName = newAssignmentInput ? newAssignmentInput.value.trim() : '';

            if (newAssignmentName === '') {
                showMessage('Please enter a name for the new assignment.', 'error');
                return;
            }
            
            const formData = new FormData(this);
            formData.set('assignment_name', newAssignmentName); 
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('New mark added successfully! Reloading to update table structure.');
                    window.location.reload(); 
                } else {
                    showMessage('Error adding mark: ' + data.message, 'error');
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