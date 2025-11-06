<?php
// File: views/student.php

require_once 'header.php';
require_once __DIR__ . '/../functions/auth.php';
checkLogin(__DIR__ . '/../login.php');

// Gọi process để load hàm handleGetAllStudents()
require '../handle/student_process.php'; 
// Hàm này gọi đến functions/student_functions.php và getAllStudents()
$students = handleGetAllStudents(); 

// --- HTML VÀ CSS KHÔNG THAY ĐỔI ---
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-3">
    <h3 class="mt-3">DANH SÁCH SINH VIÊN</h3>

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
        // Tự ẩn thông báo sau 3 giây
        // [Script Bootstrap tự động ẩn thông báo]
        setTimeout(() => {
            let alertNode = document.querySelector('.alert');
            if (alertNode) {
                let bsAlert = bootstrap.Alert.getOrCreateInstance(alertNode);
                bsAlert.close();
            }
        }, 3000);
    </script>

    <a href="student/create_student.php" class="btn btn-primary mb-3">Thêm</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Mã sinh viên</th>
                <th scope="col">Họ và tên</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Không cần require lần nữa, đã require ở trên
            // $students = handleGetAllStudents(); // Đã được gọi ở trên

            foreach ($students as $index => $stu) {
            ?>
                <tr>
                    <td><?= htmlspecialchars($stu["id"]) ?></td>
                    <td><?= htmlspecialchars($stu["student_id"] ?? $stu["student_code"] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($stu["student_name"]) ?></td>
                    <td>
                        <a href="student/edit_student.php?id=<?= $stu["id"] ?>" class="btn btn-warning btn-sm">Sửa</a>
                        <a href="../handle/student_process.php?action=delete&id=<?= $stu["id"] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này?')">Xóa</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
require_once 'footer.php'; // đóng layout
?>