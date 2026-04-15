<?php
require_once '../includes/header.php';
require_once '../admin_check.php'; 
require_once '../includes/db_connect.php';

$conn = connectToDatabase();

$sql = 'SELECT f.feedback_id, f.feedback_target, f.person_in_charge, f.reason, f.submission_date, 
               u.user_id, u.full_name 
        FROM feedback f
        JOIN users u ON f.student_id = u.id
        ORDER BY f.submission_date DESC';

pg_prepare($conn, "get_all_feedback", $sql);
$result = pg_execute($conn, "get_all_feedback", array());

$feedbacks = [];
if ($result) {
    $feedbacks = pg_fetch_all($result) ?: [];
}

pg_close($conn);
?>

<title>View Feedback - Admin</title>

<div class="page-container">
    <h1>Student Feedback List</h1>
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
        <p class="message success">Feedback deleted successfully.</p>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Target</th>
                    <th>Person in Charge</th>
                    <th>Content</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="7" style="text-align:center;">No feedback found.</td></tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fb['feedback_id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($fb['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($fb['user_id']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($fb['feedback_target']); ?></td>
                            <td><?php echo htmlspecialchars($fb['person_in_charge'] ?: 'N/A'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($fb['reason'])); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($fb['submission_date'])); ?></td>
                            <td>
                                <form action="delete_feedback.php" method="POST" onsubmit="return confirm('Delete this feedback?');">
                                    <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                                    <button type="submit" class="btn-submit" style="background-color: #dc3545; padding: 5px 10px; font-size: 0.8em;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>