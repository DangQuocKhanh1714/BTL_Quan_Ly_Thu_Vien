<?php
// Tên file: handle/category_process.php
require_once __DIR__ . '/../functions/auth.php'; 
require_once __DIR__ . '/../functions/category_functions.php'; 
require_once __DIR__ . '/../functions/log_functions.php'; // 1. GỌI LOG FUNCTION

// Lấy thông tin người dùng hiện tại (người thực hiện hành động)
$currentUser = getCurrentUser();
// Giả định student_id trong logs là ID của user đang đăng nhập
$executor_student_id = $currentUser['id'] ?? 0; 

checkLogin(__DIR__ . '/../index.php');

// SỬA LỖI ĐƯỜNG DẪN: Sử dụng đường dẫn tương đối an toàn từ thư mục 'handle/' 
// để trỏ tới 'views/category.php'
$redirect_to = '../views/category.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    // Sử dụng strtoupper() để thống nhất Mã thể loại
    $category_code = trim(strtoupper($_POST['category_code'] ?? '')); 
    $category_name = trim($_POST['category_name'] ?? '');
    $id = $_POST['id'] ?? null;

    // Kiểm tra trường trống
    if (empty($category_code) || empty($category_name)) {
        // Nếu lỗi xảy ra khi tạo/sửa, chuyển hướng về form tương ứng
        $location = ($action == 'edit' && $id) 
            ? "/../views/category/edit_category.php?id=" . urlencode($id) . "&error=" 
            : '../views/category/create_category.php?error='; 
            
        header("Location: " . $location . urlencode("Mã và Tên thể loại không được để trống."));
        exit;
    }

    if ($action == 'create') {
        // Giả định hàm addCategory trả về ID của thể loại vừa tạo
        $newCategoryId = addCategory($category_code, $category_name); 

        if ($newCategoryId > 0) {
            // Ghi Log khi TẠO thành công
            logActivity(
                $executor_student_id, 
                'CREATE', 
                'CATEGORY', 
                "Thêm thể loại mới: $category_name (Mã: $category_code)", 
                $newCategoryId 
            );

            header("Location: " . $redirect_to . "?success=Thêm Thể loại thành công!");
        } else {
            // Giả định lỗi này là do Mã thể loại đã tồn tại (UNIQUE constraint trong DB)
            $error_msg = "Lỗi khi thêm Thể loại. Mã Thể loại **{$category_code}** có thể đã tồn tại.";
            header("Location: ../views/category/create_category.php?error=" . urlencode($error_msg));
        }
        exit;
    } elseif ($action == 'edit' && $id) {
        if (updateCategory($id, $category_code, $category_name)) {
            // Ghi Log khi SỬA thành công
            logActivity(
                $executor_student_id, 
                'UPDATE', 
                'CATEGORY', 
                "Cập nhật thể loại ID #$id: $category_name (Mã: $category_code)", 
                $id 
            );

            header("Location: " . $redirect_to . "?success=Cập nhật Thể loại thành công!");
        } else {
             // Giả định lỗi này là do Mã thể loại đã tồn tại hoặc lỗi DB
             $error_msg = "Lỗi khi cập nhật. Mã Thể loại **{$category_code}** có thể đã tồn tại.";
             header("Location: ../views/category/edit_category.php?id=" . urlencode($id) . "&error=" . urlencode($error_msg));
        }
        exit;
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? null;

    if ($action == 'delete' && $id) {
        // Lấy thông tin thể loại trước khi xóa để ghi log chi tiết
        $categoryDetails = getCategoryById($id); 

        if (deleteCategory($id)) {
            $categoryName = $categoryDetails['category_name'] ?? "ID $id";
            $logDetail = "Xóa thể loại: $categoryName (ID #$id) khỏi CSDL.";
            
            // Ghi Log khi XÓA thành công
            logActivity(
                $executor_student_id, 
                'DELETE', 
                'CATEGORY', 
                $logDetail, 
                $id 
            );

            // Chuyển hướng sau khi thành công
            header("Location: " . $redirect_to . "?success=Xóa Thể loại thành công!");
        } else {
            // Xử lý lỗi Khóa ngoại (Foreign Key)
            $error_message = "Lỗi khi xóa Thể loại. Vẫn còn Sách đang sử dụng Thể loại này."; 
            header("Location: " . $redirect_to . "?error=" . urlencode($error_message));
        }
        exit;
    }
}

// Chuyển hướng về trang danh sách nếu không có hành động hợp lệ
header("Location: " . $redirect_to . "?error=Hành động không hợp lệ.");
exit;