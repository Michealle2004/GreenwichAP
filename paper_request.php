<?php 
require_once 'includes/header.php'; 
?>

<title>Paper Request - Greenwich AP</title>

<div class="page-container">
    <h1>Paper Request</h1>
    <p>Please fill out the form below to request a paper. All fields are required.</p>

    <?php 
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'success') {
                echo '<p class="message success">Your request has been submitted successfully!</p>';
            } elseif ($_GET['status'] == 'error') {
                $msg = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error';
                echo '<p class="message error">Failed to submit request. Error: ' . $msg . '</p>';
            }
        }
    ?>

    <form action="paper_request_process.php" method="POST" enctype="multipart/form-data" class="request-form">
        <div class="form-group">
            <label for="paper_type">Type of Paper</label>
            <select name="paper_type" id="paper_type" required>
                <option value="">-- Choose a type --</option>
                <option value="Interruption of study">Interruption of study</option>
                <option value="Hoan nghia vu quan su">Hoãn nghĩa vụ quân sự</option>
                <option value="Giay gioi thieu">Giấy giới thiệu</option>
                <option value="Xac nhan sinh vien (tieng Anh)">Xác nhận sinh viên (English)</option>
                <option value="Xac nhan sinh vien (tieng Viet)">Xác nhận sinh viên (Vietnamese)</option>
                <option value="Cap the SV tam thoi">Cấp thẻ SV tạm thời</option>
                <option value="Giay xac nhan vay von">Giấy xác nhận vay vốn</option>
                <option value="Cap lai the SV">Cấp lại thẻ SV</option>
                <option value="Interim academic transcript">Interim academic transcript</option>
                <option value="Recheck of your mark">Recheck of your mark</option>
                <option value="Other Requests">Other Requests</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reason">Reason for Request</label>
            <textarea name="reason" id="reason" rows="4" required placeholder="Describe why you need this paper..."></textarea>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" name="quantity" id="quantity" min="1" value="1" required>
        </div>

        <div class="form-group">
            <label>Submit File (PDF, DOCX, ZIP, RAR, JPG, PNG)</label>
            <p class="form-instruction">
                Download the template, fill it, and upload:
                <a href="templates/Paper Templates.rar" download class="download-link">Download Template</a>
            </p>
            <input type="file" name="attachment_file" id="attachment_file" required>
        </div>

        <button type="submit" class="btn-submit">Submit Request</button>
    </form>
</div>

<?php 
require_once 'includes/footer.php'; 
?>