<?php
require_once '../includes/header.php';
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

$conn = connectToDatabase();

$sql = 'SELECT f.*, u.full_name, u.user_id 
        FROM teacher_feedback f 
        JOIN users u ON f.teacher_id = u.id 
        ORDER BY f.created_at DESC';

$result = pg_query($conn, $sql);
$feedbacks = pg_fetch_all($result) ?: [];
?>

<style>
    .report-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .report-table th, .report-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: bold;
        display: inline-block;
    }

    .status-resolved {
        background-color: #dcfce7; 
        color: #166534 !important; 
    }

    .status-pending {
        background-color: #fef3c7; 
        color: #92400e !important; 
    }

    .action-container {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .action-container select {
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: #fff;
    }

    .action-container textarea {
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        height: 38px; 
        resize: horizontal;
        min-width: 120px;
        font-family: inherit;
        font-size: 0.9em;
    }

    .btn-update {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-update:hover {
        background-color: #45a049;
    }
</style>

<div class="page-container">
    <h1 style="margin-bottom: 20px;">Teacher Support Management</h1>
    
    <table class="report-table">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="text-align: left;">Teacher</th>
                <th style="text-align: left;">Subject</th>
                <th style="text-align: left;">Content</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($feedbacks)): ?>
                <tr><td colspan="5" style="text-align:center;">No feedback found.</td></tr>
            <?php else: ?>
                <?php foreach ($feedbacks as $fb): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($fb['full_name']) ?></strong><br>
                            <small style="color: #666;"><?= htmlspecialchars($fb['user_id']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($fb['subject']) ?></td>
                        <td><small><?= nl2br(htmlspecialchars($fb['content'])) ?></small></td>
                        <td style="text-align: center;">
                            <?php 
                                $status_class = (strtolower($fb['status']) == 'resolved') ? 'status-resolved' : 'status-pending';
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <?= strtoupper($fb['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form action="update_teacher_feedback.php" method="POST" class="action-container">
                                <input type="hidden" name="feedback_id" value="<?= $fb['feedback_id'] ?>">
                                
                                <select name="status">
                                    <option value="Pending" <?= ($fb['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="Resolved" <?= ($fb['status'] == 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                                </select>

                                <textarea name="admin_response" placeholder="Type response..."><?= htmlspecialchars($fb['admin_response'] ?? '') ?></textarea>
                                
                                <button type="submit" class="btn-update">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
pg_close($conn);
require_once '../includes/footer.php'; 
?>