<?php
// BẮT ĐẦU PHIÊN
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, chuyển hướng ngay lập tức
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Đường dẫn đi lùi ra một cấp (../) để tìm file config.php
require_once '../config.php';

$login_error = '';
$register_message = '';
$register_message_type = ''; // 'success' or 'error'

// Khởi tạo biến cho form đăng ký để giữ lại giá trị nếu có lỗi
$ho_ten = $_POST['ho_ten'] ?? '';
$username_reg = $_POST['username_reg'] ?? '';
$email_reg = $_POST['email_reg'] ?? '';

// Khởi tạo class mặc định: '' (rỗng) là hiển thị form Đăng nhập
$wrapper_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        // === LOGIC ĐĂNG NHẬP (Sign In) ===
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            try {
                // 1. Lấy thông tin người dùng
                $stmt = $db->prepare("SELECT
                                             ma_nguoi_dung, ho_ten, ten_dang_nhap, mat_khau, ma_vai_tro
                                             FROM nguoi_dung
                                             WHERE ten_dang_nhap = :username AND trang_thai = 1");

                $stmt->bindParam(':username', $username);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // 2. Kiểm tra mật khẩu bằng password_verify
                if ($user && $password == $user['mat_khau']) {
                    $_SESSION['user_id'] = $user['ma_nguoi_dung'];
                    $_SESSION['username'] = $user['ten_dang_nhap'];
                    $_SESSION['ho_ten'] = $user['ho_ten'];
                    $_SESSION['role_id'] = $user['ma_vai_tro'];

                    // Chuyển hướng về trang chủ chính (index.php)
                    header("Location: ../index.php");
                    exit();
                } else {
                    $login_error = "Tên đăng nhập hoặc mật khẩu không chính xác, hoặc tài khoản đã bị khóa.";
                }
            } catch (PDOException $e) {
                $login_error = "Lỗi hệ thống đăng nhập: " . $e->getMessage();
            }
        } else {
            $login_error = "Vui lòng nhập đầy đủ Tên đăng nhập và Mật khẩu.";
        }

        // Nếu Đăng nhập thất bại, giữ nguyên trạng thái mặc định (Sign In)
        $wrapper_class = '';
    } elseif ($action === 'register') {
        // === LOGIC ĐĂNG KÝ (Sign Up) ===
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $username_reg = trim($_POST['username_reg'] ?? '');
        $email_reg = trim($_POST['email_reg'] ?? '');
        $password = $_POST['password_reg'] ?? '';
        $validation_failed = false;

        // Tạm thời sử dụng $username_reg làm ten_dang_nhap
        $ten_dang_nhap_db = $username_reg;

        // 1. Kiểm tra dữ liệu đầu vào
        if (empty($ho_ten) || empty($ten_dang_nhap_db) || empty($email_reg) || empty($password)) {
            $register_message = "Vui lòng nhập đầy đủ Họ tên, Tên đăng nhập, Email và Mật khẩu.";
            $register_message_type = 'error';
            $validation_failed = true;
        } elseif (strlen($ten_dang_nhap_db) < 3) {
            $register_message = "Tên đăng nhập phải có ít nhất 3 ký tự.";
            $register_message_type = 'error';
            $validation_failed = true;
        } elseif (strlen($password) < 6) {
            $register_message = "Mật khẩu phải có ít nhất 6 ký tự.";
            $register_message_type = 'error';
            $validation_failed = true;
        }

        if (!$validation_failed) {
            try {
                $db->beginTransaction();

                // 2. Kiểm tra tên đăng nhập đã tồn tại chưa
                $stmt = $db->prepare("SELECT ten_dang_nhap FROM nguoi_dung WHERE ten_dang_nhap = :username");
                $stmt->bindParam(':username', $ten_dang_nhap_db);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $register_message = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
                    $register_message_type = 'error';
                } else {
                    // Mật khẩu được mã hóa an toàn
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // 3. Thêm người dùng mới vào database
                    $sql = "INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, email, mat_khau, ma_vai_tro, trang_thai) 
                            VALUES (:ho_ten, :username, :email, :password, 2, 1)";
                    $stmt = $db->prepare($sql);

                    $stmt->bindParam(':ho_ten', $ho_ten);
                    $stmt->bindParam(':username', $ten_dang_nhap_db);
                    $stmt->bindParam(':email', $email_reg);
                    $stmt->bindParam(':password', $hashed_password);

                    if ($stmt->execute()) {
                        $db->commit();
                        $register_message = "Đăng ký thành công! Vui lòng đăng nhập.";
                        $register_message_type = 'success';

                        // Xóa dữ liệu form và chuyển hướng sang panel Đăng nhập
                        $ho_ten = $username_reg = $email_reg = '';
                    } else {
                        $db->rollBack();
                        $register_message = "Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại.";
                        $register_message_type = 'error';
                    }
                }
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $register_message = "Lỗi hệ thống đăng ký: " . $e->getMessage();
                $register_message_type = 'error';
            }
        }

        // Logic Panel Fix: Nếu đăng ký thất bại, force hiển thị panel đăng ký
        if ($register_message_type === 'error') {
            $wrapper_class = 'active';
        }
        // Nếu đăng ký thành công, show panel đăng nhập (mặc định: $wrapper_class = '')
        else {
            $wrapper_class = '';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập tài khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.0/animate.min.css">
    <style>
        /* --- CÁC BIẾN MÀU DÀNH CHO CHỦ ĐỀ THƯ VIỆN/TRI THỨC --- */
        :root {
            --primary-color: #0077b6;
            /* Xanh Lam đậm - chủ đạo */
            --secondary-color: #00b4d8;
            /* Xanh Lam nhạt hơn - nhấn mạnh */
            --background-form: #ffffff;
            /* Nền form trắng */
            --background-body: #f1f4f8;
            /* Nền body mờ nhạt */
            --text-color: #333333;
            /* Màu chữ chính */
            --link-color: #03045e;
            /* Màu link/chi tiết */
            --shadow-color: rgba(0, 0, 0, 0.15);
            /* Màu đổ bóng nhẹ nhàng */
            --error-bg: #ffe0e0;
            --error-text: #cc0000;
            --success-bg: #e0f8ff;
            --success-text: #004d40;
            --overlay-color: rgba(0, 119, 182, 0.8);
            /* Màu overlay trong suốt */
        }

        body {
            /* Giả định bg1.jpg có thể được truy cập - Nên chọn ảnh nền liên quan đến sách hoặc kiến trúc */
            /* background-image: url(../assets/bg1.jpg);  */
            background-color: var(--background-body);
            background-position: center;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-color);
            overflow: hidden;
            margin: 0;
            font-family: sans-serif;
        }

        .container {
            border-radius: 20px;
            box-shadow: 0 10px 25px var(--shadow-color), 0 0 5px var(--shadow-color);
            position: relative;
            overflow: hidden;
            width: 768px;
            max-width: 90%;
            min-height: 550px;
            /* Tăng nhẹ chiều cao */
            margin: 60px auto;
        }

        .form {
            position: absolute;
            top: 0;
            background-color: var(--background-form);
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        form {
            display: flex;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
            box-sizing: border-box;
        }

        .login {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .register {
            left: 0;
            width: 50%;
            z-index: 1;
            opacity: 0;
        }

        form .top {
            margin: 15px 0 20px;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            /* Màu tiêu đề */
            text-align: center;
        }

        /* --- Input Styling --- */
        form .input {
            width: 100%;
            position: relative;
            margin-bottom: 30px;
        }

        .input input {
            width: 100%;
            border: none;
            outline: none;
            background-color: var(--background-form);
            border-bottom: 2px solid #aaaaaa;
            /* Màu border input */
            padding: 10px 0 5px 0;
            font-size: 17px;
            color: var(--text-color);
        }

        .input i {
            position: absolute;
            right: -2px;
            top: 10px;
            color: #999999;
            /* Màu icon */
        }

        /* Icon cho trường password (eye) */
        .login .input:nth-child(2) i,
        .register .input:nth-child(4) i {
            cursor: pointer;
        }


        .input label {
            position: absolute;
            left: 0;
            top: 10px;
            font-size: 15px;
            pointer-events: none;
            color: #999999;
            padding: 0;
            transition: 0.3s ease;
        }

        .input input:focus~label,
        .input input:valid~label {
            font-size: 13px;
            top: -15px;
            /* Đẩy lên cao hơn */
            color: var(--primary-color);
        }

        .input span {
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 0%;
            border-bottom: 3px solid var(--secondary-color);
            /* Màu gạch chân khi focus */
            transition: 0.5s ease-in-out;
        }

        .input input:focus~span {
            width: 100%;
        }

        /* Message Box Styling - Thêm cho PHP */
        .message-box {
            padding: 12px;
            margin: 10px 0 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-box.error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-text);
        }

        .message-box.success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-text);
        }


        /* --- Forget and Button Styling --- */
        .forget {
            display: flex;
            justify-content: flex-end;
            /* Căn phải */
            align-items: center;
            width: 100%;
            padding: 0 5px;
            margin: -5px 0 15px;
            font-size: 14px;
        }

        .forget a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
            transition: color 0.3s;
        }

        .forget a:hover {
            color: var(--link-color);
            text-decoration: underline;
        }

        button {
            margin: 10px 0;
            padding: 12px 50px;
            border: none;
            outline: none;
            background-color: var(--secondary-color);
            /* Màu nút */
            border-radius: 30px;
            cursor: pointer;
            color: white;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease-in-out;
        }

        /* Đảm bảo nút Register có chiều rộng đầy đủ */
        .form button#Sign-up,
        .form .login button {
            width: 100%;
        }

        button:hover {
            background-color: var(--primary-color);
            /* Màu nút hover */
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: 1.5px;
        }

        button:active {
            transform: scale(0.95);
        }

        /* Nút bên Overlay */
        button.btn {
            background-color: transparent;
            border: 2px solid white;
            padding: 10px 40px;
            font-size: 14px;
            color: white;
            box-shadow: none;
        }

        button.btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: white;
            letter-spacing: 0.5px;
        }

        .or {
            text-align: center;
            color: #999999;
            font-size: 14px;
            margin: 10px 0;
        }

        .icon {
            width: 100%;
            display: flex;
            column-gap: 15px;
            justify-content: center;
            margin-top: 10px;
        }

        .icon a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 50%;
            border: 1px solid var(--primary-color);
            text-align: center;
            font-size: 18px;
            transition: all 0.3s;
        }

        .icon a:hover {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        /* --- Content Container/Overlay Styling --- */
        .content-container {
            height: 100%;
            position: absolute;
            width: 50%;
            top: 0;
            left: 50%;
            overflow: hidden;
            z-index: 10;
            transition: transform 0.6s ease-in-out;
        }

        .content {
            /* Giả định bg2.jpg có thể được truy cập */
            background-image: url(../assets/bg2.jpg);
            height: 100%;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            position: relative;
            top: 0;
            width: 200%;
            left: -100%;
            transform: translateX(0);
            color: white;
            transition: transform 0.6s ease-in-out;
        }

        /* Thay thế nền bằng màu overlay gradient cho chủ đề thư viện */
        .content::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            opacity: 0.9;
        }


        .content-text {
            display: flex;
            position: absolute;
            top: 0;
            height: 100%;
            width: 50%;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 40px;
            flex-direction: column;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            font-size: large;
            box-sizing: border-box;
            z-index: 1;
            /* Đảm bảo chữ nằm trên lớp overlay */
        }

        .content-text .title {
            font-size: 36px;
            margin-bottom: 15px;
            font-weight: 900;
        }

        .content-text p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .left {
            left: 0;
        }

        .right {
            right: 0;
            transform: translateX(0);
        }

        /* --- Active States --- */
        .container.active .login {
            transform: translateX(100%);
        }

        .container.active .register {
            transform: translateX(100%);
            z-index: 3;
            opacity: 1;
        }

        .container.active .content-container {
            transform: translate(-100%);
        }

        .container.active .content {
            transform: translateX(50%);
        }

        /* Responsive Mobile Layout (Stacked Forms) */
        @media (max-width: 768px) {
            .container {
                min-height: 600px;
                margin: 30px auto;
                max-width: 95%;
            }

            /* Tắt hiệu ứng trượt/chuyển động desktop */
            .form,
            .login,
            .register {
                width: 100% !important;
                left: 0 !important;
                transform: translateX(0) !important;
                position: absolute;
                transition: opacity 0.6s ease-in-out, z-index 0s 0.6s;
            }

            /* Ẩn Content/Overlay */
            .content-container {
                display: none;
                width: 0 !important;
                left: 0 !important;
            }

            /* Form mặc định: Login (Ẩn Register) */
            .login {
                z-index: 3;
                opacity: 1;
            }

            .register {
                z-index: 1;
                opacity: 0;
            }

            /* Active State: Register (Ẩn Login) */
            .container.active .login {
                z-index: 1;
                opacity: 0;
            }

            .container.active .register {
                z-index: 3;
                opacity: 1;
            }

            /* Mobile Switch Button - Thêm vào form */
            .mobile-switch {
                display: block;
                text-align: center;
                margin-top: 15px;
                color: var(--primary-color);
                cursor: pointer;
                font-size: 15px;
                font-weight: 600;
            }
        }

        /* --- HEADER STYLING CHO PHÉP QUAY VỀ TRANG CHỦ --- */
        .main-header {
            position: fixed;
            /* Giữ header luôn ở trên cùng */
            top: 0;
            left: 0;
            width: 100%;
            height: 50px;
            /* Chiều cao cố định */
            background-color: var(--primary-color);
            /* Màu nền giống màu chủ đạo */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 100;
            /* Đảm bảo header nằm trên mọi thứ */
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .header-content {
            max-width: 1200px;
            /* Giới hạn chiều rộng nội dung (tùy chọn) */
            width: 100%;
            margin: 0 auto;
        }

        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            /* Màu chữ trắng */
            font-size: 18px;
            font-weight: 700;
            transition: opacity 0.3s;
        }

        .logo-link i {
            margin-right: 10px;
        }

        .logo-link:hover {
            opacity: 0.8;
        }

        /* Điều chỉnh lại vị trí của container để không bị che bởi header */
        body {
            /* Thêm padding-top bằng chiều cao của header */
            padding-top: 50px;
            /* Bỏ align-items: center; để container không bị đẩy xuống dưới cùng */
            align-items: flex-start;
        }

        /* Đảm bảo container vẫn nằm giữa màn hình */
        .container {
            margin-top: 30px;
            /* Thêm khoảng cách phía trên cho đẹp hơn */
            margin-bottom: 30px;
        }

        /* Điều chỉnh thêm cho mobile */
        @media (max-width: 768px) {
            .main-header {
                height: 45px;
            }

            body {
                padding-top: 45px;
            }

            .logo-link {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="header-content">
            <a href="../index.php" class="logo-link">
                <i class="fas fa-book-reader"></i>
                <span>THƯ VIỆN KỸ THUẬT</span>
            </a>
        </div>
    </header>
    <div class="container <?php echo $wrapper_class; ?>" id="container">

        <div class="form register">
            <form action="auth.php" method="POST">
                <h2 class="top">Đăng ký Tài khoản</h2>
                <input type="hidden" name="action" value="register">

                <?php if ($register_message): ?>
                    <div class="message-box <?php echo $register_message_type; ?> animate__animated animate__headShake">
                        <?php echo $register_message; ?>
                    </div>
                <?php endif; ?>

                <div class="input">
                    <input type="text" name="ho_ten" required value="<?php echo htmlspecialchars($ho_ten); ?>">
                    <label>Họ và Tên</label>
                    <i class="fa-solid fa-signature"></i>
                    <span></span>
                </div>

                <div class="input">
                    <input type="text" name="username_reg" required value="<?php echo htmlspecialchars($username_reg); ?>">
                    <label>Tên Đăng Nhập</label>
                    <i class="fa-solid fa-user"></i>
                    <span></span>
                </div>

                <div class="input">
                    <input type="email" name="email_reg" required value="<?php echo htmlspecialchars($email_reg); ?>">
                    <label>Email</label>
                    <i class="fa-solid fa-envelope"></i>
                    <span></span>
                </div>

                <div class="input">
                    <input type="password" name="password_reg" required>
                    <label>Mật Khẩu</label>
                    <i class="fa-solid fa-eye" id="toggleRegPassword"></i>
                    <span></span>
                </div>

                <button type="submit" id="Sign-up">Đăng ký</button>
            </form>
        </div>

        <div class="form login">
            <form action="auth.php" method="POST">
                <h2 class="top">Đăng nhập</h2>
                <input type="hidden" name="action" value="login">

                <?php if ($login_error): ?>
                    <div class="message-box error animate__animated animate__headShake">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <div class="input">
                    <input type="text" name="username" required>
                    <label>Tên Đăng Nhập</label>
                    <i class="fa-solid fa-user"></i>
                    <span></span>
                </div>
                <div class="input">
                    <input type="password" name="password" required>
                    <label>Mật Khẩu</label>
                    <i class="fa-solid fa-eye" id="toggleLoginPassword"></i>
                    <span></span>
                </div>

                <button type="submit">Đăng nhập</button>
            </form>
        </div>

        <div class="content-container">
            <div class="content">
                <div class="content-text left">
                    <i class="fas fa-book-open fa-4x" style="margin-bottom: 20px;"></i>
                    <h2 class="title">Sẵn sàng Khám phá?</h2>
                    <p>Hãy cùng chúng tôi xây dựng Thư viện tri thức của bạn! Đăng nhập để tiếp tục những trải nghiệm học tập và làm việc tuyệt vời.</p>
                    <button class="btn" id="login">Đăng Nhập Ngay <i class="fa-solid fa-arrow-left"></i></button>
                </div>
                <div class="content-text right">
                    <i class="fas fa-user-plus fa-4x" style="margin-bottom: 20px;"></i>
                    <h2 class="title">Chào mừng Trở lại!</h2>
                    <p>Truy cập ngay vào bộ sưu tập sách và tài liệu khổng lồ của chúng tôi. Nếu bạn là thành viên mới, hãy đăng ký ngay!</p>
                    <button class="btn" id="register">Đăng ký ngay <i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const loginButton = document.getElementById('login');
        const registerButton = document.getElementById('register');
        const container = document.getElementById('container');

        // Mobile Switches
        const mobileLogin = document.getElementById('mobileLogin');
        const mobileRegister = document.getElementById('mobileRegister');

        // Password Toggles
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const toggleRegPassword = document.getElementById('toggleRegPassword');


        // --- Desktop & Overlay Logic ---
        if (registerButton) {
            registerButton.addEventListener("click", () => {
                container.classList.add("active");
            });
        }

        if (loginButton) {
            loginButton.addEventListener("click", () => {
                container.classList.remove("active");
            });
        }

        // --- Mobile Logic ---
        if (mobileRegister) {
            mobileRegister.addEventListener("click", () => {
                container.classList.add("active");
            });
        }

        if (mobileLogin) {
            mobileLogin.addEventListener("click", () => {
                container.classList.remove("active");
            });
        }

        // --- Password Visibility Toggle ---
        function setupPasswordToggle(toggleId, inputSelector) {
            const toggle = document.getElementById(toggleId);
            if (toggle) {
                const input = toggle.closest('.input').querySelector(inputSelector);
                toggle.addEventListener('click', () => {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    toggle.classList.toggle('fa-eye-slash');
                });
            }
        }

        // Setup toggles 
        setupPasswordToggle('toggleLoginPassword', 'input[name="password"]');
        setupPasswordToggle('toggleRegPassword', 'input[name="password_reg"]');
    </script>
</body>

</html>