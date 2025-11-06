<?php
// Tên file: views/category/edit_category.php
require_once __DIR__ . '/../../functions/auth.php'; 
require_once __DIR__ . '/../../functions/category_functions.php'; 

checkLogin(__DIR__ . '/../../index.php'); 

// 1. Lấy ID từ URL
$id = $_GET['id'] ?? null;

// 2. Kiểm tra ID và lấy dữ liệu
if (!$id || !is_numeric($id)) {
    // Chuyển hướng nếu ID không hợp lệ
    header("Location: ../category.php?error=" . urlencode("ID Thể loại không hợp lệ."));
    exit;
}

$category = getCategoryById($id);

if (!$category) {
    // Chuyển hướng nếu không tìm thấy Thể loại
    header("Location: ../category.php?error=" . urlencode("Không tìm thấy Thể loại có ID: " . htmlspecialchars($id)));
    exit;
}

// Dữ liệu thể loại hiện tại
$current_category_code = htmlspecialchars($category['category_code']);
$current_category_name = htmlspecialchars($category['category_name']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>DNU - Chỉnh sửa Thể loại</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body> 
    <div class="container mt-4">
        <h3 class="mb-4">CHỈNH SỬA THỂ LOẠI: <?= $current_category_name ?></h3>
        
        <form action="../../handle/category_process.php" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="action" value="edit">

            <div class="mb-3">
                <label for="category_code" class="form-label">Mã Thể loại</label>
                <input type="text" class="form-control" id="category_code" name="category_code" 
                       value="<?= $current_category_code ?>" required maxlength="10"> 
            </div>

            <div class="mb-3">
                <label for="category_name" class="form-label">Tên Thể loại</label>
                <input type="text" class="form-control" id="category_name" name="category_name" 
                       value="<?= $current_category_name ?>" required maxlength="100">
            </div>

            <button type="submit" class="btn btn-warning">
                <i class="fas fa-edit"></i> Cập nhật
            </button>
            <a href="../category.php" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Hủy
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>