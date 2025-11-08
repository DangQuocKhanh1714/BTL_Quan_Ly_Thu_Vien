<?php
// layout/footer.php
?>
    </main>
</div> <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-section about">
                <h4><i class="fas fa-book"></i> Thư Viện Kỹ Thuật</h4>
                <p>Nơi chia sẻ và kết nối cộng đồng yêu thích lập trình và thuật toán.</p>
            </div>
            <div class="footer-section contact">
                <h4><i class="fas fa-headset"></i> Liên Hệ</h4>
                <p>Email: support@techlibrary.com</p>
                <p>Hotline: 0123 456 789</p>
            </div>
            <div class="footer-section links">
                <h4><i class="fas fa-link"></i> Đường Dẫn</h4>
                <a href="<?php echo BASE_URL; ?>">Trang Chủ</a>
                <a href="<?php echo BASE_URL; ?>#books">Danh Mục Sách</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Hệ Thống Quản Lý Thư Viện. | Developed by [Tên của bạn/Đội nhóm].</p>
        </div>
    </footer>

<script>
    // Script để xử lý hiển thị/ẩn dropdown menu
    const profile = document.getElementById('userProfile');
    const dropdown = document.getElementById('userDropdown');
    
    if (profile && dropdown) {
        profile.addEventListener('click', function(e) {
            // Đảo ngược trạng thái hiển thị
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            e.stopPropagation(); // Ngăn chặn sự kiện nổi bọt
        });

        // Đóng dropdown khi click ra ngoài
        document.addEventListener('click', function() {
            dropdown.style.display = 'none';
        });
    }
</script>
</body>
</html>

<style>
/* CSS cho Footer (Đảm bảo full chiều ngang) */
.main-footer {
    background-color: #343a40; /* Nền tối */
    color: #f8f9fa; /* Chữ sáng */
    padding: 30px 0 10px;
    width: 100%; 
    margin-top: 40px;
}
.footer-content {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 20px 20px;
}
.footer-section {
    flex: 1;
    min-width: 200px;
    margin: 10px;
}
.footer-section h4 {
    color: var(--secondary-color);
    margin-bottom: 15px;
    font-size: 1.2em;
}
.footer-section p, .footer-section a {
    font-size: 0.9em;
    color: #ccc;
    line-height: 1.6;
    display: block;
    margin-bottom: 5px;
    text-decoration: none;
}
.footer-section a:hover {
    color: var(--secondary-color);
    text-decoration: underline;
}
.footer-bottom {
    text-align: center;
    border-top: 1px solid #495057;
    padding: 10px 0;
    font-size: 0.8em;
    color: #aaa;
}
</style>