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
$stats = [
    'total_books' => 0, // Tổng số lượng sách (bản)
    'total_users' => 0, // Tổng số lượng độc giả (Role 3)
    'borrowing_loans' => 0, // Đang mượn
    'overdue_loans' => 0, // Quá hạn
];
$error = '';

try {
    // 1. Tổng số lượng sách hiện có (Dựa trên tong_so_luong)
    $stmt = $db->query("SELECT IFNULL(SUM(tong_so_luong), 0) AS count FROM sach");
    $stats['total_books'] = $stmt->fetch(PDO::FETCH_COLUMN) ?? 0;

    // 2. Tổng số lượng độc giả (Người dùng có ma_vai_tro = 3)
    $stmt = $db->query("SELECT COUNT(*) FROM nguoi_dung WHERE ma_vai_tro = 3");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_COLUMN) ?? 0;

    // 3. Số lượng phiếu mượn đang MƯỢN (Trạng thái 'dang_muon')
    $stmt = $db->query("SELECT COUNT(*) FROM phieu_muon WHERE trang_thai_muon = 'dang_muon'");
    $stats['borrowing_loans'] = $stmt->fetch(PDO::FETCH_COLUMN) ?? 0;

    // 4. Số lượng phiếu mượn QUÁ HẠN (Trạng thái 'dang_muon' và Ngày trả dự kiến < Ngày hiện tại)
    $stmt = $db->query("SELECT COUNT(*) FROM phieu_muon WHERE trang_thai_muon = 'dang_muon' AND ngay_den_han < CURDATE()");
    $stats['overdue_loans'] = $stmt->fetch(PDO::FETCH_COLUMN) ?? 0;

} catch (PDOException $e) {
    $error = "Lỗi khi tải dữ liệu thống kê: " . $e->getMessage();
}

// Lấy 5 sách đang được mượn nhiều nhất (Báo cáo chi tiết)
$top_borrowed_books = [];
try {
    $query_top_books = "
        SELECT 
            s.ten_sach, 
            COUNT(pm.ma_sach) AS total_borrows
        FROM 
            phieu_muon pm
        JOIN 
            sach s ON pm.ma_sach = s.ma_sach
        GROUP BY 
            s.ma_sach
        ORDER BY 
            total_borrows DESC
        LIMIT 5
    ";
    $stmt_top = $db->query($query_top_books);
    $top_borrowed_books = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= "Lỗi lấy dữ liệu sách mượn nhiều nhất: " . $e->getMessage();
}

// DỮ LIỆU GIẢ LẬP CHO BIỂU ĐỒ (Có thể thay thế bằng dữ liệu thật trong tương lai)
$chart_data = [
    'labels' => ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
    'data' => [150, 180, 210, 190, 250, 220], // Số lượng giao dịch mượn/trả
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Thủ Thư</title>
    <!-- Thư viện Font Awesome (Icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Thư viện Chart.js (Biểu đồ) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- CSS TÙY CHỈNH CHO DASHBOARD VÀ LAYOUT -->
    <style>
        :root {
            --primary-color: #007bff; /* Blue */
            --secondary-color: #6c757d; /* Grey */
            --success-color: #28a745; /* Green */
            --danger-color: #dc3545; /* Red */
            --warning-color: #ffc107; /* Yellow */
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
        
        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); 
            gap: 20px;
            margin-bottom: 25px;
        }
        .kpi-card {
            display: flex;
            flex-direction: column;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 6px solid;
            background: #ffffff;
        }
        .kpi-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08); 
        }
        .kpi-label {
            font-size: 1rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        .kpi-value-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kpi-value { 
            font-size: 2.5rem; 
            font-weight: 900; 
            margin: 0; 
        }
        .kpi-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }

        /* KPI Colors */
        .kpi-books { border-left-color: var(--success-color); }
        .kpi-books .kpi-value { color: var(--success-color); }
        .kpi-books .kpi-icon { color: var(--success-color); }

        .kpi-users { border-left-color: var(--primary-color); }
        .kpi-users .kpi-value { color: var(--primary-color); }
        .kpi-users .kpi-icon { color: var(--primary-color); }

        .kpi-borrowing { border-left-color: var(--warning-color); }
        .kpi-borrowing .kpi-value { color: #cc9c00; } 
        .kpi-borrowing .kpi-icon { color: var(--warning-color); }

        .kpi-overdue { border-left-color: var(--danger-color); }
        .kpi-overdue .kpi-value { color: var(--danger-color); }
        .kpi-overdue .kpi-icon { color: var(--danger-color); }


        /* Dashboard Content Grid (Chart + Report) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* 2/3 cho Biểu đồ, 1/3 cho Báo cáo */
            gap: 25px;
            margin-bottom: 25px; 
        }

        /* Reports Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            text-align: left;
        }
        .report-table th, .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .report-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .report-table tr:hover {
            background-color: #e9ecef;
        }
        .badge {
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
        }

        /* --- Responsive Mobile --- */
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
                padding: 10px 15px;
                font-size: 0.9em;
                margin-bottom: 0;
            }
            .sidebar-menu hr { display: none; }
            .sidebar-header {
                margin-bottom: 10px;
            }
            .main-content-wrapper {
                max-width: 100%;
            }
            .dashboard-grid {
                grid-template-columns: 1fr; /* Stack Biểu đồ và Báo cáo */
            }
            .kpi-grid {
                grid-template-columns: 1fr 1fr; /* 2 cột cho KPI trên di động */
            }
        }
        @media (max-width: 576px) {
            .kpi-grid {
                grid-template-columns: 1fr; /* 1 cột cho KPI trên di động nhỏ */
            }
        }
    </style>
