<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex gap-2" method="GET">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm sản phẩm..." value="<?= e($keyword) ?>" style="width:220px">
    <button class="btn btn-sm btn-outline-secondary">Tìm</button>
  </form>
  <a href="<?= BASE_URL ?>/admin/products/create" class="btn btn-danger btn-sm">
    <i class="bi bi-plus-lg"></i> Thêm sản phẩm
  </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3" style="width: 80px;">STT</th> <th>Ảnh</th>
          <th>Tên sản phẩm</th>
          <th>Giá</th>
          <th class="text-center">Tồn kho</th>
          <th>Trạng thái</th>
          <th class="text-end pe-3">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          // LOGIC TÍNH STT: Lấy trang hiện tại * số lượng mỗi trang
          $currentPage = $result['current_page'] ?? 1;
          $perPage = 15; // Phải khớp với số lượng trong AdminProductController->index()
          $stt = ($currentPage - 1) * $perPage + 1; 
        ?>

        <?php if (!empty($result['data'])): ?>
          <?php foreach ($result['data'] as $p): ?>
          <tr>
            <td class="ps-3 text-muted small"><?= $stt++ ?></td> 
            
            <td>
              <img src="<?= e($p['primary_image'] ?? BASE_URL . '/img/no-image.png') ?>" 
                   style="width:48px;height:48px;object-fit:cover;border-radius:8px">
            </td>
            <td>
              <div class="fw-semibold"><?= e($p['name']) ?></div>
              <small class="text-muted"><?= e($p['category_name'] ?? 'Không có danh mục') ?></small>
            </td>
            <td>
              <?php if (!empty($p['sale_price'])): ?>
                <span class="text-danger fw-semibold"><?= formatMoney((float)$p['sale_price']) ?></span><br>
                <small class="text-muted text-decoration-line-through"><?= formatMoney((float)$p['price']) ?></small>
              <?php else: ?>
                <span class="fw-semibold"><?= formatMoney((float)$p['price']) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="badge <?= $p['stock'] <= 5 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $p['stock'] ?></span>
            </td>
            <td>
              <?php if ($p['status'] === 'active'): ?>
                <span class="badge bg-success">Đang bán</span>
              <?php elseif ($p['status'] === 'inactive'): ?>
                <span class="badge bg-secondary">Tạm ẩn</span>
              <?php else: ?>
                <span class="badge bg-danger">Đã xóa</span>
              <?php endif; ?>
            </td>
            <td class="text-end pe-3">
              <a href="<?= BASE_URL ?>/admin/products/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-primary" title="Sửa">
                <i class="bi bi-pencil"></i>
              </a>
              <button class="btn btn-sm btn-outline-danger btn-delete-product" data-id="<?= $p['id'] ?>" title="Xóa">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">Không tìm thấy sản phẩm nào.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($result['last_page'] > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center pagination-sm">
      <?php for ($i = 1; $i <= $result['last_page']; $i++): ?>
        <li class="page-item <?= $i == $result['current_page'] ? 'active' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($keyword) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>