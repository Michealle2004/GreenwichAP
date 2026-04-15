<?php 
require_once 'includes/header.php';
if ($_SESSION['role'] !== 'teacher') { header("Location: index.php"); exit(); }
?>

<div class="page-container">
    <h1>Teacher Support Request</h1>
    <p style="margin-bottom: 25px;">Please describe the issue or support you need from the Academic Department.</p>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <p class="message success">Your request has been sent successfully!</p>
    <?php endif; ?>

    <form action="teacher_feedback_process.php" method="POST" class="request-form">
        <div class="form-group">
            <label for="subject">Subject / Category</label>
            <input type="text" name="subject" id="subject" placeholder="e.g., Classroom Equipment, Mark Entry Issue" required>
        </div>

        <div class="form-group">
            <label for="content">Detailed Content</label>
            <textarea name="content" id="content" rows="6" placeholder="Describe your problem in detail..." required></textarea>
        </div>

        <button type="submit" class="btn-submit">Send Request</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>