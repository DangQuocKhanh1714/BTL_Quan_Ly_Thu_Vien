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
$error = '';
$tab = $_GET['tab'] ?? 'active_loans'; // Mặc định là tab Phiếu Mượn Đang Hoạt Động

// --- KHAI BÁO DỮ LIỆU CẦN TRUY VẤN ---
$active_loans = [];
$loan_history = [];
$fine_history = [];

try {
    // 1. Lấy danh sách Phiếu Mượn Đang Hoạt Động (trang_thai_muon = 'dang_muon')
    if ($tab == 'active_loans') {
        $query_active_loans = "
            SELECT 
                pm.ma_phieu_muon, nd.ho_ten AS ten_doc_gia, s.ten_sach,
                pm.ngay_muon, pm.ngay_tra_du_kien, pm.trang_thai_muon
            FROM phieu_muon pm
            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
            JOIN sach s ON pm.ma_sach = s.ma_sach
            WHERE pm.trang_thai_muon = 'dang_muon'
            ORDER BY pm.ngay_tra_du_kien ASC";
        $stmt = $db->query($query_active_loans);
        $active_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Lấy lịch sử Mượn/Trả (trang_thai_muon = 'da_tra')
    if ($tab == 'history') {
        $query_history = "
            SELECT 
                pm.ma_phieu_muon, nd.ho_ten AS ten_doc_gia, s.ten_sach,
                pm.ngay_muon, pm.ngay_tra_du_kien, pm.ngay_tra_thuc_te, pm.trang_thai_muon
            FROM phieu_muon pm
            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
            JOIN sach s ON pm.ma_sach = s.ma_sach
            WHERE pm.trang_thai_muon = 'da_tra'
            ORDER BY pm.ngay_tra_thuc_te DESC
            LIMIT 100"; // Giới hạn 100 giao dịch gần nhất
        $stmt = $db->query($query_history);
        $loan_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Lấy lịch sử Phiếu Phạt (Tất cả phiếu phạt)
    if ($tab == 'fines') {
        $query_fines = "
            SELECT 
                pp.ma_phieu_phat, pp.so_tien_phat, pp.ly_do, pp.trang_thai_thanh_toan, pp.ngay_thanh_toan,
                pm.ma_phieu_muon, nd.ho_ten AS ten_doc_gia
            FROM phieu_phat pp
            JOIN phieu_muon pm ON pp.ma_phieu_muon = pm.ma_phieu_muon
            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
            ORDER BY pp.ma_phieu_phat DESC";
        $stmt = $db->query($query_fines);
        $fine_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Ghi log lỗi và thông báo lỗi thân thiện hơn
    error_log("Report Data Loading Error: " . $e->getMessage());
    $error = "Lỗi khi tải dữ liệu báo cáo. Vui lòng kiểm tra kết nối CSDL.";
}

// Hàm format ngày và trạng thái
function formatDate($date) {
    // Định dạng Y-m-d H:i:s có thể có, nên dùng strtotime an toàn hơn
    return $date && $date != '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($date)) : 'N/A';
}

function getStatusLabel($status) {
    if ($status == 'dang_muon') return '<span class="status-badge status-active">Đang Mượn</span>';
    if ($status == 'da_tra') return '<span class="status-badge status-returned">Đã Trả</span>';
    if ($status == 'chua_thanh_toan') return '<span class="status-badge status-unpaid">Chưa TT</span>';
    if ($status == 'da_thanh_toan') return '<span class="status-badge status-paid">Đã TT</span>';
    return htmlspecialchars($status);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo & Lịch Sử Giao Dịch - Thủ Thư</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        /* --- KHAI BÁO BIẾN CƠ BẢN VÀ LAYOUT 2 CỘT --- */
        :root {
            --primary-color: #0d6efd; /* Blue */
            --secondary-color: #6c757d; /* Grey */
            --success-color: #198754; /* Green */
            --danger-color: #dc3545; /* Red */
            --warning-color: #ffc107; /* Yellow */
            --info-color: #0dcaf0;
            --paid-color: #6f42c1; /* Purple for Paid */
            --bg-light: #f4f6f9; 
            --text-color: #212529;
            --radius: 0.75rem;
            --sidebar-width: 250px;
        }
        body {
            background-color: var(--bg-light); 
            display: flex; 
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', 'Arial', sans-serif;
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
            z-index: 10;
        }
        .main-content-wrapper {
            flex-grow: 1; 
            padding: 20px;
            max-width: calc(100% - var(--sidebar-width)); 
            z-index: 1;
        }

        /* SIDEBAR MENU */
        .sidebar-header { text-align: center; margin-bottom: 30px; padding: 0 15px; }
        .sidebar-header h2 { color: var(--primary-color); font-size: 1.5rem; font-weight: 700; }
        .sidebar-menu { list-style: none; padding: 0 15px; margin: 0; }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 15px; text-decoration: none; 
            color: var(--text-color); border-radius: 8px; margin-bottom: 5px; 
            transition: all 0.3s; font-weight: 500;
        }
        .sidebar-menu a i { margin-right: 10px; font-size: 1.1rem; color: var(--secondary-color); }
        .sidebar-menu a:hover { background-color: var(--primary-color); color: white; }
        .sidebar-menu a:hover i { color: white; }
        .sidebar-menu a.active {
            background-color: var(--primary-color); color: white; font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .sidebar-menu a.active i { color: white; }
        .sidebar-menu hr { border-color: #f1f1f1; margin: 15px 0; }

        /* Responsive Mobile */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding-bottom: 0; }
            .sidebar-menu { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; padding: 10px 15px; }
            .sidebar-menu a { padding: 10px 15px; font-size: 0.9em; margin-bottom: 0; }
            .sidebar-menu hr { display: none; }
            .sidebar-header { margin-bottom: 10px; }
            .main-content-wrapper { max-width: 100%; }
        }

        /* --- STYLES CỦA REPORT PAGE --- */
        .report-content { 
            background: white; 
            border-radius: var(--radius); 
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.08); 
            padding: 25px; 
        }
        .report-content h3 { 
            color: var(--primary-color); 
            border-bottom: 2px solid #eee; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
            font-weight: 700; 
            font-size: 1.8rem;
        }
        .report-content h4 {
            margin-top: 30px; margin-bottom: 15px; color: #333; font-weight: 600;
            border-left: 4px solid var(--primary-color); padding-left: 10px;
        }
        
        /* Tabs Styling */
        .tabs-nav { display: flex; flex-wrap: wrap; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .tabs-nav a { 
            padding: 10px 15px; text-decoration: none; color: var(--secondary-color); font-weight: 600; 
            border-bottom: 2px solid transparent; transition: all 0.3s; 
        }
        .tabs-nav a.active { 
            color: var(--primary-color); border-bottom: 2px solid var(--primary-color); font-weight: 700;
        }

        /* Table Styling */
        .data-table { 
            width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; 
        }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; }
        .data-table thead th { 
            background-color: var(--primary-color); color: white; font-weight: 600; font-size: 0.95rem;
            position: sticky; top: 0; z-index: 10;
        }
        .data-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .data-table tbody tr:hover { background-color: #f7f7ff; }
        .data-table td { font-size: 0.9rem; color: #333; }
        .data-table thead th:first-child { border-top-left-radius: 0.5rem; }
        .data-table thead th:last-child { border-top-right-radius: 0.5rem; }

        /* Status Badges */
        .status-badge { 
            padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem;
            display: inline-block; min-width: 60px; text-align: center;
        }
        .status-active { background-color: var(--info-color); color: white; }
        .status-returned { background-color: var(--success-color); color: white; }
        .status-unpaid { background-color: var(--danger-color); color: white; }
        .status-paid { background-color: var(--paid-color); color: white; } 

        /* Overdue highlight */
        .overdue-row { background-color: #fff8e1; }
        .overdue-row:hover { background-color: #fff3cd; }
        .overdue-text { color: var(--danger-color); font-weight: 700; }
        .overdue-text::before { content: "⚠️ "; }

        /* Alert Boxes (for errors/success) */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 0.5rem; border: 1px solid transparent; }
        .alert.error { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }
        .alert.success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-book-open"></i> THỦ THƯ PANEL</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
        <li><a href="book_management.php"><i class="fas fa-book"></i> Quản lý sách</a></li>
        <li><a href="borrow_transaction.php"><i class="fas fa-exchange-alt"></i> Quản lý mượn trả</a></li>
        <li><a href="report.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Lịch sử mượn trả</a></li>
        
        <hr>
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="main-content-wrapper">
    <div class="report-content">
        <h3><i class="fas fa-file-invoice"></i> Báo Cáo Chi Tiết Thư Viện</h3>
        
        <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

        <div class="tabs-nav">
            <a href="?tab=active_loans" class="<?php echo $tab == 'active_loans' ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i> Phiếu Mượn Đang Hoạt Động
            </a>
            <a href="?tab=history" class="<?php echo $tab == 'history' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Lịch Sử Giao Dịch
            </a>
            <a href="?tab=fines" class="<?php echo $tab == 'fines' ? 'active' : ''; ?>">
                <i class="fas fa-balance-scale"></i> Lịch Sử Phiếu Phạt
            </a>
        </div>

        <div class="tab-content">

            <?php if ($tab == 'active_loans'): ?>
                <h4>Danh sách Sách đang được mượn và kiểm tra Quá Hạn</h4>
                
                <?php if (empty($active_loans)): ?>
                    <div class="alert success">Không có phiếu mượn nào đang hoạt động.</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã PM</th>
                                    <th>Độc Giả</th>
                                    <th>Tên Sách</th>
                                    <th>Ngày Mượn</th>
                                    <th>Ngày Trả Dự Kiến</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $today = new DateTime(date('Y-m-d'));
                                foreach ($active_loans as $loan): 
                                    $date_expected = new DateTime($loan['ngay_tra_du_kien']);
                                    // Kiểm tra nếu ngày dự kiến trả < ngày hôm nay (quá hạn)
                                    $is_overdue = $date_expected < $today;
                                ?>
                                    <tr class="<?php echo $is_overdue ? 'overdue-row' : ''; ?>">
                                        <td><?php echo htmlspecialchars($loan['ma_phieu_muon']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['ten_doc_gia']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['ten_sach']); ?></td>
                                        <td><?php echo formatDate($loan['ngay_muon']); ?></td>
                                        <td class="<?php echo $is_overdue ? 'overdue-text' : ''; ?>">
                                            <?php echo formatDate($loan['ngay_tra_du_kien']); ?>
                                            <?php if ($is_overdue): ?> (Quá Hạn)<?php endif; ?>
                                        </td>
                                        <td><?php echo getStatusLabel($loan['trang_thai_muon']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tab == 'history'): ?>
                <h4>Lịch Sử Giao Dịch Mượn/Trả (100 Giao dịch Gần Nhất)</h4>
                
                <?php if (empty($loan_history)): ?>
                    <p style="font-style: italic; color: var(--secondary-color); padding: 10px;">Chưa có giao dịch trả sách nào được ghi nhận.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã PM</th>
                                    <th>Độc Giả</th>
                                    <th>Tên Sách</th>
                                    <th>Ngày Mượn</th>
                                    <th>Dự Kiến Trả</th>
                                    <th>Ngày Trả Thực Tế</th>
                                    <th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loan_history as $loan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loan['ma_phieu_muon']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['ten_doc_gia']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['ten_sach']); ?></td>
                                        <td><?php echo formatDate($loan['ngay_muon']); ?></td>
                                        <td><?php echo formatDate($loan['ngay_tra_du_kien']); ?></td>
                                        <td><?php echo formatDate($loan['ngay_tra_thuc_te']); ?></td>
                                        <td><?php echo getStatusLabel($loan['trang_thai_muon']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($tab == 'fines'): ?>
                <h4>Chi Tiết các Phiếu Phạt đã được ghi nhận</h4>
                
                <?php if (empty($fine_history)): ?>
                    <p style="font-style: italic; color: var(--secondary-color); padding: 10px;">Chưa có phiếu phạt nào được ghi nhận.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã PP</th>
                                    <th>Mã PM</th>
                                    <th>Độc Giả</th>
                                    <th>Số Tiền Phạt</th>
                                    <th>Lý Do</th>
                                    <th>Trạng Thái TT</th>
                                    <th>Ngày TT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fine_history as $fine): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fine['ma_phieu_phat']); ?></td>
                                        <td><?php echo htmlspecialchars($fine['ma_phieu_muon']); ?></td>
                                        <td><?php echo htmlspecialchars($fine['ten_doc_gia']); ?></td>
                                        <td style="color: <?php echo $fine['trang_thai_thanh_toan'] == 'chua_thanh_toan' ? 'var(--danger-color)' : 'var(--success-color)'; ?>; font-weight: 700;">
                                            <?php echo number_format($fine['so_tien_phat']); ?> VNĐ
                                        </td>
                                        <td><?php echo htmlspecialchars($fine['ly_do']); ?></td>
                                        <td><?php echo getStatusLabel($fine['trang_thai_thanh_toan']); ?></td>
                                        <td><?php echo formatDate($fine['ngay_thanh_toan']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>