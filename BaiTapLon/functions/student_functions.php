<?php
// Tên file: functions/student_functions.php
require_once 'db_connection.php';



/**
 * Lấy tất cả sinh viên (ID, Mã sinh viên và Tên)
 * @return array Danh sách sinh viên
 */
function getAllStudents() {
    $conn = getDbConnection();
    // ĐÃ SỬA: Thêm cột student_code vào truy vấn
    $sql = "SELECT id, student_code, student_name FROM students ORDER BY student_name ASC"; 
    $result = mysqli_query($conn, $sql);
    
    $students = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $students[] = $row; 
        }
    }
    mysqli_close($conn);
    return $students;
}

/**
 * Thêm student mới
 * @param string $student_code Mã sinh viên
 * @param string $student_name Tên sinh viên
 * @return int ID của sinh viên vừa tạo nếu thành công, 0 nếu thất bại.
 */
function addStudent($student_code, $student_name) {
    $conn = getDbConnection();
    
    $sql = "INSERT INTO students (student_code, student_name) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    $newId = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $student_code, $student_name);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
             $newId = mysqli_insert_id($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $newId; // Trả về ID để dùng cho việc ghi Log
}

/**
 * Lấy thông tin một student theo ID
 * @param int $id ID của student
 * @return array|null Thông tin student hoặc null nếu không tìm thấy
 */
function getStudentById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT id, student_code, student_name FROM students WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $student = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $student;
}

/**
 * Cập nhật thông tin student
 * @param int $id ID của student
 * @param string $student_code Mã sinh viên mới
 * @param string $student_name Tên sinh viên mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateStudent($id, $student_code, $student_name) {
    $conn = getDbConnection();
    
    $sql = "UPDATE students SET student_code = ?, student_name = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $student_code, $student_name, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

/**
 * Xóa student theo ID
 * @param int $id ID của student cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteStudent($id) {
    $conn = getDbConnection();
    
    // Bạn nên thêm logic kiểm tra/xóa phiếu mượn (borrow_slips) liên quan ở đây
    
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

// ------------------------------------------------------------------
// BỔ SUNG HÀM BỊ THIẾU ĐỂ KHẮC PHỤC LỖI TRONG borrow_process.php
// ------------------------------------------------------------------

/**
 * Lấy tên sinh viên (student_name) dựa trên ID.
 * Hàm này cần được tối ưu để chỉ trả về tên mà không đóng kết nối DB,
 * nhưng trong phiên bản này, nó gọi lại getStudentById() để đơn giản.
 * @param int $id ID của sinh viên.
 * @return string|null Tên sinh viên hoặc null nếu không tìm thấy.
 */
function getStudentNameById($id) {
    $student = getStudentById($id);
    return $student['student_name'] ?? null;
}

/**
 * Lấy tổng số lượng sinh viên trong hệ thống (dùng cho thống kê)
 * @return int Tổng số sinh viên
 */
function getTotalStudentsCount() {
    $conn = getDbConnection();
    $sql = "SELECT COUNT(id) AS total FROM students";
    $result = mysqli_query($conn, $sql);
    $count = 0;
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $count = (int)$row['total'];
    }
    mysqli_close($conn);
    return $count;
}

?>
