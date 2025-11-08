<?php
session_start();
require_once '../config.php';

// Bảo vệ trang: Chỉ cho phép Admin (Role ID 1) truy cập
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

$db = $GLOBALS['db'];
$error = '';
$success = '';
$title = "Sửa Thông Tin Người Dùng";
$roles = [];
$user = null;

// Kiểm tra ID người dùng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}
$user_id = (int)$_GET['id'];

// Lấy danh sách Vai trò (Roles)
try {
    $roles = $db->query("SELECT ma_vai_tro, ten_vai_tro FROM vai_tro")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi lấy danh sách vai trò: " . $e->getMessage();
}

// ===============================================
// --- XỬ LÝ CẬP NHẬT DỮ LIỆU ---
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim($_POST['ho_ten']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Mật khẩu mới (có thể để trống)
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $ma_vai_tro = (int)$_POST['ma_vai_tro'];
    $trang_thai = (int)$_POST['trang_thai'];

    if (empty($ho_ten) || empty($email) || empty($so_dien_thoai)) {
        $error = "Vui lòng điền đầy đủ các trường bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Định dạng email không hợp lệ.";
    } else {
        try {
            // Chuẩn bị câu lệnh SQL cơ bản
            $sql = "UPDATE nguoi_dung SET ho_ten = :ho_ten, email = :email, so_dien_thoai = :sdt, ma_vai_tro = :ma_vai_tro, trang_thai = :trang_thai";
            $params = [
                ':ho_ten' => $ho_ten,
                ':email' => $email,
                ':sdt' => $so_dien_thoai,
                ':ma_vai_tro' => $ma_vai_tro,
                ':trang_thai' => $trang_thai,
                ':id' => $user_id
            ];

            // Nếu người dùng nhập mật khẩu mới, hash và thêm vào câu lệnh SQL
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", mat_khau = :mat_khau";
                $params[':mat_khau'] = $hashed_password;
            }

            $sql .= " WHERE ma_nguoi_dung = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['success_message'] = "Cập nhật thông tin người dùng $ho_ten thành công!";
            header("Location: users.php");
            exit();

        } catch (PDOException $e) {
            $error = "Lỗi CSDL khi cập nhật: " . $e->getMessage();
        }
    }
}


// ===============================================
// --- LẤY DỮ LIỆU CŨ ĐỂ ĐIỀN VÀO FORM (GET) ---
// ===============================================
try {
    $stmt = $db->prepare("SELECT * FROM nguoi_dung WHERE ma_nguoi_dung = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Không tìm thấy người dùng có ID: " . $user_id;
    }
} catch (PDOException $e) {
    $error = "Lỗi lấy dữ liệu người dùng: " . $e->getMessage();
}

// Nếu có lỗi, không hiển thị form
if ($error && !$user) {
    // Chuyển hướng hoặc xử lý lỗi nếu người dùng không tồn tại
    // Ở đây tôi giữ lại để thông báo lỗi rõ ràng.
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd; 
            --success-color: #198754; 
            --danger-color: #dc3545; 
            --bg-light: #f4f6f9; 
            --text-color: #212529;
            --border-color: #e9ecef;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            --radius: 0.75rem;
            --sidebar-width: 250px;
        }
        body {
            background-color: var(--bg-light); 
            display: flex; 
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 20px 0;
            position: sticky; 
            top: 0;
            height: 100vh;
            flex-shrink: 0; 
            overflow-y: auto;
        }
        .main-content-wrapper {
            flex-grow: 1; 
            padding: 20px;
            max-width: calc(100% - var(--sidebar-width)); 
        }
        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }
        .sidebar-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0 15px;
            margin: 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 1.1rem;
            color: #6c757d;
        }
        .sidebar-menu a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        .breadcrumb {
            padding: 0;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .card { 
            background: white; 
            border: none;
            border-radius: var(--radius); 
            box-shadow: var(--card-shadow); 
            padding: 25px; 
            margin-bottom: 25px; 
        }
        .card h3 { 
            font-size: 1.8rem; 
            margin-bottom: 0; 
            color: var(--text-color);
            font-weight: 700;
        }

        /* --- FORM STYLES --- */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* --- BUTTON STYLES --- */
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .btn i { margin-right: 8px; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-secondary { background-color: #6c757d; color: white; margin-left: 10px; }
        .btn:hover { opacity: 0.9; }
        .btn-info { background-color: #0dcaf0; color: white; }

        /* --- Alert Messages --- */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: var(--radius);
            font-weight: 600;
        }
        .alert.error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Responsive Mobile */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding-bottom: 0; }
            .sidebar-menu { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; padding: 10px 15px; }
            .main-content-wrapper { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-tools"></i> ADMIN PANEL</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
        <li><a href="users.php" class="active"><i class="fas fa-user-shield"></i> Quản lý Người dùng</a></li>
        <li><a href="transactions.php?tab=books"><i class="fas fa-book"></i> Quản lý Sách</a></li>
        <li><a href="transactions.php?tab=loans"><i class="fas fa-exchange-alt"></i> Xử lý Mượn/Trả</a></li>
        <li><a href="transactions.php?tab=fines"><i class="fas fa-gavel"></i> Quản lý Phiếu phạt</a></li>
        
        <hr style="border-color: #f1f1f1; margin: 15px 0;">
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="main-content-wrapper">
    <div class="admin-container">

        <div class="breadcrumb">
            <a href="dashboard.php">Quản lý thư viện</a>
            <span>/</span>
            <a href="users.php">Quản lý người dùng</a>
            <span>/</span>
            <strong>Sửa người dùng #<?php echo $user_id; ?></strong>
        </div>

        <div class="card">
            <h3><i class="fas fa-user-edit"></i> Sửa Thông Tin Người Dùng #<?php echo $user_id; ?></h3>
        </div>

        <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

        <?php if ($user): ?>
            <div class="card full-width-report">
                <form method="POST" action="edit_user.php?id=<?php echo $user_id; ?>">
                    <div class="form-group">
                        <label for="ho_ten">Họ Tên</label>
                        <input type="text" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($_POST['ho_ten'] ?? $user['ho_ten']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật Khẩu Mới (Bỏ trống nếu không muốn thay đổi)</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="so_dien_thoai">Số Điện Thoại</label>
                        <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="<?php echo htmlspecialchars($_POST['so_dien_thoai'] ?? $user['so_dien_thoai']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="ma_vai_tro">Vai Trò</label>
                        <select id="ma_vai_tro" name="ma_vai_tro" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['ma_vai_tro']; ?>" 
                                    <?php 
                                        $selected_role = $_POST['ma_vai_tro'] ?? $user['ma_vai_tro'];
                                        echo ($selected_role == $role['ma_vai_tro']) ? 'selected' : ''; 
                                    ?>>
                                    <?php echo htmlspecialchars($role['ten_vai_tro']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="trang_thai">Trạng Thái</label>
                        <select id="trang_thai" name="trang_thai" required>
                            <?php 
                                $selected_status = $_POST['trang_thai'] ?? $user['trang_thai'];
                            ?>
                            <option value="1" <?php echo ($selected_status == 1) ? 'selected' : ''; ?>>1 - Kích hoạt</option>
                            <option value="0" <?php echo ($selected_status == 0) ? 'selected' : ''; ?>>0 - Đã khóa/Vô hiệu hóa</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                    <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>