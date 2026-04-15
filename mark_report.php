<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$role = $_SESSION['role'] ?? 'student';

$s_id = $_SESSION['id'] ?? null;
$s_code = $_SESSION['user_id'] ?? null;

if (empty($s_id) && is_numeric($s_code)) {
    $s_id = $s_code;
}

$view_student_id = ($role === 'student') ? $s_id : ($_GET['student_id'] ?? null);

$marks = [];
if ($view_student_id) {
    $sql_marks = "SELECT m.mark_id, m.assignment_name, m.score, c.course_id, c.course_name, 
                         u.full_name as student_name, u.user_id as student_code, e.enrollment_id
                  FROM marks m
                  JOIN enrollments e ON m.enrollment_id = e.enrollment_id
                  JOIN schedules sch ON e.schedule_id = sch.schedule_id
                  JOIN courses c ON sch.course_id = c.course_id
                  JOIN users u ON e.student_id = u.id
                  WHERE e.student_id = $1::integer";

    $res_marks = pg_query_params($conn, $sql_marks, array($view_student_id));
    if ($res_marks) {
        while ($row = pg_fetch_assoc($res_marks)) { $marks[] = $row; }
    }
}

$student_list = [];
if ($role !== 'student') {
    $q_list = ($role === 'admin') 
        ? "SELECT id, user_id, full_name FROM users WHERE role = 'student' ORDER BY user_id"
        : "SELECT DISTINCT u.id, u.user_id, u.full_name FROM users u 
           JOIN enrollments e ON u.id = e.student_id 
           JOIN schedules s ON e.schedule_id = s.schedule_id 
           WHERE s.teacher_id = '$s_code' ORDER BY u.user_id";
    $res_list = pg_query($conn, $q_list);
    while ($r = pg_fetch_assoc($res_list)) { $student_list[] = $r; }
}
?>

<style>
    .report-card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 25px; border: 1px solid #eee; }
    .fancy-input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; }
    .badge-pass { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 0.9em; }
    .badge-fail { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 0.9em; }
    .btn-post { background: #3182ce; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; }
    .action-icons span { cursor: pointer; margin-right: 10px; font-size: 1.1em; }
</style>

<div class="page-container" style="max-width: 1100px; margin: auto; padding: 20px;">
    <h1 style="color: #1a365d; margin-bottom: 25px;">Mark Report Management</h1>

    <?php if ($role !== 'student'): ?>
        <div class="report-card">
            <form method="GET">
                <label><strong>Select Student to View Marks:</strong></label>
                <select name="student_id" class="fancy-input" onchange="this.form.submit()">
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($student_list as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($view_student_id == $s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['user_id'] . " - " . $s['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="report-card" id="form-container">
            <h3 id="form-title">Add / Update Mark</h3>
            <form id="add-mark-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="hidden" name="mark_id" id="input-mark-id">
                <div>
                    <label>Target Student</label>
                    <select id="select-student-mark" class="fancy-input" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($student_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['user_id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Course</label>
                    <select name="enrollment_id" id="select-enrollment" class="fancy-input" required disabled>
                        <option value="">-- Select student first --</option>
                    </select>
                </div>
                <div>
                    <label>Assignment</label>
                    <input type="text" name="assignment_name" id="input-assignment" class="fancy-input" required>
                </div>
                <div>
                    <label>Score (0-100)</label>
                    <input type="number" name="score" id="input-score" class="fancy-input" step="0.01" required>
                </div>
                <div style="grid-column: span 2; display: flex; gap: 10px;">
                    <button type="submit" class="btn-post" id="btn-submit">Post Mark Now</button>
                    <button type="button" onclick="resetMarkForm()" id="btn-cancel" class="btn-post" style="background: #95a5a6; display: none; width: 180px;">Cancel Edit</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="report-card">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left; background: #f8f9fa;">
                    <th style="padding: 12px;">COURSE</th>
                    <?php if($role !== 'student'): ?><th>STUDENT</th><?php endif; ?>
                    <th>ASSIGNMENT</th>
                    <th>SCORE</th>
                    <th>RESULT</th>
                    <?php if ($role !== 'student'): ?><th>ACTION</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: #999;">No marks found.</td></tr>
                <?php else: ?>
                    <?php foreach ($marks as $m): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;"><strong><?= $m['course_id'] ?></strong><br><small><?= $m['course_name'] ?></small></td>
                            <?php if($role !== 'student'): ?><td><?= $m['student_code'] ?><br><?= $m['student_name'] ?></td><?php endif; ?>
                            <td><?= htmlspecialchars($m['assignment_name']) ?></td>
                            <td style="font-weight: bold; color: <?= $m['score'] < 40 ? '#dc3545' : '#28a745' ?>;"><?= number_format($m['score'], 2) ?></td>
                            <td><span class="<?= $m['score'] >= 40 ? 'badge-pass' : 'badge-fail' ?>"><?= $m['score'] >= 40 ? 'PASSED' : 'FAILED' ?></span></td>
                            <?php if ($role !== 'student'): ?>
                                <td class="action-icons">
                                    <span onclick='editMark(<?= json_encode($m) ?>, <?= $view_student_id ?>)'>📝</span>
                                    <span onclick="deleteMark(<?= $m['mark_id'] ?>)" style="color:red">🗑️</span>
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
function loadCourses(studentId, selectedId = null) {
    const courseSelect = document.getElementById('select-enrollment');
    fetch(`admin/get_student_enrollments.php?student_id=${studentId}`)
        .then(res => res.json())
        .then(data => {
            courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
            data.forEach(item => {
                const sel = (selectedId == item.enrollment_id) ? 'selected' : '';
                courseSelect.innerHTML += `<option value="${item.enrollment_id}" ${sel}>${item.course_id} - ${item.course_name}</option>`;
            });
            courseSelect.disabled = false;
        });
}

document.getElementById('select-student-mark')?.addEventListener('change', function() { loadCourses(this.value); });

document.getElementById('add-mark-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('admin/post_mark_process.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.status === 'success') location.reload();
        });
});

function editMark(data, studentId) {
    document.getElementById('form-title').innerText = "📝 Edit Mark Mode";
    document.getElementById('btn-submit').innerText = "Update Mark Now";
    document.getElementById('btn-cancel').style.display = "block";
    document.getElementById('input-mark-id').value = data.mark_id;
    document.getElementById('select-student-mark').value = studentId;
    document.getElementById('input-assignment').value = data.assignment_name;
    document.getElementById('input-score').value = data.score;
    loadCourses(studentId, data.enrollment_id);
    window.scrollTo({ top: document.getElementById('form-container').offsetTop - 20, behavior: 'smooth' });
}

function resetMarkForm() {
    document.getElementById('add-mark-form').reset();
    document.getElementById('form-title').innerText = "Add / Update Mark";
    document.getElementById('btn-cancel').style.display = "none";
}

function deleteMark(id) {
    if(confirm('Delete this mark?')) {
        const fd = new FormData(); fd.append('mark_id', id);
        fetch('admin/delete_mark_process.php', { method: 'POST', body: fd })
            .then(res => res.json()).then(data => { if(data.status === 'success') location.reload(); });
    }
}
</script>

<?php pg_close($conn); require_once 'includes/footer.php'; ?>