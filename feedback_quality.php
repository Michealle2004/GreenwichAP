<?php 
require_once 'includes/header.php';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<title>Feedback Quality - Greenwich AP</title>

<div class="page-container">
    <h1 style="margin-bottom: 20px;">Feedback Quality</h1>
    
    <?php if ($is_admin): ?>
        <div class="admin-view-section" style="text-align: center; padding: 40px 20px; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ccc; margin-top: 20px;">
            <p style="font-size: 1.1em; color: #555;">You are logged in as <strong>Admin</strong>. You can review all feedback submitted by students below.</p>
            <br>
            <a href="admin/view_feedback.php" class="btn-submit" style="background-color: #007bff; display: inline-block; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 4px; color: white;">
                View All Student Feedback
            </a>
        </div>

    <?php else: ?>
        <p style="margin-bottom: 25px;">We highly value your feedback. Please let us know your thoughts to help us improve.</p>

        <?php 
            if (isset($_GET['status'])) {
                if ($_GET['status'] == 'success') {
                    echo '<p class="message success">Your feedback has been submitted successfully. Thank you!</p>';
                } elseif ($_GET['status'] == 'error') {
                    echo '<p class="message error">Failed to submit feedback. Please try again.</p>';
                }
            }
        ?>

        <form action="feedback_process.php" method="POST" class="request-form">
            <div class="form-group">
                <label for="feedback_target">Subject / Department Name</label>
                <input type="text" name="feedback_target" id="feedback_target" placeholder="e.g., PRO192 Programming or Academic Department" required>
            </div>

            <div class="form-group">
                <label for="person_in_charge">Person in Charge / Lecturer (Optional)</label>
                <input type="text" name="person_in_charge" id="person_in_charge" placeholder="e.g., Le Thi C">
            </div>

            <div class="form-group">
                <label for="reason">Feedback Content</label>
                <textarea name="reason" id="reason" rows="6" placeholder="Please provide your detailed feedback here..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">Submit Feedback</button>
        </form>
    <?php endif; ?>
</div>

<?php 
require_once 'includes/footer.php'; 
?>