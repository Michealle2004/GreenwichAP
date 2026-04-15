<?php 
require_once 'includes/header.php'; 
require_once 'admin_check.php'; 
require_once 'includes/db_connect.php';
?>

<link rel="stylesheet" href="/GreenwichAP/css/account_manager.css">

<?php
$conn = connectToDatabase();
$campuses = pg_fetch_all(pg_query($conn, "SELECT * FROM campuses ORDER BY campus_name")) ?: [];
$majors = pg_fetch_all(pg_query($conn, "SELECT * FROM majors ORDER BY major_name")) ?: [];

$search = $_GET['search'] ?? '';
$sql_users = "SELECT u.*, c.campus_name, m.major_name 
              FROM users u 
              JOIN campuses c ON u.campus_id = c.campus_id 
              LEFT JOIN majors m ON u.major_id = m.major_id";
if (!empty($search)) {
    $search_safe = pg_escape_string($conn, $search);
    $sql_users .= " WHERE u.user_id ILIKE '%$search_safe%' OR u.full_name ILIKE '%$search_safe%'";
}
$sql_users .= " ORDER BY u.role, u.user_id";
$users = pg_fetch_all(pg_query($conn, $sql_users)) ?: [];
pg_close($conn);
?>

<div class="page-container">
    <h1>Account Manager</h1>
    
    <div id="status-message" class="message" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px;"></div>

    <div class="search-bar-container" style="margin-bottom: 20px;">
        <form method="GET" action="account_manager.php" style="display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search by ID or Name..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn-submit" style="width: auto; margin-top: 0; padding: 0 30px; background-color: #007bff;">Search</button>
        </form>
    </div>

    <div id="form-anchor" class="form-container" style="background: #f9f9f9; padding: 25px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
        <h2 id="form-title" style="margin-top: 0; color: #003366;">Add New Account</h2>
        <form id="user-form" action="/GreenwichAP/admin/manage_user_process.php" method="POST">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="db_id" id="form-db-id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" name="user_id" id="modal-user-id" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="modal-full-name" required>
                </div>
                <div class="form-group">
                    <label>Password <small id="pass-note" style="color:red"></small></label>
                    <input type="password" name="password" id="modal-password">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="modal-role" required onchange="toggleMajor()">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Campus</label>
                    <select name="campus_id" id="modal-campus" required>
                        <?php foreach ($campuses as $c): ?>
                            <option value="<?= $c['campus_id'] ?>"><?= htmlspecialchars($c['campus_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="major-container">
                    <label>Major</label>
                    <select name="major_id" id="modal-major">
                        <option value="">-- No Major --</option>
                        <?php foreach ($majors as $m): ?>
                            <option value="<?= $m['major_id'] ?>"><?= htmlspecialchars($m['major_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-submit" style="background: #28a745; width: auto; margin: 0; padding: 10px 40px;">Save</button>
                <button type="button" onclick="resetForm()" id="cancel-btn" class="btn-submit" style="background: #6c757d; width: auto; margin: 0; padding: 10px 40px; display: none;">Cancel Edit</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Campus</th>
                    <th>Major</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['user_id']) ?></strong></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td style="text-align: center;">
                        <span class="role-badge role-<?= strtolower($user['role']) ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($user['campus_name']) ?></td>
                    <td><?= htmlspecialchars($user['major_name'] ?? 'N/A') ?></td>
                    <td>
                        <button type="button" class="btn-edit" 
                                data-id="<?= $user['id'] ?>"
                                data-userid="<?= htmlspecialchars($user['user_id']) ?>"
                                data-name="<?= htmlspecialchars($user['full_name']) ?>"
                                data-role="<?= $user['role'] ?>"
                                data-campus="<?= $user['campus_id'] ?>"
                                data-major="<?= $user['major_id'] ?? '' ?>"
                                onclick="handleEdit(this)">✏️ Edit</button>
                                
                        <button type="button" class="btn-delete" onclick="deleteUser(<?= $user['id'] ?>)">🗑️ Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const userForm = document.getElementById('user-form');
const formTitle = document.getElementById('form-title');
const cancelBtn = document.getElementById('cancel-btn');

function toggleMajor() {
    const role = document.getElementById('modal-role').value;
    const majorSelect = document.getElementById('modal-major');
    majorSelect.disabled = (role !== 'student');
    document.getElementById('major-container').style.opacity = (role === 'student') ? '1' : '0.5';
}

function handleEdit(btn) {
    formTitle.innerText = 'Edit Account: ' + btn.getAttribute('data-userid');
    document.getElementById('form-action').value = 'edit';
    
    document.getElementById('form-db-id').value = btn.getAttribute('data-id');
    document.getElementById('modal-user-id').value = btn.getAttribute('data-userid');
    document.getElementById('modal-full-name').value = btn.getAttribute('data-name');
    document.getElementById('modal-role').value = btn.getAttribute('data-role');
    document.getElementById('modal-campus').value = btn.getAttribute('data-campus');
    document.getElementById('modal-major').value = btn.getAttribute('data-major');
    
    document.getElementById('pass-note').innerText = '(Blank to keep current)';
    cancelBtn.style.display = 'inline-block';
    
    toggleMajor();
    document.getElementById('form-anchor').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    formTitle.innerText = 'Add New Account';
    document.getElementById('form-action').value = 'add';
    document.getElementById('pass-note').innerText = '';
    userForm.reset();
    cancelBtn.style.display = 'none';
    toggleMajor();
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this account?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('db_id', id);
        fetch('/GreenwichAP/admin/manage_user_process.php', { method: 'POST', body: fd })
        .then(res => res.json()).then(data => {
            if(data.status === 'success') location.reload();
            else alert(data.message);
        });
    }
}

userForm.onsubmit = function(e) {
    e.preventDefault();
    const url = this.getAttribute('action');
    const formData = new FormData(this);

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.text()) 
    .then(text => {
        console.log("Raw Server Response:", text); 
        try {
            const data = JSON.parse(text);
            if(data.status === 'success') {
                location.reload();
            } else {
                alert("Server Error: " + data.message);
            }
        } catch(err) {
            alert("System error. Open Console (F12) to see raw response.");
        }
    })
    .catch(err => alert("Connection lost."));
};

toggleMajor();
</script>

<?php require_once 'includes/footer.php'; ?>