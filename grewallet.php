<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$role = $_SESSION['role'] ?? 'student';
$user_id = $_SESSION['id'] ?? ($_SESSION['user_id'] ?? null);

if ($role === 'teacher') {
    echo "<div class='container mt-5'><div class='alert alert-warning'>Teachers do not have access to GreWallet.</div></div>";
    require_once 'includes/footer.php'; exit;
}

$balance = 0;
$res_bal = @pg_query_params($conn, "SELECT wallet_balance FROM users WHERE id = $1", [$user_id]);
if ($res_bal && pg_num_rows($res_bal) > 0) {
    $balance = pg_fetch_result($res_bal, 0, 0);
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    
    .balance-card {
        background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
        color: white; border-radius: 20px; padding: 40px;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
    }
    
    .content-card { 
        background: white; border-radius: 15px; border: none; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; 
    }
    
    .btn-submit-wallet {
        background-color: #2563eb;
        color: white; border: none; border-radius: 10px;
        padding: 15px 20px; font-weight: 700; width: 100%;
        display: block; cursor: pointer; transition: 0.3s;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .btn-submit-wallet:hover { background-color: #1d4ed8; transform: translateY(-2px); }

    .form-control-custom {
        border-radius: 10px; padding: 12px 15px;
        border: 2px solid #e2e8f0; width: 100%; margin-bottom: 20px;
        font-size: 1.1rem; font-weight: 600;
    }

    .table thead th { background-color: #f1f5f9; text-transform: uppercase; font-size: 0.75rem; color: #64748b; padding: 15px; border:none; }
    .table td { vertical-align: middle; padding: 15px; border-top: 1px solid #f1f5f9; }
    
    .btn-admin { border-radius: 8px; font-weight: 600; padding: 8px 15px; border: none; cursor: pointer; color: white; transition: 0.2s; }
    .btn-approve { background-color: #10b981; margin-right: 5px; }
    .btn-approve:hover { background-color: #059669; }
    .btn-reject { background-color: #ef4444; }
    .btn-reject:hover { background-color: #dc2626; }

    .currency-unit { font-size: 1.5rem; margin-left: 5px; opacity: 0.8; }
</style>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="balance-card mb-4">
                <small class="text-uppercase" style="opacity: 0.8; letter-spacing: 1px;">Số dư tài khoản</small>
                <h1 class="font-weight-bold mt-2">
                    <?= number_format($balance, 0, ',', '.') ?><span class="currency-unit">VNĐ</span>
                </h1>
                <div class="mt-3 small"><i class="fas fa-check-circle mr-1"></i> Tài khoản GreWallet đã xác thực</div>
            </div>

            <?php if ($role === 'student'): ?>
            <div class="content-card">
                <h5 class="font-weight-bold mb-4"><i class="fas fa-wallet text-primary mr-2"></i> Yêu cầu nạp tiền</h5>
                <form id="request-form">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-muted mb-2">Số tiền muốn nạp (VNĐ)</label>
                        <input type="number" id="req_amount" class="form-control-custom" placeholder="Ví dụ: 50000" min="1000" step="1000" required>
                    </div>
                    <button type="submit" class="btn-submit-wallet">
                        <i class="fas fa-paper-plane mr-2"></i> Gửi yêu cầu nạp
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-7">
            <div class="content-card">
                <?php if ($role === 'admin'): ?>
                    <h5 class="font-weight-bold mb-4"><i class="fas fa-tasks text-primary mr-2"></i> Danh sách chờ duyệt</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Sinh viên</th><th>Số tiền</th><th class="text-center">Thao tác</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT r.*, u.full_name FROM wallet_requests r JOIN users u ON r.student_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC";
                                $res = @pg_query($conn, $sql);
                                if ($res && pg_num_rows($res) > 0):
                                    while($row = pg_fetch_assoc($res)): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                                            <td class="text-primary font-weight-bold">
                                                <?= number_format($row['amount'], 0, ',', '.') ?> đ
                                            </td>
                                            <td class="text-center">
                                                <button class="btn-admin btn-approve" onclick="approve(<?= $row['request_id'] ?>, 'approved')">Duyệt</button>
                                                <button class="btn-admin btn-reject" onclick="approve(<?= $row['request_id'] ?>, 'rejected')">Từ chối</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; 
                                else: echo "<tr><td colspan='3' class='text-center py-4 text-muted'>Không có yêu cầu nào.</td></tr>"; endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <h5 class="font-weight-bold mb-4"><i class="fas fa-history text-primary mr-2"></i> Lịch sử giao dịch</h5>
                    <?php
                    $sql_h = "SELECT * FROM wallet_transactions WHERE user_id = $1 ORDER BY created_at DESC LIMIT 5";
                    $res_h = @pg_query_params($conn, $sql_h, [$user_id]);
                    if ($res_h && pg_num_rows($res_h) > 0):
                        while($h = pg_fetch_assoc($res_h)): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <div>
                                    <div class="font-weight-bold text-dark text-capitalize"><?= htmlspecialchars($h['description']) ?></div>
                                    <small class="text-muted"><?= date('H:i - d/m/Y', strtotime($h['created_at'])) ?></small>
                                </div>
                                <div class="text-success font-weight-bold">
                                    +<?= number_format($h['amount'], 0, ',', '.') ?> đ
                                </div>
                            </div>
                        <?php endwhile;
                    else: echo "<p class='text-muted text-center py-4'>Chưa có lịch sử giao dịch.</p>"; endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('request-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const amt = document.getElementById('req_amount').value;
    const fd = new FormData();
    fd.append('amount', amt);

    fetch('./wallet_request_process.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(data => {
        Swal.fire({
            title: data.status === 'success' ? 'Thành công' : 'Lỗi',
            text: data.message,
            icon: data.status,
            confirmButtonColor: '#2563eb'
        }).then(() => { if(data.status === 'success') location.reload(); });
    });
});

function approve(id, status) {
    const fd = new FormData();
    fd.append('request_id', id);
    fd.append('status', status);

    fetch('./wallet_approve_process.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(data => {
        if(data.status === 'success') location.reload();
        else Swal.fire('Lỗi', data.message, 'error');
    });
}
</script>
<?php require_once 'includes/footer.php'; ?>