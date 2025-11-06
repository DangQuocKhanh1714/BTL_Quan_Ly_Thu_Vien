<?php
require_once 'header.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/category_functions.php';

checkLogin(__DIR__ . '/../index.php');
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>DNU - Quản lý Thể loại</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body> 
    <div class="container mt-4">
        <h3 class="mb-4">QUẢN LÝ THỂ LOẠI SÁCH</h3>
        
        <?php 
        // Hiển thị thông báo (thành công/lỗi)
        if (isset($_GET['success'])) { 
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert"> 
                ' . htmlspecialchars($_GET['success']) . ' 
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button> 
            </div>'; 
        } 
        if (isset($_GET['error'])) { 
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"> 
                ' . htmlspecialchars($_GET['error']) . ' 
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button> 
            </div>'; 
        } 
        ?>
        
        <a href="category/create_category.php" class="btn btn-primary mb-3">Thêm thể loại mới</a>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 25%;">Mã Thể loại</th>
                    <th style="width: 40%;">Tên Thể loại</th>
                    <th style="width: 30%;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($categories) && !empty($categories)): ?>
                    <?php foreach($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat["id"]) ?></td>
                            <td><?= htmlspecialchars($cat["category_code"] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($cat["category_name"]) ?></td>
                            <td> 
                                <a href="category/edit_category.php?id=<?= htmlspecialchars($cat["id"]) ?>" 
                                   class="btn btn-warning btn-sm">
                                    Sửa
                                </a>
                                <a href="../handle/category_process.php?action=delete&id=<?= $cat["id"] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa Thể loại <?= htmlspecialchars($cat['category_name']) ?>?')">
                                    Xóa
                                </a> 
                            </td>
                        </tr> 
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">Chưa có Thể loại nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tự động ẩn thông báo sau 3 giây
        setTimeout(() => { 
            let alertNode = document.querySelector('.alert'); 
            if (alertNode) { 
                let bsAlert = bootstrap.Alert.getOrCreateInstance(alertNode); 
                bsAlert.close(); 
            } 
        }, 3000); 
    </script>
    <script>
function toggleDropdown() {
    const dropdown = document.getElementById("userDropdown");
    dropdown.classList.toggle("show");
}

// Ẩn dropdown khi click ra ngoài
window.addEventListener("click", function(e) {
    const btn = document.querySelector(".user-menu-btn");
    const dropdown = document.getElementById("userDropdown");
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove("show");
    }
});
</script>

</body>

</html>