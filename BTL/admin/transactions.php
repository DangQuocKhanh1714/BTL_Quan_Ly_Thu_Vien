<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

$db = $GLOBALS['db'];
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';

// Xóa thông báo khỏi session sau khi lấy ra để tránh hiển thị lại
unset($_SESSION['message']);
unset($_SESSION['error']);

$title = "Quản Lý Nghiệp Vụ - Admin";

$items_per_page = 10;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

$tab = $_GET['tab'] ?? 'books';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $current_tab = $_POST['tab'] ?? 'books';
    $current_page_after_post = (int)($_POST['current_page'] ?? $current_page);

    try {
        $db->beginTransaction();

        if ($current_tab == 'books') {
            
            if ($action == 'add_book') {
                $ten_sach = trim($_POST['ten_sach']);
                $ma_tac_gia = (int)$_POST['ma_tac_gia'];
                $so_luong = (int)$_POST['so_luong'];
                
                if (empty($ten_sach) || $ma_tac_gia <= 0 || $so_luong <= 0) {
                    throw new Exception("Vui lòng điền đầy đủ và chính xác thông tin sách (số lượng phải lớn hơn 0).");
                }

                $stmt = $db->prepare("INSERT INTO sach (ten_sach, ma_tac_gia, tong_so_luong, so_luong_kha_dung) VALUES (?, ?, ?, ?)");
                $stmt->execute([$ten_sach, $ma_tac_gia, $so_luong, $so_luong]);
                $_SESSION['message'] = "Thêm sách **$ten_sach** thành công!";
            }
            
            elseif ($action == 'edit_book') {
                $ma_sach = (int)$_POST['ma_sach'];
                $ten_sach_moi = trim($_POST['ten_sach_moi']);
                $ma_tac_gia_moi = (int)$_POST['ma_tac_gia_moi'];
                $tong_so_luong_moi = (int)$_POST['tong_so_luong_moi'];
                
                if (empty($ten_sach_moi) || $ma_tac_gia_moi <= 0 || $tong_so_luong_moi < 0) {
                    throw new Exception("Vui lòng điền đầy đủ và chính xác thông tin sách.");
                }

                $old_book_stmt = $db->prepare("SELECT tong_so_luong, so_luong_kha_dung FROM sach WHERE ma_sach = ?");
                $old_book_stmt->execute([$ma_sach]);
                $old_book = $old_book_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$old_book) {
                    throw new Exception("Sách cần sửa (ID: $ma_sach) không tồn tại.");
                }

                $tong_cu = $old_book['tong_so_luong'];
                $kha_dung_cu = $old_book['so_luong_kha_dung'];
                
                $so_luong_dang_muon = $tong_cu - $kha_dung_cu;

                if ($tong_so_luong_moi < $so_luong_dang_muon) {
                    throw new Exception("Tổng số lượng mới phải tối thiểu là $so_luong_dang_muon (bằng số lượng sách đang được mượn).");
                }

                $so_luong_kha_dung_moi = $tong_so_luong_moi - $so_luong_dang_muon;

                $stmt = $db->prepare("UPDATE sach SET ten_sach = ?, ma_tac_gia = ?, tong_so_luong = ?, so_luong_kha_dung = ? WHERE ma_sach = ?");
                $stmt->execute([$ten_sach_moi, $ma_tac_gia_moi, $tong_so_luong_moi, $so_luong_kha_dung_moi, $ma_sach]);
                
                $_SESSION['message'] = "Cập nhật sách **$ten_sach_moi** (ID: $ma_sach) thành công! Khả dụng mới: $so_luong_kha_dung_moi.";
            }
            
            elseif ($action == 'delete_book') {
                $ma_sach = (int)$_POST['ma_sach'];
                
                $check_loan_stmt = $db->prepare("SELECT COUNT(*) FROM phieu_muon WHERE ma_sach = ? AND trang_thai_muon = 'dang_muon'");
                $check_loan_stmt->execute([$ma_sach]);
                if ($check_loan_stmt->fetchColumn() > 0) {
                    throw new Exception("Không thể xóa sách (ID: $ma_sach) vì vẫn còn phiếu mượn đang hoạt động liên quan đến cuốn sách này.");
                }

                $book_name_stmt = $db->prepare("SELECT ten_sach FROM sach WHERE ma_sach = ?");
                $book_name_stmt->execute([$ma_sach]);
                $book_name = $book_name_stmt->fetchColumn() ?? 'Không rõ';
                
                $db->prepare("DELETE FROM phieu_phat WHERE ma_phieu_muon IN (SELECT ma_phieu_muon FROM phieu_muon WHERE ma_sach = ?)")->execute([$ma_sach]);
                $db->prepare("DELETE FROM phieu_muon WHERE ma_sach = ?")->execute([$ma_sach]);
                $db->prepare("DELETE FROM sach WHERE ma_sach = ?")->execute([$ma_sach]);
                $_SESSION['message'] = "Xóa sách **$book_name** (ID: $ma_sach) thành công!";
            }
        }

        elseif ($current_tab == 'loans') {
            
            if ($action == 'edit_loan') {
                $phieu_muon_id = (int)$_POST['ma_phieu_muon'];
                $trang_thai_muon_moi = $_POST['trang_thai_muon_moi'];
                $ngay_den_han_moi = $_POST['ngay_den_han_moi'];
                $ngay_tra_thuc_te_moi = !empty($_POST['ngay_tra_thuc_te_moi']) ? $_POST['ngay_tra_thuc_te_moi'] : NULL;
                
                $loan_info_stmt = $db->prepare("SELECT ma_sach, trang_thai_muon FROM phieu_muon WHERE ma_phieu_muon = ?");
                $loan_info_stmt->execute([$phieu_muon_id]);
                $loan_old = $loan_info_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$loan_old) {
                    throw new Exception("Phiếu mượn ID **$phieu_muon_id** không tồn tại.");
                }
                
                $ma_sach = $loan_old['ma_sach'];
                $trang_thai_cu = $loan_old['trang_thai_muon'];
                $update_book_count = 0;

                if ($trang_thai_cu == 'dang_muon' && $trang_thai_muon_moi == 'da_tra') {
                    $update_book_count = 1;
                    if (empty($ngay_tra_thuc_te_moi)) {
                        $ngay_tra_thuc_te_moi = date('Y-m-d H:i:s');
                    }
                } elseif ($trang_thai_cu == 'da_tra' && $trang_thai_muon_moi == 'dang_muon') {
                    $update_book_count = -1;
                    $ngay_tra_thuc_te_moi = NULL;
                }
                
                if ($update_book_count != 0) {
                    $update_book_stmt = $db->prepare("UPDATE sach SET so_luong_kha_dung = so_luong_kha_dung + ? WHERE ma_sach = ?");
                    $update_book_stmt->execute([$update_book_count, $ma_sach]);
                }

                $stmt = $db->prepare("
                    UPDATE phieu_muon SET 
                        ngay_tra_du_kien = ?, 
                        ngay_tra_thuc_te = ?, 
                        trang_thai_muon = ? 
                    WHERE ma_phieu_muon = ?
                ");
                $stmt->execute([$ngay_den_han_moi, $ngay_tra_thuc_te_moi, $trang_thai_muon_moi, $phieu_muon_id]);
                
                $_SESSION['message'] = "Cập nhật Phiếu Mượn ID **$phieu_muon_id** thành công! (Thay đổi khả dụng: $update_book_count).";
            }
            
            elseif ($action == 'delete_loan') {
                $phieu_muon_id = (int)$_POST['phieu_muon_id'];
                
                $loan_info_stmt = $db->prepare("SELECT ma_sach, trang_thai_muon FROM phieu_muon WHERE ma_phieu_muon = ?");
                $loan_info_stmt->execute([$phieu_muon_id]);
                $loan = $loan_info_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$loan) {
                    throw new Exception("Phiếu mượn ID **$phieu_muon_id** không tồn tại.");
                }
                
                $so_luong_muon_khoi_phuc = 1; 

                if ($loan['trang_thai_muon'] == 'dang_muon') {
                    $update_book_stmt = $db->prepare("UPDATE sach SET so_luong_kha_dung = so_luong_kha_dung + ? WHERE ma_sach = ?");
                    $update_book_stmt->execute([$so_luong_muon_khoi_phuc, $loan['ma_sach']]);
                    $message_suffix = " và đã khôi phục **$so_luong_muon_khoi_phuc** sách khả dụng.";
                } else {
                    $message_suffix = ".";
                }
                
                $db->prepare("DELETE FROM phieu_phat WHERE ma_phieu_muon = ?")->execute([$phieu_muon_id]);
                $db->prepare("DELETE FROM phieu_muon WHERE ma_phieu_muon = ?")->execute([$phieu_muon_id]);
                $_SESSION['message'] = "Xóa phiếu mượn ID **$phieu_muon_id** thành công $message_suffix";
            }
        }

        elseif ($current_tab == 'fines') {
            if ($action == 'update_fine_status') {
                $phieu_phat_id = (int)$_POST['phieu_phat_id'];
                $trang_thai_thanh_toan = $_POST['trang_thai_thanh_toan']; 
                
                if (!in_array($trang_thai_thanh_toan, ['chua_thanh_toan', 'da_thanh_toan'])) {
                    throw new Exception("Trạng thái thanh toán không hợp lệ.");
                }
                
                $db->prepare("UPDATE phieu_phat SET trang_thai_thanh_toan = ? WHERE ma_phieu_phat = ?")->execute([$trang_thai_thanh_toan, $phieu_phat_id]);
                $_SESSION['message'] = "Cập nhật trạng thái phiếu phạt ID **$phieu_phat_id** thành công!";
            }
        }
        
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Lỗi xử lý: " . $e->getMessage();
    }
    header("Location: transactions.php?tab=$current_tab&page=$current_page_after_post");
    exit();
}

