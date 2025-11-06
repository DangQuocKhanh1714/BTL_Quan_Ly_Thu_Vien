<?php 
require_once __DIR__ . '/../../functions/auth.php'; 
require_once __DIR__ . '/../../functions/borrow_functions.php';

checkLogin(__DIR__ . '/../../login.php'); 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../borrow.php?error=Không tìm thấy ID phiếu mượn");
    exit();
}

$slip_id = (int)$_GET['id'];
$slip = getBorrowSlipDetailsById($slip_id); 

if (!$slip) {
    header("Location: ../borrow.php?error=Phiếu mượn không tồn tại!");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Phiếu Mượn #<?= $slip_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9eef3;
            font-family: "Segoe UI", sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header i {
            font-size: 1.2rem;
        }
        h3 {
            color: #1f3b57;
            font-weight: 700;
            text-transform: uppercase;
        }
        .btn {
            border-radius: 8px;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-warning {
            color: #000;
            font-weight: 500;
        }
        .info-label {
            color: #495057;
            font-weight: 500;
        }
        .info-value {
            color: #212529;
        }
    </style>
</head>
<body> 
    <div class="container mt-5">
        <div class="text-center mb-4">
            <h3><i class="bi bi-journal-text me-2"></i>Chi tiết Phiếu Mượn #<?= $slip_id ?></h3>
            <hr class="w-25 mx-auto border-primary">
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle-fill"></i> Thông tin cơ bản
            </div>
            <div class="card-body">
                <p><span class="info-label">ID Phiếu:</span> <span class="info-value"><?= $slip["borrow_id"] ?></span></p>
                <p><span class="info-label">Ngày Mượn:</span> <span class="info-value"><?= $slip["borrow_date"] ?></span></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-person-fill"></i> Thông tin Sinh viên
            </div>
            <div class="card-body">
                <p><span class="info-label">Mã Sinh viên:</span> <span class="info-value"><?= $slip["student_id"] ?></span></p>
                <p><span class="info-label">Tên Sinh viên:</span> <span class="info-value"><?= htmlspecialchars($slip["student_name"] ?? 'N/A') ?></span></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <i class="bi bi-book-fill"></i> Thông tin Sách đã mượn
            </div>
            <div class="card-body">
                <p><span class="info-label">Mã Sách:</span> <span class="info-value"><?= $slip["book_id"] ?></span></p>
                <p><span class="info-label">Tên Sách:</span> <span class="info-value"><?= htmlspecialchars($slip["book_name"] ?? 'N/A') ?></span></p>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="../borrow.php" class="btn btn-secondary px-4">
                <i class="bi bi-arrow-left-circle"></i> Quay lại
            </a>
            <a href="edit_borrow.php?id=<?= $slip["borrow_id"] ?>" class="btn btn-warning px-4">
                <i class="bi bi-pencil-square"></i> Chỉnh sửa
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
