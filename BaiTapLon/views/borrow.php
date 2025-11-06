<?php 
// Tên file: views/borrow.php

// ----------------------------------------------------
// 1. YÊU CẦU CÁC FILE CHỨC NĂNG & XỬ LÝ
// ----------------------------------------------------
require_once 'header.php';
require_once __DIR__ . '/../functions/auth.php'; 
checkLogin(__DIR__ . '/../login.php'); 

// Gọi file xử lý để có thể truy cập hàm handleGetAllBorrowSlips()
require_once '../handle/borrow_process.php'; 
$slips = handleGetAllBorrowSlips(); 
?>
<!DOCTYPE html>
<html>

<head>
    <title>DNU - Quản lý Phiếu Mượn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body> 

    <div class="container mt-3">
        <h3 class="mt-3">DANH SÁCH PHIẾU MƯỢN</h3>
        
        <?php 
        // Hiển thị thông báo (SUCCESS)
        if (isset($_GET['success'])) { 
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert"> 
                ' . htmlspecialchars($_GET['success']) . ' 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> 
            </div>'; 
        } 
        // Hiển thị thông báo (ERROR)
        if (isset($_GET['error'])) { 
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"> 
                ' . htmlspecialchars($_GET['error']) . ' 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> 
            </div>'; 
        } 
        ?>
        
        <a href="borrow/create_borrow.php" class="btn btn-primary mb-3">Lập Phiếu Mượn Mới</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID Phiếu</th>
                    <th scope="col">Tên Sinh viên</th>
                    <th scope="col">Tên Sách</th>
                    <th scope="col">Ngày Mượn</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (is_array($slips) && !empty($slips)) {
                    foreach($slips as $slip){ 
                ?>
                        <tr>
                            <td><?= $slip["borrow_id"] ?></td> 
                            <td><?= htmlspecialchars($slip["student_name"] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($slip["book_name"] ?? 'N/A') ?></td>
                            <td><?= $slip["borrow_date"] ?></td>
                            
                            <td> 
                                <a href="borrow/details_borrow.php?id=<?= $slip["borrow_id"] ?>" class="btn btn-info btn-sm">Chi tiết</a> 
                                <a href="borrow/edit_borrow.php?id=<?= $slip["borrow_id"] ?>" class="btn btn-warning btn-sm">Sửa</a> 
                                <a href="../handle/borrow_process.php?action=delete&id=<?= $slip["borrow_id"] ?> "
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu mượn #<?= $slip["borrow_id"] ?>? Điều này sẽ hoàn lại sách vào kho!')">Xóa</a> 
                            </td>
                        </tr> 
                <?php 
                    } 
                } else {
                    echo '<tr><td colspan="5" class="text-center text-muted">Không tìm thấy phiếu mượn nào.</td></tr>';
                }
                ?> 
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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