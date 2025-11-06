<?php
// Tên file: views/book/edit_book.php
require_once __DIR__ . '/../../functions/auth.php';
checkLogin(__DIR__ . '/../../index.php');

// BƯỚC 1: Nhúng file chức năng Danh mục
require_once __DIR__ . '/../../functions/category_functions.php';
// BƯỚC 2: Lấy danh sách tất cả Thể loại
$categories = getAllCategories(); 

// Đảm bảo các hàm DB có sẵn
require_once __DIR__ . '/../../handle/book_process.php'; 
?>
<!DOCTYPE html>
<html>

<head>
    <title>DNU - Chỉnh Sửa Sách</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-3">
        <h3 class="mt-3 mb-4 text-center">CHỈNH SỬA THÔNG TIN SÁCH</h3> 
        <?php
            // Kiểm tra có ID không
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                header("Location: ../book.php?error=Không tìm thấy sách"); 
                exit;
            }
            
            $id = $_GET['id'];
            
            // Lấy thông tin sách
            $book = handleGetBookById($id); 

            if (!$book) {
                header("Location: ../book.php?error=Không tìm thấy sách"); 
                exit;
            }
            
            // Lấy category_id hiện tại của sách
            $currentCategoryId = $book['category_id'] ?? null;
            
            // Hiển thị thông báo lỗi
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($_GET['error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        ?>
            <script>
            // Sau 3 giây sẽ tự động ẩn alert
            setTimeout(() => {
                let alertNode = document.querySelector('.alert');
                if (alertNode) {
                    let bsAlert = new bootstrap.Alert(alertNode);
                    bsAlert.close();
                }
            }, 3000);
            </script>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form action="../../handle/book_process.php" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($book['id']); ?>">

                                <div class="mb-3">
                                    <label for="book_id" class="form-label">Mã Sách</label> 
                                    <input type="text" class="form-control" id="book_id" name="book_id"
                                        value="<?php echo htmlspecialchars($book['book_id']); ?>" required> 
                                </div>

                                <div class="mb-3">
                                    <label for="book_name" class="form-label">Tên Sách</label> 
                                    <input type="text" class="form-control" id="book_name" name="book_name"
                                        value="<?php echo htmlspecialchars($book['book_name']); ?>" required> 
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Thể Loại</label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <option value="" disabled>--- Chọn Thể Loại ---</option>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $cat): 
                                                // So sánh ID hiện tại của sách với ID của Thể loại để chọn mặc định
                                                $selected = ($cat['id'] == $currentCategoryId) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>Không tìm thấy Thể loại nào</option>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (empty($categories)): ?>
                                        <small class="text-danger">Lưu ý: Bạn cần thêm dữ liệu vào bảng Danh mục trước.</small>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="author" class="form-label">Tác Giả</label> 
                                    <input type="text" class="form-control" id="author" name="author"
                                        value="<?php echo htmlspecialchars($book['author']); ?>" required> 
                                </div>

                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Số Lượng</label> 
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="0"
                                        value="<?php echo htmlspecialchars($book['quantity']); ?>" required> 
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="../book.php" class="btn btn-secondary me-md-2">Hủy</a> 
                                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>