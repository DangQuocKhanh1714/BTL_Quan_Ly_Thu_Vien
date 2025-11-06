<?php
// Tên file: views/borrow/create_borrow.php
require_once __DIR__ . '/../../functions/auth.php';
// Gọi file borrow_functions.php đã được thêm getAllStudents và getAllAvailableBooks
require_once __DIR__ . '/../../functions/borrow_functions.php'; 

// Thêm dòng này để tải hàm getAllStudents()
require_once __DIR__ . '/../../functions/student_functions.php'; 

// Thường sẽ cần cả book_functions.php nếu trang này tạo phiếu mượn
require_once __DIR__ . '/../../functions/book_functions.php';
checkLogin(__DIR__ . '/../../index.php');

// LẤY DỮ LIỆU TỪ CÁC HÀM TRONG borrow_functions.php
$students = getAllStudents(); 
$available_books = getAllAvailableBooks(); 
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title>DNU - Lập Phiếu Mượn Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="mt-3 mb-4 text-center">LẬP PHIẾU MƯỢN MỚI</h3>

                <?php
                // Hiển thị thông báo lỗi (nếu có)
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

                <form action="../../handle/borrow_process.php" method="POST">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="student_id" class="form-label">Chọn **Sinh viên** (Người mượn)</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="" disabled selected>--- Chọn Sinh viên ---</option>
                            <?php 
                            if (!empty($students)) {
                                foreach ($students as $student) {
                                    echo '<option value="' . $student['id'] . '">' 
                                        . htmlspecialchars($student['student_name']) . ' (Mã: ' . $student['id'] . ')' 
                                        . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Chưa có Sinh viên nào trong hệ thống!</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="book_id" class="form-label">Chọn **Sách** để mượn</label>
                        <select class="form-select" id="book_id" name="book_id" required>
                            <option value="" disabled selected>--- Chọn Sách ---</option>
                            <?php 
                            if (!empty($available_books)) {
                                foreach ($available_books as $book) {
                                    echo '<option value="' . $book['id'] . '">' 
                                        . htmlspecialchars($book['book_name']) . ' (Mã: ' . $book['id'] . ')' 
                                        . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Không có sách nào còn tồn kho để mượn!</option>';
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted mt-2">
                            Chỉ những sách còn tồn kho mới hiển thị ở đây.
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lập Phiếu Mượn</button>
                        <a href="../borrow.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>