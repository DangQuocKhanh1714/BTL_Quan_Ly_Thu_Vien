<?php
// layout/header.php

if (!isset($require_session)) {
    $require_session = false;
}

if ($require_session && session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
// 1=Admin, 2=Thu_thu, 3=Nguoi_doc. 0 là Khách.
$user_role_id = $is_logged_in ? ($_SESSION['role_id'] ?? 0) : 0; 
$user_full_name = $is_logged_in ? $_SESSION['ho_ten'] : 'Khách';

$title = $title ?? 'Thư Viện Sách Kỹ Thuật'; 

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS CHUNG VÀ BIẾN MÀU MỚI */
        :root {
            --primary-color: #1f3c88; /* Xanh Navy Thư Viện */
            --secondary-color: #f2b236; /* Vàng Nhấn */
            --text-color: #333;
            --bg-color: #f4f6f9; /* Nền xám nhạt hiện đại */
            --card-bg: #fff;
            --danger-color: #dc3545;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        .container {
            width: 90%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px 0;
        }
        main {
            min-height: 60vh;
            /* Đã loại bỏ display: flex; và gap: 30px; */
            padding-top: 0;
        }
        
        /* NAVBAR STYLE */
        .navbar {
            background-color: var(--card-bg);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            max-width: 1300px;
            margin: 0 auto;
        }
        .logo a {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 30px;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            padding: 5px 10px;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: var(--primary-color);
        }

        /* USER PROFILE DROPDOWN */
        .user-profile {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .user-profile:hover {
            background-color: #eee;
        }
        .profile-icon {
            color: var(--primary-color);
            font-size: 1.2em;
        }
        .user-name {
            font-weight: 600;
            color: var(--primary-color);
        }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background-color: var(--card-bg);
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 100;
            display: none;
            list-style: none;
            padding: 10px 0;
        }
        .dropdown-menu li a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: var(--text-color);
            transition: background-color 0.2s, color 0.2s;
        }
        .dropdown-menu li a:hover {
            background-color: var(--bg-color);
            color: var(--primary-color);
        }
        .dropdown-menu li.divider {
            height: 1px;
            background-color: #eee;
            margin: 5px 0;
        }
        .dropdown-menu li.logout a {
            color: var(--danger-color);
        }
        .dropdown-menu li.logout a:hover {
            background-color: #ffeaea;
        }
        .auth-actions .btn-login {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.3s, transform 0.2s;
            background-color: var(--secondary-color) !important; 
            color: var(--primary-color) !important;
        }
        .auth-actions .btn-login:hover {
            background-color: #e0a800 !important;
            transform: translateY(-1px);
        }
        
        /* HERO BANNER - FULL WIDTH */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hero-banner {
            background: linear-gradient(-45deg, #1f3c88, #2c51b7, #1f3c88);
            background-size: 400% 400%;
            animation: slideBackground 10s ease infinite alternate;
            color: white;
            padding: 80px 20px;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
            /* Chắc chắn nó chiếm toàn bộ chiều ngang của container */
        }
        @keyframes slideBackground {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(31, 60, 136, 0.2); 
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            animation: fadeIn 1s ease-out;
        }
        .banner-icon { color: var(--secondary-color); margin-bottom: 15px; }
        .hero-content h1 { font-size: 2.5em; margin: 0 0 10px; font-weight: 700;}
        .hero-content p { font-size: 1.2em; font-weight: 300; }
        
    </style>
</head>
<body>

<div class="navbar">
    <div class="navbar-content">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-book-reader"></i> Thư Viện</a>
        </div>
        <ul class="nav-links">
            <li><a href="<?php echo BASE_URL; ?>index.php#books"><i class="fas fa-book-open-reader"></i> Tất Cả Sách</a></li>
        </ul>
        <div class="auth-actions">
            <?php if ($is_logged_in): ?>
                <div class="user-profile" id="userProfile">
                    <i class="fas fa-user-circle profile-icon"></i>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['ho_ten']); ?> <i class="fas fa-caret-down"></i></span>
                    <ul class="dropdown-menu" id="userDropdown">
                        <li><a href="<?php echo BASE_URL; ?>profile/info.php"><i class="fas fa-info-circle"></i> Xem Thông Tin</a></li>
                        
                        <?php if ($user_role_id == 1): // Admin ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-user-shield"></i> Trang Quản Lý</a></li>
                        <?php elseif ($user_role_id == 2): // Librarian ?>
                            <li><a href="<?php echo BASE_URL; ?>librarian/dashboard.php"><i class="fas fa-user-tie"></i> Trang Thủ Thư</a></li>
                        <?php elseif ($user_role_id == 3): // Reader ?>
                            <li><a href="<?php echo BASE_URL; ?>reader/history.php?type=muon"><i class="fas fa-history"></i> Lịch Sử Mượn</a></li>
                            <li><a href="<?php echo BASE_URL; ?>reader/history.php?type=tra"><i class="fas fa-undo"></i> Lịch Sử Trả</a></li>
                            <li><a href="<?php echo BASE_URL; ?>reader/history.php?type=phat"><i class="fas fa-dollar-sign"></i> Lịch Sử Phạt</a></li>
                        <?php endif; ?>
                        
                        <li class="divider"></li>
                        <li class="logout"><a href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>auth/auth.php" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> Đăng Nhập</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <main>