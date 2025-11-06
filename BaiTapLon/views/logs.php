<?php 
require_once 'header.php';
require_once __DIR__ . '/../functions/auth.php'; 
require_once __DIR__ . '/../functions/log_functions.php'; 

// Kiểm tra đăng nhập 
checkLogin(__DIR__ . '/../login.php'); 

// Lấy tất cả log
$logs = handleGetAllLogs(); 
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>DNU - Nhật ký hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body> 

<div class="container mt-3">
    <h3 class="mt-3">NHẬT KÝ HỆ THỐNG</h3>

    <?php 
    // Hiển thị thông báo thành công
    if (isset($_GET['success'])) { 
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert"> 
            ' . htmlspecialchars($_GET['success']) . ' 
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button> 
        </div>'; 
    } 
    // Hiển thị thông báo lỗi
    if (isset($_GET['error'])) { 
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"> 
            ' . htmlspecialchars($_GET['error']) . ' 
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button> 
        </div>'; 
    } 
    ?>
    
    <script>
    // Tự ẩn alert sau 3 giây
    setTimeout(() => { 
        let alertNode = document.querySelector('.alert'); 
        if (alertNode) { 
            let bsAlert = bootstrap.Alert.getOrCreateInstance(alertNode); 
            bsAlert.close(); 
        } 
    }, 3000); 
    </script>

    <table class="table table-bordered table-hover mt-3">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Thời gian</th>
            <th>Người thực hiện</th>
            <th>Module</th>
            <th>Hành động / Chi tiết</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (!empty($logs)) {
            foreach ($logs as $log) { 
                $actionType = $log['action_type'] ?? 'N/A';
                $badgeClass = match ($actionType) {
                    'CREATE' => 'bg-success',
                    'UPDATE' => 'bg-warning text-dark',
                    'DELETE' => 'bg-danger',
                    default => 'bg-secondary'
                };
        ?>
        <tr>
            <td><?= htmlspecialchars($log['id']) ?></td>
            <td><?= htmlspecialchars($log['timestamp'] ?? 'N/A') ?></td>
            <td>
                <?= htmlspecialchars($log['executor_name'] ?? 'N/A') ?><br>
                <small class="text-muted">(ID: <?= htmlspecialchars($log['student_id'] ?? 'N/A') ?>)</small>
            </td>
            <td><?= htmlspecialchars($log['module'] ?? 'N/A') ?></td>
            <td>
                <span class="badge <?= $badgeClass ?>">
                    <?= htmlspecialchars($actionType) ?>
                </span>
                <?= htmlspecialchars($log['detail'] ?? 'N/A') ?>
                <?php if (!empty($log['target_id'])): ?>
                    <br><small class="text-muted">(Đối tượng ID: <?= htmlspecialchars($log['target_id']) ?>)</small>
                <?php endif; ?>
            </td>
        </tr>
        <?php 
            } 
        } else {
            echo '<tr><td colspan="5" class="text-center text-muted">Chưa có nhật ký hoạt động nào.</td></tr>';
        }
        ?>
    </tbody>
</table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDropdown() {
    const dropdown = document.getElementById("userDropdown");
    dropdown.classList.toggle("show");
}
window.addEventListener("click", function(e) {
    const btn = document.querySelector(".user-menu-btn");
    const dropdown = document.getElementById("userDropdown");
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove("show");
    }
});
</script>

</body>
</html>
