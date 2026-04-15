<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}


$teacher_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if (!$teacher_id) {
    echo "<div class='page-container'><p style='color:red; padding:20px; background:#fff; border-radius:8px;'>
            Error: Session ID missing. Please log out and log in again.</p></div>";
    require_once 'includes/footer.php';
    exit();
}

$conn = connectToDatabase();

$sql = 'SELECT * FROM teacher_feedback 
        WHERE teacher_id = $1 
        ORDER BY created_at DESC';

$result = pg_query_params($conn, $sql, array($teacher_id));

if (!$result) {
    $history = [];
} else {
    $history = pg_fetch_all($result) ?: [];
}
?>

<style>
    .report-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        margin-top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
    }

    .report-table th {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 700;
        padding: 15px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }

    .report-table td {
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-resolved {
        background-color: #dcfce7; 
        color: #15803d !important; 
    }

    .status-pending {
        background-color: #fef3c7; 
        color: #b45309 !important; 
    }

    .admin-reply-box {
        background-color: #f8fafc;
        border-left: 4px solid #3b82f6;
        padding: 10px;
        margin-top: 5px;
        font-style: italic;
        color: #334155;
        font-size: 0.9em;
    }

    .btn-new-request {
        display: inline-block;
        background-color: #10b981;
        color: white;
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }

    .btn-new-request:hover {
        background-color: #059669;
        transform: translateY(-1px);
    }
</style>

<div class="page-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1>Teacher Support History</h1>
            <p style="color: #64748b;">Track your support requests and administrative responses.</p>
        </div>
        <a href="teacher_feedback.php" class="btn-new-request">+ Create New Request</a>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;">Subject</th>
                <th style="width: 30%;">Your Content</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 20%;">Admin Response</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">
                        <i class="fas fa-folder-open" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                        You have not submitted any requests yet.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td style="color: #64748b; font-size: 0.85em;">
                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
                        <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                        <td>
                            <?php 
                                $st = strtolower($row['status'] ?? 'pending');
                                $class = ($st === 'resolved') ? 'status-resolved' : 'status-pending';
                            ?>
                            <span class="status-badge <?= $class ?>">
                                <?= strtoupper($st) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($row['admin_response'])): ?>
                                <div class="admin-reply-box">
                                    <strong>Admin:</strong> <?= nl2br(htmlspecialchars($row['admin_response'])) ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-style: italic; font-size: 0.85em;">Awaiting response...</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
pg_close($conn);
require_once 'includes/footer.php'; 
?>