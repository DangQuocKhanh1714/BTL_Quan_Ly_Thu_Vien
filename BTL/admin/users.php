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
$title = "Quản lý Người dùng";
$users = [];
$total_users_count = 0;
$limit = 5;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;

// ===============================================
// --- BỔ SUNG: XỬ LÝ KHÓA/MỞ KHÓA NGƯỜI DÙNG ---
// ===============================================

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['id'];
    $new_status = null;
    $success_message = '';

    if ($action === 'lock') {
        $new_status = 0; // 0: Đã khóa/Vô hiệu hóa
        $success_message = 'Đã khóa tài khoản người dùng ID: ' . $user_id . ' thành công.';
    } elseif ($action === 'unlock') {
        $new_status = 1; // 1: Kích hoạt
        $success_message = 'Đã mở khóa tài khoản người dùng ID: ' . $user_id . ' thành công.';
    }

    if ($new_status !== null) {
        try {
            // Ngăn không cho Admin tự khóa tài khoản của chính mình (Giả sử Admin luôn là ID 1)
            if ($user_id == $_SESSION['user_id'] && $new_status === 0) {
                 $error = "Lỗi: Bạn không thể tự khóa tài khoản Admin của mình.";
            } else {
                $stmt = $db->prepare("UPDATE nguoi_dung SET trang_thai = :new_status WHERE ma_nguoi_dung = :user_id");
                $stmt->bindParam(':new_status', $new_status, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Chuyển hướng để xóa tham số GET khỏi URL và hiển thị tin nhắn thành công
                $_SESSION['success_message'] = $success_message;
                header("Location: users.php?p={$page}");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Lỗi cập nhật trạng thái: " . $e->getMessage();
        }
    }
}

// Kiểm tra và hiển thị thông báo thành công sau khi chuyển hướng
$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// --- LẤY DỮ LIỆU NGƯỜI DÙNG ---
try {
    // Lấy tổng số người dùng
    $total_users_count = $db->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();

    // Lấy danh sách người dùng
    $stmt = $db->prepare("
        SELECT 
            nd.ma_nguoi_dung, 
            nd.ho_ten, 
            nd.email, 
            nd.so_dien_thoai, 
            nd.ngay_dang_ky, 
            nd.trang_thai AS trang_thai_tai_khoan, 
            vt.ten_vai_tro AS ten_quyen 
        FROM nguoi_dung nd
        JOIN vai_tro vt ON nd.ma_vai_tro = vt.ma_vai_tro
        ORDER BY nd.ma_nguoi_dung ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi lấy dữ liệu người dùng: " . $e->getMessage();
}

$total_pages = ceil($total_users_count / $limit);
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
            --warning-color: #ffc107; 
            --info-color: #0dcaf0; 
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

        /* --- LAYOUT 2 CỘT --- */
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
        
        /* --- SIDEBAR MENU --- */
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
        .sidebar-menu a:hover i {
            color: white;
        }
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        .sidebar-menu a.active i {
            color: white;
        }

        /* --- BREADCRUMB --- */
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
        
        /* --- CARD & GRID --- */
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
        .card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        /* --- TABLE STYLE --- */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: left;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle; 
        }
        .user-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        .user-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .user-table tr:hover {
            background-color: #e9ecef;
        }

        /* --- BUTTON & BADGE STYLE --- */
        .btn {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 600;
            transition: opacity 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn i { margin-right: 5px; }

        .btn-edit { background-color: var(--info-color); color: white; }
        .btn-lock { background-color: var(--danger-color); color: white; } 
        .btn-unlock { background-color: var(--success-color); color: white; }
        .btn-primary { background-color: var(--primary-color); color: white; }

        /* --- Nhóm và căn chỉnh các nút thao tác --- */
        .action-buttons {
            display: flex;
            gap: 5px; 
            flex-wrap: nowrap; 
            justify-content: flex-start;
        }
        .action-buttons .btn {
            padding: 8px 10px;
            font-size: 0.85em;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8em;
            font-weight: 700;
            display: inline-block;
        }
        .status-active { background-color: #d1e7dd; color: var(--success-color); } 
        .status-inactive { background-color: #f8d7da; color: var(--danger-color); } 

        /* --- Alert Messages (Thêm vào) --- */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: var(--radius);
            font-weight: 600;
        }
        .alert.error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert.success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        
        /* --- Phân Trang (Pagination) --- */
        .pagination {
            display: flex;
            justify-content: center;
            padding: 15px 0 0 0;
            list-style: none;
        }
        .pagination li a, .pagination li span {
            padding: 8px 12px;
            margin: 0 4px;
            text-decoration: none;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .pagination li a:hover {
            background-color: #e9ecef;
        }
        .pagination li.active a, .pagination li.active span {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* --- Responsive Mobile --- */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding-bottom: 0; }
            .sidebar-menu { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; padding: 10px 15px; }
            .sidebar-menu a { padding: 10px 15px; font-size: 0.9em; margin-bottom: 0; }
            .sidebar-menu hr { display: none; }
            .sidebar-header { margin-bottom: 10px; }
            .main-content-wrapper { max-width: 100%; }
            .user-table th, .user-table td { font-size: 0.9em; }
            .user-table thead { display: none; } 
            .user-table, .user-table tbody, .user-table tr, .user-table td { display: block; width: 100%; }
            .user-table tr { margin-bottom: 15px; border: 1px solid var(--border-color); border-radius: var(--radius); }
            .user-table td { 
                text-align: right; 
                padding-left: 50%;
                position: relative;
                border: none;
            }
            .user-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: calc(50% - 30px);
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: #6c757d;
            }
            .action-buttons {
                justify-content: flex-end; 
            }
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
            <strong>Quản lý người dùng</strong>
        </div>

        <div class="card">
            <h3><i class="fas fa-user-shield"></i> Danh Sách Người Dùng (Tổng: <?php echo number_format($total_users_count); ?>)</h3>
        </div>

        <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>

        <div class="card full-width-report">
            <a href="add_user.php" class="btn btn-primary" style="margin-top: 5px; margin-bottom: 15px;"><i class="fas fa-plus-circle"></i> Thêm Người Dùng Mới</a>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ Tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Quyền</th>
                        <th>Ngày Đăng Ký</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8">Không tìm thấy người dùng nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="STT"><?php echo $user['ma_nguoi_dung']; ?></td>
                            <td data-label="Họ Tên"><?php echo htmlspecialchars($user['ho_ten']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td data-label="SĐT"><?php echo htmlspecialchars($user['so_dien_thoai']); ?></td>
                            <td data-label="Quyền"><strong><?php echo htmlspecialchars($user['ten_quyen']); ?></strong></td>
                            <td data-label="Ngày Đăng Ký"><?php echo date('d/m/Y', strtotime($user['ngay_dang_ky'])); ?></td>
                            <td data-label="Trạng Thái">
                                <?php if ($user['trang_thai_tai_khoan'] == 1): ?>
                                    <span class="status-badge status-active">Kích hoạt</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Thao Tác">
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?php echo $user['ma_nguoi_dung']; ?>" class="btn btn-edit"><i class="fas fa-pen"></i> Sửa</a>
                                    
                                    <?php if ($user['trang_thai_tai_khoan'] == 1): ?>
                                        <a href="users.php?action=lock&id=<?php echo $user['ma_nguoi_dung']; ?>" class="btn btn-lock" onclick="return confirm('Bạn có chắc chắn muốn KHÓA tài khoản này?');"><i class="fas fa-lock"></i> Khóa</a>
                                    <?php else: ?>
                                        <a href="users.php?action=unlock&id=<?php echo $user['ma_nguoi_dung']; ?>" class="btn btn-unlock" onclick="return confirm('Bạn có chắc chắn muốn MỞ KHÓA tài khoản này?');"><i class="fas fa-lock-open"></i> Mở Khóa</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <ul class="pagination">
                <?php if ($total_pages > 1): ?>
                    <li class="<?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a href="users.php?p=<?php echo $page - 1; ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a href="users.php?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="<?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a href="users.php?p=<?php echo $page + 1; ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

</body>
</html>