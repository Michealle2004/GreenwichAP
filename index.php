<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$conn = connectToDatabase();
$role = $_SESSION['role'] ?? 'student';
$full_name = $_SESSION['full_name'] ?? 'User';

$res_std = pg_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = pg_fetch_result($res_std, 0, 0);

$res_crs = pg_query($conn, "SELECT COUNT(*) FROM courses");
$total_courses = pg_fetch_result($res_crs, 0, 0);

$res_fb = pg_query($conn, "SELECT COUNT(*) FROM teacher_feedback WHERE status = 'Pending'");
$pending_feedback = pg_fetch_result($res_fb, 0, 0);

if ($role === 'admin') {
    $sql_news = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
    $res_news = pg_query($conn, $sql_news);
} else {
    $sql_news = "SELECT * FROM announcements 
                 WHERE target_role = 'all' OR target_role = $1 
                 ORDER BY created_at DESC LIMIT 5";
    $res_news = pg_query_params($conn, $sql_news, array($role));
}
$news_list = pg_fetch_all($res_news) ?: [];

pg_close($conn);
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-blue: #0091d5;
        --dark-text: #2d3748;
        --bg-color: #f7fafc;
    }

    .welcome-banner h1 {
        color: #ffffff !important;
        margin: 0; 
        font-size: 2.2em;
    }

    body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .welcome-banner {
        background: linear-gradient(135deg, #0091d5 0%, #007bb5 100%);
        padding: 40px;
        border-radius: 15px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }

    .stat-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .stat-icon {
        width: 60px; height: 60px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: #fff; margin-right: 20px;
    }

    .content-box {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        overflow: hidden;
        border: 1px solid #edf2f7;
    }
    .content-header {
        padding: 15px 20px;
        background: #fff;
        border-bottom: 1px solid #edf2f7;
        font-weight: bold;
        color: #1a365d;
        display: flex; align-items: center; justify-content: space-between;
    }
    .content-body { padding: 20px; }

    .action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .quick-link-btn {
        display: block;
        padding: 12px;
        background: #edf2f7;
        color: #2d3748;
        text-decoration: none;
        text-align: center;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .quick-link-btn:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    .news-item {
        display: flex; align-items: flex-start;
        margin-bottom: 15px; padding-bottom: 10px;
        border-bottom: 1px solid #f8fafc;
        position: relative;
    }
    .news-dot {
        width: 10px; height: 10px; border-radius: 50%;
        margin-top: 6px; margin-right: 12px; flex-shrink: 0;
    }
    .news-link {
        text-decoration: none;
        color: #2d3748;
        font-weight: 500;
        transition: color 0.2s;
        flex-grow: 1;
    }
    .news-link:hover { color: var(--primary-blue); }

    .news-admin-actions {
        display: none;
        gap: 8px;
        margin-left: 10px;
    }
    .news-item:hover .news-admin-actions {
        display: flex;
    }
    .btn-icon-admin {
        font-size: 0.85em;
        color: #cbd5e0;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-icon-admin:hover.fa-edit { color: #3182ce; }
    .btn-icon-admin:hover.fa-trash-alt { color: #e53e3e; }
</style>

<div class="page-container">
    <div class="welcome-banner">
        <h1>Welcome back, <?= htmlspecialchars($full_name) ?>!</h1>
    </div>

    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: #f6ad55;"><i class="fas fa-user-graduate"></i></div>
            <div>
                <div style="color: #718096; font-size: 0.9em;">Total Students</div>
                <div style="font-size: 1.5em; font-weight: bold;"><?= $total_students ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fc8181;"><i class="fas fa-layer-group"></i></div>
            <div>
                <div style="color: #718096; font-size: 0.9em;">Active Courses</div>
                <div style="font-size: 1.5em; font-weight: bold;"><?= $total_courses ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #68d391;"><i class="fas fa-envelope-open-text"></i></div>
            <div>
                <div style="color: #718096; font-size: 0.9em;">Support Requests</div>
                <div style="font-size: 1.5em; font-weight: bold;"><?= $pending_feedback ?></div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="content-box">
            <div class="content-header">
                <span><i class="fas fa-th-large"></i> Administrative Actions</span>
            </div>
            <div class="content-body">
                <p style="color: #666; font-size: 0.85em; margin-bottom: 15px;">Lối tắt đến các tính năng dành cho <strong><?= strtoupper($role) ?></strong>:</p>
                <div class="action-grid">
                    <?php if ($role === 'admin'): ?>
                        <a href="weekly_timetable.php" class="quick-link-btn">View Timetable</a>
                        <a href="exam_schedule.php" class="quick-link-btn">Exam Deadline</a>
                        <a href="admin/view_teacher_feedback.php" class="quick-link-btn">Teacher Support</a>
                        <a href="mark_report.php" class="quick-link-btn">View Mark Reports</a>
                        <a href="major_create.php" class="quick-link-btn">Major Create</a>
                        <a href="account_manager.php" class="quick-link-btn">Account Manager</a>
                    <?php elseif ($role === 'teacher'): ?>
                        <a href="weekly_timetable.php" class="quick-link-btn">View Timetable</a>
                        <a href="exam_schedule.php" class="quick-link-btn">Exam Deadline</a>
                        <a href="teacher_feedback.php" class="quick-link-btn">Teacher Support</a>
                        <a href="mark_report.php" class="quick-link-btn">View Mark Reports</a>
                    <?php else: // Student ?>
                        <a href="weekly_timetable.php" class="quick-link-btn">View Timetable</a>
                        <a href="exam_schedule.php" class="quick-link-btn">Exam Deadline</a>
                        <a href="paper_request.php" class="quick-link-btn">Paper Request</a>
                        <a href="mark_report.php" class="quick-link-btn">View Mark Reports</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-box">
            <div class="content-header">
                <span><i class="fas fa-bullhorn" style="color:#e53e3e;"></i> Events & Announcements</span>
                <?php if ($role === 'admin'): ?>
                    <a href="admin/create_announcement.php" title="Tạo thông báo mới" style="color: var(--primary-blue);">
                        <i class="fas fa-plus-circle"></i>
                    </a>
                <?php else: ?>
                    <i class="fas fa-ellipsis-h" style="color: #cbd5e0;"></i>
                <?php endif; ?>
            </div>
            <div class="content-body">
                <?php if (empty($news_list)): ?>
                    <p style="text-align: center; color: #a0aec0; padding: 20px;">Không có thông báo nào mới.</p>
                <?php else: ?>
                    <?php foreach ($news_list as $news): 
                        $dot_color = '#3182ce'; 
                        if ($news['target_role'] === 'student') $dot_color = '#e53e3e'; 
                        if ($news['target_role'] === 'teacher') $dot_color = '#38a169'; 
                    ?>
                        <div class="news-item" id="ann-item-<?= $news['id'] ?>">
                            <div class="news-dot" style="background: <?= $dot_color ?>;"></div>
                            <div style="flex-grow: 1;">
                                <a href="<?= htmlspecialchars($news['link'] ?: '#') ?>" 
                                   target="<?= $news['link'] ? '_blank' : '_self' ?>" 
                                   class="news-link">
                                    <?= htmlspecialchars($news['title']) ?>
                                    <?php if($role === 'admin'): ?>
                                        <small style="color:#cbd5e0; margin-left:5px;">(To: <?= $news['target_role'] ?>)</small>
                                    <?php endif; ?>
                                </a>
                                <span style="display: block; font-size: 0.8em; color: #a0aec0; margin-top: 4px;">
                                    <?= date('d/m/Y', strtotime($news['created_at'])) ?>
                                </span>
                            </div>
                            
                            <?php if ($role === 'admin'): ?>
                                <div class="news-admin-actions">
                                    <a href="admin/edit_announcement.php?id=<?= $news['id'] ?>" title="Sửa">
                                        <i class="fas fa-edit btn-icon-admin"></i>
                                    </a>
                                    <i class="fas fa-trash-alt btn-icon-admin" title="Xóa" onclick="deleteAnn(<?= $news['id'] ?>)"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="view_all_announcements.php" style="color: var(--primary-blue); font-size: 0.9em; text-decoration: none; font-weight: bold; display: block; margin-top: 10px;">Xem tất cả thông báo →</a>
            </div>
        </div>
    </div>
</div>

<script>
function deleteAnn(id) {
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Thông báo này sẽ biến mất vĩnh viễn!",
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
                        const item = document.getElementById(`ann-item-${id}`);
                        item.style.transition = '0.3s';
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(20px)';
                        setTimeout(() => item.remove(), 300);
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

<?php 
require_once 'includes/footer.php'; 
?>