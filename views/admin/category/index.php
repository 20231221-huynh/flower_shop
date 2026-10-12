<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">Thêm danh mục mới</h6>
            <form method="POST" action="<?= BASE_URL ?>/admin/categories">
                <input type="hidden" name="csrf_token" id="admin_csrf_token" value="<?= csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên danh mục</label>
                    <input type="text" name="name" class="form-control" required placeholder="VD: Hoa Hồng">
                </div>
                <button type="submit" class="btn btn-danger w-100">Thêm danh mục</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" id="categoryTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 80px;">STT</th> <th>Tên danh mục</th>
                            <th>Đường dẫn (Slug)</th>
                            <th class="text-end pe-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; ?> 
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= $stt++ ?></td> 
                                <td class="fw-semibold"><?= e($cat['name']) ?></td>
                                <td class="text-muted small"><?= e($cat['slug']) ?></td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-outline-danger btn-delete-cat" 
                                            data-id="<?= $cat['id'] ?>" 
                                            data-name="<?= e($cat['name']) ?>"
                                            title="Xóa danh mục">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Chưa có danh mục nào được tạo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sử dụng Event Delegation
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-delete-cat');
        if (!deleteBtn) return;

        const id = deleteBtn.dataset.id;
        const name = deleteBtn.dataset.name;
        const csrfToken = document.getElementById('admin_csrf_token').value;

        // Thông báo rõ ràng về việc sản phẩm ẩn vẫn gây ràng buộc
        if (confirm(`Bạn có chắc muốn xóa danh mục "${name}"?\n\nLưu ý: Hệ thống sẽ không cho phép xóa nếu vẫn còn sản phẩm (kể cả sản phẩm đã ẩn) thuộc danh mục này.`)) {
            
            deleteBtn.disabled = true; 

            fetch(`${BASE_URL}/admin/categories/${id}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ 'csrf_token': csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    // Hiển thị nội dung lỗi chi tiết từ Controller trả về
                    alert('Lỗi: ' + data.message);
                    deleteBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Lỗi kết nối hoặc hệ thống đang bảo trì.');
                deleteBtn.disabled = false;
            });
        }
    });
});
</script>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>