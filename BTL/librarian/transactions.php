<?php
// Đường dẫn đi lùi ra một cấp (../) để tìm file config.php
session_start();
// GIẢ ĐỊNH: config.php chứa kết nối $GLOBALS['db'] PDO
require_once '../config.php';

// Bảo vệ trang: Chỉ cho phép Thủ thư (Role ID 2) truy cập
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

$db = $GLOBALS['db'];
$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'loan'; // Mặc định là tab Mượn Sách (loan)

// --- HÀM XỬ LÝ LỖI / THÀNH CÔNG ---
function setMessage($msg, $is_error = false) {
    // Sử dụng Session để lưu Flash Message khi cần chuyển hướng
    if ($is_error) {
        $_SESSION['flash_error'] = $msg;
    } else {
        $_SESSION['flash_message'] = $msg;
    }
}

// Xử lý thông báo sau khi chuyển hướng (nếu có)
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}


// --- XỬ LÝ FORM (GIỮ NGUYÊN LOGIC) ---

// 1. Xử lý Tạo Phiếu Mượn Mới
if ($tab == 'loan' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_loan'])) {
    $user_id = trim($_POST['user_id']);
    $book_id = trim($_POST['book_id']);

    if (empty($user_id) || empty($book_id)) {
        setMessage("Vui lòng nhập Mã Người Dùng và Mã Sách.", true);
    } else {
        try {
            $db->beginTransaction();

            // 1. Kiểm tra tồn tại người dùng và sách
            $stmt_user = $db->prepare("SELECT ho_ten FROM nguoi_dung WHERE ma_nguoi_dung = ?");
            $stmt_user->execute([$user_id]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            // Kiểm tra sách
            $stmt_book = $db->prepare("SELECT ten_sach, so_luong_kha_dung FROM sach WHERE ma_sach = ?");
            $stmt_book->execute([$book_id]);
            $book = $stmt_book->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Mã Người Dùng không tồn tại.");
            }
            // Thêm check Role 3 cho độc giả (nếu cần)
            // if ($user['ma_vai_tro'] != 3) { ... } 
            
            if (!$book) {
                throw new Exception("Mã Sách không tồn tại.");
            }
            if ($book['so_luong_kha_dung'] <= 0) {
                throw new Exception("Sách '{$book['ten_sach']}' đã hết số lượng khả dụng."); 
            }
            
            // 2. Tạo phiếu mượn
            $ngay_muon = date('Y-m-d');
            $ngay_tra_du_kien = date('Y-m-d', strtotime('+7 days'));

            $stmt_loan = $db->prepare("INSERT INTO phieu_muon (ma_nguoi_muon, ma_sach, ma_thu_thu, ngay_muon, ngay_tra_du_kien, trang_thai_muon) VALUES (?, ?, ?, ?, ?, 'dang_muon')");
            $stmt_loan->execute([$user_id, $book_id, $_SESSION['user_id'], $ngay_muon, $ngay_tra_du_kien]);
            
            // 3. Cập nhật số lượng sách khả dụng (giảm đi 1)
            $stmt_update_book = $db->prepare("UPDATE sach SET so_luong_kha_dung = so_luong_kha_dung - 1 WHERE ma_sach = ?");
            $stmt_update_book->execute([$book_id]);

            $db->commit();
            setMessage("Đã tạo phiếu mượn thành công cho độc giả '{$user['ho_ten']}' mượn sách '{$book['ten_sach']}'.");
            header("Location: borrow_transaction.php?tab={$tab}"); 
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            setMessage("Lỗi tạo phiếu mượn: " . $e->getMessage(), true);
        }
    }
}

