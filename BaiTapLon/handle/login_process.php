<?php
session_start();
// Lưu ý: Các file này phải có trong cấu trúc thư mục của bạn
require_once '../functions/db_connection.php'; // Chứa getDbConnection()
require_once '../functions/auth.php'; // Chứa authenticateUser()

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    handleLogin();
}

function handleLogin() {
    // 1. GỌI HÀM KẾT NỐI DB THẬT SỰ
    $conn = getDbConnection(); 
    
    // 2. KIỂM TRA KẾT NỐI (Nếu getDbConnection() không die() mà trả về false/null)
    if (!$conn) {
        $_SESSION['error'] = 'Lỗi hệ thống: Không thể kết nối đến cơ sở dữ liệu.';
        header('Location: ../login.php'); 
        exit();
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ username và password!';
        header('Location: ../login.php'); 
        mysqli_close($conn); // Đóng kết nối
        exit();
    }

    // 3. XÁC THỰC NGƯỜI DÙNG VỚI KẾT NỐI DB HỢP LỆ
    $user = authenticateUser($conn, $username, $password);
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['success'] = 'Đăng nhập thành công! Chuyển hướng đến Bảng Điều Khiển...';
    
    // BỔ SUNG LOG ĐĂNG NHẬP THÀNH CÔNG
    // Cần require_once '../functions/log_functions.php'; ở đầu file (giả định đã có)
    require_once '../functions/log_functions.php'; 

    logActivity(
        $user['id'], 
        'LOGIN', 
        'AUTH', 
        "Đăng nhập thành công với tài khoản: {$user['username']}", 
        $user['id'] 
    );
    
    // Chuyển hướng thành công
    header('Location: ../views/main.php');
    exit();
}
    // 4. Đóng kết nối DB sau khi truy vấn xong
    mysqli_close($conn);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['success'] = 'Đăng nhập thành công! Chuyển hướng đến Bảng Điều Khiển...';
        
        // Chuyển hướng thành công
        header('Location: ../views/main.php');
        exit();
    }

    $_SESSION['error'] = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    header('Location: ../login.php');
    exit();
}
?>