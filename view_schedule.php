<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$schedule_by_term = [];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_code = $_SESSION['user_code'];

$user_id_to_view = $_GET['student_id'] ?? null;

if ($role === 'admin') {
    $students_list = pg_fetch_all(pg_query($conn, "SELECT id, user_id, full_name FROM users WHERE role = 'student' ORDER BY user_id")) ?: [];
    $courses_list = pg_fetch_all(pg_query($conn, "SELECT course_id, course_name FROM courses ORDER BY course_id")) ?: [];
    $teachers_list = pg_fetch_all(pg_query($conn, "SELECT user_id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")) ?: [];
}

if ($role === 'teacher') {
    $sql = 'SELECT s.schedule_id, s.term, s.year, c.course_id, c.course_name, 
                   s.day_of_week, s.start_time, s.end_time, s.room
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.teacher_id = $1
            ORDER BY s.year DESC, s.term, s.day_of_week';
    $result = pg_query_params($conn, $sql, [$user_code]);
} elseif ($role === 'admin' && empty($user_id_to_view)) {
    $sql = 'SELECT s.schedule_id, s.term, s.year, c.course_id, c.course_name, t.full_name AS teacher_name, 
                   s.day_of_week, s.start_time, s.end_time, s.room, u.full_name AS student_name, u.user_id AS student_code
            FROM schedules s
            JOIN enrollments e ON s.schedule_id = e.schedule_id
            JOIN courses c ON s.course_id = c.course_id
            JOIN users t ON s.teacher_id = t.user_id
            JOIN users u ON e.student_id = u.id
            ORDER BY s.year DESC, s.term, student_name';
    $result = pg_query($conn, $sql);
} else {
    $target = $user_id_to_view ?? $user_id;
    $sql = 'SELECT s.schedule_id, s.term, s.year, c.course_id, c.course_name, t.full_name AS teacher_name, 
                   s.day_of_week, s.start_time, s.end_time, s.room
            FROM schedules s
            JOIN enrollments e ON s.schedule_id = e.schedule_id
            JOIN courses c ON s.course_id = c.course_id
            JOIN users t ON s.teacher_id = t.user_id
            WHERE e.student_id = $1 ORDER BY s.year DESC, s.term, c.course_name';
    $result = pg_query_params($conn, $sql, [$target]);
}

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $term_year = htmlspecialchars($row['term']) . ' ' . htmlspecialchars($row['year']);
        $schedule_by_term[$term_year][] = $row;
    }
}
pg_close($conn);
?>

