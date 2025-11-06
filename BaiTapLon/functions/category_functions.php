<?php
// Tên file: functions/category_functions.php
require_once 'db_connection.php'; // Chứa getDbConnection()

/**
 * Lấy tất cả danh sách Categories (Thể loại/Danh mục) từ database
 * @return array Danh sách categories
 */
function getAllCategories() {
    $conn = getDbConnection();
    
    // Truy vấn lấy tất cả categories
    $sql = "SELECT id, category_code, category_name FROM categories ORDER BY category_name"; 
    $result = mysqli_query($conn, $sql);

    $categories = [];
    if ($result && mysqli_num_rows($result) > 0) {
        // Lặp qua từng dòng trong kết quả truy vấn $result
        while ($row = mysqli_fetch_assoc($result)) { 
            $categories[] = $row; // Thêm mảng $row vào cuối mảng $categories
        }
    }
    
    mysqli_close($conn);
    return $categories;
}

/**
 * Thêm Category (Thể loại/Danh mục) mới
 * LƯU Ý: Không có kiểm tra trùng lặp category_code trong hàm này.
 * @param string $category_code Mã danh mục
 * @param string $category_name Tên danh mục
 * @return int ID của Category vừa tạo nếu thành công, 0 nếu thất bại.
 */
function addCategory($category_code, $category_name) {
    $conn = getDbConnection();

    // Thêm category_code và category_name
    $sql = "INSERT INTO categories (category_code, category_name) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    $newId = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $category_code, $category_name);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            $newId = mysqli_insert_id($conn); // Lấy ID vừa tạo
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    // Sửa: Hàm addCategory nên trả về ID để ghi Log được chính xác
    return $newId; 
}

/**
 * Lấy thông tin một Category theo ID
 * @param int $id ID của category
 * @return array|null Thông tin category hoặc null nếu không tìm thấy
 */
function getCategoryById($id) {
    $conn = getDbConnection();

    // Lấy id, category_code, category_name
    $sql = "SELECT id, category_code, category_name FROM categories WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $category = null;
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $category = mysqli_fetch_assoc($result);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $category;
}

/**
 * LẤY TÊN THỂ LOẠI THEO ID (HÀM ĐÃ BỔ SUNG ĐỂ KHẮC PHỤC LỖI)
 * Được gọi từ book_process.php để ghi Log chi tiết.
 * @param int $categoryId ID của thể loại.
 * @return string|null Tên thể loại hoặc null nếu không tìm thấy.
 */
function getCategoryNameById($categoryId) {
    // Không cần require_once db_connection.php nếu nó đã được required ở đầu file
    $conn = getDbConnection(); 
    
    if (!$conn || !is_numeric($categoryId) || $categoryId <= 0) {
        // Không đóng kết nối nếu lỗi xảy ra
        return null; 
    }
    
    $stmt = $conn->prepare("SELECT category_name FROM categories WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categoryName = null;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $categoryName = $row['category_name'];
        }
        
        $stmt->close();
        // LƯU Ý: Không đóng $conn ở đây, chỉ đóng ở cuối các hàm CRUD chính.
        return $categoryName;
    }
    
    return null;
}

/**
 * Cập nhật thông tin Category
 * LƯU Ý: Không có kiểm tra trùng lặp category_code trong hàm này.
 * @param int $id ID của category
 * @param string $category_code Mã danh mục mới
 * @param string $category_name Tên danh mục mới
 * @return bool True nếu thành công, False nếu thất bại
 */
function updateCategory($id, $category_code, $category_name) {
    $conn = getDbConnection();
    
    // Cập nhật category_code và category_name
    $sql = "UPDATE categories SET category_code = ?, category_name = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $success = false;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $category_code, $category_name, $id);
        $success = mysqli_stmt_execute($stmt);
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
    return $success;
}

/**
 * Xóa Category theo ID
 * @param int $id ID của category cần xóa
 * @return bool True nếu thành công, False nếu thất bại
 */
function deleteCategory($id) {
    $conn = getDbConnection();
    
    $sql = "DELETE FROM categories WHERE id = ?";
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
?>