<?php
session_start(); // RẤT QUAN TRỌNG: Khởi động session để đọc lỗi/thành công

// Khởi tạo biến $error_message và $success_message từ Session
$error_message = $_SESSION['error'] ?? '';
$success_message = $_SESSION['success'] ?? '';

// Xóa session sau khi đã lấy ra để không hiển thị lại
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/login.css">
  <title>Đăng Nhập Quản Lý Thư Viện</title>
  
</head>
<body>

  <div class="login-container">
    <h2>Đăng Nhập Quản Trị</h2>

    <?php if ($error_message): ?>
      <p class="error"><?php echo $error_message; ?></p>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <p class="error" style="color: var(--primary-color);"><?php echo $success_message; ?></p>
    <?php endif; ?>


    <form method="POST" action="handle/login_process.php">
      <div class="form-group">
        <label for="username">Tên Đăng Nhập</label>
        <input type="text" id="username" name="username" required autocomplete="username" placeholder="Nhập tên đăng nhập">
      </div>

      <div class="form-group">
        <label for="password">Mật Khẩu</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu">
      </div>
      
      <input type="hidden" name="login" value="1"> 

      <button type="submit" class="btn-login">Đăng Nhập</button>
    </form>
  </div>

</body>
</html>
