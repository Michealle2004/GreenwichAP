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
                    <select name="role" id="modal-role" required onchange="toggleFormLogic()">
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

            <div id="parent-option-container" style="margin-top: 20px; padding: 15px; background: #eef2f7; border-radius: 6px; border-left: 5px solid #003366;">
                <label style="display: flex; align-items: center; cursor: pointer; font-weight: bold; color: #003366;">
                    <input type="checkbox" name="create_parent" id="create_parent" value="1" style="width: 18px; height: 18px; margin-right: 10px;">
                    Manage Parent Account for this student
                </label>
                <div id="parent-name-group" style="margin-top: 10px; display: none;">
                    <label style="font-size: 0.9em;">Parent Full Name</label>
                    <input type="text" name="parent_name" id="modal-parent-name" placeholder="Enter parent's name...">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-submit" style="background: #28a745; width: auto; margin: 0; padding: 10px 40px;">Save Account</button>
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
const parentCheckbox = document.getElementById('create_parent');
const parentNameGroup = document.getElementById('parent-name-group');
const statusMessage = document.getElementById('status-message');

function showStatusMessage(message, type = 'success') {
    statusMessage.textContent = message;
    statusMessage.style.display = 'block';
    statusMessage.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
    statusMessage.style.color = type === 'success' ? '#155724' : '#721c24';
    statusMessage.style.border = `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`;
}

function toggleFormLogic() {
    const role = document.getElementById('modal-role').value;
    
    // Major logic
    const majorSelect = document.getElementById('modal-major');
    majorSelect.disabled = (role !== 'student');
    document.getElementById('major-container').style.opacity = (role === 'student') ? '1' : '0.5';

    // Parent logic: Hiện cho cả Add và Edit nếu là Student
    const parentContainer = document.getElementById('parent-option-container');
    parentContainer.style.display = (role === 'student') ? 'block' : 'none';
}

parentCheckbox.addEventListener('change', function() {
    parentNameGroup.style.display = this.checked ? 'block' : 'none';
});

function handleEdit(btn) {
    resetForm();
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
    
    toggleFormLogic();
    document.getElementById('form-anchor').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    formTitle.innerText = 'Add New Account';
    document.getElementById('form-action').value = 'add';
    document.getElementById('pass-note').innerText = '';
    userForm.reset();
    cancelBtn.style.display = 'none';
    parentNameGroup.style.display = 'none';
    toggleFormLogic();
}

function deleteUser(id) {
    if (confirm('Are you sure? Delete user and their associated parent account?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('db_id', id);
        fetch('/GreenwichAP/admin/manage_user_process.php', { method: 'POST', body: fd })
        .then(async res => {
            const raw = await res.text();
            let data;

            try {
                data = JSON.parse(raw);
            } catch (parseError) {
                throw new Error(raw || 'Unexpected server response.');
            }

            if (!res.ok || data.status !== 'success') {
                throw new Error(data.message || 'Delete failed.');
            }

            showStatusMessage('Account deleted successfully. Reloading page...', 'success');
            setTimeout(() => location.reload(), 800);
        })
        .catch(err => {
            showStatusMessage(err.message || 'Server error.', 'error');
        });
    }
}

userForm.onsubmit = function(e) {
    e.preventDefault();
    const submitBtn = this.querySelector('button[type="submit"]');
    const formData = new FormData(this);

    if (submitBtn) submitBtn.disabled = true;

    fetch(this.getAttribute('action'), { method: 'POST', body: formData })
    .then(async res => {
        const raw = await res.text();
        let data;

        try {
            data = JSON.parse(raw);
        } catch (parseError) {
            throw new Error(raw || 'Unexpected server response.');
        }

        if (!res.ok || data.status !== 'success') {
            throw new Error(data.message || 'Request failed.');
        }

        showStatusMessage('Account saved successfully. Reloading page...', 'success');
        setTimeout(() => location.reload(), 800);
    })
    .catch(err => {
        showStatusMessage(err.message || 'Server error.', 'error');
    })
    .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
    });
};

toggleFormLogic();
</script>