<?php 
require_once 'header.php';
require_once __DIR__ . '/../functions/auth.php'; 
// Kiểm tra đăng nhập 
checkLogin(__DIR__ . '/../login.php'); 
?>
<!DOCTYPE html>
<html>

<head>
    <title>DNU - Quản lý Sách</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body> 

    <div class="container mt-3">
        <h3 class="mt-3">DANH SÁCH SÁCH</h3>
        
        <?php 
        // Hiển thị thông báo thành công
        if (isset($_GET['success'])) { 
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert"> 
                ' . htmlspecialchars($_GET['success']) . ' 
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button> 
            </div>'; 
        } 
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
        
        <a href="book/create_book.php" class="btn btn-primary mb-3">Thêm</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Mã sách</th>
                    <th scope="col">Tên sách</th>
                    <th scope="col">Thể loại</th>
                    <th scope="col">Tác giả</th>
                    <th scope="col">Số lượng</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                require '../handle/book_process.php'; 
                $books = handleGetAllBooks(); 
                
                if (is_array($books)) {
                    foreach($books as $index => $book){ 
                ?>
                    <tr>
                        <td><?= $book["id"] ?></td>
                        <td><?= $book["book_id"] ?></td>
                        <td><?= $book["book_name"] ?></td>
                        <td><?= $book["category_name"] ?></td>
                        <td><?= $book["author"] ?></td>
                        <td><?= $book["quantity"] ?></td>
                        <td> 
                            <a href="book/edit_book.php?id=<?= $book["id"] ?>" class="btn btn-warning btn-sm">Sửa</a> 
                            <a href="../handle/book_process.php?action=delete&id=<?= $book["id"] ?> "
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa sách này?')">Xóa</a> 
                        </td>
                    </tr> 
                <?php 
                    } 
                } else {
                    echo '<tr><td colspan="7" class="text-center text-muted">Không tìm thấy sách nào hoặc có lỗi kết nối cơ sở dữ liệu.</td></tr>';
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
