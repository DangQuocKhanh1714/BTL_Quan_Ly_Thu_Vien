<?php
// index.php

$title = "Trang Chủ | Thư Viện Kỹ Thuật";
require_once 'config.php';
global $BASE_URL; 
require_once 'layout/header.php';

$db = $GLOBALS['db'];

// Lấy thông tin người dùng từ session
$user_role_id = $_SESSION['role_id'] ?? 0; 
$is_logged_in = isset($_SESSION['user_id']);

// --- 1. HÀM HIỂN THỊ SÁCH ---
function display_books($books, $total_pages, $current_page, $role_id, $query_string, $show_pagination = true) {
    global $is_logged_in;
    global $BASE_URL;
    
    if (empty($books)) {
        echo '<p class="text-center" style="grid-column: 1 / -1;">Không tìm thấy sách nào.</p>';
        return;
    }

    echo '<div class="book-list-grid">';
    foreach ($books as $book) {
        $so_luong_hien_co = $book['so_luong_kha_dung'] ?? 0;
        $badge_class = $so_luong_hien_co > 0 ? 'status-available' : 'status-out';
        $status_text = $so_luong_hien_co > 0 ? 'Còn hàng' : 'Hết';

        echo '<div class="book-card">';
        echo '  <span class="book-status ' . $badge_class . '">' . $status_text . '</span>';
        echo '  <div class="book-cover-placeholder"><i class="fas fa-book fa-3x"></i></div>';
        echo '  <div class="book-info">';
        echo '    <h5 class="book-title">' . htmlspecialchars($book['ten_sach']) . '</h5>';
        echo '    <p class="book-author"><i class="fas fa-feather-alt"></i> Tác giả: <b>' . htmlspecialchars($book['ten_tac_gia'] ?? 'N/A') . '</b></p>';
        echo '    <p class="book-year"><i class="fas fa-calendar-alt"></i> Năm XB: <b>' . htmlspecialchars($book['nam_xuat_ban'] ?? 'N/A') . '</b></p>';
        
        if ($role_id == 2) { 
            echo '  <p class="book-quantity"><i class="fas fa-boxes"></i> SL Khả Dụng: <b>' . $so_luong_hien_co . '</b></p>';
        }
        
        if ($so_luong_hien_co > 0) {
            if ($role_id == 3) { // Độc giả
                echo '  <a href="' . BASE_URL . 'transactions.php?action=borrow&book_id=' . $book['ma_sach'] . '" class="btn-borrow">Mượn Sách</a>';
            } else if (!$is_logged_in) { // Chưa đăng nhập
                echo '  <a href="' . BASE_URL . 'auth/auth.php?redirect_to=borrow&book_id=' . $book['ma_sach'] . '" class="btn-borrow btn-login-borrow">Mượn Sách</a>';
            }
        }
        
        echo '  </div>'; 
        echo '</div>'; 
    }
    echo '</div>';

    if ($show_pagination && $total_pages > 1) {
        echo '<div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? 'active-page' : '';
            echo '<a href="' . BASE_URL . '?page=' . $i . $query_string . '" class="btn-pagination ' . $active_class . '">' . $i . '</a>';
        }
        echo '</div>';
    }
}
// --- KẾT THÚC HÀM HIỂN THỊ SÁCH ---


// --- 2. XỬ LÝ LỌC, TÌM KIẾM, PHÂN TRANG ---
$limit = 8; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search_term = $_GET['search'] ?? '';
$author_id = $_GET['author'] ?? 0;

$where_clauses = [];
$params = [];
$query_parts = [];

if (!empty($search_term)) {
    $where_clauses[] = "s.ten_sach LIKE :search_term";
    $params[':search_term'] = '%' . $search_term . '%';
    $query_parts['search'] = 'search=' . urlencode($search_term);
}

if (!empty($author_id) && $author_id > 0) {
    $where_clauses[] = "s.ma_tac_gia = :author_id";
    $params[':author_id'] = $author_id;
    $query_parts['author'] = 'author=' . $author_id;
}

$query_string = !empty($query_parts) ? '&' . implode('&', $query_parts) : '';
$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";


