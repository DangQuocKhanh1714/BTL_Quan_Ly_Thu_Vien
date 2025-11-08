<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

$db = $GLOBALS['db'];
$error = '';
$title = "Bảng Điều Khiển Admin";

$stats = [
    'total_users' => 0,
    'total_books_in_stock' => 0,
    'available_books' => 0,
    'active_loans' => 0,
    'unpaid_fines' => 0,
];

try {
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM nguoi_dung")->fetchColumn();
    $stats['total_books_in_stock'] = $db->query("SELECT IFNULL(SUM(tong_so_luong), 0) FROM sach")->fetchColumn();
    $stats['available_books'] = $db->query("SELECT IFNULL(SUM(so_luong_kha_dung), 0) FROM sach")->fetchColumn();
    $stats['active_loans'] = $db->query("SELECT COUNT(*) FROM phieu_muon WHERE trang_thai_muon = 'dang_muon'")->fetchColumn();
    $stats['unpaid_fines'] = $db->query("SELECT IFNULL(SUM(so_tien_phat), 0) FROM phieu_phat WHERE trang_thai_thanh_toan = 'chua_thanh_toan'")->fetchColumn();
} catch (PDOException $e) {
    $error = "Lỗi lấy dữ liệu thống kê: " . $e->getMessage();
}

