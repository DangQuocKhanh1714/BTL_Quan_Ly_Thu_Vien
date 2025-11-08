<?php
// Đường dẫn đi lùi ra một cấp (../) để tìm file config.php
session_start();
require_once '../config.php';

// Bảo vệ trang: Chỉ cho phép Thủ thư (Role ID 2) truy cập
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

$db = $GLOBALS['db'];
$message = '';
$message_type = ''; // 'success' hoặc 'danger'
$book_data = [
    'ten_sach' => '', 
    'ten_tac_gia' => '', 
    'nam_xuat_ban' => '', 
    'nha_xuat_ban' => '',
    'tong_so_luong' => '', 
    'so_luong_kha_dung' => ''
];
$edit_mode = false;
$book_id = null;
$dang_muon = 0; // Số sách đang được mượn, chỉ dùng trong chế độ sửa

// --- HÀM HỖ TRỢ: TÌM HOẶC THÊM TÁC GIẢ ---
function get_or_create_author_id($db, $author_name) {
    // 1. Tìm tác giả
    $stmt = $db->prepare("SELECT ma_tac_gia FROM tac_gia WHERE ten_tac_gia = ?");
    $stmt->execute([$author_name]);
    $author_id = $stmt->fetchColumn();

    if ($author_id) {
        return $author_id; // Đã tìm thấy
    }

    // 2. Thêm mới tác giả
    $stmt = $db->prepare("INSERT INTO tac_gia (ten_tac_gia, tieu_su) VALUES (?, ?)");
    $stmt->execute([$author_name, 'Tác giả mới được thêm tự động']);
    return $db->lastInsertId(); // Trả về ID mới
}

// --- HÀM CHUYỂN HƯỚNG VÀ HIỂN THỊ THÔNG BÁO ---
function redirect_with_message($msg, $type) {
    // Dùng Session để lưu Flash Message (Giúp thông báo không bị mất khi redirect)
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = $type;
    header("Location: crud_book.php");
    exit();
}

// Xử lý thông báo sau khi chuyển hướng
if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
    $message = htmlspecialchars($_SESSION['flash_message']);
    $message_type = htmlspecialchars($_SESSION['flash_type']);
    unset($_SESSION['flash_message']); // Xóa message sau khi hiển thị
    unset($_SESSION['flash_type']);
}

// --- XỬ LÝ LẤY DỮ LIỆU SÁCH ĐỂ SỬA (ĐÃ THÊM JOIN TÁC GIẢ) ---
if (isset($_GET['edit_id'])) {
    $book_id = (int)$_GET['edit_id'];
    try {
        $stmt = $db->prepare("
            SELECT s.*, tg.ten_tac_gia 
            FROM sach s 
            JOIN tac_gia tg ON s.ma_tac_gia = tg.ma_tac_gia
            WHERE s.ma_sach = ?
        ");
        $stmt->execute([$book_id]);
        $fetched_book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetched_book) {
            // Ánh xạ dữ liệu từ CSDL sang mảng dữ liệu form
            $book_data = [
                'ma_sach' => $fetched_book['ma_sach'],
                'ten_sach' => $fetched_book['ten_sach'],
                'ten_tac_gia' => $fetched_book['ten_tac_gia'], 
                'nam_xuat_ban' => $fetched_book['nam_xuat_ban'],
                'nha_xuat_ban' => $fetched_book['nha_xuat_ban'],
                'tong_so_luong' => $fetched_book['tong_so_luong'],
                'so_luong_kha_dung' => $fetched_book['so_luong_kha_dung']
            ];
            $edit_mode = true;
            
            // Tính số sách đang được mượn (để áp dụng ràng buộc khi sửa)
            $dang_muon = $book_data['tong_so_luong'] - $book_data['so_luong_kha_dung'];
        } else {
            redirect_with_message("Không tìm thấy sách cần sửa.", 'danger');
        }
    } catch (PDOException $e) {
        redirect_with_message("Lỗi truy vấn dữ liệu: " . $e->getMessage(), 'danger');
    }
}


