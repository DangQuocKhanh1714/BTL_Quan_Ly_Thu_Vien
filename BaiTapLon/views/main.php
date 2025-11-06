<?php
require_once 'header.php'; // Gọi layout header

// --- BƯỚC 1: REQUIRE FILE FUNCTIONS
require_once __DIR__ . '/../functions/student_functions.php';
require_once __DIR__ . '/../functions/book_functions.php';

// --- BƯỚC 2: GỌI HÀM LẤY SỐ LƯỢNG
$totalStudents = getTotalStudentsCount();
$totalBooks = getTotalBooksCount();
?>

<div class="card">
    <h4 style="
        font-size: 1.6rem;
        font-weight: 700;
        color: #4a69bd;
        text-align: center;
        letter-spacing: 0.5px;
        margin: 0 0 15px 0;
    ">
        <i class="fas fa-chart-line" style="margin-right: 10px; color: #4a69bd;"></i>
        TỔNG QUAN HỆ THỐNG
    </h4>

    <div class="stat-card-grid">
        <div class="stat-card stat-card-blue">
            <i class="fas fa-users"></i> Tổng số sinh viên: 
            <b><?= number_format($totalStudents) ?></b>
        </div>
        <div class="stat-card stat-card-green">
            <i class="fas fa-book-open-reader"></i> Tổng số đầu sách: 
            <b><?= number_format($totalBooks) ?></b>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="section-title" style="border-left-color: #007bff;">
        <i class="fas fa-cogs"></i> Chức Năng Quản Lý Chính
    </h3>
    
    <div class="func-card-grid">
        <a href="student.php" class="func-card func-card-blue">
            <i class="fas fa-user-shield"></i>
            <h4>Quản Lý Người Dùng</h4>
            <p>Thêm, sửa, xóa (vô hiệu) tài khoản hệ thống.</p>
        </a>

        <a href="book.php" class="func-card func-card-purple">
            <i class="fas fa-database"></i>
            <h4>Quản Lý Nghiệp Vụ & Sách</h4>
            <p>QL Sách, Phiếu Mượn/Trả, Phiếu Phạt.</p>
        </a>

        <a href="category.php" class="func-card func-card-green">
            <i class="fas fa-tags"></i>
            <h4>Quản Lý Thể Loại</h4>
            <p>Thêm, sửa, xóa thể loại để phân loại sách dễ dàng.</p>
        </a>

        <a href="borrow.php" class="func-card func-card-orange">
            <i class="fas fa-file-invoice"></i>
            <h4>Quản Lý Phiếu Mượn</h4>
            <p>Ghi nhận, chỉnh sửa hoặc xóa các phiếu mượn/trả.</p>
        </a>

        <a href="logs.php" class="func-card func-card-red">
            <i class="fas fa-history"></i>
            <h4>Nhật Ký Hệ Thống</h4>
            <p>Xem lại lịch sử hoạt động và thao tác của người dùng.</p>
        </a>
    </div>
</div>

<?php
require_once 'footer.php'; // Gọi footer (nếu có)
?>