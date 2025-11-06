<?php
require_once __DIR__ . '/../functions/book_functions.php';
require_once __DIR__ . '/../functions/log_functions.php'; // 1. GỌI LOG FUNCTION
require_once __DIR__ . '/../functions/auth.php';         // 2. Cần để lấy ID người dùng
require_once __DIR__ . '/../functions/category_functions.php'; // Giả định hàm lấy tên Category nằm ở đây

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
        handleCreateBook($executor_student_id, $executor_name);
        break;
    case 'edit':
        handleEditBook($executor_student_id, $executor_name);
        break;
    case 'delete':
        handleDeleteBook($executor_student_id, $executor_name);
        break;
}

// Các hàm phụ trợ không cần sửa
function handleGetAllBooks() {
    return getAllBooks();
}

function handleGetBookById($id) {
    return getBookById($id);
}

// ------------------------------------------------------------------

/**
 * Xử lý tạo sách mới - Đã thêm logic Ghi Log
 */
function handleCreateBook($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/book.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    // SỬA: Kiểm tra thêm trường author, quantity VÀ category_id
    if (!isset($_POST['book_id']) || !isset($_POST['book_name']) || !isset($_POST['author']) || !isset($_POST['quantity']) || !isset($_POST['category_id'])) {
        header("Location: ../views/book/create_book.php?error=Thiếu thông tin cần thiết: Mã sách, Tên, Tác giả, Số lượng, Danh mục");
        exit();
    }
    
    $book_id = trim($_POST['book_id']);
    $book_name = trim($_POST['book_name']);
    $author = trim($_POST['author']);
    $quantity = (int)$_POST['quantity']; 
    $category_id = (int)$_POST['category_id']; // LẤY category_id
    
    // Validate dữ liệu
    if (empty($book_id) || empty($book_name) || empty($author) || $quantity <= 0 || $category_id <= 0) { // Validate category_id
        header("Location: ../views/book/create_book.php?error=Vui lòng điền đầy đủ và hợp lệ thông tin sách");
        exit();
    }
    
    // Giả định hàm addBook trả về ID (INT) của sách vừa tạo
    $newBookId = addBook($book_id, $book_name, $author, $quantity, $category_id); 
    
    if ($newBookId > 0) {
        // Giả định bạn có hàm getCategoryNameById để lấy tên thể loại
        $categoryName = getCategoryNameById($category_id) ?? "ID: $category_id";
        
        // Ghi Log khi TẠO thành công
        logActivity(
            $executor_student_id, 
            'CREATE', 
            'BOOK', 
            "Thêm sách mới: $book_name (Mã: $book_id) - Tác giả: $author - SL: $quantity - Thể loại: $categoryName", 
            $newBookId // Target ID là ID sách vừa tạo
        );

        header("Location: ../views/book.php?success=Thêm sách thành công");
    } else {
        header("Location: ../views/book/create_book.php?error=Có lỗi xảy ra khi thêm sách");
    }
    exit();
}

/**
 * Xử lý chỉnh sửa sách - Đã thêm logic Ghi Log
 */
function handleEditBook($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../views/book.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    // SỬA: Kiểm tra thêm author, quantity VÀ category_id
    if (!isset($_POST['id']) || !isset($_POST['book_id']) || !isset($_POST['book_name']) || !isset($_POST['author']) || !isset($_POST['quantity']) || !isset($_POST['category_id'])) {
        header("Location: ../views/book.php?error=Thiếu thông tin cần thiết: ID, Mã sách, Tên, Tác giả, Số lượng, Danh mục");
        exit();
    }
    
    $id = $_POST['id'];
    $book_id = trim($_POST['book_id']);
    $book_name = trim($_POST['book_name']);
    $author = trim($_POST['author']);
    $quantity = (int)$_POST['quantity'];
    $category_id = (int)$_POST['category_id']; // LẤY category_id
    
    // Validate dữ liệu
    if (empty($book_id) || empty($book_name) || empty($author) || $quantity < 0 || $category_id <= 0) { // Validate category_id
        header("Location: ../views/book/edit_book.php?id=" . $id . "&error=Vui lòng điền đầy đủ và hợp lệ thông tin sách");
        exit();
    }
    
    $result = updateBook($id, $book_id, $book_name, $author, $quantity, $category_id); // THÊM category_id
    
    if ($result) {
        $categoryName = getCategoryNameById($category_id) ?? "ID: $category_id";

        // Ghi Log khi SỬA thành công
        logActivity(
            $executor_student_id, 
            'UPDATE', 
            'BOOK', 
            "Cập nhật sách ID #$id: $book_name (SL: $quantity, Thể loại: $categoryName)", 
            $id // Target ID là ID sách được sửa
        );

        header("Location: ../views/book.php?success=Cập nhật sách thành công");
    } else {
        header("Location: ../views/book/edit_book.php?id=" . $id . "&error=Cập nhật sách thất bại");
    }
    exit();
}

/**
 * Xử lý xóa sách - Đã thêm logic Ghi Log
 */
function handleDeleteBook($executor_student_id, $executor_name) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header("Location: ../views/book.php?error=Phương thức không hợp lệ");
        exit();
    }
    
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../views/book.php?error=Không tìm thấy ID sách");
        exit();
    }
    
    $id = $_GET['id'];
    
    // Validate ID là số
    if (!is_numeric($id)) {
        header("Location: ../views/book.php?error=ID sách không hợp lệ");
        exit();
    }
    
    // Lấy thông tin sách trước khi xóa để ghi log chi tiết
    $bookDetails = getBookById($id); 

    // Gọi function để xóa sách
    $result = deleteBook($id);
    
    if ($result) {
        $bookName = $bookDetails['book_name'] ?? "ID $id";
        $logDetail = "Xóa sách: $bookName (ID #$id) khỏi CSDL.";

        // Ghi Log khi XÓA thành công
        logActivity(
            $executor_student_id, 
            'DELETE', 
            'BOOK', 
            $logDetail, 
            $id // Target ID là ID sách bị xóa
        );

        header("Location: ../views/book.php?success=Xóa sách thành công");
    } else {
        header("Location: ../views/book.php?error=Xóa sách thất bại");
    }
    exit();
}
?>