</head>
<body>

<!-- 1. SIDEBAR MENU -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-book-open"></i> THỦ THƯ PANEL</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
        <li><a href="../librarian/book_management.php"><i class="fas fa-book"></i> Quản lý sách</a></li>
        <li><a href="../librarian/transactions.php"><i class="fas fa-exchange-alt"></i> Quản lý mượn trả</a></li>
        <li><a href="../librarian/report.php"><i class="fas fa-file-invoice-dollar"></i> Lịch sử mượn trả</a></li>
        
        <hr>
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<!-- 2. MAIN CONTENT AREA -->
<div class="main-content-wrapper">
    <div class="librarian-container">

        <!-- TIÊU ĐỀ -->
        <div class="card">
            <h3><i class="fas fa-chart-bar"></i> Bảng Điều Khiển Thủ Thư</h3>
        </div>

        <?php if ($error): ?><div class="card" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px;"><?php echo $error; ?></div><?php endif; ?>

        <!-- PHẦN 1: KEY PERFORMANCE INDICATORS (KPI) -->
        <div class="kpi-grid">
            
            <div class="kpi-card kpi-books">
                <div class="kpi-label">Tổng Số Sách (Bản)</div>
                <div class="kpi-value-container">
                    <p class="kpi-value"><?php echo number_format($stats['total_books']); ?></p>
                    <i class="fas fa-book kpi-icon"></i>
                </div>
            </div>

            <div class="kpi-card kpi-users">
                <div class="kpi-label">Tổng Số Độc Giả</div>
                <div class="kpi-value-container">
                    <p class="kpi-value"><?php echo number_format($stats['total_users']); ?></p>
                    <i class="fas fa-users kpi-icon"></i>
                </div>
            </div>

            <div class="kpi-card kpi-borrowing">
                <div class="kpi-label">Phiếu Mượn Đang Hiệu Lực</div>
                <div class="kpi-value-container">
                    <p class="kpi-value"><?php echo number_format($stats['borrowing_loans']); ?></p>
                    <i class="fas fa-exchange-alt kpi-icon"></i>
                </div>
            </div>

            <div class="kpi-card kpi-overdue">
                <div class="kpi-label">Phiếu Mượn Quá Hạn</div>
                <div class="kpi-value-container">
                    <p class="kpi-value"><?php echo number_format($stats['overdue_loans']); ?></p>
                    <i class="fas fa-exclamation-triangle kpi-icon"></i>
                </div>
            </div>
        </div>
        
        <!-- PHẦN 2 & 3: BIỂU ĐỒ & BÁO CÁO CHI TIẾT -->
        <div class="dashboard-grid">
            
            <!-- Biểu đồ (Chiếm 2/3) -->
            <div class="card chart-card">
                <h4><i class="fas fa-chart-line"></i> Tình hình Giao Dịch Mượn/Trả (6 Tháng)</h4>
                <div style="height: 350px;">
                    <canvas id="loanChart"></canvas>
                </div>
            </div>
            
            <!-- Báo cáo Top Sách (Chiếm 1/3) -->
            <div class="card report-card">
                <h4><i class="fas fa-trophy"></i> Top 5 Sách Được Mượn Nhiều Nhất</h4>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tên Sách</th>
                            <th>Lượt Mượn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_borrowed_books)): ?>
                            <tr><td colspan="3" style="text-align: center; font-style: italic;">Chưa có dữ liệu giao dịch.</td></tr>
                        <?php endif; ?>
                        <?php $rank = 1; foreach ($top_borrowed_books as $book): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($book['ten_sach']); ?></td>
                                <td><span class="badge"><?php echo number_format($book['total_borrows']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- 3. SCRIPT CHO BIỂU ĐỒ (Dùng Chart.js) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('loanChart');
        if (ctx) {
            // Dữ liệu giả lập cho biểu đồ (có thể thay thế bằng dữ liệu thật)
            const chartData = <?php echo json_encode($chart_data); ?>;

            new Chart(ctx, {
                type: 'bar', // Sử dụng biểu đồ cột để dễ nhìn hơn
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Số lượng giao dịch (Mượn/Trả)',
                        data: chartData.data,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)', 
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        borderRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, 
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0 
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>
</body>
</html>