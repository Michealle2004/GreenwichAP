<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

$conn = connectToDatabase();
$id = (int)$_GET['id'];
$res = pg_query_params($conn, "SELECT * FROM announcements WHERE id = $1", [$id]);
$ann = pg_fetch_assoc($res);
?>

<div class="page-container">
    <div class="content-box" style="max-width: 600px; margin: 40px auto; padding: 25px;">
        <h2 style="margin-bottom:20px; color:#1a365d;"><i class="fas fa-edit"></i> Chỉnh sửa thông báo</h2>
        <form id="editForm">
            <input type="hidden" name="id" value="<?= $ann['id'] ?>">
            
            <div style="margin-bottom: 15px;">
                <label>Tiêu đề:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($ann['title']) ?>" style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e0;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Nội dung:</label>
                <textarea name="content" style="width:100%; height:120px; padding:10px; border-radius:6px; border:1px solid #cbd5e0;"><?= htmlspecialchars($ann['content']) ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Link:</label>
                <input type="url" name="link" value="<?= htmlspecialchars($ann['link']) ?>" style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e0;">
            </div>

            <div style="margin-bottom: 20px;">
                <label>Đối tượng:</label>
                <select name="target_role" style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e0;">
                    <option value="all" <?= $ann['target_role'] == 'all' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="student" <?= $ann['target_role'] == 'student' ? 'selected' : '' ?>>Sinh viên</option>
                    <option value="teacher" <?= $ann['target_role'] == 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                </select>
            </div>

            <button type="submit" style="background:#0091d5; color:#white; padding:12px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Cập nhật</button>
            <a href="../view_all_announcements.php" style="margin-left:10px; color:#718096; text-decoration:none;">Quay lại</a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('update_announcement.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('Thành công!', 'Đã cập nhật thay đổi.', 'success').then(() => {
                window.location.href = '../view_all_announcements.php';
            });
        }
    });
});
</script>