// 2. Xử lý Trả Sách (Cập nhật phiếu mượn và tính phạt)
if ($tab == 'return' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_return'])) {
    $loan_id = trim($_POST['loan_id']);
    
    if (empty($loan_id)) {
        setMessage("Vui lòng nhập Mã Phiếu Mượn.", true);
    } else {
        try {
            $db->beginTransaction();
            $ngay_tra_thuc_te = date('Y-m-d');
            $phat = 0; // Số tiền phạt

            // 1. Lấy thông tin phiếu mượn
            $stmt_loan_info = $db->prepare("SELECT ma_sach, ngay_tra_du_kien FROM phieu_muon WHERE ma_phieu_muon = ? AND trang_thai_muon = 'dang_muon'");
            $stmt_loan_info->execute([$loan_id]);
            $loan = $stmt_loan_info->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                throw new Exception("Không tìm thấy Phiếu Mượn đang hoạt động có mã #{$loan_id}.");
            }

            // 2. Kiểm tra quá hạn và tính phạt (Nếu quá 7 ngày, phạt 5,000 VNĐ/ngày)
            $date_expected = new DateTime($loan['ngay_tra_du_kien']);
            $date_actual = new DateTime($ngay_tra_thuc_te);
            
            $days_late = 0;
            if ($date_actual > $date_expected) {
                $interval = $date_actual->diff($date_expected);
                $days_late = $interval->days;
                $phat = $days_late * 5000; // 5,000 VNĐ / ngày quá hạn
            }

            // 3. Cập nhật phiếu mượn
            $stmt_update_loan = $db->prepare("UPDATE phieu_muon SET trang_thai_muon = 'da_tra', ngay_tra_thuc_te = ? WHERE ma_phieu_muon = ?");
            $stmt_update_loan->execute([$ngay_tra_thuc_te, $loan_id]);

            // 4. Cập nhật số lượng sách khả dụng (tăng lên 1)
            $stmt_update_book = $db->prepare("UPDATE sach SET so_luong_kha_dung = so_luong_kha_dung + 1 WHERE ma_sach = ?");
            $stmt_update_book->execute([$loan['ma_sach']]);
            
            // 5. Tạo phiếu phạt nếu có
            if ($phat > 0) {
                $stmt_fine = $db->prepare("INSERT INTO phieu_phat (ma_phieu_muon, so_tien_phat, ly_do, trang_thai_thanh_toan) VALUES (?, ?, ?, 'chua_thanh_toan')");
                $stmt_fine->execute([$loan_id, $phat, "Quá hạn {$days_late} ngày (Dự kiến: {$loan['ngay_tra_du_kien']})"]);
            }

            $db->commit();
            
            $success_msg = "Đã xử lý trả sách thành công cho Phiếu Mượn #{$loan_id}.";
            if ($phat > 0) {
                $success_msg .= " **Cần thanh toán Phiếu Phạt** tổng cộng: " . number_format($phat) . " VNĐ.";
            }
            setMessage($success_msg);
            header("Location: borrow_transaction.php?tab={$tab}"); 
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            setMessage("Lỗi xử lý trả sách: " . $e->getMessage(), true);
        }
    }
}

// 3. Xử lý Thanh Toán Phiếu Phạt
if ($tab == 'fines' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_fine'])) {
    $fine_id = isset($_POST['fine_id']) ? trim($_POST['fine_id']) : null;

    if (empty($fine_id)) {
        setMessage("Vui lòng chọn Mã Phiếu Phạt.", true);
    } else {
        try {
            $stmt_fine = $db->prepare("SELECT so_tien_phat FROM phieu_phat WHERE ma_phieu_phat = ? AND trang_thai_thanh_toan = 'chua_thanh_toan'");
            $stmt_fine->execute([$fine_id]);
            $fine = $stmt_fine->fetch(PDO::FETCH_ASSOC);

            if (!$fine) {
                throw new Exception("Không tìm thấy Phiếu Phạt chưa thanh toán có mã #{$fine_id}.");
            }

            $stmt_update_fine = $db->prepare("UPDATE phieu_phat SET trang_thai_thanh_toan = 'da_thanh_toan', ngay_thanh_toan = ? WHERE ma_phieu_phat = ?");
            $stmt_update_fine->execute([date('Y-m-d'), $fine_id]);
            
            setMessage("Đã thanh toán Phiếu Phạt #{$fine_id} thành công với số tiền " . number_format($fine['so_tien_phat']) . " VNĐ.");
            header("Location: borrow_transaction.php?tab={$tab}"); 
            exit();

        } catch (Exception $e) {
            setMessage("Lỗi thanh toán: " . $e->getMessage(), true);
        }
    }
}


// --- LẤY DỮ LIỆU ĐỂ HIỂN THỊ ---

