<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$role = $_SESSION['role'] ?? 'student';

if ($role === 'admin') {
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $result = pg_query($conn, $sql);
} else {
    $sql = "SELECT * FROM announcements 
            WHERE target_role = 'all' OR target_role = $1 
            ORDER BY created_at DESC";
    $result = pg_query_params($conn, $sql, array($role));
}

$announcements = pg_fetch_all($result) ?: [];

pg_close($conn);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .announcement-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 30px;
        border-bottom: 2px solid #edf2f7;
        padding-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .btn-create-new {
        background-color: #0091d5;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px rgba(0, 145, 213, 0.2);
    }

    .btn-create-new:hover {
        background-color: #007bb5;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 145, 213, 0.3);
    }

    .announcement-item {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border-left: 5px solid #0091d5;
        transition: all 0.3s ease;
        position: relative;
    }

    .announcement-item:hover {
        transform: translateY(-3px);
    }

    .ann-meta {
        font-size: 0.85em;
        color: #a0aec0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ann-title {
        font-size: 1.4em;
        font-weight: bold;
        color: #1a365d;
        text-decoration: none;
        margin-bottom: 12px;
        display: block;
    }

    .ann-content {
        color: #4a5568;
        line-height: 1.7;
        white-space: pre-line;
        margin-bottom: 15px;
    }

    .badge-target {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75em;
        font-weight: 800;
        text-transform: uppercase;
    }

    .target-all { background: #e2f2ff; color: #3182ce; }
    .target-student { background: #fff5f5; color: #e53e3e; }
    .target-teacher { background: #f0fff4; color: #38a169; }

    .admin-controls { display: flex; gap: 10px; }
    .btn-action {
        width: 35px; height: 35px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; transition: 0.2s; border: none; cursor: pointer;
    }
    .btn-edit { background: #ebf4ff; color: #3182ce; }
    .btn-edit:hover { background: #3182ce; color: #fff; }
    .btn-delete { background: #fff5f5; color: #e53e3e; }
    .btn-delete:hover { background: #e53e3e; color: #fff; }
</style>

<div class="announcement-container">
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 15px;">
             <a href="index.php" style="text-decoration: none; color: #718096;" title="Back to Dashboard">
                <i class="fas fa-arrow-left fa-lg"></i>
            </a>
            <h1 style="color: #1a365d; margin: 0;">All Announcements</h1>
        </div>

        <?php if ($role === 'admin'): ?>
            <a href="admin/create_announcement.php" class="btn-create-new">
                <i class="fas fa-plus-circle"></i> Create New Announcement
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($announcements)): ?>
        <div style="text-align: center; padding: 60px; background: #fff; border-radius: 12px;">
            <p style="color: #a0aec0; font-size: 1.1em;">No announcements available.</p>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
            <div class="announcement-item" id="ann-card-<?= $ann['id'] ?>">
                <div class="ann-meta">
                    <span class="badge-target target-<?= $ann['target_role'] ?>">
                        <?= $ann['target_role'] ?>
                    </span>
                    <span><i class="far fa-calendar-alt"></i> <?= date('d M, Y', strtotime($ann['created_at'])) ?></span>
                    
                    <?php if ($role === 'admin'): ?>
                        <div class="admin-controls" style="margin-left: auto;">
                            <a href="admin/edit_announcement.php?id=<?= $ann['id'] ?>" class="btn-action btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $ann['id'] ?>)" class="btn-action btn-delete" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?= htmlspecialchars($ann['link'] ?: '#') ?>" class="ann-title" <?= $ann['link'] ? 'target="_blank"' : '' ?>>
                    <?= htmlspecialchars($ann['title']) ?>
                </a>

                <?php if (!empty($ann['content'])): ?>
                    <div class="ann-content"><?= htmlspecialchars($ann['content']) ?></div>
                <?php endif; ?>

                <?php if ($ann['link']): ?>
                    <a href="<?= htmlspecialchars($ann['link']) ?>" target="_blank" style="color: #0091d5; font-size: 0.9em; font-weight: bold; text-decoration: none;">
                        Learn More <i class="fas fa-external-link-alt" style="font-size: 0.8em;"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Thông báo này sẽ bị xóa vĩnh viễn khỏi hệ thống!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: '#718096',
        confirmButtonText: 'Đồng ý xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`admin/delete_announcement.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const card = document.getElementById(`ann-card-${id}`);
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => card.remove(), 300);
                        
                        Swal.fire({
                            title: 'Đã xóa!',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Lỗi!', 'Không thể xóa thông báo này.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire('Lỗi!', 'Đã xảy ra lỗi hệ thống.', 'error');
                });
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>