$total_items = 0;
$data = [];

try {
    if ($tab == 'books') {
        $total_items = $db->query("SELECT COUNT(*) FROM sach")->fetchColumn();
        $books = $db->prepare("
            SELECT s.*, tg.ten_tac_gia, tg.ma_tac_gia AS ma_tac_gia_tg
            FROM sach s 
            JOIN tac_gia tg ON s.ma_tac_gia = tg.ma_tac_gia 
            ORDER BY s.ma_sach ASC 
            LIMIT :limit OFFSET :offset
        ");
        $books->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $books->bindValue(':offset', $offset, PDO::PARAM_INT);
        $books->execute();
        $data = $books->fetchAll(PDO::FETCH_ASSOC);

        $authors = $db->query("SELECT ma_tac_gia, ten_tac_gia FROM tac_gia ORDER BY ten_tac_gia")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    elseif ($tab == 'loans') {
        $total_items = $db->query("SELECT COUNT(*) FROM phieu_muon")->fetchColumn();
        $loans_query = $db->prepare("
            SELECT 
                pm.*, nd.ho_ten, nd.ma_nguoi_dung, s.ten_sach
            FROM phieu_muon pm 
            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung
            JOIN sach s ON pm.ma_sach = s.ma_sach
            ORDER BY ngay_muon ASC 
            LIMIT :limit OFFSET :offset
        ");
        $loans_query->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $loans_query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $loans_query->execute();
        $data = $loans_query->fetchAll(PDO::FETCH_ASSOC);
    }

    elseif ($tab == 'fines') {
        $total_items = $db->query("SELECT COUNT(*) FROM phieu_phat")->fetchColumn();
        $fines_query = $db->prepare("
            SELECT 
                pp.*, nd.ho_ten, pm.ma_phieu_muon
            FROM phieu_phat pp 
            JOIN phieu_muon pm ON pp.ma_phieu_muon = pm.ma_phieu_muon 
            JOIN nguoi_dung nd ON pm.ma_nguoi_muon = nd.ma_nguoi_dung 
            ORDER BY pp.ngay_ghi_nhan ASC 
            LIMIT :limit OFFSET :offset
        ");
        $fines_query->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $fines_query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $fines_query->execute();
        $data = $fines_query->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Lỗi lấy dữ liệu: " . $e->getMessage();
}

$total_pages = ceil($total_items / $items_per_page);

function renderPagination($total_pages, $current_page, $tab) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination-container">';
    
    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
    $prev_page = $current_page - 1;
    $html .= "<a href='?tab=$tab&page=$prev_page' class='page-link $prev_disabled'><i class='fas fa-angle-left'></i> Trước</a>";

    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    if ($start > 1) {
        $html .= "<a href='?tab=$tab&page=1' class='page-link'>1</a>";
        if ($start > 2) $html .= "<span class='page-dots'>...</span>";
    }

    for ($i = $start; $i <= $end; $i++) {
        $active_class = $i == $current_page ? 'active' : '';
        $html .= "<a href='?tab=$tab&page=$i' class='page-link $active_class'>$i</a>";
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) $html .= "<span class='page-dots'>...</span>";
        $html .= "<a href='?tab=$tab&page=$total_pages' class='page-link'>$total_pages</a>";
    }

    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
    $next_page = $current_page + 1;
    $html .= "<a href='?tab=$tab&page=$next_page' class='page-link $next_disabled'>Sau <i class='fas fa-angle-right'></i></a>";

    $html .= '</div>';
    return $html;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd; 
            --primary-dark: #0b5ed7;
            --secondary-color: #6c757d;
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
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
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
        
        .sidebar-header { text-align: center; margin-bottom: 30px; padding: 0 15px; }
        .sidebar-header h2 { color: var(--primary-color); font-size: 1.5rem; font-weight: 700; }
        .sidebar-menu { list-style: none; padding: 0 15px; margin: 0; }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 15px; text-decoration: none;
            color: var(--text-color); border-radius: 8px; margin-bottom: 5px;
            transition: all 0.3s; font-weight: 500;
        }
        .sidebar-menu a i { margin-right: 10px; font-size: 1.1rem; color: #6c757d; }
        .sidebar-menu a:hover { background-color: var(--primary-color); color: white; }
        .sidebar-menu a:hover i { color: white; }
        .sidebar-menu a.active-link { 
            background-color: var(--primary-color); color: white; font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        .sidebar-menu a.active-link i { color: white; }

        .breadcrumb { padding: 0; margin-bottom: 25px; font-size: 0.9rem; color: #6c757d; }
        .breadcrumb a { color: var(--primary-color); text-decoration: none; }
        
        .card { 
            background: white; border: none; border-radius: var(--radius); 
            box-shadow: var(--card-shadow); padding: 30px; margin-bottom: 30px; 
        }
        .card h3 { 
            font-size: 1.8rem; color: var(--primary-color); 
            border-bottom: 2px solid var(--border-color); 
            padding-bottom: 15px; margin-bottom: 25px; font-weight: 700;
        }
        .card h4 {
            font-size: 1.4rem; font-weight: 600; margin-top: 0; margin-bottom: 20px; 
            color: var(--text-color); display: flex; align-items: center;
        }
        .card h4 i { margin-right: 10px; color: var(--primary-color); }
        
        .alert { 
            padding: 15px; margin-bottom: 25px; border-radius: var(--radius); 
            font-weight: 500; border: 1px solid transparent;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex; align-items: center;
        }
        .alert i { font-size: 1.2rem; margin-right: 10px; }
        .alert.success { background-color: #d1e7dd; color: var(--success-color); border-color: #badbcc; }
        .alert.error { background-color: #f8d7da; color: var(--danger-color); border-color: #f5c6cb; }

        .tabs { border-bottom: 2px solid var(--border-color); margin-bottom: 30px; display: flex; gap: 10px; }
        .tab-link { 
            display: flex; align-items: center; padding: 12px 25px; text-decoration: none;
            color: var(--secondary-color); font-weight: 600; transition: all 0.3s ease;
            border-top-left-radius: var(--radius); border-top-right-radius: var(--radius);
            position: relative;
        }
        .tab-link i { margin-right: 8px; }
        .tab-link.active { 
            color: var(--primary-color); background-color: white;
            border: 1px solid var(--border-color); border-bottom: none;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05); z-index: 1;
        }
        .tab-link:hover:not(.active) { color: var(--primary-dark); background-color: #f9f9f9; }

        .data-table { 
            width: 100%; border-collapse: collapse; margin-top: 15px; 
            background: white; border-radius: var(--radius); overflow: hidden; 
        }
        .data-table th, .data-table td { 
            border-bottom: 1px solid var(--border-color); padding: 15px 20px; 
            text-align: left; font-size: 0.95em; 
        }
        .data-table th { 
            background-color: #eef1f4; color: var(--text-color); font-weight: 700; 
            text-transform: uppercase;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background-color: #f8f9fa; transition: background-color 0.2s; }
        .data-table td:last-child { white-space: nowrap; }

        .btn { 
            padding: 10px 20px; border: none; border-radius: 0.5rem; 
            cursor: pointer; transition: all 0.3s ease; font-size: 0.9em; 
            font-weight: 600; text-decoration: none; 
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn i { margin-right: 5px; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-dark); box-shadow: 0 4px 10px rgba(13, 110, 253, 0.4); }
        .btn-edit { background-color: var(--warning-color); color: var(--text-color); margin-right: 5px; padding: 8px 15px;}
        .btn-edit:hover { background-color: #ffda6a; }
        .btn-delete { background-color: var(--danger-color); color: white; padding: 8px 15px;}
        .btn-delete:hover { background-color: #c9303d; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-success:hover { background-color: #147644; }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: #5c636a; }
        .btn-group { display: flex; gap: 5px;}

        .badge { 
            padding: 6px 10px; border-radius: 50px; font-weight: 700; 
            font-size: 0.8em; display: inline-block;
            min-width: 80px; text-align: center;
        }
        .badge.available { background-color: #d1e7dd; color: var(--success-color); }
        .badge.unavailable { background-color: #f8d7da; color: var(--danger-color); }
        .badge.loan { background-color: #ffeedd; color: #ff8c00; } 
        .badge.returned { background-color: #d0f0ff; color: #007bff; }
        .badge.paid { background-color: #d1e7dd; color: var(--success-color); }
        .badge.unpaid { background-color: #f8d7da; color: var(--danger-color); }

        .pagination-container {
            display: flex; justify-content: center; align-items: center;
            margin-top: 25px; gap: 5px;
        }
        .page-link {
            padding: 8px 14px; border: 1px solid #ccc; border-radius: 6px; 
            text-decoration: none; color: var(--primary-color); font-weight: 600;
            transition: background-color 0.2s;
        }
        .page-link.active {
            background-color: var(--primary-color); color: white; border-color: var(--primary-dark);
        }
        .page-link:hover:not(.active):not(.disabled) {
            background-color: #e9ecef;
        }
        .page-link.disabled {
            color: #adb5bd; pointer-events: none; background-color: #f8f9fa;
        }
        .page-dots {
            padding: 8px 5px; color: #6c757d;
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: 1000;
            display: none; justify-content: center; align-items: center;
            opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.open { opacity: 1; visibility: visible; display: flex; }
        .modal-content {
            background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 550px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3); position: relative;
            transform: translateY(-50px); transition: transform 0.3s ease;
        }
        .modal-overlay.open .modal-content { transform: translateY(0); }
        .modal-content h4 { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 1.8rem; cursor: pointer; color: #999; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-color); }
        .form-group input, .form-group select {
            width: 100%; padding: 12px; border: 1px solid var(--border-color); 
            border-radius: 8px; box-sizing: border-box; font-size: 1em;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
            outline: none;
        }

        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100%; height: auto; position: relative; padding-bottom: 0;
                display: block;
            }
            .sidebar-menu {
                display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; padding: 10px 15px;
            }
            .sidebar-menu a { padding: 10px 15px; font-size: 0.9em; margin-bottom: 0; }
            .sidebar-menu hr { display: none; }
            .sidebar-header { margin-bottom: 10px; }
            .main-content-wrapper { max-width: 100%; }
            .tabs { flex-wrap: wrap; }
            .tab-link { flex-grow: 1; text-align: center; justify-content: center; }
            .data-table, .data-table thead, .data-table tbody, .data-table th, .data-table td, .data-table tr { 
                display: block; 
            }
            .data-table thead tr { 
                position: absolute; top: -9999px; left: -9999px;
            }
            .data-table tr { margin-bottom: 15px; border: 1px solid var(--border-color); border-radius: var(--radius); }
            .data-table td { 
                border: none; border-bottom: 1px dotted #ccc; 
                position: relative; padding-left: 50%; 
                text-align: right; 
            }
            .data-table td:before {
                content: attr(data-label);
                position: absolute; left: 6px; width: 45%; 
                padding-right: 10px; white-space: nowrap;
                text-align: left; font-weight: 700; color: #6c757d;
            }
            .data-table td:last-child { border-bottom: none; }
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
        <li><a href="users.php"><i class="fas fa-user-shield"></i> Quản lý Người dùng</a></li>
        <li><a href="transactions.php?tab=books" class="active-link"><i class="fas fa-book"></i> Quản lý Sách</a></li>
        <li><a href="transactions.php?tab=loans" class="active-link"><i class="fas fa-exchange-alt"></i> Xử lý Mượn/Trả</a></li>
        <li><a href="transactions.php?tab=fines" class="active-link"><i class="fas fa-gavel"></i> Quản lý Phiếu phạt</a></li>
        
        <hr style="border-color: #f1f1f1; margin: 15px 0;">
        
        <li><a href="../index.php"><i class="fas fa-home"></i> Về Trang Chủ</a></li>
        <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>

<div class="main-content-wrapper">
    <div class="admin-container">

        <div class="breadcrumb">
            <a href="dashboard.php">Quản lý</a>
            <span>/</span>
            <strong>
                <?php 
                    if ($tab == 'books') echo 'Quản lý Sách';
                    elseif ($tab == 'loans') echo 'Quản lý Phiếu Mượn/Trả';
                    elseif ($tab == 'fines') echo 'Quản lý Phiếu Phạt';
                    else echo 'Dữ liệu Nghiệp vụ';
                ?>
            </strong>
        </div>

        <div class="card">
            <h3><i class="fas fa-database"></i> Quản Lý Dữ Liệu Nghiệp Vụ</h3>
            <?php if ($message): ?><div class="alert success"><i class="fas fa-check-circle"></i><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><i class="fas fa-times-circle"></i><?php echo $error; ?></div><?php endif; ?>
            
            <div class="tabs">
                <a href="?tab=books" class="tab-link <?php echo $tab == 'books' ? 'active' : ''; ?>"><i class="fas fa-book"></i> QL Sách</a>
                <a href="?tab=loans" class="tab-link <?php echo $tab == 'loans' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> QL Phiếu Mượn/Trả</a>
                <a href="?tab=fines" class="tab-link <?php echo $tab == 'fines' ? 'active' : ''; ?>"><i class="fas fa-gavel"></i> QL Phiếu Phạt</a>
            </div>
            
            <div class="tab-content">
                <?php if ($tab == 'books'): ?>
                    <h4><i class="fas fa-list-ul"></i> Danh Mục Sách (Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?>)</h4>
                    <button class="btn btn-primary" onclick="openModal('addBookModal');"><i class="fas fa-plus"></i> Thêm Sách Mới</button>
                    
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Tên Sách</th><th>Tác Giả</th><th>Tổng SL</th><th>SL Khả Dụng</th><th>Thao Tác</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="6" class="text-center" style="padding: 20px;">Chưa có dữ liệu sách.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($data as $book): ?>
                            <tr>
                                <td data-label="ID"><?php echo $book['ma_sach']; ?></td>
                                <td data-label="Tên Sách"><?php echo htmlspecialchars($book['ten_sach']); ?></td>
                                <td data-label="Tác Giả"><?php echo htmlspecialchars($book['ten_tac_gia']); ?></td>
                                <td data-label="Tổng SL"><?php echo $book['tong_so_luong']; ?></td>
                                <td data-label="SL Khả Dụng">
                                    <span class="badge <?php echo $book['so_luong_kha_dung'] > 0 ? 'available' : 'unavailable'; ?>">
                                        <?php echo $book['so_luong_kha_dung']; ?>
                                    </span>
                                </td>
                                <td data-label="Thao Tác">
                                    <div class="btn-group">
                                        <button class="btn btn-edit edit-book-btn" 
                                            data-id="<?php echo $book['ma_sach']; ?>"
                                            data-name="<?php echo htmlspecialchars($book['ten_sach']); ?>"
                                            data-author-id="<?php echo $book['ma_tac_gia_tg']; ?>"
                                            data-total="<?php echo $book['tong_so_luong']; ?>"
                                            data-available="<?php echo $book['so_luong_kha_dung']; ?>"
                                            title="Sửa thông tin sách">
                                            <i class="fas fa-pencil-alt"></i> Sửa
                                        </button>
                                        
                                        <button class="btn btn-delete delete-book-btn"
                                            data-id="<?php echo $book['ma_sach']; ?>"
                                            data-name="<?php echo htmlspecialchars($book['ten_sach']); ?>"
                                            title="Xóa sách">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php echo renderPagination($total_pages, $current_page, $tab); ?>
                    
                    <div id="addBookModal" class="modal-overlay" onclick="closeModal(event, 'addBookModal')">
                        <div class="modal-content">
                            <span class="modal-close" onclick="closeModalById('addBookModal');">&times;</span>
                            <h4><i class="fas fa-plus-circle"></i> Thêm Sách Mới</h4>
                            <form method="POST" action="transactions.php">
                                <input type="hidden" name="action" value="add_book">
                                <input type="hidden" name="tab" value="books">
                                <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">

                                <div class="form-group">
                                    <label for="ten_sach">Tên Sách</label>
                                    <input type="text" id="ten_sach" name="ten_sach" placeholder="Nhập tên sách..." required>
                                </div>

                                <div class="form-group">
                                    <label for="ma_tac_gia">Tác Giả</label>
                                    <select id="ma_tac_gia" name="ma_tac_gia" required>
                                        <option value="">-- Chọn Tác Giả --</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['ma_tac_gia']; ?>">
                                                <?php echo htmlspecialchars($author['ten_tac_gia']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="so_luong">Số Lượng Nhập Kho</label>
                                    <input type="number" id="so_luong" name="so_luong" required min="1" value="1">
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Sách</button>
                            </form>
                        </div>
                    </div>

                    <div id="editBookModal" class="modal-overlay" onclick="closeModal(event, 'editBookModal')">
                        <div class="modal-content">
                            <span class="modal-close" onclick="closeModalById('editBookModal');">&times;</span>
                            <h4><i class="fas fa-pencil-alt"></i> Sửa Thông Tin Sách</h4>
                            <form method="POST" action="transactions.php">
                                <input type="hidden" name="action" value="edit_book">
                                <input type="hidden" name="tab" value="books">
                                <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="ma_sach" id="edit_ma_sach">

                                <div class="form-group">
                                    <label for="edit_ten_sach_moi">Tên Sách</label>
                                    <input type="text" id="edit_ten_sach_moi" name="ten_sach_moi" required>
                                </div>

                                <div class="form-group">
                                    <label for="edit_ma_tac_gia_moi">Tác Giả</label>
                                    <select id="edit_ma_tac_gia_moi" name="ma_tac_gia_moi" required>
                                        <option value="">-- Chọn Tác Giả --</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['ma_tac_gia']; ?>">
                                                <?php echo htmlspecialchars($author['ten_tac_gia']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_tong_so_luong_moi">Tổng Số Lượng (Hiện đang mượn: <span id="so_luong_dang_muon_text" style="font-weight: 700; color: var(--danger-color);">0</span>)</label>
                                    <input type="number" id="edit_tong_so_luong_moi" name="tong_so_luong_moi" required min="0">
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                            </form>
                        </div>
                    </div>
                    
                    <div id="deleteBookModal" class="modal-overlay" onclick="closeModal(event, 'deleteBookModal')">
                        <div class="modal-content" style="max-width: 400px;">
                            <span class="modal-close" onclick="closeModalById('deleteBookModal');">&times;</span>
                            <h4 style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> Xác Nhận Xóa Sách</h4>
                            <p>Bạn có chắc chắn muốn **XÓA VĨNH VIỄN** sách: <br><strong id="delete_book_name"></strong>?</p>
                            <p style="color: var(--danger-color); font-weight: 500;">CẢNH BÁO: Không thể xóa sách nếu còn phiếu mượn **ĐANG HOẠT ĐỘNG** liên quan.</p>
                            <form method="POST" action="transactions.php" style="text-align: right; margin-top: 20px;">
                                <input type="hidden" name="action" value="delete_book">
                                <input type="hidden" name="tab" value="books">
                                <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="ma_sach" id="delete_ma_sach">
                                <button type="button" class="btn btn-secondary" onclick="closeModalById('deleteBookModal');">Hủy</button>
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Xác Nhận Xóa</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($tab == 'loans'): ?>
                    <h4><i class="fas fa-exchange-alt"></i> Phiếu Mượn/Trả (Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?>)</h4>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID PM</th><th>Độc Giả</th><th>Tên Sách</th><th>Ngày Mượn</th><th>Ngày Hạn</th><th>Trạng Thái</th><th>Thao Tác</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="7" class="text-center" style="padding: 20px;">Chưa có phiếu mượn nào được ghi nhận.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($data as $loan): ?>
                            <tr>
                                <td data-label="ID PM"><?php echo $loan['ma_phieu_muon']; ?></td>
                                <td data-label="Độc Giả"><?php echo htmlspecialchars($loan['ho_ten']); ?> (ID: <?php echo $loan['ma_nguoi_muon']; ?>)</td>
                                <td data-label="Tên Sách"><?php echo htmlspecialchars($loan['ten_sach']); ?></td>
                                <td data-label="Ngày Mượn"><?php echo $loan['ngay_muon']; ?></td>
                                <td data-label="Ngày Hạn"><?php echo $loan['ngay_tra_du_kien']; ?></td>
                                <td data-label="Trạng Thái">
                                    <span class="badge <?php echo $loan['trang_thai_muon'] == 'dang_muon' ? 'loan' : 'returned'; ?>">
                                        <?php echo $loan['trang_thai_muon'] == 'dang_muon' ? 'Đang Mượn' : 'Đã Trả'; ?>
                                    </span>
                                </td>
                                <td data-label="Thao Tác">
                                    <div class="btn-group">
                                        <button class="btn btn-edit edit-loan-btn" 
                                            data-id="<?php echo $loan['ma_phieu_muon']; ?>"
                                            data-name="<?php echo htmlspecialchars($loan['ho_ten']); ?>"
                                            data-book="<?php echo htmlspecialchars($loan['ten_sach']); ?>"
                                            data-due-date="<?php echo $loan['ngay_tra_du_kien']; ?>"
                                            data-return-date="<?php echo $loan['ngay_tra_thuc_te']; ?>"
                                            data-status="<?php echo $loan['trang_thai_muon']; ?>"
                                            title="Cập nhật phiếu mượn">
                                            <i class="fas fa-pencil-alt"></i> Cập nhật
                                        </button>
                                        <button class="btn btn-delete delete-loan-btn"
                                            data-id="<?php echo $loan['ma_phieu_muon']; ?>"
                                            data-name="Phiếu mượn ID <?php echo $loan['ma_phieu_muon']; ?>"
                                            title="Xóa phiếu mượn">
                                            <i class="fas fa-trash"></i> Xóa PM
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php echo renderPagination($total_pages, $current_page, $tab); ?>
                    
                    <div id="editLoanModal" class="modal-overlay" onclick="closeModal(event, 'editLoanModal')">
                        <div class="modal-content">
                            <span class="modal-close" onclick="closeModalById('editLoanModal');">&times;</span>
                            <h4><i class="fas fa-pencil-alt"></i> Cập Nhật Phiếu Mượn ID <span id="edit_loan_id_text" style="color: var(--primary-color);"></span></h4>
                            <p>Độc giả: <strong id="edit_loan_user_name"></strong> | Sách: <strong id="edit_loan_book_name"></strong></p>
                            <form method="POST" action="transactions.php">
                                <input type="hidden" name="action" value="edit_loan">
                                <input type="hidden" name="tab" value="loans">
                                <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="ma_phieu_muon" id="edit_ma_phieu_muon">

                                <div class="form-group">
                                    <label for="edit_ngay_den_han_moi">Ngày Trả Dự Kiến</label>
                                    <input type="date" id="edit_ngay_den_han_moi" name="ngay_den_han_moi" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_trang_thai_muon_moi">Trạng Thái Mượn</label>
                                    <select id="edit_trang_thai_muon_moi" name="trang_thai_muon_moi" required>
                                        <option value="dang_muon">Đang Mượn</option>
                                        <option value="da_tra">Đã Trả</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_ngay_tra_thuc_te_moi">Ngày Trả Thực Tế (Để trống nếu Đang Mượn)</label>
                                    <input type="datetime-local" id="edit_ngay_tra_thuc_te_moi" name="ngay_tra_thuc_te_moi">
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Cập Nhật</button>
                            </form>
                        </div>
                    </div>
                    
                    <div id="deleteLoanModal" class="modal-overlay" onclick="closeModal(event, 'deleteLoanModal')">
                        <div class="modal-content" style="max-width: 400px;">
                            <span class="modal-close" onclick="closeModalById('deleteLoanModal');">&times;</span>
                            <h4 style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> Xác Nhận Xóa Phiếu Mượn</h4>
                            <p>Bạn có chắc chắn muốn **XÓA VĨNH VIỄN** <strong id="delete_loan_name"></strong>?</p>
                            <p style="color: var(--danger-color); font-weight: 500;">Thao tác này sẽ tự động **KHÔI PHỤC** sách khả dụng nếu sách đang được mượn.</p>
                            <form method="POST" action="transactions.php" style="text-align: right; margin-top: 20px;">
                                <input type="hidden" name="action" value="delete_loan">
                                <input type="hidden" name="tab" value="loans">
                                <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="phieu_muon_id" id="delete_phieu_muon_id">
                                <button type="button" class="btn btn-secondary" onclick="closeModalById('deleteLoanModal');">Hủy</button>
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Xác Nhận Xóa</button>
                            </form>
                        </div>
                    </div>


                <?php elseif ($tab == 'fines'): ?>
                    <h4><i class="fas fa-gavel"></i> Phiếu Phạt (Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?>)</h4>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID Phạt</th><th>Độc Giả</th><th>Số Tiền</th><th>Ngày Ghi Nhận</th><th>Trạng Thái</th><th>Thao Tác</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="6" class="text-center" style="padding: 20px;">Chưa có phiếu phạt nào được ghi nhận.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($data as $fine): ?>
                            <tr>
                                <td data-label="Mã Phạt"><?php echo $fine['ma_phieu_phat']; ?></td>
                                <td data-label="Độc Giả"><?php echo htmlspecialchars($fine['ho_ten']); ?> (PM: <?php echo $fine['ma_phieu_muon']; ?>)</td>
                                <td data-label="Số Tiền"><?php echo number_format($fine['so_tien_phat'], 0, ',', '.'); ?> VNĐ</td>
                                <td data-label="Ngày Ghi Nhận"><?php echo $fine['ngay_ghi_nhan']; ?></td>
                                <td data-label="Trạng Thái">
                                    <span class="badge <?php echo $fine['trang_thai_thanh_toan'] == 'chua_thanh_toan' ? 'unpaid' : 'paid'; ?>">
                                        <?php echo $fine['trang_thai_thanh_toan'] == 'chua_thanh_toan' ? 'Chưa Thu' : 'Đã Thu'; ?>
                                    </span>
                                </td>
                                <td data-label="Thao Tác">
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="update_fine_status">
                                        <input type="hidden" name="tab" value="fines">
                                        <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
                                        <input type="hidden" name="phieu_phat_id" value="<?php echo $fine['ma_phieu_phat']; ?>">
                                        
                                        <?php 
                                        $is_unpaid = $fine['trang_thai_thanh_toan'] == 'chua_thanh_toan';
                                        $new_status = $is_unpaid ? 'da_thanh_toan' : 'chua_thanh_toan';
                                        $button_text = $is_unpaid ? '<i class="fas fa-check-circle"></i> Đã Thu' : '<i class="fas fa-undo"></i> Hoàn Tác';
                                        $button_class = $is_unpaid ? 'btn-success' : 'btn-secondary';
                                        ?>
                                        <input type="hidden" name="trang_thai_thanh_toan" value="<?php echo $new_status; ?>">
                                        
                                        <button type="submit" class="btn <?php echo $button_class; ?>" 
                                            onclick="return confirm('Bạn có chắc chắn muốn cập nhật trạng thái phiếu phạt ID <?php echo $fine['ma_phieu_phat']; ?> không?');">
                                            <?php echo $button_text; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php echo renderPagination($total_pages, $current_page, $tab); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('open');
    }

    function closeModalById(modalId) {
        document.getElementById(modalId).classList.remove('open');
    }

    function closeModal(event, modalId) {
        const modal = document.getElementById(modalId);
        if (modal && event.target === modal) {
            modal.classList.remove('open');
        }
    }
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                modal.classList.remove('open');
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.edit-book-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = 'editBookModal';
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const authorId = this.getAttribute('data-author-id');
                const total = parseInt(this.getAttribute('data-total'));
                const available = parseInt(this.getAttribute('data-available'));
                const beingBorrowed = total - available;

                document.getElementById('edit_ma_sach').value = id;
                document.getElementById('edit_ten_sach_moi').value = name;
                document.getElementById('edit_ma_tac_gia_moi').value = authorId;
                document.getElementById('edit_tong_so_luong_moi').value = total;
                document.getElementById('edit_tong_so_luong_moi').min = beingBorrowed;
                document.getElementById('so_luong_dang_muon_text').textContent = beingBorrowed;

                openModal(modalId);
            });
        });

        document.querySelectorAll('.delete-book-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = 'deleteBookModal';
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete_ma_sach').value = id;
                document.getElementById('delete_book_name').textContent = `${name} (ID: ${id})`;
                
                openModal(modalId);
            });
        });

        document.querySelectorAll('.edit-loan-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = 'editLoanModal';
                const id = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const bookName = this.getAttribute('data-book');
                const dueDate = this.getAttribute('data-due-date');
                const returnDate = this.getAttribute('data-return-date');
                const status = this.getAttribute('data-status');

                let returnDateLocal = '';
                if (returnDate && returnDate !== '0000-00-00 00:00:00') {
                    returnDateLocal = returnDate.replace(' ', 'T').substring(0, 16);
                }
                
                document.getElementById('edit_loan_id_text').textContent = id;
                document.getElementById('edit_loan_user_name').textContent = userName;
                document.getElementById('edit_loan_book_name').textContent = bookName;
                document.getElementById('edit_ma_phieu_muon').value = id;
                document.getElementById('edit_ngay_den_han_moi').value = dueDate; 
                document.getElementById('edit_ngay_tra_thuc_te_moi').value = returnDateLocal;
                document.getElementById('edit_trang_thai_muon_moi').value = status;
                
                openModal(modalId);
            });
        });
        
        document.querySelectorAll('.delete-loan-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = 'deleteLoanModal';
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete_phieu_muon_id').value = id;
                document.getElementById('delete_loan_name').textContent = name;
                
                openModal(modalId);
            });
        });
        
        const currentTab = '<?php echo $tab; ?>';
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        
        sidebarLinks.forEach(link => {
            if (link.classList.contains('active-link')) {
                link.classList.remove('active-link');
            }
            
            if (link.href.includes('transactions.php')) {
                const url = new URL(link.href);
                const linkTab = url.searchParams.get("tab");

                if (linkTab === currentTab) {
                    link.classList.add('active-link');
                }
            }
        });
    });
</script>
</body>
</html>