// Lấy danh sách phiếu phạt chưa thanh toán (cho tab 'fines')
$unpaid_fines = [];
if ($tab == 'fines') {
    try {
        $stmt = $db->query("SELECT pp.ma_phieu_phat, pp.so_tien_phat, pp.ly_do, nd.ho_ten, pm.ma_phieu_muon 
                            FROM phieu_phat pp 
                            JOIN phieu_muon pm ON pp.ma_phieu_muon = pm.ma_phieu_muon
                            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
                            WHERE pp.trang_thai_thanh_toan = 'chua_thanh_toan' 
                            ORDER BY pp.ma_phieu_phat DESC");
        $unpaid_fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Lỗi truy vấn danh sách phạt: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Giao Dịch Mượn/Trả - Thủ Thư</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    
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
            z-index: 10;
        }
        .main-content-wrapper {
            flex-grow: 1; 
            padding: 20px;
            max-width: calc(100% - var(--sidebar-width)); 
            z-index: 1;
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
        <li><a href="book_management.php"><i class="fas fa-book"></i> Quản lý sách</a></li>
        <li><a href="borrow_transaction.php" class="active"><i class="fas fa-exchange-alt"></i> Quản lý mượn trả</a></li>
        <li><a href="report.php"><i class="fas fa-file-invoice-dollar"></i> Lịch sử mượn trả</a></li>
        
        <hr>
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="main-content-wrapper">

    <div class="container mx-auto p-0"> 
        <div class="bg-white shadow-xl rounded-xl p-6 lg:p-10">
            
            <h3 class="text-2xl font-bold text-blue-600 border-b-2 border-gray-200 pb-4 mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Xử Lý Nghiệp Vụ Thư Viện
            </h3>
            
            <?php if ($error): ?>
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100 font-semibold" role="alert">
                    <span class="font-bold">Lỗi:</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-100 font-semibold" role="alert">
                    <span class="font-bold">Thành công:</span> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="flex border-b border-gray-200 mb-6">
                <a href="?tab=loan" 
                    class="py-3 px-4 text-center font-semibold transition duration-200 
                    <?php echo $tab == 'loan' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-500 hover:text-blue-600 hover:border-b-4 hover:border-blue-100'; ?>">
                    <i class="fas fa-sign-out-alt mr-1"></i> Mượn Sách
                </a>
                <a href="?tab=return" 
                    class="py-3 px-4 text-center font-semibold transition duration-200 
                    <?php echo $tab == 'return' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-500 hover:text-blue-600 hover:border-b-4 hover:border-blue-100'; ?>">
                    <i class="fas fa-sign-in-alt mr-1"></i> Trả Sách
                </a>
                <a href="?tab=fines" 
                    class="py-3 px-4 text-center font-semibold transition duration-200 
                    <?php echo $tab == 'fines' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-500 hover:text-blue-600 hover:border-b-4 hover:border-blue-100'; ?>">
                    <i class="fas fa-gavel mr-1"></i> Phiếu Phạt
                </a>
            </div>

            <div class="tab-content">

                <?php if ($tab == 'loan'): ?>
                    <h4 class="text-xl font-semibold text-gray-700 mb-4"><i class="fas fa-sign-out-alt mr-2"></i> Tạo Phiếu Mượn Mới</h4>
                    <p class="text-gray-600 mb-6">Nhập mã người dùng và mã sách để thực hiện giao dịch mượn. (Thời gian mượn mặc định 7 ngày).</p>

                    <form method="POST" class="bg-gray-50 p-6 rounded-lg shadow-md max-w-lg">
                        <div class="mb-4">
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Mã Người Dùng / ID Độc Giả:</label>
                            <input type="text" id="user_id" name="user_id" required 
                                    placeholder="Ví dụ: 1 hoặc ND001"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                        </div>
                        <div class="mb-6">
                            <label for="book_id" class="block text-sm font-medium text-gray-700 mb-1">Mã Sách / ISBN Sách:</label>
                            <input type="text" id="book_id" name="book_id" required 
                                    placeholder="Ví dụ: 101 hoặc ISBN123456"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                        </div>
                        <button type="submit" name="create_loan" 
                                class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition duration-300 shadow-md">
                            <i class="fas fa-check-circle mr-2"></i> Xác Nhận Mượn Sách
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($tab == 'return'): ?>
                    <h4 class="text-xl font-semibold text-gray-700 mb-4"><i class="fas fa-sign-in-alt mr-2"></i> Xử Lý Trả Sách</h4>
                    <p class="text-gray-600 mb-6">Nhập mã phiếu mượn để ghi nhận trả sách và kiểm tra quá hạn.</p>

                    <form method="POST" class="bg-gray-50 p-6 rounded-lg shadow-md max-w-lg">
                        <div class="mb-6">
                            <label for="loan_id" class="block text-sm font-medium text-gray-700 mb-1">Mã Phiếu Mượn:</label>
                            <input type="number" id="loan_id" name="loan_id" required 
                                    placeholder="Ví dụ: 5"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                        </div>
                        <button type="submit" name="process_return" 
                                class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition duration-300 shadow-md">
                            <i class="fas fa-undo mr-2"></i> Xác Nhận Trả Sách
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($tab == 'fines'): ?>
                    <h4 class="text-xl font-semibold text-gray-700 mb-4"><i class="fas fa-gavel mr-2"></i> Danh Sách Phiếu Phạt Chưa Thanh Toán</h4>
                    <p class="text-gray-600 mb-6">Xử lý thanh toán khi độc giả đóng tiền phạt.</p>

                    <?php if (empty($unpaid_fines)): ?>
                        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 font-semibold">
                            Tuyệt vời! Hiện không có phiếu phạt nào chưa thanh toán.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto shadow-lg rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-blue-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Mã PP</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Độc Giả</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Mã PM</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Lý Do Phạt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Số Tiền (VNĐ)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($unpaid_fines as $fine): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $fine['ma_phieu_phat']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($fine['ho_ten']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $fine['ma_phieu_muon']; ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($fine['ly_do']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                                <?php echo number_format($fine['so_tien_phat']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="fine_id" value="<?php echo $fine['ma_phieu_phat']; ?>">
                                                    <button type="submit" name="pay_fine" 
                                                            class="bg-green-500 text-white px-3 py-1 text-xs font-bold rounded-lg hover:bg-green-600 transition duration-150">
                                                        Thanh Toán
                                                    </button>
                                                </form>
                                            </td>
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
</div>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>