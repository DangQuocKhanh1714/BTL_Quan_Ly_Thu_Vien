<?php
// Tên file: functions/borrow_functions.php
require_once 'db_connection.php'; // Đảm bảo file này tồn tại trong thư mục functions/

// =========================================================
// === CÁC HÀM HỖ TRỢ CHO DROP-DOWN
// =========================================================

/**
 * Lấy tất cả sách (ID và Tên) còn tồn kho
 * @return array Danh sách sách còn tồn kho
 */
function getAllAvailableBooks() {
    $conn = getDbConnection();
    // Chỉ lấy sách có quantity > 0
    $sql = "SELECT id, book_name FROM books WHERE quantity > 0 ORDER BY book_name ASC"; 
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

// =========================================================
// === CÁC HÀM NGHIỆP VỤ QUẢN LÝ PHIẾU MƯỢN (CRUD)
// =========================================================

/**
 * Lấy tất cả danh sách phiếu mượn (kèm theo tên sinh viên và tên sách)
 */
/**
 * Lấy tất cả danh sách phiếu mượn (kèm theo tên sinh viên và tên sách)
 */
function getAllBorrowSlips() {
    $conn = getDbConnection();
    
    // ĐÃ GÕ LẠI VÀ SỬA LỖI KÝ TỰ ẨN (Dòng 55)
    $sql = "SELECT 
                bs.id AS borrow_id, 
                bs.book_id, 
                bs.student_id, 
                bs.borrow_date,
                s.student_name, 
                b.book_name 
            FROM 
                borrow_slips bs
            LEFT JOIN 
                students s ON bs.student_id = s.id 
            LEFT JOIN 
                books b ON bs.book_id = b.id
            ORDER BY 
                bs.id DESC";
    $result = mysqli_query($conn, $sql);
    
    $slips = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $slips[] = $row; 
        }
    }
    
    mysqli_close($conn);
    return $slips;
}

/**
 * Thêm phiếu mượn mới (CÓ KIỂM TRA & GIẢM TỒN KHO)
 * TRẢ VỀ ID VỪA TẠO để ghi Log
 */
function addBorrowSlip($student_id, $book_id) { 
    $conn = getDbConnection();
    mysqli_begin_transaction($conn); // Bắt đầu Transaction
    $newSlipId = 0; // Thay đổi từ $success sang ID

    try {
        // 1. KIỂM TRA SỐ LƯỢNG TỒN KHO VÀ KHÓA HÀNG (FOR UPDATE)
        $sql_check = "SELECT quantity FROM books WHERE id = ? AND quantity > 0 LIMIT 1 FOR UPDATE";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "i", $book_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            // 2. THÊM PHIẾU MƯỢN 
            $sql_insert = "INSERT INTO borrow_slips (book_id, student_id, borrow_date) VALUES (?, ?, NOW())";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ii", $book_id, $student_id); 
            $success = mysqli_stmt_execute($stmt_insert);

            if ($success) {
                $newSlipId = mysqli_insert_id($conn); // Lấy ID mới
                // 3. GIẢM SỐ LƯỢNG SÁCH ĐI 1
                $sql_update = "UPDATE books SET quantity = quantity - 1 WHERE id = ?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "i", $book_id);
                mysqli_stmt_execute($stmt_update);
            }
        }
        
        // Hoàn thành Transaction
        if ($newSlipId > 0) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Lỗi khi thêm phiếu mượn: " . $e->getMessage());
    }

    mysqli_close($conn);
    return $newSlipId; // Trả về ID
}

/**
 * Lấy thông tin một phiếu mượn theo ID (Chỉ dùng cho form SỬA)
 */
function getBorrowSlipById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT id, book_id, student_id, borrow_date FROM borrow_slips WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $slip = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $slip = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $slip;
}

/**
 * Lấy chi tiết phiếu mượn theo ID (kèm Tên và Mã chi tiết) - Dùng cho trang CHI TIẾT
 */
function getBorrowSlipDetailsById($id) {
    $conn = getDbConnection();
    
    // ĐÃ GÕ LẠI VÀ SỬA LỖI KÝ TỰ ẨN
    $sql = "SELECT 
                bs.id AS borrow_id, 
                bs.book_id, 
                bs.student_id, 
                bs.borrow_date,
                s.student_name, 
                b.book_name 
            FROM 
                borrow_slips bs
            LEFT JOIN 
                students s ON bs.student_id = s.id 
            LEFT JOIN 
                books b ON bs.book_id = b.id
            WHERE bs.id = ? 
            LIMIT 1";
            
    $stmt = mysqli_prepare($conn, $sql);
    $slip = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $slip = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $slip;
}

/**
 * Cập nhật thông tin phiếu mượn
 */
function updateBorrowSlip($id, $student_id, $book_id) { 
    $conn = getDbConnection();
    
    $sql = "UPDATE borrow_slips SET student_id = ?, book_id = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iii", $student_id, $book_id, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

/**
 * Xóa phiếu mượn theo ID (CÓ HOÀN LẠI SỐ LƯỢNG SÁCH)
 */
function deleteBorrowSlip($id) {
    $conn = getDbConnection();
    mysqli_begin_transaction($conn);
    $success = false;
    $book_id = null;

    try {
        // 1. LẤY book_id TỪ PHIẾU MƯỢN CẦN XÓA
        $sql_select = "SELECT book_id FROM borrow_slips WHERE id = ? LIMIT 1 FOR UPDATE";
        $stmt_select = mysqli_prepare($conn, $sql_select);
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        $result_select = mysqli_stmt_get_result($stmt_select);
        
        if ($row = mysqli_fetch_assoc($result_select)) {
            $book_id = $row['book_id'];
        } else {
            mysqli_close($conn);
            return false;
        }

        // 2. XÓA PHIẾU MƯỢN
        $sql_delete = "DELETE FROM borrow_slips WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        $success = mysqli_stmt_execute($stmt_delete);

        if ($success && $book_id) {
            // 3. TĂNG SỐ LƯỢNG SÁCH LÊN 1
            $sql_update = "UPDATE books SET quantity = quantity + 1 WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "i", $book_id);
            mysqli_stmt_execute($stmt_update);
        }
        
        if ($success) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Lỗi khi xóa phiếu mượn: " . $e->getMessage());
        $success = false;
    }

    mysqli_close($conn);
    return $success;
}
?>