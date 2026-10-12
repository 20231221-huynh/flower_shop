<?php require ROOT_PATH . '/views/layouts/admin_header.php'; ?>

<a href="<?= BASE_URL ?>/admin/products" class="btn btn-sm btn-outline-secondary mb-3">← Quay lại</a>

<div class="card border-0 shadow-sm rounded-4 p-4">
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div>• <?= e($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="row g-3">
      <div class="col-md-8">
        <div class="mb-3">
          <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required
                 value="<?= e($product['name'] ?? '') ?>">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select" required>
              <option value="">-- Chọn danh mục --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Giá gốc (₫) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control" min="0" step="1000" required
                   value="<?= $product['price'] ?? '' ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Giá KM (₫)</label>
            <input type="number" name="sale_price" class="form-control" min="0" step="1000"
                   value="<?= $product['sale_price'] ?? '' ?>" placeholder="Để trống nếu không KM">
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Tồn kho</label>
            <input type="number" name="stock" class="form-control" min="0"
                   value="<?= $product['stock'] ?? 0 ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
              <option value="active"   <?= ($product['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Đang bán</option>
              <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Mô tả sản phẩm</label>
          <textarea name="description" class="form-control" rows="5"><?= e($product['description'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Ảnh -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Hình ảnh (tối đa 5 ảnh)</label>
        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
        <small class="text-muted">JPG, PNG, WEBP. Ảnh đầu tiên là ảnh chính.</small>

        <?php if (!empty($images)): ?>
          <div class="row g-2 mt-2">
            <?php foreach ($images as $img): ?>
              <div class="col-4">
                <img src="<?= e($img['image_url']) ?>" class="img-thumbnail w-100"
                     style="height:80px;object-fit:cover">
                <?php if ($img['is_primary']): ?>
                  <div class="text-center"><span class="badge bg-danger mt-1">Chính</span></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <hr>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-danger fw-bold px-4">
        <i class="bi bi-save"></i> <?= $product ? 'Cập nhật' : 'Thêm mới' ?>
      </button>
      <a href="<?= BASE_URL ?>/admin/products" class="btn btn-outline-secondary">Hủy</a>
    </div>
  </form>
</div>

<?php require ROOT_PATH . '/views/layouts/admin_footer.php'; ?>
