<?php 
// File: views/borrow/edit_borrow.php

require_once __DIR__ . '/../../functions/auth.php'; 
require_once __DIR__ . '/../../functions/borrow_functions.php';
// Cần thêm file functions để lấy danh sách Student và Book
require_once __DIR__ . '/../../functions/student_functions.php'; 
require_once __DIR__ . '/../../functions/book_functions.php'; 

checkLogin(__DIR__ . '/../../login.php'); 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../borrow.php?error=Không tìm thấy ID phiếu mượn");
    exit();
}

$slip_id = (int)$_GET['id'];
// Sử dụng hàm getBorrowSlipById để lấy ID sinh viên và ID sách
$slip = getBorrowSlipById($slip_id); 

if (!$slip) {
    header("Location: ../borrow.php?error=Phiếu mượn không tồn tại!");
    exit();
}

// Lấy danh sách Sinh viên và Sách cho dropdown
$students = getAllStudents();
$books = getAllBooksDropdown(); // Lấy tất cả sách

// --- XỬ LÝ FORM SUBMIT (Giả định nằm trong handle/borrow_process.php) ---
// if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_borrow'])) {
//     // Logic gọi hàm updateBorrowSlip($slip_id, $new_student_id, $new_book_id)
// }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa Phiếu Mượn #<?= $slip_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    </head>
<body> 
    <div class="container mt-5">
        <div class="text-center mb-4">
            <h3><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa Phiếu Mượn #<?= $slip_id ?></h3>
            <hr class="w-25 mx-auto border-warning">
        </div>
        
        <?php 
        // Hiển thị thông báo (SUCCESS/ERROR) nếu có từ process
        if (isset($_GET['success'])) { /* ... */ } 
        if (isset($_GET['error'])) { /* ... */ } 
        ?>

        <div class="card p-4 mx-auto" style="max-width: 600px;">
            <form action="../../handle/borrow_process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $slip_id ?>">

                <div class="mb-3">
                    <label for="student_id" class="form-label info-label">Tên Sinh viên:</label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">-- Chọn Sinh viên --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>" 
                                <?= $student['id'] == $slip['student_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['student_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="book_id" class="form-label info-label">Tên Sách:</label>
                    <select class="form-select" id="book_id" name="book_id" required>
                        <option value="">-- Chọn Sách --</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['id'] ?>" 
                                <?= $book['id'] == $slip['book_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($book['book_name']) ?> 
                                (Tồn kho: <?= $book['quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label info-label">Ngày Mượn Gốc:</label>
                    <input type="text" class="form-control" value="<?= $slip["borrow_date"] ?>" disabled>
                    <small class="text-muted">Ngày này không thể thay đổi sau khi tạo phiếu.</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="../borrow.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Hủy
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Cập nhật Phiếu Mượn
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>