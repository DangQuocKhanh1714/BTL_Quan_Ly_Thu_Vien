<?php
// Tên file: views/category/create_category.php
require_once __DIR__ . '/../../functions/auth.php'; 

checkLogin(__DIR__ . '/../../index.php'); 
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>DNU - Thêm Thể loại mới</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4">THÊM THỂ LOẠI MỚI</h3>
                
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
                setTimeout(() => {
                    let alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        let bsAlert = bootstrap.Alert.getOrCreateInstance(alertNode);
                        bsAlert.close();
                    }
                }, 3000);
                </script>
                
                <form action="../../handle/category_process.php" method="POST"> 
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="category_code" class="form-label">Mã Thể loại</label>
                        <input type="text" class="form-control" id="category_code" name="category_code" required maxlength="10">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Tên Thể loại</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required maxlength="100">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Thêm Thể loại</button> 
                        <a href="../category.php" class="btn btn-secondary">Hủy</a> 
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>