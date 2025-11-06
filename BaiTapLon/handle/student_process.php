<?php
// Tên file: handle/student_process.php

require_once __DIR__ . '/../functions/student_functions.php';
require_once __DIR__ . '/../functions/log_functions.php'; // 1. GỌI LOG FUNCTION
require_once __DIR__ . '/../functions/auth.php'; // 2. Cần để lấy ID người dùng thực hiện

// Lấy thông tin người dùng hiện tại (người thực hiện hành động)
$currentUser = getCurrentUser();
// Giả định student_id trong logs là ID của user đang đăng nhập
$executor_student_id = $currentUser['id'] ?? 0; 
$executor_name = $currentUser['username'] ?? 'Admin/N/A';


// Kiểm tra action được truyền qua URL hoặc POST
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateStudent($executor_student_id, $executor_name);
        break;
    case 'edit':
        handleEditStudent($executor_student_id, $executor_name);
        break;
    case 'delete':
        handleDeleteStudent($executor_student_id, $executor_name);
        break;
}
/**
 * Lấy tất cả danh sách sinh viên
 */
function handleGetAllStudents() {
    return getAllStudents();
}

function handleGetStudentById($id) {
    return getStudentById($id);
}

// ------------------------------------------------------------------

/**
 * Xử lý tạo sinh viên mới - Đã thêm logic Ghi Log
 */
function handleCreateStudent($executor_student_id, $executor_name) {
    // ... [Các bước kiểm tra ban đầu giữ nguyên] ...
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['student_code']) || !isset($_POST['student_name'])) {
        header("Location: ../views/student/create_student.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $student_code = trim($_POST['student_code']);
    $student_name = trim($_POST['student_name']);
    
    // Validate dữ liệu
    if (empty($student_code) || empty($student_name)) {
        header("Location: ../views/student/create_student.php?error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Giả định hàm addStudent trả về ID của sinh viên vừa tạo nếu thành công
    $newStudentId = addStudent($student_code, $student_name);
    
    if ($newStudentId > 0) {
        // Ghi Log khi TẠO thành công
        logActivity(
            $executor_student_id, 
            'CREATE', 
            'STUDENT', 
            "Thêm sinh viên mới: $student_name (Mã: $student_code)", 
            $newStudentId // Target ID là ID sinh viên vừa tạo
        );

        header("Location: ../views/student.php?success=Thêm sinh viên thành công");
    } else {
        header("Location: ../views/student/create_student.php?error=Có lỗi xảy ra khi thêm sinh viên");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa sinh viên - Đã thêm logic Ghi Log
 */
function handleEditStudent($executor_student_id, $executor_name) {
    // ... [Các bước kiểm tra ban đầu giữ nguyên] ...
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['student_code']) || !isset($_POST['student_name'])) {
        header("Location: ../views/student.php?error=Thiếu thông tin cần thiết");
        exit();
    }
    
    $id = $_POST['id'];
    $student_code = trim($_POST['student_code']);
    $student_name = trim($_POST['student_name']);
    
    // Validate dữ liệu
    if (empty($student_code) || empty($student_name)) {
        header("Location: ../views/edit_student.php?id=" . $id . "&error=Vui lòng điền đầy đủ thông tin");
        exit();
    }
    
    // Gọi function để cập nhật sinh viên
    $result = updateStudent($id, $student_code, $student_name);
    
    if ($result) {
        // Ghi Log khi SỬA thành công
        logActivity(
            $executor_student_id, 
            'UPDATE', 
            'STUDENT', 
            "Cập nhật sinh viên ID #$id: $student_name (Mã: $student_code)", 
            $id // Target ID là ID sinh viên được sửa
        );

        header("Location: ../views/student.php?success=Cập nhật sinh viên thành công");
    } else {
        header("Location: ../views/edit_student.php?id=" . $id . "&error=Cập nhật sinh viên thất bại");
    }
    exit();
}

/**
 * Xử lý xóa sinh viên - Đã thêm logic Ghi Log
 */
function handleDeleteStudent($executor_student_id, $executor_name) {
    // ... [Các bước kiểm tra ban đầu giữ nguyên] ...
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/student.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/student.php?error=Không tìm thấy ID sinh viên");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/student.php?error=ID sinh viên không hợp lệ");
        exit();
    }

    // Lấy thông tin sinh viên trước khi xóa để ghi log chi tiết
    $studentDetails = getStudentById($id); 
    
    // Gọi function để xóa sinh viên
    $result = deleteStudent($id);
    
    if ($result) {
        $studentName = $studentDetails['student_name'] ?? "ID $id";
        $logDetail = "Xóa sinh viên: $studentName (ID #$id) khỏi hệ thống.";

        // Ghi Log khi XÓA thành công
        logActivity(
            $executor_student_id, 
            'DELETE', 
            'STUDENT', 
            $logDetail, 
            $id // Target ID là ID sinh viên bị xóa
        );

        header("Location: ../views/student.php?success=Xóa sinh viên thành công");
    } else {
        header("Location: ../views/student.php?error=Xóa sinh viên thất bại");
    }
    exit();
}
?>