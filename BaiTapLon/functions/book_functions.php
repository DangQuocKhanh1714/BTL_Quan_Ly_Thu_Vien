<?php
// Tên file: functions/book_functions.php
require_once 'db_connection.php';

/**
 * Lấy tất cả danh sách Sách (Books) từ database
 * @return array Danh sách sách
 */
function getAllBooks() {
    $conn = getDbConnection();
    
    // Truy vấn lấy sách, bao gồm cả tên thể loại (category_name)
    $sql = "SELECT b.id, b.book_id, b.book_name, b.author, b.quantity, b.category_id, c.category_name
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            ORDER BY b.book_name"; 
            
    $result = mysqli_query($conn, $sql);

    $books = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $books[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $books;
}

/**
 * Lấy tất cả sách (ID, Tên và Tồn kho)
 * @return array Danh sách tất cả sách
 */
function getAllBooksDropdown() {
    $conn = getDbConnection();
    // Lấy thêm quantity để hiển thị thông tin tồn kho
    $sql = "SELECT id, book_name, quantity FROM books ORDER BY book_name ASC";
    $result = mysqli_query($conn, $sql);
    
    $books = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $books[] = $row; 
        }
    }
    mysqli_close($conn);
    return $books;
}

/**
 * Lấy thông tin một Sách theo ID
 * @param int $id ID của sách
 * @return array|null Thông tin sách hoặc null nếu không tìm thấy
 */
function getBookById($id) {
    $conn = getDbConnection();

    // Lấy thông tin sách, bao gồm tên thể loại
    $sql = "SELECT b.id, b.book_id, b.book_name, b.author, b.quantity, b.category_id, c.category_name
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.id = ? LIMIT 1";
            
    $stmt = mysqli_prepare($conn, $sql);
    $book = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $book = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $book;
}

/**
 * Lấy tên sách theo ID
 * @param int $id ID của sách
 * @return string|null Tên sách hoặc null nếu không tìm thấy
 */
function getBookNameById($id) {
    $book = getBookById($id);
    return $book['book_name'] ?? null;
}


/**
 * Thêm Sách mới vào database
 * @param string $book_id Mã sách
 * @param string $book_name Tên sách
 * @param string $author Tác giả
 * @param int $quantity Số lượng
 * @param int $category_id ID thể loại
 * @return int ID của sách vừa tạo nếu thành công, 0 nếu thất bại
 */
function addBook($book_id, $book_name, $author, $quantity, $category_id) {
    $conn = getDbConnection();

    $sql = "INSERT INTO books (book_id, book_name, author, quantity, category_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    $newId = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssii", $book_id, $book_name, $author, $quantity, $category_id);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            $newId = mysqli_insert_id($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $newId;
}

/**
 * Cập nhật thông tin Sách
 * @param int $id ID của sách cần cập nhật
 * @param string $book_id Mã sách mới
 * @param string $book_name Tên sách mới
 * @param string $author Tác giả mới
 * @param int $quantity Số lượng mới
 * @param int $category_id ID thể loại mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateBook($id, $book_id, $book_name, $author, $quantity, $category_id) {
    $conn = getDbConnection();
    
    $sql = "UPDATE books SET book_id = ?, book_name = ?, author = ?, quantity = ?, category_id = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;

    if ($stmt) {
        // Tham số: sssiii (6 tham số)
        mysqli_stmt_bind_param($stmt, "sssiii", $book_id, $book_name, $author, $quantity, $category_id, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

/**
 * Xóa sách theo ID. 
 * ĐÃ SỬA: Thêm logic xóa các phiếu mượn liên quan trước để tránh lỗi Foreign Key Constraint.
 * @param int $id ID của sách cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteBook($id) {
    $conn = getDbConnection();
    if (!$conn) return false;

    // Bắt đầu transaction để đảm bảo cả hai thao tác đều thành công hoặc thất bại
    mysqli_begin_transaction($conn);
    $success = false;

    try {
        // BƯỚC 1: Xóa các phiếu mượn (borrow_slips) liên quan đến cuốn sách này
        // Điều này là BẮT BUỘC do Foreign Key Constraint
        $sql_delete_slips = "DELETE FROM borrow_slips WHERE book_id = ?";
        $stmt_slips = mysqli_prepare($conn, $sql_delete_slips);
        
        if ($stmt_slips) {
            mysqli_stmt_bind_param($stmt_slips, "i", $id);
            mysqli_stmt_execute($stmt_slips);
            mysqli_stmt_close($stmt_slips);
        }

        // BƯỚC 2: Xóa cuốn sách khỏi bảng books
        $sql_delete_book = "DELETE FROM books WHERE id = ?";
        $stmt_book = mysqli_prepare($conn, $sql_delete_book);
        
        if ($stmt_book) {
            mysqli_stmt_bind_param($stmt_book, "i", $id);
            $success = mysqli_stmt_execute($stmt_book);
            mysqli_stmt_close($stmt_book);
        }

        // Hoàn thành transaction
        if ($success) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        // Có thể log lỗi ở đây nếu cần thiết
        $success = false;
    }

    mysqli_close($conn);
    return $success;
}

/**
 * Cập nhật số lượng sách tồn kho (dùng khi mượn/trả)
 * @param int $id ID của sách
 * @param int $change Số lượng thay đổi (+ để tăng, - để giảm)
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateBookQuantity($id, $change) {
    $conn = getDbConnection();
    
    // Câu lệnh UPDATE với phép cộng/trừ trực tiếp trong SQL
    $sql = "UPDATE books SET quantity = quantity + ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $change, $id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

/**
 * Lấy tổng số lượng đầu sách (không phải số lượng tồn kho) trong hệ thống
 * @return int Tổng số đầu sách
 */
function getTotalBooksCount() {
    $conn = getDbConnection();
    $sql = "SELECT COUNT(id) AS total FROM books";
    $result = mysqli_query($conn, $sql);
    $count = 0;
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $count = (int)$row['total'];
    }
    mysqli_close($conn);
    return $count;
}

?>