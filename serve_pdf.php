<?php
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Error: No file specified.');
}
$filename = basename($_GET['file']); 

$pdf_directory = __DIR__ . '/regulations/';
$filepath = $pdf_directory . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Error: File not found.');
}

header('Content-Type: application/pdf'); 

header('Content-Disposition: inline; filename="' . $filename . '"'); 

header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

ob_clean();
flush();
readfile($filepath);
exit;
?>