$recent_loans = [];
try {
    $recent_loans = $db->query("
        SELECT pm.ma_phieu_muon, nd.ho_ten, s.ten_sach, pm.ngay_muon, pm.trang_thai_muon 
        FROM phieu_muon pm 
        JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
        JOIN sach s ON pm.ma_sach = s.ma_sach
        ORDER BY ngay_muon DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= "Lỗi lấy phiếu mượn gần đây: " . $e->getMessage();
}

$top_borrowed_books = [];
try {
    $top_borrowed_books = $db->query("
        SELECT 
            s.ten_sach,
            COUNT(pm.ma_phieu_muon) as so_lan_muon
        FROM phieu_muon pm 
        JOIN sach s ON pm.ma_sach = s.ma_sach
        GROUP BY s.ma_sach, s.ten_sach
        ORDER BY so_lan_muon DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= "Lỗi lấy dữ liệu sách mượn nhiều nhất: " . $e->getMessage();
}


$chart_data = [
    'labels' => ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'],
    'data' => [65, 59, 80, 81, 56, 95],
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
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
        .card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr); 
            gap: 15px;
            margin-bottom: 25px;
        }
        .kpi-card {
            background: white;
            border-radius: var(--radius);
            padding: 20px; 
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: row; 
            align-items: center;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }
        .kpi-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08); 
        }
        .kpi-card .icon {
            font-size: 1.8rem; 
            padding: 8px;
            border-radius: 6px; 
            color: white;
            margin-bottom: 10px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kpi-card .details { 
            text-align: left;
            width: 100%;
        }
        .kpi-card .details .value { 
            font-size: 1.8rem; 
            font-weight: 800; 
            margin: 5px 0 0; 
            color: var(--text-color); 
            line-height: 1.1;
        }
        .kpi-card .details .label { 
            font-size: 0.85em;
            color: #6c757d; 
            margin-top: 5px; 
        }

        .kpi-blue .icon { background: var(--primary-color); }
        .kpi-green .icon { background: var(--success-color); }
        .kpi-yellow .icon { background: var(--warning-color); }
        .kpi-red .icon { background: var(--danger-color); }
        .kpi-info .icon { background: var(--info-color); }


        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; 
            gap: 25px;
            margin-bottom: 25px; 
        }

        .recent-list { list-style: none; padding: 0; margin: 0; }
        .recent-list li { 
            padding: 15px 0; border-bottom: 1px solid var(--border-color); 
            display: flex; justify-content: space-between; align-items: center; 
            font-size: 0.95em;
            transition: background-color 0.2s;
        }
        .recent-list li:hover { background-color: #f8f9fa; }
        .recent-list li:last-child { border-bottom: none; }
        .loan-status { font-weight: 700; padding: 5px 10px; border-radius: 50px; font-size: 0.8em; }
        .loan-status.dang_muon { background-color: #fff3cd; color: #664d03; }
        .loan-status.da_tra { background-color: #d1e7dd; color: var(--success-color); }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
            background-color: var(--info-color);
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
        }

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
                grid-template-columns: 1fr; 
            }
            .kpi-grid {
                grid-template-columns: 1fr 1fr; 
            }
        }
        @media (max-width: 576px) {
            .kpi-grid {
                grid-template-columns: 1fr; 
            }
        }
        .loan-info {
            width: 70%;
        }
        /* .loan-status {
            width: 30%;
        } */
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-tools"></i> ADMIN PANEL</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tổng quan</a></li>
        <li><a href="users.php"><i class="fas fa-user-shield"></i> Quản lý Người dùng</a></li>
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
            <strong>Tổng quan</strong>
        </div>

        <div class="card">
            <h3><i class="fas fa-chart-bar"></i> Tổng Quan Thư Viện</h3>
        </div>

        <?php if ($error): ?><div class="alert error card" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;"><?php echo $error; ?></div><?php endif; ?>

        <div class="kpi-grid">
            
            <div class="kpi-card kpi-blue">
                <div class="details">
                    <p class="value"><?php echo number_format($stats['total_users']); ?></p>
                    <p class="label">Tổng Người Dùng</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>

            <div class="kpi-card kpi-green">
                <div class="details">
                    <p class="value"><?php echo number_format($stats['total_books_in_stock']); ?></p>
                    <p class="label">Tổng Sách (Kho)</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>

            <div class="kpi-card kpi-info">
                <div class="details">
                    <p class="value"><?php echo number_format($stats['available_books']); ?></p>
                    <p class="label">Sách Khả Dụng</p>
                </div>
                <div class="icon"><i class="fas fa-book-reader"></i></div>
            </div>

            <div class="kpi-card kpi-yellow">
                <div class="details">
                    <p class="value"><?php echo number_format($stats['active_loans']); ?></p>
                    <p class="label">Đang Mượn</p>
                </div>
                <div class="icon"><i class="fas fa-hand-holding"></i></div>
            </div>

            <div class="kpi-card kpi-red">
                <div class="details">
                    <p class="value"><?php echo number_format($stats['unpaid_fines']); ?> <small>VNĐ</small></p>
                    <p class="label">Phạt Chưa Thu</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            
            <div class="card chart-card">
                <h4><i class="fas fa-chart-line"></i> Tình hình Giao Dịch Sách (6 Tháng Gần Nhất)</h4>
                <div style="height: 350px;">
                    <canvas id="loanChart"></canvas>
                </div>
            </div>
            
            <div class="card activity-card">
                <h4><i class="fas fa-history"></i> 5 Giao Dịch Mượn Gần Nhất</h4>
                <ul class="recent-list">
                    <?php if (empty($recent_loans)): ?>
                        <li>Không có phiếu mượn nào gần đây.</li>
                    <?php endif; ?>
                    <?php foreach ($recent_loans as $loan): ?>
                        <li>
                            <div class="loan-info">
                                <strong><?php echo htmlspecialchars($loan['ho_ten']); ?></strong>
                                <br><small>Sách: <?php echo htmlspecialchars($loan['ten_sach']); ?></small>
                            </div>
                            <span class="loan-status <?php echo $loan['trang_thai_muon']; ?>">
                                <?php echo $loan['trang_thai_muon'] == 'dang_muon' ? 'Đang Mượn' : 'Đã Trả'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="card full-width-report">
            <h4><i class="fas fa-trophy"></i> Báo Cáo Sách Mượn Nhiều Nhất (Top 5)</h4>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Sách</th>
                        <th>Số Lần Mượn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_borrowed_books)): ?>
                        <tr><td colspan="3">Chưa có dữ liệu sách được mượn.</td></tr>
                    <?php endif; ?>
                    <?php $rank = 1; foreach ($top_borrowed_books as $book): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($book['ten_sach']); ?></td>
                            <td><span class="badge"><?php echo number_format($book['so_lan_muon']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('loanChart');
        if (ctx) {
            const chartData = <?php echo json_encode($chart_data); ?>;

            new Chart(ctx, {
                type: 'line', 
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Số lượng giao dịch (Mượn/Trả)',
                        data: chartData.data,
                        borderColor: 'rgba(13, 110, 253, 1)', 
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.4, 
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(13, 110, 253, 1)',
                        pointBorderWidth: 2,
                        pointRadius: 5
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