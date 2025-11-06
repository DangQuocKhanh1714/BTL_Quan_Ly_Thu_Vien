<?php
// Tên file: handle/borrow_process.php
// ... (các require_once giữ nguyên)

// Cần gọi các functions cần thiết
require_once __DIR__ . '/../functions/borrow_functions.php';
require_once __DIR__ . '/../functions/log_functions.php'; 
require_once __DIR__ . '/../functions/auth.php'; 
require_once __DIR__ . '/../functions/book_functions.php'; 
require_once __DIR__ . '/../functions/student_functions.php'; 

// Lấy thông tin người dùng hiện tại (người thực hiện hành động)
$currentUser = getCurrentUser();
$executor_student_id = $currentUser['id'] ?? 0; 
$executor_name = $currentUser['username'] ?? 'Admin/N/A';


$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'create':
        handleCreateBorrowSlip($executor_student_id, $executor_name);
        break;
    case 'edit':
        handleEditBorrowSlip($executor_student_id, $executor_name);
        break;
    case 'delete':
        handleDeleteBorrowSlip($executor_student_id, $executor_name);
        break;
}

function handleGetAllBorrowSlips() {
    require_once __DIR__ . '/../functions/borrow_functions.php';
    $slips = getAllBorrowSlips(); 
    return $slips;
}


function handleGetBorrowSlipById($id) {
    // ...
}

// ------------------------------------------------------------------

/**
 * Xử lý tạo phiếu mượn mới (CREATE) - Đã SỬA Log
 */
function handleCreateBorrowSlip($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/borrow.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['student_id']) || !isset($_POST['book_id'])) {
        header("Location: ../views/borrow/create_borrow.php?error=Thiếu thông tin cần thiết: Mã sinh viên, Mã sách");
        exit();
    }
    
    $student_id = (int)$_POST['student_id']; 
    $book_id = (int)$_POST['book_id'];
    
    if ($student_id <= 0 || $book_id <= 0) {
        header("Location: ../views/borrow/create_borrow.php?error=Vui lòng điền đầy đủ và hợp lệ Mã sinh viên và Mã sách");
        exit();
    }
    
    $newSlipId = addBorrowSlip($student_id, $book_id);
    
    if ($newSlipId > 0) {
        $bookName = getBookNameById($book_id) ?? "Sách ID $book_id";
        $studentName = getStudentNameById($student_id) ?? "Sinh viên ID $student_id";

        // Ghi Log khi TẠO thành công (CHỈ CÒN 5 THAM SỐ)
        logActivity(
            $executor_student_id, 
            'CREATE', 
            'BORROW', 
            "Lập phiếu mượn #$newSlipId cho $studentName mượn sách: $bookName", 
            $newSlipId 
        );
        header("Location: ../views/borrow.php?success=Lập phiếu mượn thành công (Đã trừ sách tồn kho)");
    } else {
        header("Location: ../views/borrow/create_borrow.php?error=Lập phiếu mượn thất bại. (Có thể Sách đã hết, ID không tồn tại, hoặc lỗi DB)");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa phiếu mượn (EDIT) - Đã SỬA Log
 */
function handleEditBorrowSlip($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/borrow.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_POST['id']) || !isset($_POST['student_id']) || !isset($_POST['book_id'])) {
        header("Location: ../views/borrow.php?error=Thiếu thông tin cần thiết: ID, Mã sinh viên, Mã sách");
        exit();
    }
    
    $id = (int)$_POST['id'];
    $student_id = (int)$_POST['student_id'];
    $book_id = (int)$_POST['book_id'];
    
    if ($id <= 0 || $student_id <= 0 || $book_id <= 0) {
        header("Location: ../views/borrow/edit_borrow.php?id=" . $id . "&error=ID, Mã sinh viên, Mã sách không hợp lệ");
        exit();
    }
    
    $result = updateBorrowSlip($id, $student_id, $book_id);
    
    if ($result) {
        $bookName = getBookNameById($book_id) ?? "Sách ID $book_id";
        $studentName = getStudentNameById($student_id) ?? "Sinh viên ID $student_id";

        // Ghi Log khi SỬA thành công (CHỈ CÒN 5 THAM SỐ)
        logActivity(
            $executor_student_id, 
            'UPDATE', 
            'BORROW', 
            "Cập nhật phiếu mượn #$id: $studentName mượn $bookName", 
            $id 
        );
        header("Location: ../views/borrow.php?success=Cập nhật phiếu mượn thành công");
    } else {
        header("Location: ../views/borrow/edit_borrow.php?id=" . $id . "&error=Cập nhật phiếu mượn thất bại");
    }
    exit();
}

/**
 * Xử lý xóa phiếu mượn (DELETE) - Đã SỬA Log
 */
function handleDeleteBorrowSlip($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/borrow.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/borrow.php?error=Không tìm thấy ID phiếu mượn");
        exit();
    }
    
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        header("Location: ../views/borrow.php?error=ID phiếu mượn không hợp lệ");
        exit();
    }
    
    $slipDetails = getBorrowSlipById($id); 

    $result = deleteBorrowSlip($id);
    
    if ($result) {
        $logDetail = "Xóa phiếu mượn #$id (Hoàn trả sách vào kho).";
        
        if ($slipDetails) {
            $studentName = $slipDetails['student_name'] ?? "ID SV: {$slipDetails['student_id']}";
            $bookName = $slipDetails['book_name'] ?? "ID Sách: {$slipDetails['book_id']}";
            $logDetail = "Xóa phiếu mượn #$id: Hoàn trả sách '$bookName' của sinh viên '$studentName'.";
        }
        
        // Ghi Log khi XÓA thành công (CHỈ CÒN 5 THAM SỐ)
        logActivity(
            $executor_student_id, 
            'DELETE', 
            'BORROW', 
            $logDetail, 
            $id 
        );
        header("Location: ../views/borrow.php?success=Xóa phiếu mượn thành công (Đã hoàn lại sách tồn kho)");
    } else {
        header("Location: ../views/borrow.php?error=Xóa phiếu mượn thất bại. (Phiếu mượn không tồn tại)");
    }
    exit();
}
?>