<?php
require_once __DIR__ . '/../functions/auth.php';
// BỔ SUNG: Cần require log_functions để ghi log
require_once __DIR__ . '/../functions/log_functions.php'; 

// Sửa hàm logout (Nếu bạn dùng hàm logout tùy chỉnh)
// Bạn cần đảm bảo hàm logout của bạn có thể lấy được student_id từ session
// GIẢ ĐỊNH bạn có thể lấy ID trước khi session bị hủy:
$executor_student_id = $_SESSION['user_id'] ?? 0;
$executor_name = $_SESSION['username'] ?? 'N/A';

if ($executor_student_id > 0) {
    logActivity(
        $executor_student_id, 
        'LOGOUT', 
        'AUTH', 
        "Đăng xuất thành công tài khoản: $executor_name", 
        $executor_student_id 
    );
}

// Sử dụng hàm logout chung (cần đảm bảo hàm này không hủy session trước khi Log được ghi)
logout('../login.php');
?>