// 2.1 Truy vấn tổng số sách và danh sách sách (Đã lược bớt code để tập trung vào HTML/CSS/Logic)
$total_books = 0;
$books = [];
$authors_for_filter = [];
$highlight_authors = [];
try {
    // Đếm tổng
    $stmt_count = $db->prepare("SELECT COUNT(*) FROM sach s JOIN tac_gia tg ON s.ma_tac_gia = tg.ma_tac_gia" . $where_sql);
    $stmt_count->execute($params);
    $total_books = $stmt_count->fetchColumn();
    $total_pages = ceil($total_books / $limit);

    // Truy vấn sách
    $stmt_books = $db->prepare("
        SELECT 
            s.ma_sach, s.ten_sach, s.so_luong_kha_dung, s.nam_xuat_ban, tg.ten_tac_gia
        FROM sach s
        JOIN tac_gia tg ON s.ma_tac_gia = tg.ma_tac_gia
        " . $where_sql . "
        ORDER BY s.ten_sach ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt_books->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_books->bindParam(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt_books->bindParam($key, $val);
    }
    $stmt_books->execute();
    $books = $stmt_books->fetchAll();

    // Lấy tất cả tác giả để lọc
    $stmt_a_all = $db->query("SELECT ma_tac_gia, ten_tac_gia FROM tac_gia ORDER BY ten_tac_gia ASC");
    $authors_for_filter = $stmt_a_all->fetchAll();

    // Lấy 4 tác giả tiêu biểu
    $stmt_authors = $db->query("
        SELECT tg.ten_tac_gia, COUNT(s.ma_sach) AS total_books
        FROM tac_gia tg
        JOIN sach s ON tg.ma_tac_gia = s.ma_tac_gia
        GROUP BY tg.ma_tac_gia
        ORDER BY total_books DESC
        LIMIT 4
    ");
    $highlight_authors = $stmt_authors->fetchAll();

} catch (PDOException $e) {
    echo '<div class="alert alert-error">Lỗi truy vấn: ' . $e->getMessage() . '</div>';
}

// --- 3. HIỂN THỊ NỘI DUNG TRANG CHỦ ---
?>

<header class="hero-banner">
    <div class="hero-content">
        <i class="fas fa-book-reader fa-3x banner-icon"></i>
        <h1>Khám Phá Sức Mạnh Tri Thức Công Nghệ</h1>
        <p>Hơn <?php echo number_format($total_books); ?> đầu sách chuyên ngành lập trình và thuật toán đang chờ bạn.</p>
    </div>
</header>

<div class="main-content-layout">
    <div class="sidebar-filter">
        <div class="filter-box">
            <h3 class="filter-heading"><i class="fas fa-filter"></i> Lọc Theo Tác Giả</h3>
            <ul class="filter-list">
                <li><a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $author_id == 0 ? 'selected-filter' : ''; ?>"><i class="fas fa-angle-right"></i> Tất cả Tác giả</a></li>
                <?php 
                foreach($authors_for_filter as $author) {
                    $is_selected = ($author['ma_tac_gia'] == $author_id) ? 'selected-filter' : '';
                    echo '<li><a href="' . BASE_URL . '?author=' . $author['ma_tac_gia'] . '" class="' . $is_selected . '"><i class="fas fa-angle-right"></i> ' . htmlspecialchars($author['ten_tac_gia']) . '</a></li>';
                }
                ?>
            </ul>
        </div>
        
        <div class="filter-box">
            <h3 class="filter-heading"><i class="fas fa-tags"></i> Lọc Theo Danh Mục</h3>
            <ul class="filter-list">
                <li><a href="#"><i class="fas fa-angle-right"></i> Thuật Toán</a></li>
                <li><a href="#"><i class="fas fa-angle-right"></i> Lập Trình Thi Đấu</a></li>
            </ul>
        </div>
        
    </div>
    
    <div class="book-list-area">
        
        <div class="search-bar-container">
            <form action="<?php echo BASE_URL; ?>index.php" method="GET" class="search-form">
                <input type="hidden" name="author" value="<?php echo htmlspecialchars($author_id); ?>">
                <input type="text" name="search" placeholder="Tìm kiếm sách theo tên..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Tìm Kiếm</button>
            </form>
        </div>

        <h2 id="books" class="section-heading"><i class="fas fa-list-alt title-icon"></i> Danh Mục Sách (<?php echo $total_books; ?>)</h2>
        <?php
        display_books($books, $total_pages, $page, $user_role_id, $query_string, true);
        ?>

        <hr class="section-divider">

        <h2 class="section-heading"><i class="fas fa-star title-icon"></i> Tác Giả Tiêu Biểu <small>(4 tác giả có nhiều sách nhất)</small></h2>
        <div class="author-list-grid">
            <?php foreach ($highlight_authors as $author): ?>
                <div class="author-card-styled">
                    <div class="author-image"><i class="fas fa-user-circle fa-2x"></i></div>
                    <h4 class="author-name"><?php echo htmlspecialchars($author['ten_tac_gia']); ?></h4>
                    <p class="author-detail"><?php echo htmlspecialchars($author['total_books']); ?> đầu sách</p>
                </div>
            <?php endforeach; ?>
        </div>
        
    </div>
</div> <?php
// --- 4. CSS BỔ SUNG CHO TRANG CHỦ ---
?>
<style>
.text-center { text-align: center; }

.section-heading {
    color: var(--primary-color); 
    font-size: 1.8em;
    border-bottom: 2px solid var(--secondary-color);
    padding-bottom: 5px;
    margin-bottom: 20px;
}
.title-icon { color: var(--secondary-color); margin-right: 10px; }
.section-divider { border-top: 1px solid #ddd; margin: 40px 0; }

/* FIX: CẤU TRÚC 2 CỘT ÁP DỤNG CHO DIV BAO NGOÀI */
.main-content-layout {
    display: flex; /* Kích hoạt Flexbox */
    gap: 30px; 
    margin-top: 20px; 
}
.sidebar-filter {
    flex: 0 0 250px; 
    background: var(--card-bg);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    height: fit-content; 
    position: sticky;
    top: 90px;
}
.book-list-area {
    flex: 1; 
}

/* SEARCH BAR */
.search-bar-container {
    margin-bottom: 25px;
}
.search-form {
    display: flex;
    background: var(--card-bg);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.search-form input[type="text"] {
    flex: 1;
    padding: 12px 15px;
    border: none;
    font-size: 1em;
}
.btn-search {
    background-color: var(--primary-color);
    color: white;
    padding: 12px 20px;
    border: none;
    cursor: pointer;
    font-size: 1em;
    font-weight: 600;
    transition: background-color 0.3s;
}
.btn-search:hover {
    background-color: #1a326c;
}

/* FILTER MENU */
.filter-box {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
.filter-box:last-child {
    border-bottom: none;
}
.filter-heading {
    font-size: 1.1em;
    color: var(--primary-color);
    margin-top: 0;
    margin-bottom: 15px;
    border-left: 4px solid var(--secondary-color);
    padding-left: 10px;
}
.filter-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.filter-list li a {
    display: block;
    padding: 8px 5px;
    color: #555;
    text-decoration: none;
    font-size: 0.9em;
    transition: color 0.2s, background-color 0.2s;
    border-radius: 4px;
}
.filter-list li a:hover, .filter-list li a.selected-filter {
    color: var(--primary-color);
    background-color: #e6f7ff; 
    font-weight: 600;
}
.filter-list li a i {
    margin-right: 8px;
    font-size: 0.8em;
}

/* BOOK CARD */
.book-list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
    gap: 30px; 
    margin-top: 20px;
}
.book-card {
    position: relative;
    padding: 0; 
    overflow: hidden;
    display: flex; 
    flex-direction: column;
    background-color: var(--card-bg);
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}
.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.book-cover-placeholder {
    height: 180px; 
    background-color: #ecf0f1;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #bdc3c7;
    border-radius: 12px 12px 0 0;
    font-size: 2em;
}
.book-info {
    padding: 15px;
    flex-grow: 1; 
    display: flex;
    flex-direction: column;
}

.book-title {
    font-size: 1.1em;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 10px;
    min-height: 40px; 
}

.book-author, .book-year, .book-quantity {
    font-size: 0.9em;
    color: #555;
    margin: 3px 0;
}

.book-status {
    position: absolute;
    top: 15px;
    right: 15px;
    color: var(--primary-color);
    background-color: var(--secondary-color);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8em;
    font-weight: bold;
    z-index: 10;
}

.status-available { background-color: var(--secondary-color); color: var(--primary-color); }
.status-out { background-color: #dc3545; color: white; }

.btn-borrow, .btn-login-borrow {
    display: block;
    padding: 10px;
    margin-top: 15px;
    border: none;
    border-radius: 8px;
    background-color: var(--secondary-color);
    color: var(--primary-color);
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    transition: background-color 0.3s;
    margin-top: auto; 
}
.btn-borrow:hover {
    background-color: #f0a316;
}
.btn-login-borrow {
    background-color: var(--primary-color) !important; 
    color: white !important;
}
.btn-login-borrow:hover {
    background-color: #1a326c !important;
}

/* PHÂN TRANG */
.pagination {
    margin-top: 30px;
    text-align: center;
}
.btn-pagination {
    text-decoration: none;
    color: var(--primary-color);
    padding: 8px 12px;
    border: 1px solid var(--primary-color);
    margin: 0 4px;
    border-radius: 6px;
    transition: background-color 0.3s, color 0.3s;
}

.btn-pagination.active-page, .btn-pagination:hover {
    background-color: var(--primary-color);
    color: white;
}

/* TÁC GIẢ TIÊU BIỂU */
.author-list-grid {
    grid-template-columns: repeat(4, 1fr);
    display: grid;
    gap: 20px;
    margin-top: 20px;
}
.author-card-styled {
    text-align: center;
    padding: 15px;
    background: var(--card-bg);
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}
.author-card-styled:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.author-image {
    color: var(--secondary-color);
    margin-bottom: 10px;
}
.author-name {
    font-size: 1.1em;
    color: var(--primary-color);
    margin: 0;
}
.author-detail {
    font-size: 0.85em;
    color: #777;
}
</style>

<?php
require_once 'layout/footer.php';
?>