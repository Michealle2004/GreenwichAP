<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (ob_get_length()) ob_clean();

require_once '../includes/db_connect.php';

$conn = connectToDatabase();
$response = ['status' => 'error', 'message' => 'Unexpected server error'];

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $user_id    = trim($_POST['user_id'] ?? '');
        $full_name  = trim($_POST['full_name'] ?? '');
        $role       = $_POST['role'] ?? '';
        $campus_id  = $_POST['campus_id'] ?? '';
        $major_id   = !empty($_POST['major_id']) ? $_POST['major_id'] : null;
        $password   = $_POST['password'] ?? '';

        if (empty($user_id) || empty($full_name) || empty($role) || empty($campus_id)) {
            throw new Exception("Missing required information.");
        }

        if ($action === 'add') {
    if (empty($password)) throw new Exception("Password is required.");
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $email = $user_id . "@greenwich.edu.vn";

    $sql = "INSERT INTO users (user_id, full_name, password, role, campus_id, major_id, email) 
            VALUES ($1, $2, $3, $4, $5, $6, $7)";
    $params = [$user_id, $full_name, $hashed_password, $role, $campus_id, $major_id, $email];
} else {
    $db_id = $_POST['db_id'] ?? null;
    
    $email = $user_id . "@greenwich.edu.vn"; 

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET user_id=$1, full_name=$2, password=$3, role=$4, campus_id=$5, major_id=$6, email=$7 WHERE id=$8";
        $params = [$user_id, $full_name, $hashed_password, $role, $campus_id, $major_id, $email, $db_id];
    } else {
        $sql = "UPDATE users SET user_id=$1, full_name=$2, role=$3, campus_id=$4, major_id=$5, email=$6 WHERE id=$7";
        $params = [$user_id, $full_name, $role, $campus_id, $major_id, $email, $db_id];
    }
}

        $res = pg_query_params($conn, $sql, $params);
        if ($res) {
            $response = ['status' => 'success'];
        } else {
            $error = pg_last_error($conn);
            if (strpos($error, 'duplicate key') !== false) {
                throw new Exception("This User ID already exists!");
            }
            throw new Exception("Database error: " . $error);
        }
    } 
    elseif ($action === 'delete') {
        $db_id = $_POST['db_id'] ?? null;
        $res = pg_query_params($conn, "DELETE FROM users WHERE id = $1", [$db_id]);
        if ($res) $response = ['status' => 'success'];
        else throw new Exception("Delete failed.");
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
pg_close($conn);
exit;