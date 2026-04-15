<?php
require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="page-container">
    <div class="content-box" style="max-width: 600px; margin: 40px auto; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div class="content-header" style="background: #fff; border-bottom: 2px solid #f1f5f9; padding: 20px;">
            <span style="font-size: 1.2em; color: #1e293b;"><i class="fas fa-bullhorn" style="color: #e53e3e; margin-right: 10px;"></i> Tạo thông báo nhanh</span>
        </div>
        <div class="content-body" style="padding: 25px;">
            <form id="announcementForm">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Tiêu đề (Bắt buộc):</label>
                    <input type="text" name="title" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="VD: Lịch nghỉ lễ 30/4..." required>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Nội dung chi tiết (Không bắt buộc):</label>
                    <textarea name="content" style="width: 100%; height: 120px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;" placeholder="Nhập nội dung thông báo (có thể để trống)..."></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Link liên kết (nếu có):</label>
                    <input type="url" name="link" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="https://greenwich.edu.vn/...">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Đối tượng hiển thị:</label>
                    <select name="target_role" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff;">
                        <option value="all">Tất cả (All users)</option>
                        <option value="student">Chỉ Sinh viên (Students)</option>
                        <option value="teacher">Chỉ Giáo viên (Teachers)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" id="submitBtn" style="flex: 2; background: #0091d5; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-paper-plane"></i> Phát hành ngay
                    </button>
                    <a href="../index.php" style="flex: 1; text-align: center; background: #f1f5f9; color: #475569; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold;">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('announcementForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

    const formData = new FormData(this);

    fetch('process_announcement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Đã phát hành!',
                text: 'Thông báo mới đã được cập nhật lên hệ thống.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                document.getElementById('announcementForm').reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Phát hành ngay';
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Thất bại', text: data.message });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Phát hành ngay';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>