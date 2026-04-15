<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';

if (!isset($_SESSION['parent_id'])) {
    header("Location: parent_login.php");
    exit();
}

$student_db_id = $_SESSION['student_user_id'];
$student_name = htmlspecialchars($_SESSION['student_full_name']);
$conn = connectToDatabase();

$timetable = [];
$sql_time = 'SELECT s.day_of_week, to_char(s.start_time, \'HH24:MI\') as start_hm, s.room, c.course_id, c.course_name
             FROM schedules s
             JOIN enrollments e ON s.schedule_id = e.schedule_id
             JOIN courses c ON s.course_id = c.course_id
             WHERE e.student_id = $1 AND e.status = \'approved\'';
$res_time = pg_query_params($conn, $sql_time, [$student_db_id]);
while ($row = pg_fetch_assoc($res_time)) { 
    $timetable[$row['day_of_week']][$row['start_hm']] = $row; 
}

$marks = [];
$sql_marks = 'SELECT c.course_id, c.course_name, m.assignment_name, m.score, m.status
              FROM marks m
              JOIN enrollments e ON m.enrollment_id = e.enrollment_id
              JOIN schedules s ON e.schedule_id = s.schedule_id
              JOIN courses c ON s.course_id = c.course_id
              WHERE e.student_id = $1';
$res_marks = pg_query_params($conn, $sql_marks, [$student_db_id]);
while ($row = pg_fetch_assoc($res_marks)) { 
    $marks[$row['course_name']][] = $row; 
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$slots = [['n'=>'1','s'=>'08:00'], ['n'=>'2','s'=>'09:30'], ['n'=>'3','s'=>'12:00'], ['n'=>'4','s'=>'13:30'], ['n'=>'5','s'=>'15:30'], ['n'=>'6','s'=>'17:00']];
pg_close($conn);
?>

<div class="page-container">
    <div class="welcome-banner" style="background: #1e3a8a; padding: 20px; color: white; border-radius: 8px;">
        <h2>Parent Dashboard</h2>
        <p>Viewing for student: <strong><?= $student_name ?></strong></p>
    </div>

    <h3 style="margin-top:30px; border-left: 5px solid #3b82f6; padding-left: 10px;">Weekly Timetable</h3>
    <table class="report-table">
        <thead>
            <tr style="background:#f1f5f9;">
                <th>Slot</th>
                <?php foreach($days as $d) echo "<th>$d</th>"; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($slots as $slot): ?>
            <tr>
                <td style="background:#f8fafc; text-align:center;"><strong>Slot <?= $slot['n'] ?></strong><br><small><?= $slot['s'] ?></small></td>
                <?php foreach($days as $day): ?>
                <td>
                    <?php if(isset($timetable[$day][$slot['s']])): $c = $timetable[$day][$slot['s']]; ?>
                        <div style="background:#e0f2fe; padding:5px; border-radius:4px; font-size:0.8em; border-left: 3px solid #0284c7;">
                            <strong><?= $c['course_id'] ?></strong><br>Room: <?= $c['room'] ?>
                        </div>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin-top:40px; border-left: 5px solid #10b981; padding-left: 10px;">Mark Report</h3>
    <?php if(empty($marks)): ?>
        <p>No marks found for this student.</p>
    <?php else: ?>
        <?php foreach($marks as $cName => $mList): ?>
            <div style="margin-bottom:20px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                <div style="background:#f8fafc; padding:10px; font-weight:bold; border-bottom:1px solid #e2e8f0;"><?= $cName ?></div>
                <table class="report-table" style="margin:0; border:none;">
                    <?php foreach($mList as $m): ?>
                    <tr>
                        <td style="width:60%;"><?= $m['assignment_name'] ?></td>
                        <td style="text-align:center;"><strong><?= $m['score'] ?? 'N/A' ?></strong></td>
                        <td style="text-align:right;"><span class="status-badge status-<?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>