<?php
// Tên file: views/book/create_book.php
// Đảm bảo đường dẫn đến auth.php là chính xác
require_once __DIR__ . '/../../functions/auth.php'; 
checkLogin(__DIR__ . '/../../index.php');

// BƯỚC 1: Nhúng file chức năng Danh mục
require_once __DIR__ . '/../../functions/category_functions.php'; 
// BƯỚC 2: Gọi hàm lấy danh sách danh mục (Thể loại)
$categories = getAllCategories(); 
?>
<!DOCTYPE html>
<html>

<head>
    <title>DNU - Thêm Sách Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4">THÊM SÁCH MỚI</h3> 
                
                <?php
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
                // Cần đảm bảo thư viện Bootstrap JS đã load
                setTimeout(() => {
                    let alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        // Khởi tạo đối tượng Alert mới để có thể gọi .close()
                        let bsAlert = new bootstrap.Alert(alertNode);
                        bsAlert.close();
                    }
                }, 3000);
                </script>

                <form action="../../handle/book_process.php" method="POST">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="book_id" class="form-label">Mã Sách</label> 
                        <input type="text" class="form-control" id="book_id" name="book_id" required>
                    </div>

                    <div class="mb-3">
                        <label for="book_name" class="form-label">Tên Sách</label> 
                        <input type="text" class="form-control" id="book_name" name="book_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Thể Loại</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="" disabled selected>--- Chọn Thể Loại ---</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): 
                                    // Giá trị (value) là ID, Text hiển thị là Tên Danh mục
                                ?>
                                    <option value="<?php echo htmlspecialchars($cat['id']); ?>">
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
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số Lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required min="1"
                            value="1">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Thêm Sách</button> 
                        <a href="../book.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>