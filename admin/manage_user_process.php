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
        
        // Dữ liệu phụ huynh gửi từ form
        $create_parent = isset($_POST['create_parent']) && $_POST['create_parent'] == '1';
        $parent_name   = trim($_POST['parent_name'] ?? '');

        if (empty($user_id) || empty($full_name) || empty($role) || empty($campus_id)) {
            throw new Exception("Missing required fields.");
        }

        // Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
        pg_query($conn, "BEGIN");

        if ($action === 'add') {
            if (empty($password)) throw new Exception("Password is required for new accounts.");
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $email = $user_id . "@greenwich.edu.vn";

            // Bước 1: Thêm User và lấy ID vừa tạo
            $sql_user = "INSERT INTO users (user_id, full_name, password, role, campus_id, major_id, email) 
                         VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id";
            $res_user = pg_query_params($conn, $sql_user, [$user_id, $full_name, $hashed_password, $role, $campus_id, $major_id, $email]);
            
            if (!$res_user) throw new Exception("Failed to create user: " . pg_last_error($conn));
            
            $new_student_db_id = pg_fetch_result($res_user, 0, 0);

            // Bước 2: Thêm Phụ huynh (nếu được chọn)
            if ($role === 'student' && $create_parent) {
                $p_name = !empty($parent_name) ? $parent_name : "Parent of " . $full_name;
                $sql_parent = "INSERT INTO parents (student_id, parent_name) VALUES ($1, $2)";
                $res_parent = pg_query_params($conn, $sql_parent, [$new_student_db_id, $p_name]);
                
                if (!$res_parent) throw new Exception("Failed to create parent entry.");
            }

        } else {
            // Logic Cập nhật (Edit)
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
            
            $res = pg_query_params($conn, $sql, $params);
            if (!$res) throw new Exception("Update failed: " . pg_last_error($conn));
        }

        pg_query($conn, "COMMIT");
        $response = ['status' => 'success'];

    } elseif ($action === 'delete') {
        $db_id = $_POST['db_id'] ?? null;
        
        pg_query($conn, "BEGIN");
        // Xóa phụ huynh trước vì ràng buộc khóa ngoại
        pg_query_params($conn, "DELETE FROM parents WHERE student_id = $1", [$db_id]);
        $res = pg_query_params($conn, "DELETE FROM users WHERE id = $1", [$db_id]);
        
        if ($res) {
            pg_query($conn, "COMMIT");
            $response = ['status' => 'success'];
        } else {
            pg_query($conn, "ROLLBACK");
            throw new Exception("Delete operation failed.");
        }
    }
} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
pg_close($conn);
exit;