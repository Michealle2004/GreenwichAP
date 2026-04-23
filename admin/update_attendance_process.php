<?php
require_once '../admin_check.php';
require_once '../includes/db_connect.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$expects_json = $is_ajax || strpos($accept_header, 'application/json') !== false;

if ($expects_json) {
    header('Content-Type: application/json');
}

$search_id = trim($_POST['student_user_id'] ?? '');
$redirect_url = '../attendance_report.php';
if ($search_id !== '') {
    $redirect_url .= '?student_user_id=' . urlencode($search_id);
}

$respond = function ($status, $message) use ($expects_json, $redirect_url) {
    if ($expects_json) {
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header('Location: ' . $redirect_url . $separator . 'status=' . urlencode($status) . '&message=' . urlencode($message));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['attendance_id'], $_POST['status'])) {
    $respond('error', 'Invalid request method or missing parameters.');
}

$attendance_id = $_POST['attendance_id'];
$status = strtolower(trim($_POST['status']));

if (!in_array($status, ['present', 'absent', 'not_yet'], true)) {
    $respond('error', 'Invalid attendance status.');
}

$conn = connectToDatabase();
$sql = 'UPDATE attendance SET status = $1 WHERE attendance_id = $2';
pg_prepare($conn, 'update_attendance', $sql);
$result = pg_execute($conn, 'update_attendance', [$status, $attendance_id]);

if ($result) {
    $respond('success', 'Attendance updated successfully!');
}

$error = pg_last_error($conn) ?: 'Unknown database error.';
$respond('error', 'Error updating attendance: ' . $error);