<style>
    .admin-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #edf2f7; margin-bottom: 40px; }
    .slot-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 10px; }
    .slot-card { position: relative; background: #fff; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px 10px; text-align: center; transition: all 0.2s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 5px; user-select: none; }
    .slot-card:hover { border-color: #3182ce; transform: translateY(-2px); }
    .slot-card input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    .slot-card .slot-name { font-weight: 800; font-size: 1.1em; color: #2d3748; }
    .slot-card .slot-time { font-size: 0.85em; color: #718096; font-family: monospace; }
    .slot-card.active { background: #3182ce !important; border-color: #2b6cb0 !important; }
    .slot-card.active .slot-name, .slot-card.active .slot-time { color: white !important; }
    .form-label { font-weight: 700; color: #4a5568; margin-bottom: 8px; display: block; }
    .btn-create { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; border: none; padding: 15px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; margin-top: 20px; box-shadow: 0 4px 15px rgba(72,187,120,0.3); }
</style>

<div class="page-container">
    <h1 style="color: #2d3748; margin-bottom: 25px;">Academic Schedule Portal</h1>

    <?php if ($role === 'admin'): ?>
        <div id="js-message" style="display:none; padding:15px; margin-bottom:25px; border-radius:10px; font-weight:bold;"></div>

        <div class="admin-card">
            <h3 style="margin-top:0; margin-bottom:25px; color: #1a365d; border-left: 6px solid #48bb78; padding-left: 15px;">Quick Admin Enrollment</h3>
            
            <form id="add-schedule-form" action="admin/add_schedule_to_student.php" method="POST">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
                    <div class="form-group">
                        <label class="form-label">Target Student</label>
                        <select name="student_id" onchange="window.location.href='view_schedule.php?student_id=' + this.value" style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;">
                            <option value="">-- All Students --</option>
                            <?php foreach ($students_list as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($user_id_to_view == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['user_id'] . " - " . $s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Course</label>
                        <select name="course_id" style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;">
                            <?php foreach ($courses_list as $c): ?>
                                <option value="<?= htmlspecialchars($c['course_id']) ?>"><?= htmlspecialchars($c['course_id'] . " - " . $c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_id" style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;">
                            <?php foreach ($teachers_list as $t): ?>
                                <option value="<?= htmlspecialchars($t['user_id']) ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; margin-top: 25px;">
                    <div class="left-col">
                        <label class="form-label">Day & Room</label>
                        <select name="day_of_week" style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0; margin-bottom:15px;">
                            <option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option><option value="Friday">Friday</option><option value="Saturday">Saturday</option><option value="Sunday">Sunday</option>
                        </select>
                        <input type="text" name="room" placeholder="Room (e.g. F304)" required style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;">
                    </div>

                    <div class="right-col">
                        <label class="form-label">Assign Learning Slots (Click to Select)</label>
                        <div class="slot-container">
    <?php 
    $times = [
        "Slot 1" => "08:00 - 09:30", "Slot 2" => "09:30 - 11:00",
        "Slot 3" => "12:00 - 13:30", "Slot 4" => "13:30 - 15:00",
        "Slot 5" => "15:30 - 17:00", "Slot 6" => "17:00 - 18:30"
    ];
    foreach($times as $name => $time): ?>
        <label class="slot-card">
            <input type="checkbox" name="slots[]" value="<?= $name ?>">
            <span class="slot-name"><?= $name ?></span>
            <span class="slot-time"><?= $time ?></span>
        </label>
    <?php endforeach; ?>
</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1.5fr; gap: 25px; margin-top: 25px; align-items: end;">
                    <div class="form-group"><label class="form-label">Term</label><input type="text" name="term" value="Summer" required style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;"></div>
                    <div class="form-group"><label class="form-label">Year</label><input type="number" name="year" value="<?= date('Y') ?>" required style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0;"></div>
                    <button type="submit" class="btn-create">Submit Enrollment</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($schedule_by_term)): ?>
        <div style="text-align:center; padding:50px; background:white; border-radius:15px; color:#a0aec0; border: 1px solid #e2e8f0;">No schedules found.</div>
    <?php else: ?>
        <?php foreach ($schedule_by_term as $term => $courses): ?>
            <div class="history-section" style="margin-bottom:40px;">
                <h2 class="section-header" style="background:#2d3748; padding:15px; border-radius:12px 12px 0 0;"><?= $term ?></h2>
                <table class="report-table" style="background:white; border-radius: 0 0 12px 12px; overflow:hidden; border: 1px solid #e2e8f0;">
                    <thead>
                        <tr>
                            <?php if($role === 'admin' && !$user_id_to_view) echo "<th>Student</th>"; ?>
                            <th>Course</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr id="row-<?= $c['schedule_id'] ?>">
                                <?php if($role === 'admin' && !$user_id_to_view) echo "<td><strong>".$c['student_name']."</strong><br><small>".$c['student_code']."</small></td>"; ?>
                                <td><strong style="color:#3182ce;"><?= $c['course_id'] ?></strong><br><small><?= $c['course_name'] ?></small></td>
                                <td><span style="background:#edf2f7; padding:4px 10px; border-radius:20px; font-size:0.9em; font-weight:600;"><?= $c['day_of_week'] ?></span> <?= date('H:i', strtotime($c['start_time'])) ?></td>
                                <td><?= htmlspecialchars($c['room']) ?></td>
                                <td style="text-align:center;">
                                    <?php if ($role === 'teacher'): ?>
                                        <a href="teacher_attendance_management.php?schedule_id=<?= $c['schedule_id'] ?>" class="btn-submit" style="background:#3182ce; padding:6px 12px; font-size:0.85em; text-decoration:none; border-radius:8px;">Attendance</a>
                                    <?php elseif ($role === 'admin'): ?>
                                        <button class="btn-submit" style="background:#fff5f5; color:#e53e3e; border:1px solid #feb2b2; padding:6px 12px; width:auto; border-radius:8px;" onclick="deleteSchedule(<?= $c['schedule_id'] ?>)">Remove</button>
                                    <?php else: ?>
                                        <span class="status-badge status-approved">Enrolled</span>
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

<script>
document.querySelectorAll('.slot-card').forEach(card => {
    const cb = card.querySelector('input');
    card.addEventListener('click', function(e) {
        if (e.target !== cb) cb.checked = !cb.checked;
        if(cb.checked) this.classList.add('active'); else this.classList.remove('active');
    });
});

const addForm = document.getElementById('add-schedule-form');
const jsMsg = document.getElementById('js-message');

if(addForm) {
    addForm.onsubmit = function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch(this.action, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            jsMsg.style.display = 'block';
            if(data.status === 'success') {
                jsMsg.style.background = '#f0fff4'; jsMsg.style.color = '#2f855a'; jsMsg.style.border = '2px solid #c6f6d5';
                jsMsg.innerText = "✨ " + data.message;
                setTimeout(() => { window.location.href = 'view_schedule.php?student_id=' + data.student_id; }, 800);
            } else {
                jsMsg.style.background = '#fff5f5'; jsMsg.style.color = '#c53030'; jsMsg.style.border = '2px solid #feb2b2';
                jsMsg.innerText = "⚠️ " + data.message;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    };
}

function deleteSchedule(id) {
    if(confirm('Permanently remove this registration?')) {
        const fd = new FormData(); fd.append('schedule_id', id);
        fetch('admin/delete_schedule_process.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(data => { if(data.status === 'success') location.reload(); });
    }
}
</script>
<?php require_once 'includes/footer.php'; ?>