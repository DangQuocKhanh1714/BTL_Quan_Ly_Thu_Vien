<?php
require_once __DIR__ . '/../functions/auth.php';
checkLogin('../login.php');
$current_user = getCurrentUser();
$username = $current_user['username'] ?? 'Khách';
?>
<!DOCTYPE html>
<html lang="vi">
    <style>
        :root {
            --main-bg: #bdc5cd;
        }
        body {
            background-color: var(--main-bg);
            min-height: 100vh;
            margin: 0;
        }
    </style>
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Trị Thư Viện</title> 
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>
                <i class="fas fa-book-open"></i>
                <span>Thư Viện</span>
            </h2>
        </div>
        <ul class="sidebar-nav">
    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'main.php') ? 'active' : '' ?>">
        <a href="main.php"><i class="fas fa-home"></i> Trang Chủ</a>
    </li>

    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'student.php') ? 'active' : '' ?>">
        <a href="student.php"><i class="fas fa-user-shield"></i> Quản lý Người Dùng</a>
    </li>

    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'book.php') ? 'active' : '' ?>">
        <a href="book.php"><i class="fas fa-database"></i> Quản lý Sách</a>
    </li>

    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'category.php') ? 'active' : '' ?>">
        <a href="category.php"><i class="fas fa-tags"></i> Quản lý Thể Loại</a>
    </li>

    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'borrow.php') ? 'active' : '' ?>">
        <a href="borrow.php"><i class="fas fa-file-invoice"></i> Quản lý Phiếu Mượn</a>
    </li>

    <li class="<?= (basename($_SERVER['PHP_SELF']) == 'logs.php') ? 'active' : '' ?>">
        <a href="logs.php"><i class="fas fa-history"></i> Nhật Ký Hệ Thống</a>
    </li>
</ul>

    </div>

    <div class="main-container">
        <div class="header">
            <h1>Bảng Điều Khiển Quản Trị Hệ Thống</h1>
            <div class="user-controls">
                <div class="user-menu-dropdown">
                    <button class="user-menu-btn" onclick="toggleDropdown()">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($username); ?>
                        <i class="fas fa-caret-down" style="margin-left: 5px;"></i>
                    </button>

                    <div id="userDropdown" class="dropdown-content">
                        <a href="#"><i class="fas fa-id-card"></i> Chi tiết tài khoản</a>
                        <a href="#"><i class="fas fa-cog"></i> Cài đặt</a>
                        <a href="../handle/logout_process.php" class="logout">
                            <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">