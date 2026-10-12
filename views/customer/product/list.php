<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="row g-4">
  <!-- Sidebar lọc -->
  <div class="col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-funnel text-danger"></i> Lọc sản phẩm</h6>
      <form method="GET" action="<?= BASE_URL ?>/products">
        <div class="mb-3">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm theo tên..." value="<?= e($keyword) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Danh mục</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">Tất cả</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-danger btn-sm w-100">Lọc</button>
        <a href="<?= BASE_URL ?>/products" class="btn btn-outline-secondary btn-sm w-100 mt-2">Xóa lọc</a>
      </form>
    </div>
  </div>

  <!-- Danh sách sản phẩm -->
  <div class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <p class="mb-0 text-muted small">Tìm thấy <strong><?= $result['total'] ?></strong> sản phẩm</p>
    </div>

    <?php if (empty($result['data'])): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-flower1 display-4"></i>
        <p class="mt-2">Không tìm thấy sản phẩm nào.</p>
      </div>
    <?php else: ?>
      <div class="row row-cols-2 row-cols-md-3 g-3">
        <?php foreach ($result['data'] as $p): ?>
          <div class="col">
            <div class="card h-100 border-0 shadow-sm product-card">
              <a href="<?= BASE_URL ?>/products/<?= $p['id'] ?>">
                <img src="<?= e($p['primary_image'] ?? BASE_URL . '/img/no-image.png') ?>"
                     class="card-img-top" style="height:180px;object-fit:cover" alt="<?= e($p['name']) ?>">
              </a>
              <div class="card-body p-3">
                <p class="small text-muted mb-1"><?= e($p['category_name'] ?? '') ?></p>
                <h6 class="card-title mb-2">
                  <a href="<?= BASE_URL ?>/products/<?= $p['id'] ?>" class="text-dark text-decoration-none">
                    <?= e($p['name']) ?>
                  </a>
                </h6>
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <?php if ($p['sale_price']): ?>
                      <span class="text-danger fw-bold"><?= formatMoney((float)$p['sale_price']) ?></span><br>
                      <small class="text-muted text-decoration-line-through"><?= formatMoney((float)$p['price']) ?></small>
                    <?php else: ?>
                      <span class="text-danger fw-bold"><?= formatMoney((float)$p['price']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($p['stock'] > 0): ?>
                    <button class="btn btn-sm btn-danger btn-add-cart" data-id="<?= $p['id'] ?>">
                      <i class="bi bi-cart-plus"></i>
                    </button>
                  <?php else: ?>
                    <span class="badge bg-secondary">Hết hàng</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($result['last_page'] > 1): ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $result['last_page']; $i++): ?>
              <li class="page-item <?= $i == $result['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?q=<?= urlencode($keyword) ?>&category=<?= $categoryId ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
