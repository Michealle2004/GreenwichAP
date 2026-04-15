<?php 
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$sql_majors = 'SELECT major_id, major_name FROM majors ORDER BY major_name';
$majors_result = pg_query($conn, $sql_majors);
$majors_list = pg_fetch_all($majors_result) ?: [];

$selected_major_id = $_GET['major_id'] ?? null;

$sql_main = 'SELECT m.major_name, c.course_id, c.course_name, c.credits, c.fee, cur.term_no
             FROM curriculum cur
             JOIN majors m ON cur.major_id = m.major_id
             JOIN courses c ON cur.course_id = c.course_id';

if (!empty($selected_major_id)) {
    $sql_main .= ' WHERE m.major_id = ' . (int)$selected_major_id;
}

$sql_main .= ' ORDER BY m.major_name, cur.term_no ASC, c.course_id';
$result = pg_query($conn, $sql_main);
$major_courses = pg_fetch_all($result) ?: [];

pg_close($conn);

$grouped_data = [];
foreach ($major_courses as $course) {
    $grouped_data[$course['major_name']][] = $course;
}
?>

<title>Course Fee - Greenwich AP</title>

<div class="page-container">
    <h1 style="text-align: center; color: #003366; margin-bottom: 20px;">Course Fee Information</h1>
    
    <div class="filter-section" style="margin-bottom: 30px; background: #f4f4f4; padding: 15px; border-radius: 8px;">
        <form action="course_fee.php" method="GET" style="display: flex; align-items: center; gap: 15px;">
            <label for="filter_major_id" style="font-weight: bold;">Filter by Major:</label>
            <select name="major_id" id="filter_major_id" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; min-width: 250px;">
                <option value="">-- All Majors --</option>
                <?php foreach ($majors_list as $major): ?>
                    <option value="<?= $major['major_id'] ?>" <?= ($selected_major_id == $major['major_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($major['major_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn-submit">Filter</button></noscript>
        </form>
    </div>

    <?php if (empty($grouped_data)): ?>
        <p style="text-align: center;">No course fee information available.</p>
    <?php else: ?>
        <?php foreach ($grouped_data as $major_name => $courses): ?>
            <div class="curriculum-section" style="margin-bottom: 40px;">
                <h2 class="section-header" style="background: #0056b3; color: white; padding: 10px 15px; border-radius: 4px 4px 0 0; margin-bottom: 0;">
                    <?= htmlspecialchars($major_name) ?>
                </h2>
                
                <div class="table-responsive">
                    <table class="report-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="border: 1px solid #dee2e6; padding: 10px; width: 10%;">TERM</th>
                                <th style="border: 1px solid #dee2e6; padding: 10px; width: 15%;">SUBJECT CODE</th>
                                <th style="border: 1px solid #dee2e6; padding: 10px;">SUBJECT/NAME</th>
                                <th style="border: 1px solid #dee2e6; padding: 10px; width: 10%;">CREDITS</th>
                                <th style="border: 1px solid #dee2e6; padding: 10px; width: 20%;">FEE (VND)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td style="border: 1px solid #dee2e6; padding: 10px; text-align: center; font-weight: bold; color: #f26f21;">
                                        <?= htmlspecialchars($c['term_no']) ?>
                                    </td>
                                    <td style="border: 1px solid #dee2e6; padding: 10px;"><?= htmlspecialchars($c['course_id']) ?></td>
                                    <td style="border: 1px solid #dee2e6; padding: 10px;"><?= htmlspecialchars($c['course_name']) ?></td>
                                    <td style="border: 1px solid #dee2e6; padding: 10px; text-align: center;"><?= htmlspecialchars($c['credits']) ?></td>
                                    <td style="border: 1px solid #dee2e6; padding: 10px; text-align: right; font-family: monospace;">
                                        <?= number_format($c['fee'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>