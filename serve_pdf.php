<?php

// serve_pdf.php - Xử lý việc phục vụ các file PDF từ thư mục regulations
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);    
    die('Error: No file specified.');
}
// Lấy tên file từ tham số GET và đảm bảo chỉ lấy tên file, không bao gồm đường dẫn để tránh lỗ hổng path traversal
$filename = basename($_GET['file']); 

$pdf_directory = __DIR__ . '/regulations/';
$filepath = $pdf_directory . $filename;

// Kiểm tra nếu file tồn tại, nếu không trả về lỗi 404
if (!file_exists($filepath)) {
    http_response_code(404);
    die('Error: File not found.');
}

// Phục vụ file PDF với header thích hợp để hiển thị trực tiếp trên trình duyệt

header('Content-Type: application/pdf'); 

header('Content-Disposition: inline; filename="' . $filename . '"'); 

header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

ob_clean();
flush();
readfile($filepath);
exit;
?>