// --- XỬ LÝ THÊM/SỬA SÁCH (LOGIC TỒN KHO VÀ RÀNG BUỘC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['ma_sach']) ? (int)$_POST['ma_sach'] : null;
    $ten_sach = trim($_POST['ten_sach']);
    $ten_tac_gia = trim($_POST['ten_tac_gia']);
    $nam_xuat_ban = filter_var($_POST['nam_xuat_ban'], FILTER_VALIDATE_INT);
    $nha_xuat_ban = trim($_POST['nha_xuat_ban']);
    $tong_so_luong = filter_var($_POST['tong_so_luong'], FILTER_VALIDATE_INT);
    
    // Giữ nguyên dữ liệu để hiển thị lại form nếu có lỗi
    $book_data = [
        'ma_sach' => $book_id,
        'ten_sach' => $ten_sach, 
        'ten_tac_gia' => $ten_tac_gia, 
        'nam_xuat_ban' => $nam_xuat_ban,
        'nha_xuat_ban' => $nha_xuat_ban,
        'tong_so_luong' => $tong_so_luong,
        'so_luong_kha_dung' => 0 
    ];

    // Validation
    if (empty($ten_sach) || empty($ten_tac_gia) || empty($nha_xuat_ban) || $nam_xuat_ban === false || $nam_xuat_ban <= 0 || $tong_so_luong === false || $tong_so_luong <= 0) {
        $message = "Vui lòng điền đầy đủ và chính xác thông tin sách (Năm XB và Tổng SL phải là số dương).";
        $message_type = 'danger';
    } else {
        try {
            // 1. Lấy hoặc tạo ma_tac_gia
            $ma_tac_gia = get_or_create_author_id($db, $ten_tac_gia);

            if ($book_id) {
                // Chế độ SỬA (Update) - Lấy thông tin sách hiện tại trước
                $stmt_current = $db->prepare("SELECT tong_so_luong, so_luong_kha_dung FROM sach WHERE ma_sach = ?");
                $stmt_current->execute([$book_id]);
                $current_book = $stmt_current->fetch(PDO::FETCH_ASSOC);

                if (!$current_book) {
                    throw new Exception("Không tìm thấy sách để cập nhật.");
                }

                // TÍNH TOÁN SỐ SÁCH ĐANG ĐƯỢC MƯỢN
                $dang_muon_hien_tai = $current_book['tong_so_luong'] - $current_book['so_luong_kha_dung'];
                $so_luong_kha_dung_moi = $tong_so_luong - $dang_muon_hien_tai;
                
                // Kiểm tra ràng buộc: Tổng số lượng mới không được nhỏ hơn số sách đang được mượn
                if ($so_luong_kha_dung_moi < 0) {
                    $message = "Tổng số lượng mới ({$tong_so_luong}) không thể nhỏ hơn số sách đang được mượn ({$dang_muon_hien_tai}).";
                    $message_type = 'danger';
                } else {
                    // Cập nhật sách
                    $stmt = $db->prepare("UPDATE sach SET ten_sach = ?, ma_tac_gia = ?, nam_xuat_ban = ?, nha_xuat_ban = ?, tong_so_luong = ?, so_luong_kha_dung = ? WHERE ma_sach = ?");
                    $stmt->execute([$ten_sach, $ma_tac_gia, $nam_xuat_ban, $nha_xuat_ban, $tong_so_luong, $so_luong_kha_dung_moi, $book_id]);
                    
                    redirect_with_message("Cập nhật sách (#{$book_id}) thành công! SL khả dụng: {$so_luong_kha_dung_moi}.", 'success');
                }
            } else {
                // Chế độ THÊM MỚI (Insert)
                // Khi thêm mới, Số lượng còn lại = Tổng số lượng
                $stmt = $db->prepare("INSERT INTO sach (ten_sach, ma_tac_gia, nam_xuat_ban, nha_xuat_ban, tong_so_luong, so_luong_kha_dung) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ten_sach, $ma_tac_gia, $nam_xuat_ban, $nha_xuat_ban, $tong_so_luong, $tong_so_luong]);
                
                redirect_with_message("Thêm sách mới thành công!", 'success');
            }
        } catch (Exception $e) {
            $message = "Lỗi thao tác: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// --- XỬ LÝ XÓA SÁCH (RÀNG BUỘC VÀ XÓA CASCADING THỦ CÔNG) ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        // 1. Kiểm tra sách có đang trong giao dịch mượn ĐANG HIỆU LỰC nào không
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM phieu_muon WHERE ma_sach = ? AND trang_thai_muon IN ('dang_muon', 'qua_han')"); 
        $stmt_check->execute([$delete_id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $msg = "Không thể xóa sách này vì hiện tại đang có phiếu mượn Đang Mượn/Quá Hạn liên quan.";
            $type = 'danger';
        } else {
            // 2. Thực hiện xóa các bản ghi liên quan (phieu_phat -> phieu_muon -> sach)
            // Lấy danh sách ma_phieu_muon liên quan
            $stmt_loans = $db->prepare("SELECT ma_phieu_muon FROM phieu_muon WHERE ma_sach = ?");
            $stmt_loans->execute([$delete_id]);
            $loan_ids = $stmt_loans->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($loan_ids)) {
                $placeholders = str_repeat('?,', count($loan_ids) - 1) . '?';
                // Xóa phiếu phạt
                $db->prepare("DELETE FROM phieu_phat WHERE ma_phieu_muon IN ($placeholders)")->execute($loan_ids);
            }
            
            // Xóa phiếu mượn
            $db->prepare("DELETE FROM phieu_muon WHERE ma_sach = ?")->execute([$delete_id]);

            // 3. Thực hiện xóa sách
            $stmt = $db->prepare("DELETE FROM sach WHERE ma_sach = ?");
            $stmt->execute([$delete_id]);
            $msg = "Xóa sách (Mã: #$delete_id) thành công!";
            $type = 'success';
        }
        redirect_with_message($msg, $type);
        
    } catch (PDOException $e) {
        redirect_with_message("Lỗi CSDL khi xóa: " . $e->getMessage(), 'danger');
    }
}


// --- TẢI TOÀN BỘ DANH SÁCH SÁCH ---
$books = [];
try {
    $stmt = $db->query("
        SELECT 
            s.*, 
            tg.ten_tac_gia 
        FROM sach s
        JOIN tac_gia tg ON s.ma_tac_gia = tg.ma_tac_gia
        ORDER BY s.ma_sach DESC
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Lỗi khi tải danh sách sách: " . $e->getMessage();
    $message_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sách - Thủ Thư</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* --- CSS TÙY CHỈNH CHO SIDEBAR (ĐÃ TRÍCH XUẤT) --- */
        :root {
            --primary-color: #0d6efd; 
            --secondary-color: #6c757d; 
            --success-color: #198754; 
            --danger-color: #dc3545; 
            --warning-color: #ffc107; 
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

        /* LAYOUT 2 CỘT */
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
        
        /* SIDEBAR MENU */
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
            color: var(--secondary-color);
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
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .sidebar-menu a.active i {
            color: white;
        }
        .sidebar-menu hr { border-color: #f1f1f1; margin: 15px 0; }
        
        /* --- CSS CỦA TRANG QUẢN LÝ SÁCH --- */
        .container { 
            padding: 0; /* Đã có padding ở main-content-wrapper */
            margin: 0; 
            max-width: 100%; 
        }
        .card { 
            background: white; 
            border-radius: var(--radius); 
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1); 
            padding: 25px; 
            margin-bottom: 30px; 
        }
        .card h3 { 
            color: var(--primary-color); 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px; 
            margin-bottom: 25px; 
            font-weight: 700; 
            font-size: 1.6rem; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2 {
            color: var(--text-color);
            margin-bottom: 30px;
            font-weight: 800;
            text-align: center;
            font-size: 2rem;
        }

        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--text-color); 
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: var(--radius);
            box-sizing: border-box;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            margin-right: 10px;
        }
        .btn:active { transform: translateY(1px); }

        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #0b5ed7; }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-danger:hover { background-color: #bb2d3b; }
        .btn-warning { background-color: var(--warning-color); color: var(--text-color); }
        .btn-warning:hover { background-color: #ffda6a; }
        .btn-secondary { background-color:#6c757d; color:white; }
        .btn-secondary:hover { background-color:#5a6268; }

        /* Layout for form fields */
        .row { 
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap;
        }
        .col { flex: 1; min-width: 250px; } 
        .col-full { flex: 1 1 100%; }
        
        /* Alert Styling */
        .alert { padding: 15px; margin-bottom: 25px; border-radius: var(--radius); font-weight: 600; }
        .alert-success { background-color: #d1e7dd; color: var(--success-color); border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: var(--danger-color); border: 1px solid #f5c2c7; }

        /* Table Styling */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.95rem; }
        .data-table th, .data-table td { padding: 14px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background-color: var(--primary-color); color: white; font-weight: 700; text-transform: uppercase; }
        .data-table tr:nth-child(even) { background-color: #fcfcfc; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .table-actions { display: flex; gap: 8px; }
        .btn-sm { padding: 6px 10px; font-size: 0.85rem; }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            body {
                flex-direction: column; 
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-bottom: 0;
            }
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                padding: 10px 15px;
            }
            .sidebar-menu a {
                margin-bottom: 0;
                padding: 10px 15px;
                font-size: 0.9em;
            }
            .sidebar-menu hr { display: none; }
            .main-content-wrapper {
                max-width: 100%;
                padding-top: 0;
            }
        }
        @media (max-width: 768px) {
            .row { flex-direction: column; gap: 0; }
            .col { min-width: 100%; }
            .data-table th, .data-table td { padding: 10px 8px; font-size: 0.8rem; }
            .table-actions { justify-content: flex-start; }
            /* Cần thêm điều chỉnh cho cột Tiêu đề sách để tránh tràn */
            .data-table td:nth-child(2) { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-book-open"></i> THỦ THƯ PANEL</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
        <li><a href="crud_book.php" class="active"><i class="fas fa-book"></i> Quản lý sách</a></li> 
        <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Quản lý mượn trả</a></li>
        <li><a href="report.php"><i class="fas fa-file-invoice-dollar"></i> Lịch sử mượn trả</a></li>
        
        <hr>
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="main-content-wrapper">
    <div class="container">
        <h2><i class="fas fa-book-reader"></i> QUẢN LÝ KHO SÁCH CHI TIẾT</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?php echo $edit_mode ? '<i class="fas fa-edit"></i> Chỉnh Sửa Sách (#'.$book_id.')' : '<i class="fas fa-plus-circle"></i> Thêm Sách Mới'; ?></h3>
            
            <form method="POST" action="crud_book.php">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="ma_sach" value="<?php echo $book_id; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="ten_sach">Tiêu Đề Sách</label>
                            <input type="text" id="ten_sach" name="ten_sach" class="form-control" value="<?php echo htmlspecialchars($book_data['ten_sach']); ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="ten_tac_gia">Tác Giả</label>
                            <input type="text" id="ten_tac_gia" name="ten_tac_gia" class="form-control" value="<?php echo htmlspecialchars($book_data['ten_tac_gia']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="nam_xuat_ban">Năm Xuất Bản</label>
                            <input type="number" id="nam_xuat_ban" name="nam_xuat_ban" class="form-control" value="<?php echo $book_data['nam_xuat_ban']; ?>" min="1900" max="<?php echo date('Y'); ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="nha_xuat_ban">Nhà Xuất Bản</label>
                            <input type="text" id="nha_xuat_ban" name="nha_xuat_ban" class="form-control" value="<?php echo htmlspecialchars($book_data['nha_xuat_ban']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-full">
                        <div class="form-group">
                            <label for="tong_so_luong">Tổng Số Lượng (Bản)</label>
                            <input 
                                type="number" 
                                id="tong_so_luong" 
                                name="tong_so_luong" 
                                class="form-control" 
                                value="<?php echo $book_data['tong_so_luong']; ?>" 
                                min="<?php echo $edit_mode ? $dang_muon : 1; ?>" 
                                required>
                            <?php if ($edit_mode): ?>
                                <small class="form-text text-muted" style="color:<?php echo $dang_muon > 0 ? 'var(--danger-color)' : '#6c757d'; ?>; margin-top: 5px; display: block;">
                                    Hiện có **<?php echo number_format($dang_muon); ?>** sách đang được mượn. 
                                    Tổng số lượng phải là &ge; <?php echo number_format($dang_muon); ?>.
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Cập Nhật Sách' : 'Thêm Sách'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="crud_book.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy Bỏ</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-list-alt"></i> Danh Sách Sách Hiện Có</h3>
            
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tiêu Đề</th>
                            <th>Tác Giả</th>
                            <th>Năm XB</th>
                            <th>NXB</th>
                            <th>Tổng SL</th>
                            <th>SL Khả Dụng</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; font-style: italic; color: var(--secondary-color);">
                                    Kho sách trống. Vui lòng thêm sách mới!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td>#<?php echo $book['ma_sach']; ?></td>
                                    <td title="<?php echo htmlspecialchars($book['ten_sach']); ?>"><?php echo htmlspecialchars($book['ten_sach']); ?></td>
                                    <td><?php echo htmlspecialchars($book['ten_tac_gia']); ?></td>
                                    <td><?php echo $book['nam_xuat_ban']; ?></td>
                                    <td><?php echo htmlspecialchars($book['nha_xuat_ban']); ?></td>
                                    <td><?php echo number_format($book['tong_so_luong']); ?></td>
                                    <td>
                                        <?php
                                        $con_lai = $book['so_luong_kha_dung'];
                                        $style = '';
                                        if ($con_lai == 0) {
                                            $style = 'color: var(--danger-color); font-weight: 700;';
                                        } elseif ($con_lai < 3) {
                                            $style = 'color: var(--warning-color); font-weight: 700;';
                                        } else {
                                            $style = 'color: var(--success-color); font-weight: 700;';
                                        }
                                        ?>
                                        <span style="<?php echo $style; ?>">
                                            <?php echo number_format($con_lai); ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <a href="crud_book.php?edit_id=<?php echo $book['ma_sach']; ?>" class="btn btn-warning btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                        <a href="crud_book.php?delete_id=<?php echo $book['ma_sach']; ?>" class="btn btn-danger btn-sm" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa sách này không? Tất cả phiếu mượn (đã trả) và phiếu phạt liên quan sẽ bị xóa. CHỈ XÓA NẾU KHÔNG CÓ PHIẾU MƯỢN ĐANG HIỆU LỰC!');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>