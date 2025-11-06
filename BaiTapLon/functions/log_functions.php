<?php
// File: functions/log_functions.php

require_once 'db_connection.php'; 

/**
 * Ghi lại hoạt động vào bảng logs (Chỉ sử dụng 5 cột chính + timestamp)
 * @param int $executor_student_id ID của người thực hiện
 * @param string $action_type Loại hành động (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, v.v.)
 * @param string $module Module bị ảnh hưởng (BOOK, STUDENT, BORROW, CATEGORY, AUTH, v.v.)
 * @param string $detail Chi tiết hành động
 * @param int|null $target_id ID của đối tượng bị ảnh hưởng
 * @return bool True nếu ghi log thành công, False nếu thất bại
 */
function logActivity($executor_student_id, $action_type, $module, $detail, $target_id = null) {
    // 1. Kết nối Database
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }

    // 2. Câu lệnh SQL chỉ sử dụng các cột hiện có: student_id, action_type, module, detail, target_id, timestamp
    $sql = "INSERT INTO logs 
            (student_id, action_type, module, detail, target_id, timestamp) 
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        // 3. Bind 5 tham số tương ứng với 5 cột chính
        // i: student_id, s: action_type, s: module, s: detail, i: target_id
        mysqli_stmt_bind_param(
            $stmt, 
            "isssi", 
            $executor_student_id, 
            $action_type, 
            $module, 
            $detail, 
            $target_id
        );

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return $success;
    }
    
    mysqli_close($conn);
    return false;
}

/**
 * Lấy tất cả nhật ký Log (Đã bao gồm thông tin user/sinh viên nếu có)
 * Hàm này LẤY TÊN NGƯỜI THỰC HIỆN bằng cách JOIN với bảng students.
 * @return array Danh sách logs
 */
function handleGetAllLogs() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }

    $sql = "SELECT 
                l.id, 
                l.timestamp, 
                l.student_id, 
                l.action_type, 
                l.module, 
                l.target_id, 
                l.detail,
                s.student_name AS executor_name  -- Lấy tên sinh viên và đặt tên alias là executor_name
            FROM 
                logs l
            LEFT JOIN 
                students s ON l.student_id = s.id
            ORDER BY 
                l.timestamp DESC"; // Sắp xếp theo thời gian mới nhất (DESC)
            
    $result = mysqli_query($conn, $sql);

    $logs = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { 
            $logs[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $logs;
}