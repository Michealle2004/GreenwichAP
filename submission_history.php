<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$conn = connectToDatabase();
$requests = []; 
$search_id = '';

if ($is_admin) {
    $sql = 'SELECT r.request_id, r.paper_type, r.request_date, r.status, r.response, r.file_path, u.user_id, u.full_name 
            FROM paper_requests r
            JOIN users u ON r.student_id = u.id';
    if (!empty($_GET['student_user_id'])) {
        $search_id = trim($_GET['student_user_id']);
        $sql .= ' WHERE u.user_id = $1 ORDER BY r.request_date DESC';
        pg_prepare($conn, "admin_search", $sql);
        $result = pg_execute($conn, "admin_search", array($search_id));
    } else {
        $sql .= ' ORDER BY r.request_date DESC';
        pg_prepare($conn, "get_all", $sql);
        $result = pg_execute($conn, "get_all", array());
    }
} else {
    $sql = 'SELECT request_id, paper_type, request_date, status, response, file_path 
            FROM paper_requests WHERE student_id = $1 ORDER BY request_date DESC';
    pg_prepare($conn, "std_req", $sql);
    $result = pg_execute($conn, "std_req", array((int)$_SESSION['user_id']));
}

if ($result) {
    $data = pg_fetch_all($result);
    $requests = $data ?: [];
}
pg_close($conn);
?>

<title>Submission History - Admin</title>

<div class="page-container">
    <h1>Submission History</h1>
    
    <?php if ($is_admin): ?>
        <form method="GET" class="request-form" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="student_user_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="Enter Student ID...">
            <button type="submit" class="btn-submit" style="width: auto;">Search</button>
        </form>
    <?php endif; ?>

    <div class="history-section">
        <table class="report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if($is_admin) echo "<th>Student</th>"; ?>
                    <th>Paper Type</th>
                    <th>Date</th>
                    <th>File</th>
                    <th>Status</th>
                    <th>Admin Response</th>
                    <?php if($is_admin) echo "<th>Actions</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?php echo $r['request_id']; ?></td>
                        <?php if($is_admin) echo "<td>".$r['full_name']." (".$r['user_id'].")</td>"; ?>
                        <td><?php echo htmlspecialchars($r['paper_type']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($r['request_date'])); ?></td>
                        <td><?php if($r['file_path']) echo "<a href='".$r['file_path']."' download>Download</a>"; ?></td>
                        
                        <td>
                            <span class="status-badge status-<?php echo strtolower($r['status']); ?>">
                                <?php echo htmlspecialchars($r['status']); ?>
                            </span>
                        </td>

                        <td><?php echo nl2br(htmlspecialchars($r['response'] ?? 'Waiting...')); ?></td>

                        <?php if ($is_admin): ?>
                            <td>
                                <form action="update_request.php" method="POST" style="display: flex; flex-direction: column; gap: 5px;">
                                    <input type="hidden" name="request_id" value="<?php echo $r['request_id']; ?>">
                                    <select name="status" style="padding: 5px;">
                                        <option value="Pending" <?php if($r['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Approved" <?php if($r['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                        <option value="Rejected" <?php if($r['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
                                    </select>
                                    <textarea name="response" placeholder="Type response..." rows="2" style="padding: 5px;"><?php echo htmlspecialchars($r['response'] ?? ''); ?></textarea>
                                    <button type="submit" class="btn-submit" style="font-size: 0.8em; padding: 5px;">Update</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>