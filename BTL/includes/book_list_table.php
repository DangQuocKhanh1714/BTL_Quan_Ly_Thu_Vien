<?php
// File này chỉ chứa nội dung hiển thị cho tab QL Sách
// Các biến $books, $authors được truyền từ admin_transactions.php

if (empty($books)) {
    echo '<p class="alert info">Chưa có dữ liệu sách nào trong thư viện.</p>';
    return;
}
?>

<table class="data-table">
    <thead>
        <tr>
            <th>Mã Sách</th>
            <th>Tên Sách</th>
            <th>Tác Giả</th>
            <th>Tổng SL</th>
            <th>SL Khả Dụng</th>
            <th>Thao Tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($books as $book): ?>
        <tr>
            <td><?php echo $book['ma_sach']; ?></td>
            <td><?php echo htmlspecialchars($book['ten_sach']); ?></td>
            <td><?php echo htmlspecialchars($book['ten_tac_gia']); ?></td>
            <td><?php echo $book['tong_so_luong']; ?></td>
            <td><?php echo $book['so_luong_kha_dung']; ?></td>
            <td>
                <button class="btn btn-sm btn-edit">Sửa</button>
                <form method="POST" style="display:inline-block;" onsubmit="return confirm('CẢNH BÁO: Xóa sách sẽ ảnh hưởng đến nghiệp vụ. Bạn chắc chắn chứ?');">
                    <input type="hidden" name="action" value="delete_book">
                    <input type="hidden" name="tab" value="books">
                    <input type="hidden" name="book_id" value="<?php echo $book['ma_sach']; ?>">
                    <button type="submit" class="btn btn-sm btn-delete">Xóa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
